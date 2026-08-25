import * as AnnouncementController from '@/actions/App/Http/Controllers/Admin/AnnouncementController';
import { AnnouncementForm } from '@/components/admin/storefront/announcement-form';
import { __ } from '@/lib/i18n';
import type { Announcement } from '@/types';

export default function AnnouncementEdit({ announcement }: { announcement: Announcement }) {
    return <AnnouncementForm announcement={announcement} />;
}

AnnouncementEdit.layout = {
    title: __('Edit announcement'),
    backHref: AnnouncementController.index(),
};
