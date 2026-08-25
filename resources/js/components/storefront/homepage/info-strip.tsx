import { SectionFrame } from '@/components/storefront/section-frame';
import { Section } from '@/components/storefront/section-shell';
import { getIconComponent } from '@/lib/icons';
import { getTranslation } from '@/lib/utils';
import type { InfoStripSectionData } from '@/types';

export function InfoStripSection({ section, tight = false }: { section: InfoStripSectionData; tight?: boolean }) {
    const items = section.settings.items;

    if (items.length === 0) {
        return null;
    }

    return (
        <Section className={tight ? 'mt-4 sm:mt-5' : undefined}>
            <SectionFrame cols="grid-cols-2 lg:grid-cols-4">
                {items.map((item, index) => {
                    const Icon = getIconComponent(item.icon_name);

                    return (
                        <div
                            key={index}
                            className="flex flex-col gap-2 border-e border-b border-line px-5 py-5 sm:flex-row sm:items-center sm:gap-4 sm:px-6"
                        >
                            {Icon && (
                                <span className="shrink-0 text-primary">
                                    <Icon aria-hidden="true" width={30} height={30} strokeWidth={1.5} />
                                </span>
                            )}
                            <div>
                                <div className="font-semibold text-ink">{getTranslation(item.title)}</div>
                                {item.subtitle && (
                                    <div className="mt-0.5 text-sm text-muted">{getTranslation(item.subtitle)}</div>
                                )}
                            </div>
                        </div>
                    );
                })}
            </SectionFrame>
        </Section>
    );
}
