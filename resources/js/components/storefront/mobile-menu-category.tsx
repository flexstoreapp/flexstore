import { Link } from '@inertiajs/react';
import { ChevronRightIcon } from 'lucide-react';

import type { MenuAccordion } from '@/hooks/storefront/use-menu-accordion';
import { cn } from '@/lib/utils';
import type { StorefrontMenuItem } from '@/types';

const rowPad: Record<number, string> = {
    1: 'px-5 h-11',
    2: 'ps-9 pe-5 h-10',
    3: 'ps-14 pe-5 h-9',
};

const nestedLeafText = 'text-sm text-muted transition-colors hover:text-primary';

const leafText: Record<number, string> = {
    1: 'text-ink transition-colors hover:bg-surface-2',
    2: nestedLeafText,
    3: nestedLeafText,
};

interface MobileMenuCategoryProps {
    node: StorefrontMenuItem;
    depth: number;
    itemKey: string;
    accordion: MenuAccordion;
    onNavigate: () => void;
}

export function MobileMenuCategory({ node, depth, itemKey, accordion, onNavigate }: MobileMenuCategoryProps) {
    if (!node.children?.length) {
        return (
            <Link
                href={node.url}
                onClick={onNavigate}
                className={cn('flex items-center', rowPad[depth], leafText[depth])}
            >
                {node.label}
            </Link>
        );
    }

    const isOpen = accordion.isOpen(itemKey);

    return (
        <div>
            <div
                {...accordion.triggerProps(itemKey)}
                className={cn(
                    'flex w-full items-center justify-between',
                    rowPad[depth],
                    depth === 1 ? 'text-ink transition-colors hover:bg-surface-2' : 'text-sm text-muted',
                )}
            >
                <Link
                    href={node.url}
                    onClick={(event) => {
                        event.stopPropagation();
                        onNavigate();
                    }}
                    className="transition-colors hover:text-primary"
                >
                    {node.label}
                </Link>
                <ChevronRightIcon
                    size={depth === 1 ? 12 : 11}
                    strokeWidth={2}
                    aria-hidden="true"
                    className={cn(
                        'ms-3 shrink-0 text-muted transition-[rotate] duration-(--duration-fast) ease-out-quart rtl:-scale-x-100',
                        isOpen && 'rotate-90',
                    )}
                />
            </div>
            <div
                className={cn(
                    'grid transition-[grid-template-rows] duration-(--duration-base) ease-out-quart [&>*]:overflow-hidden',
                    depth === 1 && 'bg-surface-2',
                    isOpen ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]',
                )}
            >
                <div>
                    {node.children.map((child) => (
                        <MobileMenuCategory
                            key={`${itemKey}/${child.label}`}
                            node={child}
                            depth={depth + 1}
                            itemKey={`${itemKey}/${child.label}`}
                            accordion={accordion}
                            onNavigate={onNavigate}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
}
