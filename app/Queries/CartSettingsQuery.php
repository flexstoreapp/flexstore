<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Setting;

final readonly class CartSettingsQuery
{
    /**
     * @return array{show_cross_sells: bool}
     */
    public function execute(): array
    {
        return [
            'show_cross_sells' => (bool) Setting::getValue('storefront_cart_show_cross_sells', true),
        ];
    }
}
