import { useState } from 'react';

import { ProductGrid } from '@/components/storefront/product-grid';
import { Section, SectionTitle } from '@/components/storefront/section-shell';
import { useTabIndicator } from '@/hooks/storefront/use-tab-indicator';
import { __ } from '@/lib/i18n';
import { cn, getTranslation } from '@/lib/utils';
import type { ProductTabsSectionData } from '@/types';

export function ProductTabsSection({ section }: { section: ProductTabsSectionData }) {
    const tabs = section.settings.tabs;
    const [activeTab, setActiveTab] = useState(0);
    const { tabRefs, indicator, onKeyDown } = useTabIndicator(activeTab, tabs.length, setActiveTab);

    if (tabs.length === 0) {
        return null;
    }

    return (
        <Section>
            <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <SectionTitle>{getTranslation(section.title)}</SectionTitle>
                <div
                    role="tablist"
                    aria-label={__('Product categories')}
                    className="relative -mx-6 no-scrollbar flex shrink-0 gap-4 overflow-x-auto px-6 font-semibold sm:mx-0 sm:px-0"
                    onKeyDown={onKeyDown}
                >
                    {tabs.map((tab, index) => (
                        <button
                            key={index}
                            ref={(el) => {
                                tabRefs.current[index] = el;
                            }}
                            type="button"
                            role="tab"
                            id={`section-${section.id}-tab-${index}`}
                            aria-controls={`section-${section.id}-panel-${index}`}
                            aria-selected={activeTab === index}
                            tabIndex={activeTab === index ? 0 : -1}
                            onClick={() => setActiveTab(index)}
                            className={cn(
                                'px-1.5 py-2.5 whitespace-nowrap transition-colors duration-(--duration-fast)',
                                'text-muted transition-colors hover:text-ink',
                                'aria-selected:text-primary',
                                'focus-visible:-outline-offset-2',
                            )}
                        >
                            {getTranslation(tab.label)}
                        </button>
                    ))}
                    <span
                        aria-hidden="true"
                        className="absolute bottom-0 h-0.5 rounded-full bg-primary transition-[left,width] duration-(--duration-base) ease-out-quart"
                        style={{ left: indicator.left, width: indicator.width }}
                    />
                </div>
            </div>

            {tabs.map((tab, index) => (
                <div
                    key={index}
                    id={`section-${section.id}-panel-${index}`}
                    role="tabpanel"
                    aria-labelledby={`section-${section.id}-tab-${index}`}
                    hidden={activeTab !== index}
                >
                    {activeTab === index && <ProductGrid products={tab.products} />}
                </div>
            ))}
        </Section>
    );
}
