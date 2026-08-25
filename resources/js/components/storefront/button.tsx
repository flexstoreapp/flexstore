import { cva, type VariantProps } from 'class-variance-authority';
import type { ButtonHTMLAttributes } from 'react';

import { Spinner } from '@/components/storefront/spinner';
import { cn } from '@/lib/utils';

export const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 rounded-md transition-colors focus-visible:outline-offset-2 disabled:pointer-events-none disabled:opacity-50',
    {
        variants: {
            variant: {
                primary: 'bg-primary text-white can-hover:hover:bg-primary-hover no-hover:active:bg-primary-hover',
                secondary:
                    'bg-primary-tint text-primary can-hover:hover:bg-primary-tint-strong no-hover:active:bg-primary-tint-strong focus-visible:outline-offset-0',
                outline:
                    'border border-primary text-primary can-hover:hover:bg-primary-tint no-hover:active:bg-primary-tint focus-visible:outline-offset-0',
                success: 'bg-success text-white',
                error: 'bg-error text-white',
            },
            size: {
                lg: 'text-md h-12 px-6 font-bold',
                md: 'h-11 px-5 font-semibold',
                sm: 'h-10 px-4 text-sm font-semibold',
            },
            block: {
                true: 'w-full',
            },
        },
        defaultVariants: {
            variant: 'primary',
            size: 'lg',
        },
    },
);

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement>, VariantProps<typeof buttonVariants> {
    processing?: boolean;
}

export function Button({
    className,
    variant,
    size,
    block,
    type = 'button',
    processing = false,
    disabled,
    children,
    ...props
}: ButtonProps) {
    return (
        <button
            type={type}
            disabled={disabled || processing}
            className={cn(buttonVariants({ variant, size, block }), 'relative', className)}
            {...props}
        >
            <span
                className={cn(
                    'inline-flex items-center gap-2 transition-opacity duration-(--duration-fast)',
                    processing && 'opacity-0',
                )}
            >
                {children}
            </span>
            <span
                aria-hidden="true"
                className={cn(
                    'absolute inset-0 flex items-center justify-center transition-opacity duration-(--duration-fast)',
                    processing ? 'opacity-100' : 'opacity-0',
                )}
            >
                <Spinner className={cn('size-4 border-2', !processing && 'animate-none')} />
            </span>
        </button>
    );
}
