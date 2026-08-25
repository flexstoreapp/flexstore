<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Utilities\Translations;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class TranslationScriptController
{
    public function show(string $locale, string $bundle): Response
    {
        if (! Translations::isBundle($bundle) || ! Translations::hasLocale($locale)) {
            throw new NotFoundHttpException();
        }

        return response(Translations::script($bundle, $locale), 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
