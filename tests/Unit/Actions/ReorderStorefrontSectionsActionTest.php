<?php

declare(strict_types=1);

use App\Actions\ReorderStorefrontSectionsAction;
use App\Models\StorefrontSection;

use function Pest\Laravel\assertDatabaseHas;

covers(ReorderStorefrontSectionsAction::class);

uses()->group('actions', 'storefront');

test('reorders sections according to provided ids', function () {
    $section1 = StorefrontSection::factory()->create(['sort_order' => 0]);
    $section2 = StorefrontSection::factory()->create(['sort_order' => 1]);
    $section3 = StorefrontSection::factory()->create(['sort_order' => 2]);

    $action = new ReorderStorefrontSectionsAction();

    $action->handle([$section3->id, $section1->id, $section2->id]);

    assertDatabaseHas('storefront_sections', [
        'id' => $section3->id,
        'sort_order' => 0,
    ]);
    assertDatabaseHas('storefront_sections', [
        'id' => $section1->id,
        'sort_order' => 1,
    ]);
    assertDatabaseHas('storefront_sections', [
        'id' => $section2->id,
        'sort_order' => 2,
    ]);
});

test('handles different order combinations', function () {
    $section1 = StorefrontSection::factory()->create(['sort_order' => 0]);
    $section2 = StorefrontSection::factory()->create(['sort_order' => 1]);
    $section3 = StorefrontSection::factory()->create(['sort_order' => 2]);
    $section4 = StorefrontSection::factory()->create(['sort_order' => 3]);

    $action = new ReorderStorefrontSectionsAction();

    $action->handle([$section2->id, $section4->id, $section1->id, $section3->id]);

    assertDatabaseHas('storefront_sections', [
        'id' => $section2->id,
        'sort_order' => 0,
    ]);
    assertDatabaseHas('storefront_sections', [
        'id' => $section4->id,
        'sort_order' => 1,
    ]);
    assertDatabaseHas('storefront_sections', [
        'id' => $section1->id,
        'sort_order' => 2,
    ]);
    assertDatabaseHas('storefront_sections', [
        'id' => $section3->id,
        'sort_order' => 3,
    ]);
});

test('handles single section reordering', function () {
    $section = StorefrontSection::factory()->create(['sort_order' => 5]);

    $action = new ReorderStorefrontSectionsAction();

    $action->handle([$section->id]);

    assertDatabaseHas('storefront_sections', [
        'id' => $section->id,
        'sort_order' => 0,
    ]);
});

test('handles empty array gracefully', function () {
    $section1 = StorefrontSection::factory()->create(['sort_order' => 0]);
    $section2 = StorefrontSection::factory()->create(['sort_order' => 1]);

    $action = new ReorderStorefrontSectionsAction();

    $action->handle([]);

    assertDatabaseHas('storefront_sections', [
        'id' => $section1->id,
        'sort_order' => 0,
    ]);
    assertDatabaseHas('storefront_sections', [
        'id' => $section2->id,
        'sort_order' => 1,
    ]);
});
