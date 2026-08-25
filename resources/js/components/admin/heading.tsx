import { type UrlMethodPair } from '@inertiajs/core';
import { Head } from '@inertiajs/react';
import { ArrowLeftIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { HelpBlock } from '@/components/ui/help-block';
import { useBackNavigation } from '@/hooks/admin/use-back-navigation';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface HeadingProps {
    pageTitle?: string;
    title: React.ReactNode | string;
    description?: string;
    backHref?: UrlMethodPair;
    className?: string;
}

export function Heading({
    pageTitle,
    title,
    description,
    backHref,
    className,
    children,
}: React.PropsWithChildren<HeadingProps>) {
    const { goBack } = useBackNavigation({ fallbackHref: backHref });

    return (
        <>
            <Head title={pageTitle ?? (title as string)} />

            <div className={cn('flex flex-col items-start gap-4 sm:flex-row sm:justify-between', className)}>
                <div className="flex items-center gap-2">
                    {backHref && (
                        <Button
                            variant="ghost"
                            size="icon-md"
                            onClick={goBack}
                            className="cursor-pointer"
                            aria-label={__('Go back')}
                        >
                            <ArrowLeftIcon className="rtl:rotate-180" />
                        </Button>
                    )}

                    <div className="flex flex-col gap-1.5">
                        <h1 className="text-xl leading-none font-semibold tracking-tight">{title}</h1>
                        <HelpBlock>{description}</HelpBlock>
                    </div>
                </div>

                {children && (
                    <div className="flex w-full min-w-0 flex-wrap items-center justify-start gap-2 sm:w-auto sm:flex-1 sm:justify-end">
                        {children}
                    </div>
                )}
            </div>
        </>
    );
}
