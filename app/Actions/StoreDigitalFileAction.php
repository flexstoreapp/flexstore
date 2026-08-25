<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final readonly class StoreDigitalFileAction
{
    public function handle(UploadedFile $file): Media
    {
        $extension = mb_strtolower($file->getClientOriginalExtension());
        $filename = Str::random(40) . ($extension !== '' ? ".{$extension}" : '');
        $disk = (string) config('filesystems.default');

        $path = $file->storeAs('downloads', $filename, $disk);
        assert(is_string($path));

        return Media::query()->create([
            'type' => MediaType::File,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getClientMimeType() ?: null,
            'size' => $file->getSize() ?: 0,
            'original_filename' => $file->getClientOriginalName(),
        ]);
    }
}
