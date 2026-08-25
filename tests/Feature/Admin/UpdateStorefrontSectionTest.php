<?php

declare(strict_types=1);

use App\Actions\UpdateStorefrontSectionAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontSectionController;
use App\Http\Requests\Admin\UpdateStorefrontSectionRequest;
use App\Models\StorefrontSection;
use App\Queries\ResolveSectionSettingsQuery;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;

covers([
    StorefrontSectionController::class,
    UpdateStorefrontSectionRequest::class,
    UpdateStorefrontSectionAction::class,
    ResolveSectionSettingsQuery::class,
]);

uses()->group('storefront');

test('displays edit section page', function () {
    $section = StorefrontSection::factory()->create();

    $response = actingAsSuperAdmin()->get(route('admin.storefront.homepage.sections.edit', $section));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/storefront/sections/edit')
            ->has('section')
            ->where('section.id', $section->id)
            ->where('section.title', castAsTranslatableArray($section->title)));
});

test('updates a storefront section', function () {
    $section = StorefrontSection::factory()->create([
        'title' => 'Old Title',
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.homepage.sections.update', $section), [
        'title' => 'New Title',
        'is_active' => false,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('storefront_sections', [
        'id' => $section->id,
        'title' => castAsTranslatableJson('New Title'),
        'is_active' => false,
    ]);
});

test('can toggle section visibility', function () {
    $section = StorefrontSection::factory()->active()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.homepage.sections.update', $section), [
        'is_active' => false,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect($section->fresh()->is_active)->toBeFalse();
});

test('validates section type enum when provided', function () {
    $section = StorefrontSection::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.homepage.sections.update', $section), [
        'type' => 'invalid_type',
    ]);

    $response->assertRedirect()
        ->assertInvalid('type');
});

test('validates title maximum length on update', function () {
    $section = StorefrontSection::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.homepage.sections.update', $section), [
        'title' => str_repeat('a', 256),
    ]);

    $response->assertRedirect()
        ->assertInvalid('title');
});

test('requires authentication', function () {
    $section = StorefrontSection::factory()->create();

    $response = patch(route('admin.storefront.homepage.sections.update', $section), [
        'title' => 'Updated Title',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $section = StorefrontSection::factory()->create(['title' => 'Original Title']);

    $response = actingAsAdmin()->patch(route('admin.storefront.homepage.sections.update', $section), [
        'title' => 'Updated Title',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect($section->fresh()->title)->toBe('Updated Title');

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->patch(route('admin.storefront.homepage.sections.update', $section), [
        'title' => 'Another Update',
    ]);

    $response->assertForbidden();
});
