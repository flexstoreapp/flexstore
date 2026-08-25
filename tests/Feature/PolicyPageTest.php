<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\PrivacyPolicyController;
use App\Http\Controllers\Storefront\RefundPolicyController;
use App\Http\Controllers\Storefront\TermsOfServiceController;
use App\Models\Setting;

use function Pest\Laravel\get;

uses()->group('policies');

covers(RefundPolicyController::class, PrivacyPolicyController::class, TermsOfServiceController::class);

test('shows refund policy page with its content', function () {
    Setting::setValue('refund_policy', '<p>Our refund terms.</p>');

    get(route('policies.refund'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/policies/refund')
            ->where('content', '<p>Our refund terms.</p>'));
});

test('shows privacy policy page with its content', function () {
    Setting::setValue('privacy_policy', '<p>How we handle your data.</p>');

    get(route('policies.privacy'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/policies/privacy')
            ->where('content', '<p>How we handle your data.</p>'));
});

test('shows terms of service page with its content', function () {
    Setting::setValue('terms_of_service', '<p>The rules of engagement.</p>');

    get(route('policies.terms'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/policies/terms')
            ->where('content', '<p>The rules of engagement.</p>'));
});

test('shows policy page with null content when unset', function () {
    get(route('policies.privacy'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/policies/privacy')
            ->where('content', null));
});
