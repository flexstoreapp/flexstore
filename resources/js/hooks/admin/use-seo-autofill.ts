import { useState } from 'react';

import { getTextFromHTML } from '@/lib/utils';

const SEO_TITLE_MAX_LENGTH = 70;
const SEO_DESCRIPTION_MAX_LENGTH = 160;

interface UseSeoAutofillOptions {
    title?: string;
    description?: string;
    stripHtml?: boolean;
}

export function useSeoAutofill({ title = '', description = '', stripHtml = false }: UseSeoAutofillOptions = {}) {
    const [seoTitle, setSeoTitle] = useState(title);
    const [seoDescription, setSeoDescription] = useState(description);
    const [isTitleManuallyChanged, setIsTitleManuallyChanged] = useState(false);
    const [isDescriptionManuallyChanged, setIsDescriptionManuallyChanged] = useState(false);

    const syncTitleFromSource = (value: string) => {
        if (!isTitleManuallyChanged && value.trim()) {
            setSeoTitle(value.trim().substring(0, SEO_TITLE_MAX_LENGTH));
        }
    };

    const syncDescriptionFromSource = (value: string) => {
        if (!isDescriptionManuallyChanged && value.trim()) {
            const text = stripHtml ? getTextFromHTML(value.trim()) : value.trim();
            setSeoDescription(text.substring(0, SEO_DESCRIPTION_MAX_LENGTH));
        }
    };

    const handleSeoTitleChange = (value: string) => {
        setSeoTitle(value);
        setIsTitleManuallyChanged(true);
    };

    const handleSeoDescriptionChange = (value: string) => {
        setSeoDescription(value);
        setIsDescriptionManuallyChanged(true);
    };

    const reset = (nextTitle = '', nextDescription = '') => {
        setSeoTitle(nextTitle);
        setSeoDescription(nextDescription);
        setIsTitleManuallyChanged(false);
        setIsDescriptionManuallyChanged(false);
    };

    return {
        seoTitle,
        seoDescription,
        syncTitleFromSource,
        syncDescriptionFromSource,
        handleSeoTitleChange,
        handleSeoDescriptionChange,
        reset,
    };
}
