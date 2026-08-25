import * as StorefrontHomepageController from '@/actions/App/Http/Controllers/Admin/StorefrontHomepageController';
import { SectionForm } from '@/components/admin/storefront/section-form';
import { sectionTypes } from '@/components/admin/storefront/section-item';
import { __ } from '@/lib/i18n';
import type { StorefrontSection } from '@/types';

interface SectionEditProps {
    section: StorefrontSection;
    [key: string]: unknown;
}

export default function SectionEdit({ section }: SectionEditProps) {
    return <SectionForm section={section} sectionType={section.type} />;
}

SectionEdit.layout = ({ section }: SectionEditProps) => ({
    title: __('Edit section'),
    subtitle: `(${sectionTypes[section.type]})`,
    backHref: StorefrontHomepageController.edit(),
});
