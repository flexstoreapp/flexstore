import { getIconComponent } from '@/lib/icons';
import { getTranslation } from '@/lib/utils';
import type { InfoStripItem } from '@/types';

export function InfoStrip({ items }: { items: InfoStripItem[] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <ul className="mt-7 grid list-none grid-cols-1 overflow-hidden rounded-sm border border-line bg-surface p-0 @[34rem]:grid-cols-3">
            {items.map((item, index) => {
                const Icon = getIconComponent(item.icon_name);
                return (
                    <li
                        key={index}
                        className="flex items-center gap-3 border-t border-line p-3 first:border-t-0 @[34rem]:-ms-px @[34rem]:border-s @[34rem]:border-t-0 @[34rem]:first:border-s-0"
                    >
                        {Icon && (
                            <span aria-hidden="true" className="shrink-0 text-primary">
                                <Icon size={20} strokeWidth={1.6} />
                            </span>
                        )}
                        <span className="text-sm leading-tight">
                            <span className="block font-semibold text-ink">{getTranslation(item.title)}</span>
                            {item.subtitle && <span className="block text-muted">{getTranslation(item.subtitle)}</span>}
                        </span>
                    </li>
                );
            })}
        </ul>
    );
}
