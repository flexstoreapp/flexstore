import type { ReactNode } from 'react';

interface AccountEmptyStateProps {
    icon: ReactNode;
    title: string;
    description: string;
    action?: ReactNode;
}

export function AccountEmptyState({ icon, title, description, action }: AccountEmptyStateProps) {
    return (
        <div className="flex flex-col items-center px-6 py-14 text-center">
            <span
                aria-hidden="true"
                className="flex h-16 w-16 items-center justify-center rounded-full bg-surface-2 text-muted"
            >
                {icon}
            </span>
            <h2 className="mt-5 mb-0 text-xl font-semibold text-ink">{title}</h2>
            <p className="mx-auto mt-2 mb-0 max-w-[42ch] text-sm text-muted">{description}</p>
            {action && <div className="mt-6">{action}</div>}
        </div>
    );
}
