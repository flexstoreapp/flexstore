<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Override;

final class StoreDigitalFileRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    private const array ALLOWED_EXTENSIONS = [
        'zip', 'rar', '7z', 'tar', 'gz',
        'pdf', 'epub', 'mobi',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt', 'rtf',
        'mp3', 'wav', 'flac', 'aac', 'ogg',
        'mp4', 'mov', 'avi', 'mkv', 'webm',
        'png', 'jpg', 'jpeg', 'gif', 'svg', 'psd', 'ai', 'eps',
        'ttf', 'otf', 'woff', 'woff2',
    ];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::default()->extensions(self::ALLOWED_EXTENSIONS),
            ],
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
        ];
    }
}
