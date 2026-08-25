import { MinusIcon, PlusIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { __ } from '@/lib/i18n';

interface QuantityInputProps {
    itemId: number;
    name?: string;
    quantity: number;
    minQuantity?: number;
    maxQuantity?: number;
    onQuantityChange: (itemId: number, delta: number) => void;
}

export function QuantityInput({
    itemId,
    name,
    quantity,
    minQuantity = 0,
    maxQuantity,
    onQuantityChange,
}: QuantityInputProps) {
    return (
        <div className="flex items-center gap-2">
            <div dir="ltr" className="flex items-center rounded-md border bg-background">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    onClick={() => onQuantityChange(itemId, -1)}
                    disabled={quantity <= minQuantity}
                >
                    <MinusIcon className="size-3" />
                </Button>
                <input
                    type="number"
                    name={name}
                    min={minQuantity}
                    max={maxQuantity}
                    value={quantity}
                    onChange={(e) => {
                        const val = parseInt(e.target.value, 10);
                        if (isNaN(val)) return;
                        const clamped =
                            maxQuantity !== undefined
                                ? Math.max(minQuantity, Math.min(val, maxQuantity))
                                : Math.max(minQuantity, val);
                        onQuantityChange(itemId, clamped - quantity);
                    }}
                    className="w-8 [appearance:textfield] bg-transparent text-center text-sm outline-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                />
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    onClick={() => onQuantityChange(itemId, 1)}
                    disabled={maxQuantity !== undefined && quantity >= maxQuantity}
                >
                    <PlusIcon className="size-3" />
                </Button>
            </div>
            {maxQuantity !== undefined && (
                <span className="text-xs whitespace-nowrap">
                    {__('of :max', { max: maxQuantity })}
                </span>
            )}
        </div>
    );
}
