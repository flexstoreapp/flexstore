<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Enums\SettingGroup;
use App\Http\Requests\Admin\UpdateStorefrontFooterRequest;
use App\Models\Setting;
use App\Queries\AdminFooterMenuItemListQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StorefrontFooterController
{
    public function edit(AdminFooterMenuItemListQuery $menuItemQuery): Response
    {
        $storefrontSettings = Setting::getByGroup(SettingGroup::Storefront);

        return Inertia::render('admin/storefront/footer', [
            'menuItems' => $menuItemQuery->execute(),
            'settings' => [
                'show_copyright' => $storefrontSettings->get('storefront_footer_show_copyright', true),
                'show_social_links' => $storefrontSettings->get('storefront_footer_show_social_links', true),
                'show_payment_badges' => $storefrontSettings->get('storefront_footer_show_payment_badges', true),
                'payment_method_preset' => $storefrontSettings->get('storefront_footer_payment_method_preset', 'all'),
                'payment_methods' => $storefrontSettings->get('storefront_footer_payment_methods', ['visa', 'mastercard', 'amex', 'paypal', 'apple_pay', 'google_pay']),
                'copyright_text' => $storefrontSettings->get('storefront_footer_copyright_text'),
            ],
        ]);
    }

    public function update(UpdateStorefrontFooterRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
