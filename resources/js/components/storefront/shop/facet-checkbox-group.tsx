import { FilterGroup } from '@/components/storefront/shop/filter-group';
import { FilterOption } from '@/components/storefront/shop/filter-option';
import { getTranslation } from '@/lib/utils';
import type { ShopFacet } from '@/types';

interface FacetCheckboxGroupProps {
    title: string;
    facets: ShopFacet[];
    selected: number[];
    onToggle: (id: number) => void;
}

export function FacetCheckboxGroup({ title, facets, selected, onToggle }: FacetCheckboxGroupProps) {
    return (
        <FilterGroup title={title} visibleCount={8}>
            {facets.map((facet) => (
                <FilterOption
                    key={facet.id}
                    type="checkbox"
                    checked={selected.includes(facet.id)}
                    onSelect={() => onToggle(facet.id)}
                    label={getTranslation(facet.name)}
                    count={facet.count}
                />
            ))}
        </FilterGroup>
    );
}
