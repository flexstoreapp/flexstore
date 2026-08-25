<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Models\User;
use BackedEnum;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

final readonly class RedirectIfNotAuthorized
{
    public static function using(string|BackedEnum $ability): string
    {
        $value = $ability instanceof BackedEnum ? $ability->value : $ability;

        return self::class . ':' . $value;
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        if ($request->user()?->can($ability)) {
            return $next($request);
        }

        $fallback = $this->getFirstAccessibleRoute($request->user());

        throw_if(is_null($fallback), AuthorizationException::class);

        return to_route($fallback);
    }

    private function getFirstAccessibleRoute(?User $user): ?string
    {
        foreach ($this->routePermissions() as $route => $permissions) {
            foreach (Arr::wrap($permissions) as $permission) {
                if ($user?->can($permission)) {
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, Permission|list<Permission>>
     */
    private function routePermissions(): array
    {
        return [
            'admin.dashboard' => Permission::DashboardView,
            'admin.orders.index' => Permission::OrdersView,
            'admin.products.index' => Permission::ProductsView,
            'admin.inventory.index' => Permission::InventoryView,
            'admin.categories.index' => Permission::CategoriesView,
            'admin.brands.index' => Permission::BrandsView,
            'admin.coupons.index' => Permission::CouponsView,
            'admin.customers.index' => Permission::CustomersView,
            'admin.reviews.index' => Permission::ReviewsView,
            'admin.regions.index' => Permission::RegionsView,
            'admin.storefront.builder.index' => Permission::StorefrontView,
            'admin.settings.index' => Permission::settings(),
        ];
    }
}
