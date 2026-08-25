<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DigitalFileController;
use App\Http\Requests\Admin\StoreDigitalFileRequest;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

covers(DigitalFileController::class, StoreDigitalFileRequest::class);

uses()->group('product', 'admin');

test('stores an uploaded digital file and returns its metadata as json', function () {
    Storage::fake();

    $file = UploadedFile::fake()->create('manual.zip', 100, 'application/zip');

    $response = actingAsSuperAdmin()->post(route('admin.product-downloads.store'), [
        'file' => $file,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['id', 'type', 'mime_type', 'size', 'original_filename', 'url', 'name'])
        ->assertJson([
            'original_filename' => 'manual.zip',
            'size' => $file->getSize(),
            'mime_type' => 'application/zip',
            'name' => 'manual.zip',
        ]);

    $media = Media::query()->findOrFail($response->json('id'));

    expect($media->path)->toStartWith('downloads/');
    Storage::disk()->assertExists($media->path);
});

test('rejects a file with a disallowed extension', function () {
    Storage::fake();

    $response = actingAsSuperAdmin()->post(route('admin.product-downloads.store'), [
        'file' => UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream'),
    ]);

    $response->assertSessionHasErrors('file');
});

test('requires a file', function () {
    $response = actingAsSuperAdmin()->post(route('admin.product-downloads.store'), []);

    $response->assertSessionHasErrors('file');
});

test('forbids users without the products manage permission', function () {
    Storage::fake();

    $user = userWithPermissions([]);

    $response = actingAs($user)->post(route('admin.product-downloads.store'), [
        'file' => UploadedFile::fake()->create('manual.zip', 100, 'application/zip'),
    ]);

    $response->assertForbidden();
});
