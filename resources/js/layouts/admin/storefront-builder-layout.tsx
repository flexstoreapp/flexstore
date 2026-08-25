import { type UrlMethodPair } from '@inertiajs/core';
import { Head, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    ExternalLinkIcon,
    EyeIcon,
    MonitorIcon,
    PanelLeftIcon,
    SmartphoneIcon,
    LogOutIcon,
} from 'lucide-react';
import { m } from 'motion/react';
import {
    type Dispatch,
    type ReactNode,
    type SetStateAction,
    createContext,
    useCallback,
    useContext,
    useEffect,
    useRef,
    useState,
} from 'react';

import * as StorefrontController from '@/actions/App/Http/Controllers/Admin/StorefrontController';
import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import { Button } from '@/components/ui/button';
import { useDirection } from '@/components/ui/direction';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Spinner } from '@/components/ui/spinner';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useBackNavigation } from '@/hooks/admin/use-back-navigation';
import { useIsomorphicLayoutEffect } from '@/hooks/admin/use-isomorphic-layout-effect';
import { useIsMobile } from '@/hooks/admin/use-mobile';
import { runLeaveGuard } from '@/hooks/use-leave-guard';
import { AdminShell } from '@/layouts/admin/admin-shell';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const IFRAME_URL_KEY = 'storefront-builder-iframe-url';

function getValidIframeUrl(): string {
    if (typeof window === 'undefined') return HomepageController.show().url;

    const storedUrl = sessionStorage.getItem(IFRAME_URL_KEY);
    if (!storedUrl) {
        return HomepageController.show().url;
    }

    try {
        const stored = new URL(storedUrl, window.location.origin);
        if (stored.origin === window.location.origin) {
            return stored.pathname + stored.search;
        }
    } catch {
        // Invalid URL, ignore
    }

    sessionStorage.removeItem(IFRAME_URL_KEY);
    return HomepageController.show().url;
}

interface StorefrontBuilderContextValue {
    reloadIframe: () => void;
    overrideBackButton: (handler: (() => void) | null) => void;
}

const StorefrontBuilderContext = createContext<StorefrontBuilderContextValue | null>(null);

export function useStorefrontBuilder() {
    const context = useContext(StorefrontBuilderContext);
    if (!context) {
        throw new Error('useStorefrontBuilder must be used within StorefrontBuilderLayout');
    }
    return context;
}

interface StorefrontBuilderLayoutConfig {
    title?: string;
    subtitle?: string;
    backHref?: UrlMethodPair;
}

const StorefrontBuilderActionContext = createContext<Dispatch<SetStateAction<ReactNode>> | null>(null);

export function useStorefrontBuilderAction(action: ReactNode): void {
    const setAction = useContext(StorefrontBuilderActionContext);

    useIsomorphicLayoutEffect(() => {
        if (!setAction) return;
        setAction(action);
        return () => setAction(null);
    }, [setAction, action]);
}

