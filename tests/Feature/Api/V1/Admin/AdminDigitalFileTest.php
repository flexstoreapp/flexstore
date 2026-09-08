<?php

declare(strict_types=1);

use App\Actions\StoreDigitalFileAction;
use App\Enums\MediaType;
use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\DigitalFileController;
use App\Http\Requests\Admin\StoreDigitalFileRequest;
use App\Http\Resources\Api\V1\Admin\DigitalFileResource;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

covers(
    DigitalFileController::class,
    StoreDigitalFileRequest::class,
    DigitalFileResource::class,
    StoreDigitalFileAction::class,
);

uses()->group('api', 'admin-api');

beforeEach(function (): void {
    Storage::fake();
});

test('a digital file is uploaded', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsManage]));

    post('/api/v1/admin/product-downloads', [
        'file' => UploadedFile::fake()->create('manual.pdf', 20, 'application/pdf'),
    ])
        ->assertCreated()
        ->assertJsonPath('name', 'manual.pdf')
        ->assertJsonPath('original_filename', 'manual.pdf');

    assertDatabaseHas(Media::class, [
        'type' => MediaType::File->value,
        'original_filename' => 'manual.pdf',
    ]);
});

test('an unsupported file extension is rejected', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsManage]));

    post('/api/v1/admin/product-downloads', [
        'file' => UploadedFile::fake()->create('script.php', 5),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

test('uploading a digital file requires the products manage permission', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsView]));

    post('/api/v1/admin/product-downloads', [
        'file' => UploadedFile::fake()->create('manual.pdf', 20, 'application/pdf'),
    ])->assertForbidden();
});
