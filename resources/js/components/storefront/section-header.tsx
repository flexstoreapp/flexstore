import type { ReactNode } from 'react';

import { SectionTitle } from '@/components/storefront/section-shell';

interface SectionHeaderProps {
    title?: string;
    action?: ReactNode;
}

export function SectionHeader({ title, action }: SectionHeaderProps) {
    return (
        <div className="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            {title && <SectionTitle>{title}</SectionTitle>}
            {action}
        </div>
    );
}
