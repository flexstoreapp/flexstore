<?php

declare(strict_types=1);

namespace App\Enums;

enum MenuPage: string
{
    case Home = 'home';
    case Products = 'products';
    case Categories = 'categories';
    case Brands = 'brands';
    case Wishlist = 'wishlist';
    case OrderTracking = 'order_tracking';
    case RefundPolicy = 'refund_policy';
    case PrivacyPolicy = 'privacy_policy';
    case TermsOfService = 'terms_of_service';

    public function url(): string
    {
        return match ($this) {
            self::Home => route('home'),
            self::Products => route('shop.index'),
            self::Categories => route('categories.index'),
            self::Brands => route('brands.index'),
            self::Wishlist => route('wishlist.show'),
            self::OrderTracking => route('orders.track'),
            self::RefundPolicy => route('policies.refund'),
            self::PrivacyPolicy => route('policies.privacy'),
            self::TermsOfService => route('policies.terms'),
        };
    }
}
