<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\StoreMediaAction;
use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

#[Signature('demo:import-media
    {--source= : Directory holding the demo image folders}
    {--build : Run every file through the media pipeline and rebuild the processed cache}
    {--prune : Delete stored files that no media row references}')]
#[Description('Install demo-data images from the prebuilt processed cache, falling back to the media pipeline.')]
final class ImportDemoMediaCommand extends Command
{
    private const string PLACEHOLDER_PREFIX = 'assets/';

    private const array MANAGED_DIRECTORIES = ['media', 'thumbnails', 'files', 'videos'];

    private const string PROCESSED_DIRECTORY = 'cache';

    private const string MANIFEST_FILE = 'cache/manifest.json';

    public function handle(StoreMediaAction $storeMedia): int
    {
        $source = mb_rtrim($this->option('source') ?? resource_path('demo'), '/');

        if (! is_dir($source)) {
            $this->error("Demo media source directory not found: {$source}");

            return self::FAILURE;
        }

        $pending = Media::query()
            ->whereLike('path', self::PLACEHOLDER_PREFIX . '%')
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No demo media to import.');
            $this->prune();

            return self::SUCCESS;
        }

        $build = $this->option('build') === true;
        $manifest = $build ? [] : $this->readManifest($source);

        if ($build) {
            $this->resetProcessedCache($source);
        }

        $copied = 0;
        $processed = 0;
        $missing = [];
        $failed = [];
        $built = [];

        $bar = $this->output->createProgressBar($pending->count());
        $bar->start();

