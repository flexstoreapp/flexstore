import { BuyBoxActions } from '@/components/storefront/product/buy-box-actions';
import { BuyBoxBrand } from '@/components/storefront/product/buy-box-brand';
import { BuyBoxQuantity } from '@/components/storefront/product/buy-box-quantity';
import { BuyBoxUtilities } from '@/components/storefront/product/buy-box-utilities';
import { InfoStrip } from '@/components/storefront/product/info-strip';
import { ProductOptions } from '@/components/storefront/product-options';
import { ProductPrice } from '@/components/storefront/product-price';
import { ProductRating } from '@/components/storefront/product-rating';
import type { ProductPurchase } from '@/hooks/storefront/use-product-purchase';
import { getTranslation } from '@/lib/utils';
import type { ProductDetailData, ProductDetailSettings } from '@/types';

interface ProductInfoProps {
    product: ProductDetailData;
    settings: ProductDetailSettings;
    purchase: ProductPurchase;
    onReviewsClick?: () => void;
}

export function ProductInfo({ product, settings, purchase, onReviewsClick }: ProductInfoProps) {
    const { cart, pricing, resolvedVariant } = purchase;

    const title = getTranslation(product.title, 'Product');
    const sku = resolvedVariant?.sku ?? product.sku;

    const isDigital = product.type === 'digital';

    return (
        <div className="@container">
            <BuyBoxBrand brand={product.brand} />

            <h1 className="text-7xl leading-[1.15] font-bold text-ink sm:text-9xl">{title}</h1>

            <ProductRating
                rating={product.rating}
                reviewCount={product.review_count}
                onReviewsClick={settings.show_reviews ? onReviewsClick : undefined}
            />

            <ProductPrice
                product={product}
                variant={resolvedVariant}
                size="xl"
                as="div"
                showTaxNote={product.prices_include_tax}
                className="mt-3"
            />

            <ProductOptions options={product.options} selected={purchase.selected} onSelect={purchase.selectOption} />

            {!isDigital && (
                <BuyBoxQuantity
                    value={purchase.quantity}
                    onChange={purchase.changeQuantity}
                    max={purchase.maxQuantity}
                />
            )}

            <BuyBoxActions
                cart={cart}
                label={purchase.addToCartLabel}
                canPurchase={purchase.canPurchase}
                onAddToCart={purchase.submitAddToCart}
                onBuyNow={purchase.submitBuyNow}
                size="lg"
            />

            <BuyBoxUtilities product={product} sku={sku} price={pricing.price} />

            {settings.show_info_strip && <InfoStrip items={settings.info_strip} />}
        </div>
    );
}
