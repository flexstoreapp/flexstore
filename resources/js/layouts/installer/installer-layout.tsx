import { usePage } from '@inertiajs/react';

import { cn } from '@/lib/utils';

const LICENSE_STEP = 4;

const allSteps = ['Requirements', 'Database', 'Admin account', 'License', 'Finish'];

interface InstallerLayoutProps {
    step: number;
}

export function InstallerLayout({ children, step }: React.PropsWithChildren<InstallerLayoutProps>) {
    const { licenseRequired } = usePage<{ licenseRequired: boolean }>().props;

    const steps = licenseRequired ? allSteps : allSteps.filter((label) => label !== 'License');
    const activeStep = licenseRequired || step < LICENSE_STEP ? step : step - 1;

    return (
        <div className="flex min-h-svh flex-col items-center justify-center bg-muted p-4 sm:p-8">
            <div className="w-full max-w-xl">
                <div className="mb-6 text-center">
                    <h1 className="text-xl font-semibold tracking-tight">FlexStore installer</h1>
                </div>

                <nav aria-label="Installation progress" className="mb-6">
                    <div className={cn('grid', licenseRequired ? 'grid-cols-5' : 'grid-cols-4')}>
                        {steps.map((label, i) => {
                            const stepNumber = i + 1;
                            const isActive = stepNumber === activeStep;
                            const isCompleted = stepNumber < activeStep;

                            return (
                                <div key={label} className="relative flex flex-col items-center gap-1.5">
                                    <div
                                        className={cn(
                                            'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-medium transition-colors',
                                            (isActive || isCompleted) && 'bg-primary text-primary-foreground',
                                            !isActive && !isCompleted && 'border bg-background text-muted-foreground',
                                        )}
                                    >
                                        {isCompleted ? (
                                            <svg
                                                className="size-3.5"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                strokeWidth={3}
                                            >
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        ) : (
                                            stepNumber
                                        )}
                                    </div>
                                    <span
                                        className={cn(
                                            'px-1 text-center text-xs',
                                            isActive ? 'font-medium text-foreground' : 'text-muted-foreground',
                                        )}
                                    >
                                        {label}
                                    </span>
                                    {i < steps.length - 1 && (
                                        <div
                                            className={cn(
                                                'absolute start-[calc(50%+1.375rem)] end-[calc(-50%+1.375rem)] top-3.5 h-px -translate-y-1/2 transition-colors',
                                                isCompleted ? 'bg-primary' : 'bg-border',
                                            )}
                                        />
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </nav>

                {children}
            </div>
        </div>
    );
}
