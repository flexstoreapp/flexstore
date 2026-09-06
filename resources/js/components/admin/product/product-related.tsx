import { reorder } from '@atlaskit/pragmatic-drag-and-drop/reorder';
import { PackagePlusIcon, PlusIcon } from 'lucide-react';
import { LayoutGroup, m, type Transition } from 'motion/react';
import { useState } from 'react';

import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { ProductPicker, type SelectableItem } from '@/components/admin/product-picker';
import { ThumbnailRatio } from '@/components/admin/thumbnail';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { FieldError } from '@/components/ui/field';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { __ } from '@/lib/i18n';

import { ProductRelatedItem } from './product-related-item';

const animationTransition: Transition = {
    type: 'tween',
    ease: 'easeInOut',
    duration: 0.24,
};

const instantTransition: Transition = { duration: 0 };

type RelationKind = 'cross_sells' | 'up_sells';

interface ProductRelatedProps {
    productId?: number;
    crossSells: SelectableItem[];
    onCrossSellsChange: (selection: SelectableItem[]) => void;
    upSells: SelectableItem[];
    onUpSellsChange: (selection: SelectableItem[]) => void;
    errors: Record<string, string>;
}

interface RelationListProps {
    name: RelationKind;
    emptyTitle: string;
    emptyDescription: string;
    items: SelectableItem[];
    onChange: (selection: SelectableItem[]) => void;
    onAdd: () => void;
    errors: Record<string, string>;
}

function RelationList({ name, emptyTitle, emptyDescription, items, onChange, onAdd, errors }: RelationListProps) {
    const [animateReorder, setAnimateReorder] = useState(false);
    const itemErrorKey = Object.keys(errors).find((key) => key.startsWith(`${name}.`));

    const handleRemove = (id: number) => {
        setAnimateReorder(false);
        onChange(items.filter((item) => item.id !== id));
    };

    const handleReorder = (startIndex: number, endIndex: number) => {
        setAnimateReorder(true);
        onChange(reorder({ list: items, startIndex, finishIndex: endIndex }));
    };

    return (
        <>
            <FormDirtySignal signal={items.map((item) => item.id).join(',')} />

            {items.length === 0 ? (
                <Empty className="border border-dashed">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <PackagePlusIcon />
                        </EmptyMedia>
                        <EmptyTitle>{emptyTitle}</EmptyTitle>
                        <EmptyDescription>{emptyDescription}</EmptyDescription>
                    </EmptyHeader>
                    <EmptyContent>
                        <Button type="button" variant="outline" onClick={onAdd}>
                            <PlusIcon />
                            {__('Add products')}
                        </Button>
                    </EmptyContent>
                </Empty>
            ) : (
                <ThumbnailRatio media={items.map((item) => item.featured_media)}>
                    <LayoutGroup>
                        <m.div layout className="flex flex-col gap-3" transition={instantTransition}>
                            {items.map((item, index) => (
                                <m.div
                                    key={item.id}
                                    layout
                                    initial={false}
                                    transition={animateReorder ? animationTransition : instantTransition}
                                >
                                    <ProductRelatedItem
                                        name={name}
                                        item={item}
                                        index={index}
                                        isLast={index === items.length - 1}
                                        onRemove={handleRemove}
                                        onReorder={handleReorder}
                                    />
                                </m.div>
                            ))}
                        </m.div>
                    </LayoutGroup>
                </ThumbnailRatio>
            )}

            <FieldError>{errors[name]}</FieldError>
            {itemErrorKey && <FieldError>{errors[itemErrorKey]}</FieldError>}
        </>
    );
}

export function ProductRelated({
    productId,
    crossSells,
    onCrossSellsChange,
    upSells,
    onUpSellsChange,
    errors,
}: ProductRelatedProps) {
    const [activeTab, setActiveTab] = useState<RelationKind>('cross_sells');
    const [pickerOpen, setPickerOpen] = useState(false);

    const lists: Record<RelationKind, { items: SelectableItem[]; onChange: (selection: SelectableItem[]) => void }> = {
        cross_sells: { items: crossSells, onChange: onCrossSellsChange },
        up_sells: { items: upSells, onChange: onUpSellsChange },
    };

    const active = lists[activeTab];

    const handleSelectionChange = (selection: SelectableItem[]) => {
        active.onChange(selection.filter((item) => item.id !== productId));
    };

    return (
        <Card>
            <CardHeader className="max-sm:has-data-[slot=card-action]:grid-cols-1">
                <CardTitle>{__('Recommendations')}</CardTitle>
                <CardDescription>{__('Hand-picked products to sell alongside or instead of this one')}</CardDescription>
                {active.items.length > 0 && (
                    <CardAction className="max-sm:col-start-1 max-sm:row-span-1 max-sm:row-start-3 max-sm:mt-1 max-sm:justify-self-start">
                        <Button type="button" variant="outline" onClick={() => setPickerOpen(true)}>
                            <PlusIcon />
                            {__('Add products')}
                        </Button>
                    </CardAction>
                )}
            </CardHeader>
            <CardContent>
                <Tabs value={activeTab} onValueChange={(value) => setActiveTab(value as RelationKind)}>
                    <TabsList className="flex w-full">
                        <TabsTrigger value="cross_sells" className="flex-1">
                            {__('Cross-sells')}
                            {crossSells.length > 0 && <Badge variant="secondary">{crossSells.length}</Badge>}
                        </TabsTrigger>
                        <TabsTrigger value="up_sells" className="flex-1">
                            {__('Up-sells')}
                            {upSells.length > 0 && <Badge variant="secondary">{upSells.length}</Badge>}
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="cross_sells" className="mt-4 data-[state=inactive]:hidden" forceMount>
                        <RelationList
                            name="cross_sells"
                            emptyTitle={__('No cross-sells yet')}
                            emptyDescription={__('Complementary products, suggested in the cart and cart drawer.')}
                            items={crossSells}
                            onChange={onCrossSellsChange}
                            onAdd={() => setPickerOpen(true)}
                            errors={errors}
                        />
                    </TabsContent>

                    <TabsContent value="up_sells" className="mt-4 data-[state=inactive]:hidden" forceMount>
                        <RelationList
                            name="up_sells"
                            emptyTitle={__('No up-sells yet')}
                            emptyDescription={__('Higher-end alternatives, suggested on the product page.')}
                            items={upSells}
                            onChange={onUpSellsChange}
                            onAdd={() => setPickerOpen(true)}
                            errors={errors}
                        />
                    </TabsContent>
                </Tabs>
            </CardContent>

            <ProductPicker
                open={pickerOpen}
                onOpenChange={setPickerOpen}
                selectedItems={active.items}
                onSelectionChange={handleSelectionChange}
                multiple
            />
        </Card>
    );
}
