<?php

declare(strict_types=1);

use App\Enums\MediaType;
use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\MediaController;
use App\Http\Resources\Api\V1\Admin\AdminMediaResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\postJson;

covers(
    MediaController::class,
    AdminMediaResource::class,
);

uses()->group('api', 'admin-api');

test('an image is uploaded and returned as media', function (): void {
    Storage::fake('public');

    actingAsApiAdmin(userWithPermissions([Permission::MediaUpload]));

    postJson('/api/v1/admin/media', [
        'file' => UploadedFile::fake()->image('banner.jpg', 800, 600),
    ])
        ->assertCreated()
        ->assertJsonPath('type', MediaType::Image->value)
        ->assertJsonStructure(['id', 'url', 'thumbnail_url', 'small_thumbnail_url', 'width', 'height', 'mime_type', 'size']);

    assertDatabaseCount('media', 1);
});

test('uploading media validates the file', function (): void {
    Storage::fake('public');

    actingAsApiAdmin(userWithPermissions([Permission::MediaUpload]));

    postJson('/api/v1/admin/media', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

test('a staff member without the upload permission cannot upload media', function (): void {
    Storage::fake('public');

    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    postJson('/api/v1/admin/media', [
        'file' => UploadedFile::fake()->image('banner.jpg'),
    ])->assertForbidden();
});
