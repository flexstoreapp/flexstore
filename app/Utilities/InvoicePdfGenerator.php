<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Address\AddressFieldRules;
use App\Enums\DisplayTaxTotals;
use App\Enums\SettingGroup;
use App\Models\Media;
use App\Models\Order;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final readonly class InvoicePdfGenerator
{
    public static function response(Order $order, bool $download = false): Response
    {
        return new Response(self::generate($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline') . "; filename=\"invoice-{$order->id}.pdf\"",
        ]);
    }

    public static function generate(Order $order): string
    {
        $order->loadMissing([
            'items',
            'billingAddress',
            'shippingAddress',
            'taxDetails',
        ]);

        $storeSettings = Setting::getByGroup(SettingGroup::Store);

        $storeCountry = $storeSettings->get('store_country_code');
        $storeState = $storeSettings->get('store_state');

        $logoMediaId = Setting::query()->where('key', 'store_logo')->first()?->getRawOriginal('value');
        $logoMedia = $logoMediaId ? Media::query()->select(Media::displayColumns())->find((int) $logoMediaId) : null;

        $pdf = Pdf::loadView('invoice', [
            'order' => $order,
            'store' => [
                'name' => $storeSettings->get('store_name'),
                'email' => $storeSettings->get('store_email'),
                'phone' => $storeSettings->get('store_phone'),
                'street_address' => $storeSettings->get('store_street_address'),
                'city' => $storeSettings->get('store_city'),
                'state' => AddressFieldRules::stateName(
                    is_string($storeCountry) ? $storeCountry : null,
                    is_string($storeState) ? $storeState : null,
                ),
                'postal_code' => $storeSettings->get('store_postal_code'),
                'country' => $storeSettings->get('store_country_code'),
                'logo' => $logoMedia,
            ],
            'displayTaxTotals' => DisplayTaxTotals::from(Setting::getValue('display_tax_totals', 'single')),
        ]);

        return $pdf->output();
    }
}
