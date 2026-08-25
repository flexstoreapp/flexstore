import { monitorForElements } from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import { reorder } from '@atlaskit/pragmatic-drag-and-drop/reorder';
import { extractInstruction, type Instruction } from '@atlaskit/pragmatic-drag-and-drop-hitbox/list-item';
import { Link, router } from '@inertiajs/react';
import { LayoutGridIcon } from 'lucide-react';
import { LayoutGroup } from 'motion/react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

import * as StorefrontController from '@/actions/App/Http/Controllers/Admin/StorefrontController';
import * as StorefrontSectionController from '@/actions/App/Http/Controllers/Admin/StorefrontSectionController';
import * as StorefrontSectionReorderController from '@/actions/App/Http/Controllers/Admin/StorefrontSectionReorderController';
import { useConfirm } from '@/components/admin/confirm';
import { SectionItem, sectionTypes } from '@/components/admin/storefront/section-item';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { SearchInput } from '@/components/ui/search-input';
import { useStorefrontBuilder, useStorefrontBuilderAction } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import type { StorefrontSection } from '@/types';

export default function Homepage({ sections: initialSections }: { sections: StorefrontSection[] }) {
    const [sections, setSections] = useState<StorefrontSection[]>(initialSections);
    const [prevInitial, setPrevInitial] = useState(initialSections);
    const [searchQuery, setSearchQuery] = useState('');
    const { confirm } = useConfirm();
    const { reloadIframe } = useStorefrontBuilder();

    useStorefrontBuilderAction(
        <Can permission={Permission.StorefrontUpdate}>
            <Button asChild>
                <Link href={StorefrontSectionController.create()} prefetch>
                    {__('Add section')}
                </Link>
            </Button>
        </Can>,
    );

    if (initialSections !== prevInitial) {
        setPrevInitial(initialSections);
        setSections(initialSections);
    }

    const filteredSections = useMemo(() => {
        if (!searchQuery.trim()) {
            return sections;
        }

        const query = searchQuery.toLowerCase();
        return sections.filter((section) => {
            if (section.title) {
                const titleMatches = Object.values(section.title).some((translation) =>
                    String(translation).toLowerCase().includes(query),
                );
                if (titleMatches) return true;
            }

            const typeLabel = sectionTypes[section.type];
            if (typeLabel && typeLabel.toLowerCase().includes(query)) {
                return true;
            }

            return false;
        });
    }, [sections, searchQuery]);

    useEffect(() => {
        return monitorForElements({
            onDrop({ location, source }) {
                if (!location.current.dropTargets.length) return;

                const sourceIndex = source.data.index as number;
                const target = location.current.dropTargets[0];
                const targetIndex = target.data.index as number;

                if (typeof sourceIndex !== 'number' || typeof targetIndex !== 'number') return;
                if (sourceIndex === targetIndex) return;

                const instruction: Instruction | null = extractInstruction(target.data);
                if (instruction === null) return;

                let finishIndex: number;

                if (instruction.operation === 'reorder-before') {
                    finishIndex = sourceIndex < targetIndex ? targetIndex - 1 : targetIndex;
                } else {
                    finishIndex = sourceIndex < targetIndex ? targetIndex : targetIndex + 1;
                }

                if (sourceIndex === finishIndex) return;

                const reorderedSections = reorder({
                    list: sections,
                    startIndex: sourceIndex,
                    finishIndex,
                });

                setSections(reorderedSections);

                const orderedIds = reorderedSections.map((s) => s.id);
                router.patch(
                    StorefrontSectionReorderController.update(),
                    { ordered_ids: orderedIds },
                    {
                        preserveScroll: true,
                        only: ['sections'],
                        onError: (errors) => {
                            console.error('Failed to reorder category:', errors);
                            toast.error(__('Something went wrong.'));
                            setSections(initialSections);
                        },
                        onSuccess: () => reloadIframe(),
                    },
                );
            },
        });
    }, [sections, initialSections, reloadIframe]);

    const handleDeleteSection = (section: StorefrontSection) => {
        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description: __('This will permanently delete this section from your storefront.'),
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(StorefrontSectionController.destroy(section), {
                        preserveScroll: true,
                        only: ['sections'],
                        onSuccess: () => reloadIframe(),
                        onFinish: () => resolve(),
                    });
                }),
        });
    };

    const handleToggleActive = (section: StorefrontSection) => {
        router.patch(
            StorefrontSectionController.update(section),
            { is_active: !section.is_active },
            {
                preserveScroll: true,
                only: ['sections'],
                onSuccess: () => reloadIframe(),
            },
        );
    };

    return (
        <div className="mb-6 space-y-4 p-4 text-sm">
            {sections.length > 0 && (
                <SearchInput value={searchQuery} onChange={setSearchQuery} placeholder={__('Search...')} />
            )}

            {sections.length === 0 ? (
                <Empty className="border border-dashed">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <LayoutGridIcon />
                        </EmptyMedia>
                        <EmptyTitle>{__('No homepage sections')}</EmptyTitle>
                        <EmptyDescription>{__('Add your first section to build your homepage.')}</EmptyDescription>
                    </EmptyHeader>
                </Empty>
            ) : filteredSections.length === 0 ? (
                <div className="col-span-2 py-8 text-center text-muted-foreground">{__('No items found.')}</div>
            ) : (
                <LayoutGroup>
                    <div className="space-y-2">
                        {filteredSections.map((section, index) => (
                            <SectionItem
                                key={section.id}
                                section={section}
                                index={index}
                                onDelete={handleDeleteSection}
                                onToggleActive={handleToggleActive}
                            />
                        ))}
                    </div>
                </LayoutGroup>
            )}
        </div>
    );
}

Homepage.layout = {
    title: __('Homepage'),
    backHref: StorefrontController.index(),
};
