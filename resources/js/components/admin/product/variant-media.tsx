import { InlineImageUploader } from '@/components/admin/inline-image-uploader';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { __ } from '@/lib/i18n';
import { mediaBoxRatio } from '@/lib/media';
import { stringifyVariantName } from '@/lib/product-form-utils';
import { type ProductVariant } from '@/types';

interface VariantMediaProps {
    variants: ProductVariant[];
    onVariantUpdate: (variantId: string, updates: Partial<ProductVariant>) => void;
}

export function VariantMedia({ variants, onVariantUpdate }: VariantMediaProps) {
    const ratio = mediaBoxRatio(variants.map((variant) => variant.media));

    return (
        <ScrollArea className="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>{__('Variant')}</TableHead>
                        <TableHead>{__('Image')}</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {variants.map((variant, index) => (
                        <TableRow key={variant.id}>
                            <TableCell className="font-medium">{stringifyVariantName(variant)}</TableCell>
                            <TableCell>
                                <InlineImageUploader
                                    id={`image-${variant.id}`}
                                    name={`variants.${index}.media_id`}
                                    value={variant.media ?? null}
                                    onChange={(media) =>
                                        onVariantUpdate(variant.id, {
                                            media,
                                        })
                                    }
                                    size="sm"
                                    ratio={ratio}
                                    label={`${stringifyVariantName(variant)} image`}
                                    generateThumbnail
                                />
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>

            <ScrollBar orientation="horizontal" />
        </ScrollArea>
    );
}
