<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreBrandInput;
use App\Models\Brand;

final readonly class StoreBrandAction
{
    public function handle(StoreBrandInput $input): Brand
    {
        return Brand::query()->create([
            'name' => $input->name,
            'url_handle' => $input->urlHandle,
            'description' => $input->description,
            'seo_title' => $input->seoTitle,
            'seo_description' => $input->seoDescription,
            'image_id' => $input->imageId,
            'is_active' => $input->isActive,
        ]);
    }
}
