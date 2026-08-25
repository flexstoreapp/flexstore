import { ClockIcon, MailIcon, NewspaperIcon } from 'lucide-react';
import { useMemo, useState } from 'react';

import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import { sectionTypeIcons, sectionTypes } from '@/components/admin/storefront/section-item';
import { SearchInput } from '@/components/ui/search-input';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { StorefrontSectionType } from '@/types';

interface SectionTypePickerProps {
    onSelect: (type: StorefrontSectionType) => void;
}

const proSectionTypes: { key: string; label: string; icon: React.ReactNode }[] = [
    { key: 'deal_of_the_day', label: __('Deal of the day'), icon: <ClockIcon className="size-5" /> },
    { key: 'blog_posts', label: __('Blog posts'), icon: <NewspaperIcon className="size-5" /> },
    { key: 'newsletter', label: __('Newsletter'), icon: <MailIcon className="size-5" /> },
];

export function SectionTypePicker({ onSelect }: SectionTypePickerProps) {
    const [searchQuery, setSearchQuery] = useState('');
    const { open: openProUpgrade } = useProUpgrade();

    const filteredProSectionTypes = useMemo(() => {
        const query = searchQuery.trim().toLowerCase();

        return query === ''
            ? proSectionTypes
            : proSectionTypes.filter((item) => item.label.toLowerCase().includes(query));
    }, [searchQuery]);

    const filteredSectionTypes = useMemo(() => {
        if (!searchQuery.trim()) {
            return Object.entries(sectionTypes);
        }

        const query = searchQuery.toLowerCase();
        return Object.entries(sectionTypes).filter(([, label]) => label.toLowerCase().includes(query));
    }, [searchQuery]);

    return (
        <div className="space-y-4 p-4">
            <SearchInput value={searchQuery} onChange={setSearchQuery} placeholder={__('Search...')} />

            <div className="grid grid-cols-2 gap-3">
                {filteredSectionTypes.length > 0 ? (
                    filteredSectionTypes.map(([type, label]) => (
                        <button
                            key={type}
                            type="button"
                            onClick={() => onSelect(type as StorefrontSectionType)}
                            className={cn(
                                'group flex flex-col items-center gap-3 rounded-lg border p-4 text-center',
                                'transition-all hover:bg-muted/50',
                                'outline-none focus-visible:outline-none',
                                'focus-visible:ring-2 focus-visible:ring-primary',
                                'focus-visible:ring-offset-2 focus-visible:ring-offset-background',
                            )}
                        >
                            <div
                                className={cn(
                                    'flex size-12 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground',
                                    'transition-colors',
                                )}
                            >
                                {sectionTypeIcons[type as StorefrontSectionType]}
                            </div>
                            <span className="text-xs leading-tight font-medium">{label}</span>
                        </button>
                    ))
                ) : filteredProSectionTypes.length > 0 ? null : (
                    <div className="col-span-2 py-8 text-center text-sm text-muted-foreground">
                        {__('No items found.')}
                    </div>
                )}

                {filteredProSectionTypes.map((item) => (
                    <button
                        key={item.key}
                        type="button"
                        onClick={() => openProUpgrade(item.label)}
                        className={cn(
                            'group relative flex flex-col items-center gap-3 rounded-lg border p-4 text-center',
                            'transition-all hover:bg-muted/50',
                            'outline-none focus-visible:outline-none',
                            'focus-visible:ring-2 focus-visible:ring-primary',
                            'focus-visible:ring-offset-2 focus-visible:ring-offset-background',
                        )}
                    >
                        <ProBadge className="absolute end-2 top-2" />
                        <div className="flex size-12 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground opacity-60">
                            {item.icon}
                        </div>
                        <span className="text-xs leading-tight font-medium opacity-70">{item.label}</span>
                    </button>
                ))}
            </div>
        </div>
    );
}
