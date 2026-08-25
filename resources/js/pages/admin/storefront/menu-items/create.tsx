import * as StorefrontFooterController from '@/actions/App/Http/Controllers/Admin/StorefrontFooterController';
import * as StorefrontHeaderController from '@/actions/App/Http/Controllers/Admin/StorefrontHeaderController';
import { MenuItemForm } from '@/components/admin/storefront/menu-item-form';
import { __ } from '@/lib/i18n';

interface MenuItemCreateProps {
    location: 'header' | 'footer';
    [key: string]: unknown;
}

export default function MenuItemCreate({ location }: MenuItemCreateProps) {
    return <MenuItemForm location={location} />;
}

MenuItemCreate.layout = ({ location }: MenuItemCreateProps) => ({
    title: location === 'footer' ? __('Add link') : __('Add menu item'),
    backHref: location === 'footer' ? StorefrontFooterController.edit() : StorefrontHeaderController.edit(),
});
