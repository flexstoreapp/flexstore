import { __ } from '@/lib/i18n';

export function InclTaxLabel({ show, className }: { show: boolean; className?: string }) {
    if (!show) return null;

    return className ? <p className={className}>{__('(incl. tax)')}</p> : __('(incl. tax)');
}
