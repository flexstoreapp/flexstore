<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\ResolveVisitorCartAction;
use App\Actions\ResolveWishlistAction;
use App\Address\SellingCountries;
use App\Enums\Currency as CurrencyEnum;
use App\Enums\Locale;
use App\Enums\SettingGroup;
use App\Models\Cart;
use App\Models\Currency;
use App\Models\Setting;
use App\Queries\StorefrontCartQuery;
use App\Queries\StorefrontLayoutDataQuery;
use App\Queries\StorefrontWishlistQuery;
use App\Utilities\AdminPath;
use App\Utilities\CartCookie;
use App\Utilities\PermissionResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Middleware;
use Override;

final class HandleInertiaRequests extends Middleware
{
    #[Override]
    public function rootView(Request $request): string
    {
        return AdminPath::matches($request) ? 'admin' : 'storefront';
    }

    #[Override]
    public function share(Request $request): array
    {
        $isAdmin = AdminPath::matches($request);

        return [
            ...parent::share($request),
            'activeLocale' => fn (): string => app()->getLocale(),
            'direction' => $this->getDirection(...),
            'activeCurrency' => fn (): string => $isAdmin
                ? $this->getBaseCurrency()
                : ($request->attributes->get('active_currency') ?? $this->getBaseCurrency()),
            ...($isAdmin ? $this->getAdminProps($request) : $this->getStorefrontProps($request)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function shareOnce(Request $request): array
    {
        return [
            'appUrl' => fn (): string => (string) config('app.url'),
            'storeName' => fn (): string => (string) Setting::getValue('store_name', config('app.name', 'FlexStore')),
            'storeLogo' => fn (): ?array => Setting::getValue('store_logo'),
            'storeLogoDark' => fn (): ?array => Setting::getValue('store_logo_dark'),
            'defaultLocale' => fn (): string => (string) Setting::getByGroup(SettingGroup::Locale)
                ->get('default_locale', config('app.locale', 'en')),
            'availableLocales' => fn (): array => $this->getAvailableLocales(Setting::getByGroup(SettingGroup::Locale)),
            'sellingCountries' => SellingCountries::codes(...),
            'baseCurrency' => $this->getBaseCurrency(...),
            'availableCurrencies' => $this->getAvailableCurrencies(...),
            ...(AdminPath::matches($request) ? $this->getAdminOnceProps() : $this->getStorefrontOnceProps()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getAdminOnceProps(): array
    {
        return [
            'timezone' => fn (): string => (string) config('app.timezone', 'UTC'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getStorefrontOnceProps(): array
    {
        return [
            'storefront' => fn (): array => resolve(StorefrontLayoutDataQuery::class)->execute(),
            'theme' => fn (): string => (string) Setting::getByGroup(SettingGroup::Storefront)
                ->get('storefront_theme_color', 'blue'),
        ];
    }

    private function getBaseCurrency(): string
    {
        return (string) Setting::getByGroup(SettingGroup::Currency)
            ->get('base_currency', CurrencyEnum::USD->name);
    }

    /**
     * @return array<string, mixed>
     */
    private function getAdminProps(Request $request): array
    {
        return [
            'auth' => fn (): array => [
                'user' => $request->user(),
                'roles' => $request->user()?->roles,
                'permissions' => $request->user()
                    ? PermissionResolver::effectiveAbilities($request->user())
                        ->map(fn (string $ability): array => ['name' => $ability])
                    : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'appearance' => fn (): string => $request->user()->appearance->value ?? 'system',
            'adminThemeColor' => fn (): string => $request->user()->admin_theme_color->value ?? 'neutral',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getStorefrontProps(Request $request): array
    {
        return [
            'flash' => fn (): array => [
                'message' => $request->session()->get('message'),
                'error' => $request->session()->get('error'),
            ],
            'cart' => fn (): Cart => $this->resolveCart($request),
            'wishlist' => fn (): array => $this->resolveWishlist($request),
            'auth' => fn (): array => [
                'user' => $request->user(),
            ],
        ];
    }

    private function resolveCart(Request $request): Cart
    {
        $cart = resolve(ResolveVisitorCartAction::class)->handle(CartCookie::from($request), $request->user());

        if (CartCookie::from($request) !== $cart->id) {
            cookie()->queue(cookie()->forever(CartCookie::NAME, $cart->id));
        }

        return resolve(StorefrontCartQuery::class)->execute($cart);
    }

    /**
     * @return array{id: string, product_ids: array<int, int>}
     */
    private function resolveWishlist(Request $request): array
    {
        $cookieWishlistId = $request->cookie('wishlist_id');
        $wishlistId = is_string($cookieWishlistId) ? $cookieWishlistId : null;

        $wishlist = resolve(ResolveWishlistAction::class)->handle($wishlistId, $request->user());

        cookie()->queue(cookie()->forever('wishlist_id', $wishlist->id));

        return resolve(StorefrontWishlistQuery::class)->execute($wishlist);
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    private function getAvailableLocales(Collection $localeSettings): array // @phpstan-ignore missingType.generics
    {
        $codes = (array) $localeSettings->get('available_locales', [config('app.locale', 'en')]);

        return array_map(fn (string $code): array => [
            'code' => $code,
            'name' => Locale::nativeName($code),
        ], $codes);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAvailableCurrencies(): array
    {
        return Currency::query()
            ->where('is_active', true)
            ->get(['code', 'symbol', 'symbol_position', 'thousands_separator', 'decimal_separator', 'decimal_places', 'exchange_rate'])
            ->toArray();
    }

    private function getDirection(): string
    {
        return Locale::isRtl(app()->getLocale()) ? 'rtl' : 'ltr';
    }
}
