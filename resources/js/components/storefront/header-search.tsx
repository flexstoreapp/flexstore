import { router, usePage } from '@inertiajs/react';
import { SearchIcon, XIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import * as ProductController from '@/actions/App/Http/Controllers/Storefront/ProductController';
import SearchController from '@/actions/App/Http/Controllers/Storefront/SearchController';
import { useSearchDropdown } from '@/hooks/storefront/use-search-dropdown';
import { useSearchSuggestions } from '@/hooks/storefront/use-search-suggestions';
import { analytics } from '@/lib/analytics';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { StorefrontSharedData } from '@/types';

import { SearchDropdownPanel } from './search-dropdown-panel';

function searchTermFromUrl(url: string): string {
    const [path, queryString] = url.split('?');

    if (path !== SearchController.url()) {
        return '';
    }

    return new URLSearchParams(queryString ?? '').get('query') ?? '';
}

export function HeaderSearch({ className, active = false }: { className?: string; active?: boolean }) {
    const page = usePage<StorefrontSharedData>();
    const { storefront } = page.props;
    const [value, setValue] = useState(() => searchTermFromUrl(page.url));
    const wrapperRef = useRef<HTMLDivElement | null>(null);
    const inputRef = useRef<HTMLInputElement | null>(null);

    const { suggestions, loading } = useSearchSuggestions(value);
    const trending = storefront.trendingSearches;
    const showSuggestions = value.trim().length >= 2;
    const hasContent = showSuggestions || trending.length > 0;

    const { open, setOpen, activeIndex, setActiveIndex, handleKeyDown } = useSearchDropdown(
        showSuggestions ? suggestions : [],
        (suggestion) => router.visit(ProductController.show(suggestion.url_handle).url),
    );

    const visible = open && hasContent;

    useEffect(() => {
        if (active) {
            inputRef.current?.focus();
        }
    }, [active]);

    useEffect(() => router.on('navigate', (event) => setValue(searchTermFromUrl(event.detail.page.url))), []);

    const searchFor = (term: string) => {
        setOpen(false);
        if (term) {
            analytics.search(term);
        }
        router.get(SearchController.url(), term ? { query: term } : {});
    };

    const clear = () => {
        setValue('');
        setActiveIndex(-1);
        setOpen(true);
        inputRef.current?.focus();
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        inputRef.current?.blur();
        searchFor(value);
    };

    return (
        <div ref={wrapperRef} className={cn('relative z-40', className)}>
            <form
                role="search"
                onSubmit={submit}
                onBlur={(event) => {
                    if (!wrapperRef.current?.contains(event.relatedTarget as Node)) {
                        setOpen(false);
                    }
                }}
                className="flex h-12 items-stretch overflow-hidden rounded-md border border-line bg-surface-2 transition has-[input:focus-visible]:border-primary has-[input:focus-visible]:ring-1 has-[input:focus-visible]:ring-primary"
            >
                <input
                    ref={inputRef}
                    type="search"
                    name="query"
                    placeholder={__('Search for products...')}
                    aria-label={__('Search for products')}
                    role="combobox"
                    aria-expanded={visible}
                    aria-controls="search-listbox"
                    aria-autocomplete="list"
                    aria-haspopup="listbox"
                    aria-activedescendant={activeIndex >= 0 ? `search-opt-${activeIndex}` : undefined}
                    autoComplete="off"
                    value={value}
                    onChange={(event) => {
                        setValue(event.target.value);
                        setActiveIndex(-1);
                        setOpen(true);
                    }}
                    onFocus={() => setOpen(true)}
                    onKeyDown={handleKeyDown}
                    className="w-full min-w-0 flex-1 bg-transparent ps-5 pe-2 placeholder:text-muted focus-visible:outline-hidden"
                />
                {value && (
                    <button
                        type="button"
                        aria-label={__('Clear search')}
                        onClick={clear}
                        className="flex items-center px-3 text-muted transition-colors hover:text-ink"
                    >
                        <XIcon size={18} strokeWidth={2} aria-hidden="true" />
                    </button>
                )}
                <button
                    type="submit"
                    aria-label={__('Search')}
                    className="my-1 me-1 flex w-12 items-center justify-center rounded-sm bg-primary text-white ring-offset-surface-2 transition hover:bg-primary-hover focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-hidden"
                >
                    <SearchIcon size={18} strokeWidth={2} aria-hidden="true" />
                </button>
            </form>

            <div
                className={cn(
                    'absolute start-0 end-0 top-full mt-2 overflow-hidden rounded-md border border-line bg-surface shadow-md transition-[opacity,translate,visibility] duration-(--duration-base) ease-out-quart',
                    visible ? 'visible translate-y-0 opacity-100' : 'invisible -translate-y-2 opacity-0',
                )}
            >
                <SearchDropdownPanel
                    showSuggestions={showSuggestions}
                    suggestions={suggestions}
                    loading={loading}
                    query={value.trim()}
                    trending={trending}
                    activeIndex={activeIndex}
                    onClose={() => setOpen(false)}
                />
            </div>
        </div>
    );
}
