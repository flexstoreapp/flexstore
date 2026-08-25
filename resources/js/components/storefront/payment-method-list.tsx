import { BanknoteIcon, CreditCardIcon, WalletIcon } from 'lucide-react';
import { Suspense, type ReactNode } from 'react';

import { RadioCard } from '@/components/storefront/radio-card';
import { __ } from '@/lib/i18n';
import { cn, getTranslation } from '@/lib/utils';
import type { PaymentOption } from '@/types';

const PAYMENT_ICONS: Record<string, typeof CreditCardIcon> = {
    cod: BanknoteIcon,
    paypal: WalletIcon,
    mercadopago: WalletIcon,
};

function PaymentIcon({ option }: { option: PaymentOption }) {
    const Icon = PAYMENT_ICONS[option.driver] ?? CreditCardIcon;
    return <Icon size={20} strokeWidth={1.6} aria-hidden="true" className="text-muted" />;
}

interface PaymentMethodListProps {
    options: PaymentOption[];
    loading?: boolean;
    selectedId: string;
    onSelect: (id: string) => void;
    renderInlineContent?: ((option: PaymentOption) => ReactNode) | null;
    labelledBy: string;
}

export function PaymentMethodList({
    options,
    loading = false,
    selectedId,
    onSelect,
    renderInlineContent,
    labelledBy,
}: PaymentMethodListProps) {
    if (loading && options.length === 0) {
        return (
            <div className="mt-5 animate-pulse divide-y divide-line-strong overflow-hidden rounded-md border border-line-strong">
                {[0, 1].map((index) => (
                    <div key={index} className="h-14 bg-surface-2" />
                ))}
            </div>
        );
    }

    if (options.length === 0) {
        return <p className="mt-5 mb-0 text-sm text-muted">{__('No payment methods are available.')}</p>;
    }

    return (
        <div role="radiogroup" aria-labelledby={labelledBy} className="mt-5 flex flex-col">
            {options.map((option, index) => {
                const id = String(option.id);
                const selected = selectedId === id;
                const inlineContent = selected && renderInlineContent ? renderInlineContent(option) : null;
                const isLast = index === options.length - 1;

                return (
                    <div key={id} className="relative not-first:-mt-px">
                        <RadioCard
                            checked={selected}
                            onSelect={() => onSelect(id)}
                            showDot={options.length > 1}
                            className={cn(
                                'rounded-none bg-transparent',
                                index === 0 && 'rounded-t-md',
                                isLast && !inlineContent && 'rounded-b-md',
                            )}
                        >
                            <span className="flex-1 font-semibold text-ink">{getTranslation(option.name)}</span>
                            <PaymentIcon option={option} />
                        </RadioCard>
                        <div
                            className={cn(
                                'grid transition-[grid-template-rows] duration-200 ease-out',
                                inlineContent ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]',
                            )}
                        >
                            <div className="overflow-hidden">
                                {inlineContent && (
                                    <div
                                        className={cn(
                                            'border border-t-0 border-line-strong bg-surface-2 p-5',
                                            isLast && 'rounded-b-md',
                                        )}
                                    >
                                        <Suspense fallback={null}>{inlineContent}</Suspense>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
