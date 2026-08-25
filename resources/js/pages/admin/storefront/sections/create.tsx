import { useEffect, useState } from 'react';

import * as StorefrontHomepageController from '@/actions/App/Http/Controllers/Admin/StorefrontHomepageController';
import { SectionForm } from '@/components/admin/storefront/section-form';
import { SectionTypePicker } from '@/components/admin/storefront/section-type-picker';
import { useStorefrontBuilder } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import type { StorefrontSectionType } from '@/types';

export default function SectionCreate() {
    const [selectedType, setSelectedType] = useState<StorefrontSectionType | null>(null);
    const { overrideBackButton } = useStorefrontBuilder();

    const handleSelectType = (type: StorefrontSectionType) => {
        setSelectedType(type);
    };

    const handleBack = () => {
        setSelectedType(null);
    };

    useEffect(() => {
        if (selectedType) {
            overrideBackButton(handleBack);
        } else {
            overrideBackButton(null);
        }

        return () => overrideBackButton(null);
    }, [selectedType, overrideBackButton]);

    return !selectedType ? (
        <SectionTypePicker onSelect={handleSelectType} />
    ) : (
        <SectionForm sectionType={selectedType} />
    );
}

SectionCreate.layout = {
    title: __('Add section'),
    backHref: StorefrontHomepageController.edit(),
};
