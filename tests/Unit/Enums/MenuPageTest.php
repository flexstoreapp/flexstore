<?php

declare(strict_types=1);

use App\Enums\MenuPage;

covers(MenuPage::class);

uses()->group('enums');

test('url resolves for every case', function () {
    foreach (MenuPage::cases() as $case) {
        expect($case->url())->toBeString()->not->toBe('');
    }
});

test('home url matches home route', function () {
    expect(MenuPage::Home->url())->toBe(route('home'));
});

test('products url matches shop index route', function () {
    expect(MenuPage::Products->url())->toBe(route('shop.index'));
});

test('categories url matches categories index route', function () {
    expect(MenuPage::Categories->url())->toBe(route('categories.index'));
});

test('brands url matches brands index route', function () {
    expect(MenuPage::Brands->url())->toBe(route('brands.index'));
});

test('wishlist url matches wishlist show route', function () {
    expect(MenuPage::Wishlist->url())->toBe(route('wishlist.show'));
});

test('order tracking url matches orders.track route', function () {
    expect(MenuPage::OrderTracking->url())->toBe(route('orders.track'));
});

test('refund policy url matches policies.refund route', function () {
    expect(MenuPage::RefundPolicy->url())->toBe(route('policies.refund'));
});

test('privacy policy url matches policies.privacy route', function () {
    expect(MenuPage::PrivacyPolicy->url())->toBe(route('policies.privacy'));
});

test('terms of service url matches policies.terms route', function () {
    expect(MenuPage::TermsOfService->url())->toBe(route('policies.terms'));
});
