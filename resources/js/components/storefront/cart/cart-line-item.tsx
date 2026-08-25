import { Link } from '@inertiajs/react';
import { CircleAlertIcon, Trash2Icon } from 'lucide-react';

import * as ProductController from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { ContainedMedia } from '@/components/storefront/contained-media';
import { QuantityStepper } from '@/components/storefront/quantity-stepper';
import { useCartItem } from '@/hooks/storefront/use-cart-item';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { lineItemMedia } from '@/lib/media';
import { getTranslation } from '@/lib/utils';
import type { CartItem } from '@/types';

export function CartLineItem({ item }: { item: CartItem }) {
    const { formatMoney } = useFormatMoney();
    const cartItem = useCartItem(item);

    const product = item.product;
    const title = getTranslation(product?.title);
    const productUrl = product ? ProductController.show(product.url_handle).url : '#';

    return (
        <li className="grid grid-cols-1 gap-4 border-b border-line px-5 py-5 last:border-b-0 lg:col-span-4 lg:grid-cols-subgrid lg:items-center lg:gap-6 lg:px-6 xl:gap-8">
            <div className="flex min-w-0 items-start gap-4">
                <Link
                    href={productUrl}
                    tabIndex={-1}
                    aria-hidden="true"
                    className="relative block size-16 shrink-0 overflow-hidden rounded-sm border border-line bg-surface-2"
                >
                    <ContainedMedia media={lineItemMedia(item)} source="small" />
                </Link>
                <div className="min-w-0">
                    <Link
                        href={productUrl}
                        className="line-clamp-2 leading-snug font-semibold text-ink transition-colors hover:text-primary"
                    >
                        {title}
                    </Link>
                    {item.variant_title && <div className="mt-0.5 text-sm text-muted">{item.variant_title}</div>}
                    <button
                        type="button"
                        onClick={cartItem.remove}
                        disabled={cartItem.removing}
                        aria-label={__('Remove :name', { name: title })}
                        className="mt-2 inline-flex items-center gap-1.5 rounded-xs text-sm text-muted transition focus-visible:outline-offset-2 disabled:opacity-50 can-hover:hover:text-error"
                    >
                        <Trash2Icon size={14} strokeWidth={1.7} aria-hidden="true" />
                        {__('Remove')}
                    </button>
                </div>
            </div>

            <div className="whitespace-nowrap text-muted lg:text-center lg:text-ink">
                <span className="me-2 text-2xs font-bold tracking-label text-muted uppercase lg:hidden">
                    {__('Unit price')}
                </span>
                {item.compare_at_price && <span className="sr-only">{__('Sale price')}</span>}
                {formatMoney(item.unit_price)}
                {item.compare_at_price && (
                    <>
                        <span className="sr-only">{__('Regular price')}</span>
                        <s className="ms-2 font-normal text-muted">{formatMoney(item.compare_at_price)}</s>
                    </>
                )}
            </div>

            <div className="lg:justify-self-center">
                <QuantityStepper
                    value={cartItem.quantity}
                    onChange={cartItem.setQuantity}
                    disabled={cartItem.updating}
                    size="md"
                    ariaLabel={`Quantity for ${title}`}
                />
            </div>

            <div className="font-bold whitespace-nowrap text-ink lg:text-end">
                <span className="me-2 text-2xs font-bold tracking-label text-muted uppercase lg:hidden">
                    {__('Line total')}
                </span>
                {formatMoney(item.total_price)}
            </div>

            {cartItem.error && (
                <p role="alert" className="flex items-center gap-1.5 text-sm text-error lg:col-span-4">
                    <CircleAlertIcon size={14} aria-hidden="true" />
                    {cartItem.error}
                </p>
            )}
        </li>
    );
}
