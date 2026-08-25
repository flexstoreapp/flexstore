import { CheckboxCard } from '@/components/ui/checkbox-card';
import { __ } from '@/lib/i18n';
import type { Product } from '@/types';

interface DuplicateProductOptionsProps {
    product: Product;
}

export function DuplicateProductOptions({ product }: DuplicateProductOptionsProps) {
    const hasCategory = product.category_id !== null;
    const hasBrand = product.brand_id !== null;
    const hasMedia =
        (product.media_gallery?.length ?? 0) > 0 || (product.variants?.some((v) => v.media != null) ?? false);
    const hasPricing = product.price !== null || (product.variants?.some((v) => v.price !== null) ?? false);
    const hasTaxSettings = product.tax_category !== null || product.is_tax_exempt;
    const hasInventory = (product.variants?.length ?? 0) === 0 && product.track_stock;
    const hasSkus = product.sku !== null || (product.variants?.some((v) => v.sku !== null) ?? false);
    const hasBarcodes = product.barcode !== null || (product.variants?.some((v) => v.barcode !== null) ?? false);
    const hasShipping = product.weight !== null || (product.variants?.some((v) => v.weight !== null) ?? false);
    const hasSeo = !!product.seo_title || !!product.seo_description;
    const hasDigitalFiles = (product.downloads?.length ?? 0) > 0;

    return (
        <div className="grid gap-2 sm:grid-cols-2">
            <CheckboxCard
                id="duplicate-category"
                name="duplicate_category"
                label={__('Category')}
                defaultChecked={hasCategory}
                disabled={!hasCategory}
            />

            <CheckboxCard
                id="duplicate-brand"
                name="duplicate_brand"
                label={__('Brand')}
                defaultChecked={hasBrand}
                disabled={!hasBrand}
            />

            <CheckboxCard
                id="duplicate-media"
                name="duplicate_media"
                label={__('Media')}
                defaultChecked={hasMedia}
                disabled={!hasMedia}
            />

            <CheckboxCard
                id="duplicate-pricing"
                name="duplicate_pricing"
                label={__('Pricing')}
                defaultChecked={hasPricing}
                disabled={!hasPricing}
            />

            <CheckboxCard
                id="duplicate-tax"
                name="duplicate_tax"
                label={__('Tax settings')}
                defaultChecked={hasTaxSettings}
                disabled={!hasTaxSettings}
            />

            <CheckboxCard
                id="duplicate-inventory"
                name="duplicate_inventory"
                label={__('Inventory')}
                defaultChecked={hasInventory}
                disabled={!hasInventory}
            />

            <CheckboxCard
                id="duplicate-skus"
                name="duplicate_skus"
                label={__('SKUs')}
                defaultChecked={false}
                disabled={!hasSkus}
            />

            <CheckboxCard
                id="duplicate-barcodes"
                name="duplicate_barcodes"
                label={__('Barcodes')}
                defaultChecked={false}
                disabled={!hasBarcodes}
            />

            <CheckboxCard
                id="duplicate-shipping"
                name="duplicate_shipping"
                label={__('Shipping')}
                defaultChecked={hasShipping}
                disabled={!hasShipping}
            />

            <CheckboxCard
                id="duplicate-seo"
                name="duplicate_seo"
                label={__('SEO')}
                defaultChecked={hasSeo}
                disabled={!hasSeo}
            />

            <CheckboxCard
                id="duplicate-digital-files"
                name="duplicate_digital_files"
                label={__('Digital files')}
                defaultChecked={hasDigitalFiles}
                disabled={!hasDigitalFiles}
            />
        </div>
    );
}
