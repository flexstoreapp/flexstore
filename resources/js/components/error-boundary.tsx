import { router } from '@inertiajs/react';
import { AlertTriangleIcon, RefreshCwIcon } from 'lucide-react';
import { Component, type ReactNode } from 'react';

import { __ } from '@/lib/i18n';

import { ErrorDetails } from './error-details';

interface ErrorBoundaryProps {
    children: ReactNode;
    fallback?: (error: Error, resetError: () => void) => ReactNode;
}

interface ErrorBoundaryState {
    hasError: boolean;
    error: Error | null;
}

export class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
    constructor(props: ErrorBoundaryProps) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error: Error): ErrorBoundaryState {
        return { hasError: true, error };
    }

    componentDidCatch(error: Error, errorInfo: React.ErrorInfo): void {
        console.error('Global Error Boundary caught an error:', error, errorInfo);

        if (import.meta.env.DEV) {
            console.error('Error stack:', error.stack);
            console.error('Component stack:', errorInfo.componentStack);
        }
    }

    resetErrorBoundary = (): void => {
        this.setState({ hasError: false, error: null });
    };

    handleGoHome = (): void => {
        this.resetErrorBoundary();
        router.visit('/');
    };

    render(): ReactNode {
        if (this.state.hasError) {
            if (this.props.fallback && this.state.error) {
                return this.props.fallback(this.state.error, this.resetErrorBoundary);
            }

            return (
                <div className="flex min-h-svh items-center justify-center bg-white p-4 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
                    <div className="w-full max-w-110 space-y-6 text-center">
                        <div className="mx-auto flex size-16 items-center justify-center rounded-full bg-red-600/10 dark:bg-red-500/10">
                            <AlertTriangleIcon className="size-8 text-red-600 dark:text-red-500" />
                        </div>

                        <div className="space-y-2">
                            <h1 className="text-2xl font-semibold tracking-tight">{__('Something went wrong.')}</h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                {__('Please try refreshing the page.')}
                            </p>
                        </div>

                        {import.meta.env.DEV && this.state.error && <ErrorDetails error={this.state.error} />}

                        <button
                            type="button"
                            onClick={this.resetErrorBoundary}
                            className="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-neutral-900 px-5 text-sm font-semibold text-white transition-colors hover:bg-neutral-700 hover:text-white dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-neutral-300"
                        >
                            <RefreshCwIcon className="size-4" />
                            {__('Try again')}
                        </button>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}
