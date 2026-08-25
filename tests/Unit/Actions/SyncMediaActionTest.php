<?php

declare(strict_types=1);

use App\Actions\SyncMediaAction;
use App\Models\Brand;
use App\Models\Media;
use App\Models\Mediable;

covers(SyncMediaAction::class);

uses()->group('actions', 'media');

test('attaches media in the given order', function () {
    $brand = Brand::factory()->create();
    $media = Media::factory()->count(3)->create();

    (new SyncMediaAction())->handle($brand, $media->pluck('id')->all());

    $rows = Mediable::query()
        ->where('mediable_type', $brand->getMorphClass())
        ->where('mediable_id', (string) $brand->getKey())
        ->orderBy('sort_order')
        ->get();

    expect($rows)->toHaveCount(3)
        ->and($rows->pluck('media_id')->all())->toBe($media->pluck('id')->all())
        ->and($rows->pluck('sort_order')->all())->toBe([0, 1, 2]);
});

test('detaches media no longer present and reorders the rest', function () {
    $brand = Brand::factory()->create();
    $media = Media::factory()->count(3)->create();
    $action = new SyncMediaAction();

    $action->handle($brand, $media->pluck('id')->all());
    $action->handle($brand, [$media[2]->id, $media[0]->id]);

    $rows = Mediable::query()
        ->where('mediable_type', $brand->getMorphClass())
        ->where('mediable_id', (string) $brand->getKey())
        ->orderBy('sort_order')
        ->get();

    expect($rows->pluck('media_id')->all())->toBe([$media[2]->id, $media[0]->id])
        ->and($rows->pluck('sort_order')->all())->toBe([0, 1]);
});

test('an empty list detaches everything', function () {
    $brand = Brand::factory()->create();
    $media = Media::factory()->count(2)->create();
    $action = new SyncMediaAction();

    $action->handle($brand, $media->pluck('id')->all());
    $action->handle($brand, []);

    expect(Mediable::query()->count())->toBe(0);
});

test('does not touch attachments on other models', function () {
    $brandA = Brand::factory()->create();
    $brandB = Brand::factory()->create();
    $mediaA = Media::factory()->create();
    $mediaB = Media::factory()->create();
    $action = new SyncMediaAction();

    $action->handle($brandA, [$mediaA->id]);
    $action->handle($brandB, [$mediaB->id]);

    expect(Mediable::query()->where('mediable_id', (string) $brandA->getKey())->count())->toBe(1)
        ->and(Mediable::query()->where('mediable_id', (string) $brandB->getKey())->count())->toBe(1);
});
