import { useEffect, useState } from 'react';

import SearchSuggestionController from '@/actions/App/Http/Controllers/Storefront/SearchSuggestionController';
import type { SearchSuggestion } from '@/types';

const MIN_QUERY_LENGTH = 2;
const DEBOUNCE_MS = 200;

interface LoadedResult {
    query: string;
    suggestions: SearchSuggestion[];
}

export function useSearchSuggestions(query: string) {
    const [result, setResult] = useState<LoadedResult>({ query: '', suggestions: [] });

    const trimmed = query.trim();
    const eligible = trimmed.length >= MIN_QUERY_LENGTH;
    const loading = eligible && result.query !== trimmed;
    const suggestions = result.query === trimmed ? result.suggestions : [];

    useEffect(() => {
        if (!eligible) {
            return;
        }

        const controller = new AbortController();

        const timer = setTimeout(() => {
            fetch(SearchSuggestionController.url({ query: { query: trimmed } }), {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            })
                .then((response) => response.json())
                .then((data: { suggestions: SearchSuggestion[] }) => {
                    setResult({ query: trimmed, suggestions: data.suggestions ?? [] });
                })
                .catch((error: unknown) => {
                    if (error instanceof Error && error.name === 'AbortError') {
                        return;
                    }

                    setResult({ query: trimmed, suggestions: [] });
                });
        }, DEBOUNCE_MS);

        return () => {
            controller.abort();
            clearTimeout(timer);
        };
    }, [trimmed, eligible]);

    return { suggestions, loading };
}
