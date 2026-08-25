import { buttonVariants } from '@/components/storefront/button';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const config: Record<number, { title: string; description: string }> = {
    503: {
        title: __('Service unavailable'),
        description: __('Sorry, we are doing some maintenance. Please check back soon.'),
    },
    500: {
        title: __('Server error'),
        description: __('Whoops, something went wrong on our servers.'),
    },
    404: {
        title: __('Page not found'),
        description: __('Sorry, the page you are looking for could not be found.'),
    },
    403: {
        title: __('Forbidden'),
        description: __('Sorry, you are not authorized to access this page.'),
    },
    410: {
        title: __('No longer available'),
        description: __('Sorry, the page you are looking for is no longer available.'),
    },
    429: {
        title: __('Too many requests'),
        description: __('Sorry, you are making too many requests. Please try again later.'),
    },
};

export default function ErrorPage({ status }: { status: number }) {
    const { title, description } = config[status] ?? {
        title: __('Error'),
        description: __('An unexpected error occurred.'),
    };

    return (
        <>
            <div className="flex min-h-svh items-center justify-center px-6">
                <div className="max-w-md text-center">
                    <p className="mt-6 text-sm font-semibold tracking-label text-muted uppercase">{status}</p>
                    <h1 className="mt-1 font-head text-8xl font-semibold text-ink">{title}</h1>
                    <p className="mt-3 text-md text-muted">{description}</p>
                    <a href="/" className={cn(buttonVariants({ variant: 'primary', size: 'lg' }), 'mt-8')}>
                        {__('Back to homepage')}
                    </a>
                </div>
            </div>
        </>
    );
}
