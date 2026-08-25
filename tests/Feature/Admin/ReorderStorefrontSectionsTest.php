<?php

declare(strict_types=1);

use App\Actions\ReorderStorefrontSectionsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontSectionReorderController;
use App\Http\Requests\Admin\ReorderStorefrontSectionsRequest;
use App\Models\StorefrontSection;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;

covers(StorefrontSectionReorderController::class, ReorderStorefrontSectionsRequest::class, ReorderStorefrontSectionsAction::class);

uses()->group('storefront');

test('reorders storefront sections', function () {
    $section1 = StorefrontSection::factory()->create(['title' => 'Section 1', 'sort_order' => 0]);
    $section2 = StorefrontSection::factory()->create(['title' => 'Section 2', 'sort_order' => 1]);
    $section3 = StorefrontSection::factory()->create(['title' => 'Section 3', 'sort_order' => 2]);

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.homepage.sections.reorder'), [
        'ordered_ids' => [$section3->id, $section1->id, $section2->id],
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

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

test('validates ordered_ids are required', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.homepage.sections.reorder'), []);

    $response->assertRedirectBack()
        ->assertInvalid('ordered_ids');
});

test('validates ordered_ids is an array', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.homepage.sections.reorder'), [
        'ordered_ids' => 'not-an-array',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ordered_ids');
});

test('validates ordered_ids has minimum one item', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.homepage.sections.reorder'), [
        'ordered_ids' => [],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ordered_ids');
});

test('validates ordered_ids contain existing section ids', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.homepage.sections.reorder'), [
        'ordered_ids' => [99999],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ordered_ids.0');
});

test('requires authentication', function () {
    $section1 = StorefrontSection::factory()->create(['sort_order' => 0]);
    $section2 = StorefrontSection::factory()->create(['sort_order' => 1]);

    $response = patch(route('admin.storefront.homepage.sections.reorder'), [
        'ordered_ids' => [$section2->id, $section1->id],
    ]);

    $response->assertRedirect(route('admin.login'));

    // Verify sections weren't reordered
    assertDatabaseHas('storefront_sections', [
        'id' => $section1->id,
        'sort_order' => 0,
    ]);
    assertDatabaseHas('storefront_sections', [
        'id' => $section2->id,
        'sort_order' => 1,
    ]);
});

test('requires storefront.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $section1 = StorefrontSection::factory()->create(['sort_order' => 0]);
    $section2 = StorefrontSection::factory()->create(['sort_order' => 1]);

    $response = actingAsAdmin()->patch(route('admin.storefront.homepage.sections.reorder'), [
        'ordered_ids' => [$section2->id, $section1->id],
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('storefront_sections', [
        'id' => $section2->id,
        'sort_order' => 0,
    ]);
    assertDatabaseHas('storefront_sections', [
        'id' => $section1->id,
        'sort_order' => 1,
    ]);

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $section3 = StorefrontSection::factory()->create(['sort_order' => 2]);
    $section4 = StorefrontSection::factory()->create(['sort_order' => 3]);

    $response = actingAsAdmin()->patch(route('admin.storefront.homepage.sections.reorder'), [
        'ordered_ids' => [$section4->id, $section3->id],
    ]);

    $response->assertForbidden();

    // Verify sections weren't reordered
    assertDatabaseHas('storefront_sections', [
        'id' => $section3->id,
        'sort_order' => 2,
    ]);
    assertDatabaseHas('storefront_sections', [
        'id' => $section4->id,
        'sort_order' => 3,
    ]);
});
