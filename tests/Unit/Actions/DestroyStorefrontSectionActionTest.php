<?php

declare(strict_types=1);

use App\Actions\DestroyStorefrontSectionAction;
use App\Models\StorefrontSection;

use function Pest\Laravel\assertDatabaseMissing;

covers(DestroyStorefrontSectionAction::class);

uses()->group('actions', 'storefront');

test('deletes the storefront section', function () {
    $section = StorefrontSection::factory()->create();

    $action = new DestroyStorefrontSectionAction();

    $result = $action->handle($section);

    expect($result)->toBeTrue();

    assertDatabaseMissing('storefront_sections', [
        'id' => $section->id,
    ]);
});

test('returns true when deletion succeeds', function () {
    $section = StorefrontSection::factory()->create();

    $action = new DestroyStorefrontSectionAction();

    $result = $action->handle($section);

    expect($result)->toBeTrue();
});

test('deletes section media that is no longer referenced', function () {
    $media = App\Models\Media::factory()->create();
    $section = StorefrontSection::factory()->create();
    (new App\Actions\SyncMediaAction())->handle($section, [$media->id]);

    (new DestroyStorefrontSectionAction())->handle($section);

    expect(App\Models\Media::query()->whereKey($media->id)->exists())->toBeFalse();
});
