import * as AnnouncementController from '@/actions/App/Http/Controllers/Admin/AnnouncementController';
import { AnnouncementForm } from '@/components/admin/storefront/announcement-form';
import { __ } from '@/lib/i18n';

export default function AnnouncementCreate() {
    return <AnnouncementForm />;
}

AnnouncementCreate.layout = {
    title: __('Add announcement'),
    backHref: AnnouncementController.index(),
};
