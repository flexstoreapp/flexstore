<?php

declare(strict_types=1);

namespace App\TwoFactor;

use App\Models\Setting;
use App\Models\User;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;
use SensitiveParameter;

final readonly class Totp
{
    public function __construct(
        private Google2FA $google2fa
    ) {
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function generateQrCodeSvg(User $user, #[SensitiveParameter] string $secret): string
    {
        $svg = new Writer(
            new ImageRenderer(
                new RendererStyle(192, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                new SvgImageBackEnd()
            )
        )->writeString($this->generateQrCodeUrl($user, $secret));

        return mb_trim(mb_substr($svg, mb_strpos($svg, "\n") + 1));
    }

    public function generateQrCodeUrl(User $user, #[SensitiveParameter] string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            Setting::getValue('store_name', config('google2fa.issuer')),
            $user->email,
            $secret
        );
    }

    public function validateCode(#[SensitiveParameter] string $secret, #[SensitiveParameter] string $code): bool
    {
        return (bool) $this->google2fa->verifyKey($secret, $code, config('google2fa.window'));
    }

    public function getCurrentCode(#[SensitiveParameter] string $secret): string
    {
        return $this->google2fa->getCurrentOtp($secret);
    }
}