        foreach ($pending as $media) {
            $relativePath = $media->path;
            assert(is_string($relativePath));

            $absolutePath = "{$source}/{$relativePath}";

            if (! is_file($absolutePath)) {
                $missing[] = $relativePath;
                $bar->advance();

                continue;
            }

            try {
                $entry = $manifest[$relativePath] ?? null;

                if ($entry !== null && $this->restore($media, $absolutePath, $entry, $source)) {
                    $copied++;
                } else {
                    $this->import($storeMedia, $media, $absolutePath);
                    $processed++;

                    if ($build) {
                        $this->stabilize($media, $relativePath);
                        $built[$relativePath] = $this->export($media, $source);
                    }
                }
            } catch (Throwable $e) {
                $failed[] = "{$relativePath}: {$e->getMessage()}";
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Copied {$copied} media file(s) from the processed cache.");
        $this->info("Imported {$processed} media file(s) through the pipeline.");

        if ($build) {
            $this->writeManifest($source, $built);
            $this->info(sprintf('Wrote %d entries to %s.', count($built), self::MANIFEST_FILE));
        }

        foreach ($missing as $path) {
            $this->warn("Missing source file: {$path}");
        }

        foreach ($failed as $failure) {
            $this->error("Failed to import {$failure}");
        }

        $this->prune();

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function readManifest(string $source): array
    {
        $path = "{$source}/" . self::MANIFEST_FILE;

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, array<string, mixed>>  $entries
     */
    private function writeManifest(string $source, array $entries): void
    {
        ksort($entries);

        $path = "{$source}/" . self::MANIFEST_FILE;
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function restore(Media $media, string $absolutePath, array $entry, string $source): bool
    {
        $path = $entry['path'] ?? null;

        if (! is_string($path)) {
            return false;
        }

        $thumbnailPath = $entry['thumbnail_path'] ?? null;
        $smallThumbnailPath = $entry['small_thumbnail_path'] ?? null;

        $derivatives = [];

        foreach ([$thumbnailPath, $smallThumbnailPath] as $derivative) {
            if (! is_string($derivative)) {
                continue;
            }

            $derivativeSource = "{$source}/" . self::PROCESSED_DIRECTORY . "/{$derivative}";

            if (! is_file($derivativeSource)) {
                return false;
            }

            $derivatives[$derivative] = $derivativeSource;
        }

        $disk = Storage::disk('public');
        $disk->put($path, (string) file_get_contents($absolutePath));

        foreach ($derivatives as $derivative => $derivativeSource) {
            $disk->put($derivative, (string) file_get_contents($derivativeSource));
        }

        $media->forceFill([
            'type' => $entry['type'] ?? MediaType::Image->value,
            'disk' => 'public',
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'small_thumbnail_path' => $smallThumbnailPath,
            'mime_type' => $entry['mime_type'] ?? null,
            'size' => $entry['size'] ?? null,
            'width' => $entry['width'] ?? null,
            'height' => $entry['height'] ?? null,
            'original_filename' => $entry['original_filename'] ?? null,
        ])->save();

        return true;
    }

    private function resetProcessedCache(string $source): void
    {
        $directory = "{$source}/" . self::PROCESSED_DIRECTORY;

        if (! is_dir($directory)) {
            return;
        }

        foreach (File::directories($directory) as $subdirectory) {
            File::deleteDirectory($subdirectory);
        }
    }

    private function stabilize(Media $media, string $relativePath): void
    {
        $disk = Storage::disk('public');
        $renamed = [];

        foreach (['path', 'thumbnail_path', 'small_thumbnail_path'] as $attribute) {
            $current = $media->{$attribute};

            if (! is_string($current)) {
                continue;
            }

            $target = $this->stablePath($current, $relativePath);

            if ($target === $current) {
                continue;
            }

            $disk->delete($target);
            $disk->move($current, $target);

            $renamed[$attribute] = $target;
        }

        if ($renamed !== []) {
            $media->forceFill($renamed)->save();
        }
    }

    private function stablePath(string $current, string $relativePath): string
    {
        $filename = basename($current);
        $prefix = str_starts_with($filename, 'small-') ? 'small-' : '';
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return dirname($current) . '/' . $prefix . hash('xxh128', $relativePath) . ($extension === '' ? '' : ".{$extension}");
    }

    /**
     * @return array<string, mixed>
     */
    private function export(Media $media, string $source): array
    {
        $thumbnailPath = $media->thumbnail_path;
        $smallThumbnailPath = $media->small_thumbnail_path;

        foreach ([$thumbnailPath, $smallThumbnailPath] as $derivative) {
            if (! is_string($derivative)) {
                continue;
            }

            $target = "{$source}/" . self::PROCESSED_DIRECTORY . "/{$derivative}";
            File::ensureDirectoryExists(dirname($target));
            File::put($target, (string) Storage::disk('public')->get($derivative));
        }

        return [
            'type' => $media->type->value,
            'path' => $media->path,
            'thumbnail_path' => $thumbnailPath,
            'small_thumbnail_path' => $smallThumbnailPath,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'original_filename' => $media->original_filename,
        ];
    }

    private function import(StoreMediaAction $storeMedia, Media $media, string $absolutePath): void
    {
        $extension = pathinfo($absolutePath, PATHINFO_EXTENSION);
        $temporaryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'demo-media-' . Str::uuid()->toString()
            . ($extension === '' ? '' : '.' . $extension);

        copy($absolutePath, $temporaryPath);

        try {
            $stored = $storeMedia->handle(
                new UploadedFile($temporaryPath, basename($absolutePath), null, null, true),
                preserveFormat: true,
            );
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        $media->forceFill($stored->only([
            'type',
            'disk',
            'path',
            'thumbnail_path',
            'small_thumbnail_path',
            'mime_type',
            'size',
            'width',
            'height',
            'original_filename',
        ]))->save();

        $stored->forceFill(['path' => null, 'thumbnail_path' => null, 'small_thumbnail_path' => null])->delete();
    }

    private function prune(): void
    {
        if ($this->option('prune') !== true) {
            return;
        }

        $this->info(sprintf('Pruned %d unreferenced file(s).', $this->pruneOrphanedFiles()));
    }

    private function pruneOrphanedFiles(): int
    {
        $disk = Storage::disk('public');

        $referenced = Media::query()
            ->select(['path', 'thumbnail_path', 'small_thumbnail_path'])
            ->get()
            ->flatMap(fn (Media $media): array => [$media->path, $media->thumbnail_path, $media->small_thumbnail_path])
            ->filter()
            ->flip();

        $pruned = 0;

        foreach (self::MANAGED_DIRECTORIES as $directory) {
            foreach ($disk->files($directory) as $file) {
                if (! $referenced->has($file)) {
                    $disk->delete($file);
                    $pruned++;
                }
            }
        }

        return $pruned;
    }
}
