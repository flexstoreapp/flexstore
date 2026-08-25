<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Override;

final class StoreMediaRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', File::image(allowSvg: true)],
            'generate_thumbnail' => ['sometimes', 'boolean'],
            'preserve_format' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'file' => mb_strtolower(__('File')),
            'generate_thumbnail' => mb_strtolower(__('Generate thumbnail')),
            'preserve_format' => mb_strtolower(__('Preserve format')),
        ];
    }
}
