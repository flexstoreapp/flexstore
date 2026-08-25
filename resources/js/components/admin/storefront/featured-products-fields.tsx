import { ProductSourceFields } from '@/components/admin/storefront/product-source-fields';
import type { FeaturedProductsSettings } from '@/types';

export function FeaturedProductsFields({
    settings,
    errors,
}: {
    settings?: FeaturedProductsSettings;
    errors: Record<string, string>;
}) {
    return <ProductSourceFields prefix="settings" settings={settings} errors={errors} />;
}
