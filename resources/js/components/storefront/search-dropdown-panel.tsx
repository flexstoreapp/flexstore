import { Link } from '@inertiajs/react';

import SearchController from '@/actions/App/Http/Controllers/Storefront/SearchController';
import { SearchSuggestionRow } from '@/components/storefront/search-suggestion-row';
import { Spinner } from '@/components/storefront/spinner';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { SearchSuggestion } from '@/types';

interface SuggestionsBodyProps {
    suggestions: SearchSuggestion[];
    loading: boolean;
    query: string;
    activeIndex: number;
    onClose: () => void;
}

function SuggestionsBody({ suggestions, loading, query, activeIndex, onClose }: SuggestionsBodyProps) {
    if (suggestions.length > 0) {
        return (
            <>
                <div
                    role="presentation"
                    className="px-4 pt-3 pb-1.5 text-xs font-bold tracking-label text-muted uppercase"
                >
                    {__('Products')}
                </div>
                {suggestions.map((suggestion, index) => (
                    <SearchSuggestionRow
                        key={suggestion.id}
                        suggestion={suggestion}
                        active={index === activeIndex}
                        optionId={`search-opt-${index}`}
                        onNavigate={onClose}
                    />
                ))}
            </>
        );
    }

    if (loading) {
        return (
            <div className="flex items-center justify-center gap-2 px-4 py-6 text-sm text-muted">
                <Spinner />
                {__('Searching...')}
            </div>
        );
    }

    return (
        <div className="px-4 py-6 text-center text-sm text-muted">
            {__('No products found for ":query".', { query })}
        </div>
    );
}

interface SearchDropdownPanelProps {
    showSuggestions: boolean;
    suggestions: SearchSuggestion[];
    loading: boolean;
    query: string;
    trending: string[];
    activeIndex: number;
    onClose: () => void;
}

export function SearchDropdownPanel({
    showSuggestions,
    suggestions,
    loading,
    query,
    trending,
    activeIndex,
    onClose,
}: SearchDropdownPanelProps) {
    return (
        <div id="search-listbox" role="listbox" aria-label={__('Product suggestions')}>
            {showSuggestions && (
                <SuggestionsBody
                    suggestions={suggestions}
                    loading={loading}
                    query={query}
                    activeIndex={activeIndex}
                    onClose={onClose}
                />
            )}

            {trending.length > 0 && (
                <div className={cn(showSuggestions && suggestions.length > 0 && 'border-t border-line', 'px-4 py-1.5')}>
                    <div className="pt-1.5 pb-1 text-xs font-bold tracking-label text-muted uppercase">
                        {__('Trending')}
                    </div>
                    <div className="flex flex-wrap gap-2 pt-0.5 pb-3">
                        {trending.map((term) => (
                            <Link
                                key={term}
                                href={SearchController.url({ query: { query: term } })}
                                onClick={onClose}
                                className="rounded-sm border border-line-strong px-3 py-1 text-sm text-muted capitalize transition-colors hover:border-primary hover:text-primary"
                            >
                                {term}
                            </Link>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
