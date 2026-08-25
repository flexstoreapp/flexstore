import {
    BracesIcon,
    CodeIcon,
    FileTextIcon,
    HomeIcon,
    LayoutGridIcon,
    MegaphoneIcon,
    NewspaperIcon,
    PackageSearchIcon,
    PaletteIcon,
    PanelBottomIcon,
    PanelTopIcon,
} from 'lucide-react';

import * as AnnouncementController from '@/actions/App/Http/Controllers/Admin/AnnouncementController';
import * as DashboardController from '@/actions/App/Http/Controllers/Admin/DashboardController';
import * as StorefrontCustomCssController from '@/actions/App/Http/Controllers/Admin/StorefrontCustomCssController';
import * as StorefrontCustomJsController from '@/actions/App/Http/Controllers/Admin/StorefrontCustomJsController';
import * as StorefrontFooterController from '@/actions/App/Http/Controllers/Admin/StorefrontFooterController';
import * as StorefrontHeaderController from '@/actions/App/Http/Controllers/Admin/StorefrontHeaderController';
import * as StorefrontHomepageController from '@/actions/App/Http/Controllers/Admin/StorefrontHomepageController';
import * as StorefrontProductDetailController from '@/actions/App/Http/Controllers/Admin/StorefrontProductDetailController';
import * as StorefrontProductListController from '@/actions/App/Http/Controllers/Admin/StorefrontProductListController';
import * as StorefrontThemeController from '@/actions/App/Http/Controllers/Admin/StorefrontThemeController';
import { SidebarOption } from '@/components/admin/storefront/sidebar-option';
import { __ } from '@/lib/i18n';

export default function StorefrontBuilder() {
    return (
        <div className="space-y-1 p-2">
            <SidebarOption
                title={__('Homepage')}
                description={__('Manage your homepage layout')}
                icon={<HomeIcon className="size-5" />}
                href={StorefrontHomepageController.edit()}
            />
            <SidebarOption
                title={__('Header')}
                description={__('Customize navigation and branding')}
                icon={<PanelTopIcon className="size-5" />}
                href={StorefrontHeaderController.edit()}
            />
            <SidebarOption
                title={__('Footer')}
                description={__('Customize footer links and content')}
                icon={<PanelBottomIcon className="size-5" />}
                href={StorefrontFooterController.edit()}
            />
            <SidebarOption
                title={__('Announcements')}
                description={__('Rotating messages in the top bar')}
                icon={<MegaphoneIcon className="size-5" />}
                href={AnnouncementController.index()}
            />
            <SidebarOption
                title={__('Product list')}
                description={__('Configure filters and display')}
                icon={<LayoutGridIcon className="size-5" />}
                href={StorefrontProductListController.edit()}
            />
            <SidebarOption
                title={__('Product detail')}
                description={__('Configure product page sections')}
                icon={<PackageSearchIcon className="size-5" />}
                href={StorefrontProductDetailController.edit()}
            />
            <SidebarOption
                title={__('Blog list')}
                description={__('Configure how posts are listed')}
                icon={<NewspaperIcon className="size-5" />}
                pro
            />
            <SidebarOption
                title={__('Blog detail')}
                description={__('Configure post page sections')}
                icon={<FileTextIcon className="size-5" />}
                pro
            />
            <SidebarOption
                title={__('Theme')}
                description={__('Customize colors and appearance')}
                icon={<PaletteIcon className="size-5" />}
                href={StorefrontThemeController.edit()}
            />
            <SidebarOption
                title={__('Custom CSS')}
                description={__('Add custom styles')}
                icon={<CodeIcon className="size-5" />}
                href={StorefrontCustomCssController.edit()}
            />
            <SidebarOption
                title={__('Custom JavaScript')}
                description={__('Add custom scripts')}
                icon={<BracesIcon className="size-5" />}
                href={StorefrontCustomJsController.edit()}
            />
        </div>
    );
}

StorefrontBuilder.layout = { backHref: DashboardController.index() };
