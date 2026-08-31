import { router, type Page } from '@inertiajs/core';
import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import * as Sentry from '@sentry/react';
import { StrictMode, type ComponentType, type ReactElement, type ReactNode } from 'react';
import { createRoot, hydrateRoot } from 'react-dom/client';

import { ErrorBoundary } from '@/components/error-boundary';
import { setTranslationLocale } from '@/lib/utils';
import type { BaseSharedData, Direction } from '@/types';

if (typeof window !== 'undefined' && import.meta.env.VITE_SENTRY_DSN) {
    Sentry.init({
        dsn: import.meta.env.VITE_SENTRY_DSN,
        environment: import.meta.env.MODE,
        integrations: [Sentry.browserTracingIntegration()],
        tracesSampleRate: 0.2,
    });
}

type CreateAppOptions = {
    resolve: (name: string) => Promise<ResolvedComponent>;
    layout?: (name: string, page: Page) => unknown;
    wrap?: (children: ReactNode, direction: Direction) => ReactNode;
    serverHead?: boolean;
};

type CreateSsrAppOptions = CreateAppOptions & {
    page: Page;
    render: (node: ReactNode) => string;
};

const fallbackAppName = import.meta.env.VITE_APP_NAME ?? 'FlexStore';
const appState = { storeName: fallbackAppName };

if (typeof window !== 'undefined') {
    router.on('navigate', (event) => {
        const next = (event.detail.page.props as Partial<BaseSharedData>).storeName;
        if (next) appState.storeName = next;
    });
}

const title = (heading: string) => (heading ? `${heading} - ${appState.storeName}` : appState.storeName);
const serverTitle = (heading: string) => heading || appState.storeName;

type TreeSetup = {
    App: ComponentType<{ initialPage: Page }>;
    props: { initialPage: Page };
};

function buildTree(
    { App, props }: TreeSetup,
    wrap?: (children: ReactNode, direction: Direction) => ReactNode,
): ReactElement {
    const initial = props.initialPage.props as Partial<BaseSharedData>;
    if (initial.storeName) appState.storeName = initial.storeName;
    const direction: Direction = initial.direction ?? 'ltr';
    setTranslationLocale(initial.activeLocale ?? 'en', initial.defaultLocale ?? 'en');

    const app = <App {...props} />;

    return (
        <StrictMode>
            <ErrorBoundary>{wrap ? wrap(app, direction) : app}</ErrorBoundary>
        </StrictMode>
    );
}

export function createApp({ resolve, layout, wrap, serverHead }: CreateAppOptions) {
    return createInertiaApp({
        title: serverHead ? serverTitle : title,
        serverHead,
        resolve,
        layout,
        setup({ el, App, props }) {
            const tree = buildTree({ App, props }, wrap);

            if (!el) {
                return tree;
            }

            if (el.hasAttribute('data-server-rendered')) {
                hydrateRoot(el, tree);
            } else {
                createRoot(el).render(tree);
            }
        },
    });
}

export function createSsrApp({ resolve, layout, wrap, page, render, serverHead }: CreateSsrAppOptions) {
    return createInertiaApp({
        title: serverHead ? serverTitle : title,
        serverHead,
        resolve,
        layout,
        page,
        render,
        setup: ({ App, props }) => buildTree({ App, props }, wrap),
    });
}
