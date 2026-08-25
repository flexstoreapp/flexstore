<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateBrandInput;
use App\Models\Brand;

final readonly class UpdateBrandAction
{
    public function handle(Brand $brand, UpdateBrandInput $input): Brand
    {
        $brand->update([
            'name' => $input->has('name') ? $input->name : $brand->name,
            'url_handle' => $input->has('url_handle') ? $input->urlHandle : $brand->url_handle,
            'description' => $input->has('description') ? $input->description : $brand->description,
            'seo_title' => $input->has('seo_title') ? $input->seoTitle : $brand->seo_title,
            'seo_description' => $input->has('seo_description') ? $input->seoDescription : $brand->seo_description,
            'image_id' => $input->has('image_id') ? $input->imageId : $brand->image_id,
            'is_active' => $input->has('is_active') ? $input->isActive : $brand->is_active,
        ]);

        return $brand;
    }
}
