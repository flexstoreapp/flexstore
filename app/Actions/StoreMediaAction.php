<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Image\Image;
use Illuminate\Support\Facades\Image as ImageFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class StoreMediaAction
{
    private const int THUMBNAIL_SIZE = 640;

    private const int SMALL_THUMBNAIL_SIZE = 160;

    private const int THUMBNAIL_QUALITY = 88;

    public function handle(UploadedFile|string $file, bool $generateThumbnail = true, bool $preserveFormat = false): Media
    {
        $type = $this->resolveType($file);

        if ($type !== MediaType::Image) {
            assert($file instanceof UploadedFile);

            return $this->storeRawFile($file, $type);
        }

        if ($this->shouldStoreRaw($file, $preserveFormat)) {
            return $this->storeRawImage($file, $generateThumbnail);
        }

        $image = $this->readImage($file);
        $filename = $this->generateFilename();
        $path = "media/{$filename}";

        $optimized = $image->optimize(quality: 82);
        $encoded = $optimized->toBytes();
        Storage::disk('public')->put($path, $encoded);

        $width = $optimized->width();
        $height = $optimized->height();

        [$thumbnailPath, $smallThumbnailPath] = $generateThumbnail
            ? $this->storeThumbnailImages($image, $filename)
            : [null, null];

        return $this->persist(
            type: MediaType::Image,
            path: $path,
            thumbnailPath: $thumbnailPath,
            smallThumbnailPath: $smallThumbnailPath,
            mimeType: 'image/webp',
            size: mb_strlen($encoded, '8bit'),
            width: $width,
            height: $height,
            originalFilename: $file instanceof UploadedFile ? $file->getClientOriginalName() : null,
        );
    }

    private function readImage(UploadedFile|string $file): Image
    {
        return $file instanceof UploadedFile
            ? ImageFactory::fromUpload($file)
            : ImageFactory::fromBytes($file);
    }

    private function resolveType(UploadedFile|string $file): MediaType
    {
        if (! $file instanceof UploadedFile) {
            return MediaType::Image;
        }

        $mimeType = $file->getMimeType() ?? '';

        return match (true) {
            str_starts_with($mimeType, 'image/') => MediaType::Image,
            str_starts_with($mimeType, 'video/') => MediaType::Video,
            default => MediaType::File,
        };
    }

    private function storeRawFile(UploadedFile $file, MediaType $type): Media
    {
        $directory = $type === MediaType::Video ? 'videos' : 'files';
        $path = $file->store($directory, 'public');
        assert(is_string($path));

        $size = $file->getSize();

        return $this->persist(
            type: $type,
            path: $path,
            thumbnailPath: null,
            smallThumbnailPath: null,
            mimeType: $file->getMimeType(),
            size: $size === false ? null : $size,
            width: null,
            height: null,
            originalFilename: $file->getClientOriginalName(),
        );
    }

    private function shouldStoreRaw(UploadedFile|string $file, bool $preserveFormat = false): bool
    {
        if ($preserveFormat || ! config('images.extension_loaded')) {
            return true;
        }

        return $file instanceof UploadedFile && $file->getMimeType() === 'image/svg+xml';
    }

    private function storeRawImage(UploadedFile|string $file, bool $generateThumbnail = true): Media
    {
        if ($file instanceof UploadedFile) {
            $mimeType = $file->getMimeType();
            $size = $file->getSize();
            $originalFilename = $file->getClientOriginalName();

            $image = config('images.extension_loaded') && $mimeType !== 'image/svg+xml'
                ? $this->readImage($file)
                : null;

            $width = $image?->width();
            $height = $image?->height();

            $path = $file->store('media', 'public');
            assert(is_string($path));

            $thumbnailPath = null;
            $smallThumbnailPath = null;

            if ($generateThumbnail && $image instanceof Image) {
                [$thumbnailPath, $smallThumbnailPath] = $this->storeThumbnailImages($image, $this->generateFilename());
            } elseif ($generateThumbnail) {
                $thumbnailPath = $file->store('thumbnails', 'public');
                assert(is_string($thumbnailPath));
            }

            return $this->persist(
                type: MediaType::Image,
                path: $path,
                thumbnailPath: $thumbnailPath,
                smallThumbnailPath: $smallThumbnailPath,
                mimeType: $mimeType,
                size: $size === false ? null : $size,
                width: $width,
                height: $height,
                originalFilename: $originalFilename,
            );
        }

        $path = "media/{$this->generateFilename('.jpg')}";
        Storage::disk('public')->put($path, $file);

        return $this->persist(
            type: MediaType::Image,
            path: $path,
            thumbnailPath: null,
            smallThumbnailPath: null,
            mimeType: 'image/jpeg',
            size: mb_strlen($file, '8bit'),
            width: null,
            height: null,
            originalFilename: null,
        );
    }

    private function persist(
        MediaType $type,
        string $path,
        ?string $thumbnailPath,
        ?string $smallThumbnailPath,
        ?string $mimeType,
        ?int $size,
        ?int $width,
        ?int $height,
        ?string $originalFilename,
    ): Media {
        return Media::query()->create([
            'type' => $type,
            'disk' => 'public',
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'small_thumbnail_path' => $smallThumbnailPath,
            'mime_type' => $mimeType,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'original_filename' => $originalFilename,
        ]);
    }

    private function generateFilename(string $extension = '.webp'): string
    {
        return Str::random(40) . $extension;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function storeThumbnailImages(Image $image, string $filename): array
    {
        $disk = Storage::disk('public');

        $thumbnailPath = "thumbnails/{$filename}";
        $disk->put($thumbnailPath, $this->scaleDownToShorterSide($image, self::THUMBNAIL_SIZE));

        $smallThumbnailPath = "thumbnails/small-{$filename}";
        $disk->put($smallThumbnailPath, $this->scaleDownToShorterSide($image, self::SMALL_THUMBNAIL_SIZE));

        return [$thumbnailPath, $smallThumbnailPath];
    }

    /**
     * @param  int<1, max>  $size
     */
    private function scaleDownToShorterSide(Image $image, int $size): string
    {
        $scaled = $image->width() <= $image->height()
            ? $image->scale(width: $size)
            : $image->scale(height: $size);

        return $scaled->optimize(quality: self::THUMBNAIL_QUALITY)->toBytes();
    }
}
