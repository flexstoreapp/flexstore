import { router, usePage } from '@inertiajs/react';
import { TagIcon, XIcon } from 'lucide-react';
import { useRef, useState } from 'react';

import * as CheckoutCouponController from '@/actions/App/Http/Controllers/Storefront/CheckoutCouponController';
import { Button } from '@/components/storefront/button';
import { __, __nodes } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { StorefrontSharedData } from '@/types';

interface CouponFormProps {
    inputId?: string;
    couponCode?: string;
    onCouponCodeChange?: (code: string) => void;
    email?: string;
}

export function CouponForm({ inputId = 'coupon', couponCode, onCouponCodeChange, email }: CouponFormProps) {
    const { cart, auth } = usePage<StorefrontSharedData>().props;
    const [localCode, setLocalCode] = useState('');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    const code = couponCode ?? localCode;
    const setCode = onCouponCodeChange ?? setLocalCode;
    const customerEmail = email ?? auth.user?.email ?? '';
    const appliedCoupon = cart.coupon_code ?? null;

    const apply = () => {
        if (!code.trim()) return;
        setProcessing(true);
        setError(null);
        router.post(
            CheckoutCouponController.store().url,
            { coupon_code: code, customer_email: customerEmail },
            {
                preserveScroll: true,
                only: ['cart'],
                onError: (errors) => {
                    setError(
                        errors.coupon_code ?? errors.customer_email ?? __('This discount code could not be applied.'),
                    );
                    inputRef.current?.focus();
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    const remove = () => {
        setProcessing(true);
        router.delete(CheckoutCouponController.destroy().url, {
            preserveScroll: true,
            only: ['cart'],
            onFinish: () => setProcessing(false),
        });
    };

    if (appliedCoupon) {
        return (
            <div className="flex h-11 items-center gap-2.5 rounded-md border border-success/40 bg-success/10 ps-3.5 pe-2">
                <TagIcon size={15} strokeWidth={1.6} aria-hidden="true" className="text-success" />
                <span className="min-w-0 text-sm text-muted">
                    {__nodes(':code applied', {
                        code: (
                            <span dir="ltr" className="font-semibold text-ink">
                                {appliedCoupon}
                            </span>
                        ),
                    })}
                </span>
                <button
                    type="button"
                    onClick={remove}
                    disabled={processing}
                    aria-label={__('Remove discount code :code', { code: appliedCoupon })}
                    className="ms-auto flex h-7 w-7 shrink-0 items-center justify-center rounded-xs text-muted transition disabled:opacity-50 can-hover:hover:text-error"
                >
                    <XIcon size={14} strokeWidth={2} aria-hidden="true" />
                </button>
            </div>
        );
    }

    const errorId = `${inputId}-error`;

    return (
        <div>
            <div className="flex gap-2">
                <div className="relative flex-1">
                    <TagIcon
                        size={16}
                        strokeWidth={1.6}
                        aria-hidden="true"
                        className="absolute start-3.5 top-1/2 -translate-y-1/2 text-muted"
                    />
                    <input
                        id={inputId}
                        ref={inputRef}
                        type="text"
                        value={code}
                        onChange={(event) => {
                            setCode(event.target.value);
                            setError(null);
                        }}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                apply();
                            }
                        }}
                        placeholder={__('Discount code')}
                        aria-label={__('Discount code')}
                        aria-invalid={error ? true : undefined}
                        aria-describedby={error ? errorId : undefined}
                        className={cn(
                            'h-11 w-full rounded-md border bg-surface-2 ps-10 pe-4 text-ink transition placeholder:text-muted focus-visible:outline-hidden',
                            error
                                ? 'border-error ring-1 ring-error'
                                : 'border-line-strong focus-visible:border-primary focus-visible:ring-1 focus-visible:ring-primary',
                        )}
                    />
                </div>
                <Button
                    variant="secondary"
                    size="md"
                    onClick={apply}
                    disabled={!code.trim()}
                    processing={processing}
                    className="shrink-0"
                >
                    {__('Apply')}
                </Button>
            </div>
            {error && (
                <p id={errorId} className="mt-1.5 mb-0 text-sm text-error">
                    {error}
                </p>
            )}
        </div>
    );
}