export function StorefrontBuilderLayout({
    title,
    subtitle,
    backHref,
    children,
}: React.PropsWithChildren<StorefrontBuilderLayoutConfig>) {
    const isMobile = useIsMobile();
    const direction = useDirection();
    const iframeRef = useRef<HTMLIFrameElement>(null);
    const scrollPositionRef = useRef(0);
    const { goBack } = useBackNavigation({ fallbackHref: backHref });
    const backLeavesBuilder = !backHref || !backHref.url.startsWith(StorefrontController.index().url);
    const [backOverride, setBackOverride] = useState<(() => void) | null>(null);

    const handleBack =
        backOverride ??
        (() => {
            void runLeaveGuard().then((canLeave) => {
                if (canLeave) {
                    goBack();
                }
            });
        });
    const [viewport, setViewport] = useState<'desktop' | 'mobile'>('desktop');
    const [mobilePanel, setMobilePanel] = useState<'form' | 'preview'>('form');
    const [iframeLoading, setIframeLoading] = useState(true);
    const [action, setAction] = useState<ReactNode>(null);
    const setActionStable = useCallback<Dispatch<SetStateAction<ReactNode>>>((next) => setAction(next), []);
    const [iframeUrl, setIframeUrl] = useState(getValidIframeUrl);

    const getIframeDocument = (): Document | null => {
        try {
            return iframeRef.current?.contentDocument ?? null;
        } catch {
            return null;
        }
    };

    const getIframeCurrentUrl = (): string | undefined => {
        try {
            return iframeRef.current?.contentWindow?.location.href;
        } catch {
            return undefined;
        }
    };

    const saveIframeUrl = (url: string) => {
        try {
            const parsed = new URL(url, window.location.origin);
            const relativePath = parsed.pathname + parsed.search;
            setIframeUrl(relativePath);
            sessionStorage.setItem(IFRAME_URL_KEY, relativePath);
        } catch {
            setIframeUrl(url);
            sessionStorage.setItem(IFRAME_URL_KEY, url);
        }
    };

    const restoreIframeScroll = () => {
        if (iframeRef.current && scrollPositionRef.current > 0) {
            iframeRef.current.contentWindow?.scrollTo(0, scrollPositionRef.current);
        }
    };

    const reloadIframe = () => {
        if (iframeRef.current) {
            setIframeLoading(true);
            scrollPositionRef.current = iframeRef.current.contentWindow?.scrollY ?? 0;
            iframeRef.current.src = getIframeCurrentUrl() ?? iframeUrl;
        }
    };

    const handleIframeLoad = () => {
        const newUrl = getIframeCurrentUrl();
        if (newUrl) {
            saveIframeUrl(newUrl);
        }

        restoreIframeScroll();
        setIframeLoading(false);
    };

    useEffect(() => {
        if (!iframeLoading) return;

        let frame = 0;

        const check = () => {
            const iframeDocument = getIframeDocument();

            if (iframeDocument && iframeDocument.readyState !== 'loading' && iframeDocument.URL !== 'about:blank') {
                restoreIframeScroll();
                setIframeLoading(false);
                return;
            }

            frame = requestAnimationFrame(check);
        };

        frame = requestAnimationFrame(check);

        return () => cancelAnimationFrame(frame);
    }, [iframeLoading]);

    useEffect(() => {
        const iframe = iframeRef.current;
        if (!iframe) return;

        const handleNavigation = () => {
            const newUrl = getIframeCurrentUrl();
            if (newUrl) {
                saveIframeUrl(newUrl);
            }
        };

        iframe.contentWindow?.addEventListener('popstate', handleNavigation);
        return () => {
            iframe.contentWindow?.removeEventListener('popstate', handleNavigation);
        };
    }, [iframeLoading]);

    useEffect(() => {
        const saveCurrentUrl = () => {
            const currentUrl = getIframeCurrentUrl();
            if (currentUrl) {
                sessionStorage.setItem(IFRAME_URL_KEY, currentUrl);
            }
        };

        const removeInertiaListener = router.on('before', (event) => {
            const destination = event.detail.visit.url.pathname;
            const isStayingInBuilder = destination.startsWith(StorefrontController.index().url);

            if (isStayingInBuilder) {
                saveCurrentUrl();
            } else {
                sessionStorage.removeItem(IFRAME_URL_KEY);
            }
        });

        window.addEventListener('beforeunload', saveCurrentUrl);
        return () => {
            window.removeEventListener('beforeunload', saveCurrentUrl);
            removeInertiaListener();
        };
    }, []);

    const overrideBackButton = (handler: (() => void) | null) => {
        setBackOverride(handler ? () => handler : null);
    };

    const formPanel = (
        <div className="flex h-full w-full flex-col border-e md:w-96 dark:border-input">
            <div className="flex items-center justify-between border-b bg-muted px-4 py-3 dark:border-input">
                <div className="flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="icon-md"
                        className="size-8 cursor-pointer hover:bg-background/60 dark:hover:bg-background/30"
                        onClick={handleBack}
                    >
                        {backLeavesBuilder ? (
                            <LogOutIcon className="size-4 rotate-180 rtl:rotate-0" />
                        ) : (
                            <ArrowLeftIcon className="size-4 rtl:rotate-180" />
                        )}
                    </Button>
                    <h3 className="text-sm font-medium">{title ?? __('Storefront')}</h3>
                    {subtitle && <span className="text-xs text-muted-foreground">{subtitle}</span>}
                </div>
                <div className="flex items-center gap-2">
                    {action}
                    <Button
                        variant="ghost"
                        size="icon-md"
                        className="size-8 hover:bg-background/60 md:hidden dark:hover:bg-background/30"
                        onClick={() => setMobilePanel('preview')}
                    >
                        <EyeIcon className="size-4" />
                    </Button>
                </div>
            </div>
            <ScrollArea className="flex-1" scrollRegion>
                <StorefrontBuilderContext.Provider value={{ reloadIframe, overrideBackButton }}>
                    <StorefrontBuilderActionContext.Provider value={setActionStable}>
                        {children}
                    </StorefrontBuilderActionContext.Provider>
                </StorefrontBuilderContext.Provider>
            </ScrollArea>
        </div>
    );

    const previewPanel = (
        <div className="flex h-full flex-1 flex-col">
            <div className="relative flex items-center justify-end border-b bg-muted px-4 py-3 dark:border-input">
                <Button
                    variant="ghost"
                    size="icon-md"
                    className="absolute start-4 size-8 hover:bg-background/60 md:hidden dark:hover:bg-background/30"
                    onClick={() => setMobilePanel('form')}
                >
                    <PanelLeftIcon className="size-4 rtl:-scale-x-100" />
                </Button>
                <Tabs defaultValue="desktop" className="hidden md:absolute md:inset-x-0 md:mx-auto md:block md:w-fit">
                    <TabsList>
                        <TabsTrigger value="desktop" className="gap-1.5" onClick={() => setViewport('desktop')}>
                            <MonitorIcon className="size-4" />
                            {__('Desktop')}
                        </TabsTrigger>
                        <TabsTrigger value="mobile" className="gap-1.5" onClick={() => setViewport('mobile')}>
                            <SmartphoneIcon className="size-4" />
                            {__('Mobile')}
                        </TabsTrigger>
                    </TabsList>
                </Tabs>
                <Button
                    variant="ghost"
                    size="icon-md"
                    title={__('Open store')}
                    className="size-8 cursor-pointer hover:bg-background/60 dark:hover:bg-background/30"
                    onClick={() => window.open(getIframeCurrentUrl() ?? iframeUrl, '_blank')}
                >
                    <ExternalLinkIcon className="size-4 rtl:-scale-x-100" />
                </Button>
            </div>
            <div className={cn('flex flex-1 bg-muted/50', viewport === 'mobile' && 'items-center justify-center p-4')}>
                <div
                    className={cn(
                        'relative h-full bg-background duration-300',
                        viewport === 'desktop' && 'w-full',
                        viewport === 'mobile' && 'w-98 rounded-2xl shadow-sm',
                    )}
                >
                    <iframe
                        ref={iframeRef}
                        src={iframeUrl}
                        onLoad={handleIframeLoad}
                        title="Storefront preview"
                        className={cn(
                            'size-full border-0',
                            viewport === 'mobile' && 'rounded-2xl',
                            iframeLoading && 'invisible',
                        )}
                    />
                    {iframeLoading && (
                        <div className="absolute inset-0 flex items-center justify-center bg-background">
                            <Spinner className="size-6 text-muted-foreground" />
                        </div>
                    )}
                </div>
            </div>
        </div>
    );

    return (
        <AdminShell>
            <Head title={title ? `${title} - ${__('Storefront')}` : __('Storefront')} />

            <div className="flex h-screen overflow-hidden bg-sidebar">
                {isMobile ? (
                    <div className="relative h-full w-full overflow-hidden">
                        <m.div
                            className="flex h-full w-[200%]"
                            animate={{ x: mobilePanel === 'form' ? 0 : direction === 'rtl' ? '50%' : '-50%' }}
                            transition={{ type: 'tween', ease: [0.32, 0.72, 0, 1], duration: 0.4 }}
                        >
                            <div className="h-full w-1/2">{formPanel}</div>
                            <div className="h-full w-1/2">{previewPanel}</div>
                        </m.div>
                    </div>
                ) : (
                    <div className="flex h-full w-full">
                        {formPanel}
                        {previewPanel}
                    </div>
                )}
            </div>
        </AdminShell>
    );
}
