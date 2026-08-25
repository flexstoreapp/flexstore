<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\CreateExternalMediaAction;
use App\Actions\SyncSectionMediaAction;
use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Enums\StorefrontSectionType;
use App\Models\Announcement;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\StorefrontSection;
use Illuminate\Database\Seeder;

final class StorefrontSeeder extends Seeder
{
    public function __construct(
        private readonly CreateExternalMediaAction $createExternalMediaAction,
        private readonly SyncSectionMediaAction $syncSectionMediaAction,
    ) {
    }

    public function run(): void
    {
        $this->seedAnnouncements();
        $this->seedHeaderMenuItems();
        $this->seedFooterMenuItems();
        $this->seedStorefrontSections();
    }

    private function seedAnnouncements(): void
    {
        $announcements = [
            [
                'content' => [
                    'en' => 'Free shipping on orders over $50!',
                    'ar' => 'شحن مجاني للطلبات فوق 50 دولار!',
                ],
                'url' => '/shipping',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'content' => [
                    'en' => 'Summer Sale - Up to 40% off selected items',
                    'ar' => 'تخفيضات الصيف — خصم يصل إلى 40٪ على منتجات مختارة',
                ],
                'url' => '/shop?sale=true',
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($announcements as $announcement) {
            Announcement::query()->create($announcement);
        }
    }

    private function seedHeaderMenuItems(): void
    {
        $menuItems = [
            [
                'label' => 'Shop',
                'url' => '/shop',
                'sort_order' => 1,
                'is_mega_menu' => true,
                'children' => [
                    [
                        'label' => 'New Arrivals',
                        'url' => '/shop?sort=newest',
                        'sort_order' => 1,
                        'children' => [
                            ['label' => 'This Week', 'url' => '/shop?sort=newest&period=week', 'sort_order' => 1],
                            ['label' => 'This Month', 'url' => '/shop?sort=newest&period=month', 'sort_order' => 2],
                            ['label' => 'Trending Now', 'url' => '/shop?trending=true', 'sort_order' => 3],
                        ],
                    ],
                    [
                        'label' => 'Best Sellers',
                        'url' => '/shop?sort=popular',
                        'sort_order' => 2,
                        'children' => [
                            ['label' => 'Top Rated', 'url' => '/shop?sort=rating', 'sort_order' => 1],
                            ['label' => 'Most Reviewed', 'url' => '/shop?sort=reviews', 'sort_order' => 2],
                            ['label' => 'Customer Favorites', 'url' => '/shop?favorites=true', 'sort_order' => 3],
                        ],
                    ],
                    [
                        'label' => 'Electronics',
                        'url' => '/categories/electronics',
                        'sort_order' => 3,
                        'children' => [
                            ['label' => 'Smartphones', 'url' => '/categories/electronics/smartphones', 'sort_order' => 1],
                            ['label' => 'Laptops', 'url' => '/categories/electronics/laptops', 'sort_order' => 2],
                            ['label' => 'Audio', 'url' => '/categories/electronics/audio', 'sort_order' => 3],
                            ['label' => 'Wearables', 'url' => '/categories/electronics/wearables', 'sort_order' => 4],
                            ['label' => 'Cameras', 'url' => '/categories/electronics/cameras', 'sort_order' => 5],
                        ],
                    ],
                    [
                        'label' => 'Home & Garden',
                        'url' => '/categories/home-garden',
                        'sort_order' => 4,
                        'children' => [
                            ['label' => 'Furniture', 'url' => '/categories/home-garden/furniture', 'sort_order' => 1],
                            ['label' => 'Decor', 'url' => '/categories/home-garden/decor', 'sort_order' => 2],
                            ['label' => 'Kitchen', 'url' => '/categories/home-garden/kitchen', 'sort_order' => 3],
                            ['label' => 'Bedding', 'url' => '/categories/home-garden/bedding', 'sort_order' => 4],
                            ['label' => 'Outdoor', 'url' => '/categories/home-garden/outdoor', 'sort_order' => 5],
                        ],
                    ],
                    [
                        'label' => 'Sports & Outdoors',
                        'url' => '/categories/sports',
                        'sort_order' => 5,
                        'children' => [
                            ['label' => 'Exercise Equipment', 'url' => '/categories/sports/equipment', 'sort_order' => 1],
                            ['label' => 'Outdoor Gear', 'url' => '/categories/sports/outdoor-gear', 'sort_order' => 2],
                            ['label' => 'Sports Apparel', 'url' => '/categories/sports/apparel', 'sort_order' => 3],
                        ],
                    ],
                ],
            ],
            [
                'label' => 'Categories',
                'url' => '/categories',
                'sort_order' => 2,
            ],
            [
                'label' => 'Brands',
                'url' => '/brands',
                'sort_order' => 3,
            ],
            [
                'label' => 'Women',
                'url' => '/categories/women',
                'sort_order' => 4,
                'is_mega_menu' => true,
                'featured_image' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=400&q=80',
                'children' => [
                    [
                        'label' => 'Clothing',
                        'url' => '/categories/women/clothing',
                        'sort_order' => 1,
                        'children' => [
                            ['label' => 'Dresses', 'url' => '/categories/women/clothing/dresses', 'sort_order' => 1],
                            ['label' => 'Tops & Blouses', 'url' => '/categories/women/clothing/tops', 'sort_order' => 2],
                            ['label' => 'Pants & Jeans', 'url' => '/categories/women/clothing/pants', 'sort_order' => 3],
                            ['label' => 'Skirts', 'url' => '/categories/women/clothing/skirts', 'sort_order' => 4],
                            ['label' => 'Activewear', 'url' => '/categories/women/clothing/activewear', 'sort_order' => 5],
                            ['label' => 'Outerwear', 'url' => '/categories/women/clothing/outerwear', 'sort_order' => 6],
                        ],
                    ],
                    [
                        'label' => 'Shoes',
                        'url' => '/categories/women/shoes',
                        'sort_order' => 2,
                        'children' => [
                            ['label' => 'Heels', 'url' => '/categories/women/shoes/heels', 'sort_order' => 1],
                            ['label' => 'Flats', 'url' => '/categories/women/shoes/flats', 'sort_order' => 2],
                            ['label' => 'Sneakers', 'url' => '/categories/women/shoes/sneakers', 'sort_order' => 3],
                            ['label' => 'Boots', 'url' => '/categories/women/shoes/boots', 'sort_order' => 4],
                            ['label' => 'Sandals', 'url' => '/categories/women/shoes/sandals', 'sort_order' => 5],
                        ],
                    ],
                    [
                        'label' => 'Accessories',
                        'url' => '/categories/women/accessories',
                        'sort_order' => 3,
                        'children' => [
                            ['label' => 'Handbags', 'url' => '/categories/women/accessories/handbags', 'sort_order' => 1],
                            ['label' => 'Jewelry', 'url' => '/categories/women/accessories/jewelry', 'sort_order' => 2],
                            ['label' => 'Watches', 'url' => '/categories/women/accessories/watches', 'sort_order' => 3],
                            ['label' => 'Sunglasses', 'url' => '/categories/women/accessories/sunglasses', 'sort_order' => 4],
                            ['label' => 'Scarves', 'url' => '/categories/women/accessories/scarves', 'sort_order' => 5],
                        ],
                    ],
                    [
                        'label' => 'Beauty',
                        'url' => '/categories/women/beauty',
                        'sort_order' => 4,
                        'children' => [
                            ['label' => 'Makeup', 'url' => '/categories/women/beauty/makeup', 'sort_order' => 1],
                            ['label' => 'Skincare', 'url' => '/categories/women/beauty/skincare', 'sort_order' => 2],
                            ['label' => 'Haircare', 'url' => '/categories/women/beauty/haircare', 'sort_order' => 3],
                            ['label' => 'Fragrance', 'url' => '/categories/women/beauty/fragrance', 'sort_order' => 4],
                        ],
                    ],
                ],
            ],
            [
                'label' => 'Men',
                'url' => '/categories/men',
                'sort_order' => 5,
                'is_mega_menu' => true,
                'featured_image' => 'https://images.unsplash.com/photo-1517938889432-a2ac9241a486?w=400&q=80',
                'children' => [
                    [
                        'label' => 'Clothing',
                        'url' => '/categories/men/clothing',
                        'sort_order' => 1,
                        'children' => [
                            ['label' => 'T-Shirts', 'url' => '/categories/men/clothing/tshirts', 'sort_order' => 1],
                            ['label' => 'Shirts', 'url' => '/categories/men/clothing/shirts', 'sort_order' => 2],
                            ['label' => 'Pants & Jeans', 'url' => '/categories/men/clothing/pants', 'sort_order' => 3],
                            ['label' => 'Suits & Blazers', 'url' => '/categories/men/clothing/suits', 'sort_order' => 4],
                            ['label' => 'Activewear', 'url' => '/categories/men/clothing/activewear', 'sort_order' => 5],
                            ['label' => 'Outerwear', 'url' => '/categories/men/clothing/outerwear', 'sort_order' => 6],
                        ],
                    ],
                    [
                        'label' => 'Shoes',
                        'url' => '/categories/men/shoes',
                        'sort_order' => 2,
                        'children' => [
                            ['label' => 'Sneakers', 'url' => '/categories/men/shoes/sneakers', 'sort_order' => 1],
                            ['label' => 'Dress Shoes', 'url' => '/categories/men/shoes/dress', 'sort_order' => 2],
                            ['label' => 'Boots', 'url' => '/categories/men/shoes/boots', 'sort_order' => 3],
                            ['label' => 'Loafers', 'url' => '/categories/men/shoes/loafers', 'sort_order' => 4],
                            ['label' => 'Sandals', 'url' => '/categories/men/shoes/sandals', 'sort_order' => 5],
                        ],
                    ],
                    [
                        'label' => 'Accessories',
                        'url' => '/categories/men/accessories',
                        'sort_order' => 3,
                        'children' => [
                            ['label' => 'Watches', 'url' => '/categories/men/accessories/watches', 'sort_order' => 1],
                            ['label' => 'Belts', 'url' => '/categories/men/accessories/belts', 'sort_order' => 2],
                            ['label' => 'Wallets', 'url' => '/categories/men/accessories/wallets', 'sort_order' => 3],
                            ['label' => 'Sunglasses', 'url' => '/categories/men/accessories/sunglasses', 'sort_order' => 4],
                            ['label' => 'Bags', 'url' => '/categories/men/accessories/bags', 'sort_order' => 5],
                        ],
                    ],
                    [
                        'label' => 'Grooming',
                        'url' => '/categories/men/grooming',
                        'sort_order' => 4,
                        'children' => [
                            ['label' => 'Skincare', 'url' => '/categories/men/grooming/skincare', 'sort_order' => 1],
                            ['label' => 'Shaving', 'url' => '/categories/men/grooming/shaving', 'sort_order' => 2],
                            ['label' => 'Haircare', 'url' => '/categories/men/grooming/haircare', 'sort_order' => 3],
                            ['label' => 'Cologne', 'url' => '/categories/men/grooming/cologne', 'sort_order' => 4],
                        ],
                    ],
                ],
            ],
            [
                'label' => 'Collections',
                'url' => '/collections',
                'sort_order' => 6,
                'children' => [
                    [
                        'label' => 'Seasonal',
                        'url' => '/collections/seasonal',
                        'sort_order' => 1,
                        'children' => [
                            ['label' => 'Summer Collection', 'url' => '/collections/summer', 'sort_order' => 1],
                            ['label' => 'Winter Essentials', 'url' => '/collections/winter', 'sort_order' => 2],
                            ['label' => 'Spring Refresh', 'url' => '/collections/spring', 'sort_order' => 3],
                            ['label' => 'Fall Favorites', 'url' => '/collections/fall', 'sort_order' => 4],
                        ],
                    ],
                    [
                        'label' => 'Lifestyle',
                        'url' => '/collections/lifestyle',
                        'sort_order' => 2,
                        'children' => [
                            ['label' => 'Work From Home', 'url' => '/collections/wfh', 'sort_order' => 1],
                            ['label' => 'Travel Ready', 'url' => '/collections/travel', 'sort_order' => 2],
                            ['label' => 'Sustainable Living', 'url' => '/collections/sustainable', 'sort_order' => 3],
                            ['label' => 'Minimalist', 'url' => '/collections/minimalist', 'sort_order' => 4],
                        ],
                    ],
                    [
                        'label' => 'Featured',
                        'url' => '/collections/featured',
                        'sort_order' => 3,
                        'children' => [
                            ['label' => 'Editor\'s Picks', 'url' => '/collections/editors-picks', 'sort_order' => 1],
                            ['label' => 'Staff Favorites', 'url' => '/collections/staff-favorites', 'sort_order' => 2],
                            ['label' => 'Gift Ideas', 'url' => '/collections/gifts', 'sort_order' => 3],
                            ['label' => 'Limited Edition', 'url' => '/collections/limited-edition', 'sort_order' => 4],
                        ],
                    ],
                ],
            ],
            [
                'label' => 'Flash Sales',
                'url' => '/flash-sales',
                'sort_order' => 7,
            ],
            [
                'label' => 'Sale',
                'url' => '/shop?sale=true',
                'sort_order' => 8,
                'children' => [
                    [
                        'label' => 'Women\'s Sale',
                        'url' => '/shop?sale=true&category=women',
                        'sort_order' => 1,
                        'children' => [
                            ['label' => 'Up to 30% Off', 'url' => '/shop?sale=true&category=women&discount=30', 'sort_order' => 1],
                            ['label' => 'Up to 50% Off', 'url' => '/shop?sale=true&category=women&discount=50', 'sort_order' => 2],
                            ['label' => 'Clearance', 'url' => '/shop?clearance=true&category=women', 'sort_order' => 3],
                        ],
                    ],
                    [
                        'label' => 'Men\'s Sale',
                        'url' => '/shop?sale=true&category=men',
                        'sort_order' => 2,
                        'children' => [
                            ['label' => 'Up to 30% Off', 'url' => '/shop?sale=true&category=men&discount=30', 'sort_order' => 1],
                            ['label' => 'Up to 50% Off', 'url' => '/shop?sale=true&category=men&discount=50', 'sort_order' => 2],
                            ['label' => 'Clearance', 'url' => '/shop?clearance=true&category=men', 'sort_order' => 3],
                        ],
                    ],
                    [
                        'label' => 'Flash Deals',
                        'url' => '/shop?flash=true',
                        'sort_order' => 3,
                        'children' => [
                            ['label' => 'Today\'s Deals', 'url' => '/shop?deal=today', 'sort_order' => 1],
                            ['label' => 'Weekly Specials', 'url' => '/shop?deal=weekly', 'sort_order' => 2],
                            ['label' => 'Last Chance', 'url' => '/shop?last-chance=true', 'sort_order' => 3],
                        ],
                    ],
                ],
            ],
        ];

        $this->createMenuItems($menuItems, MenuLocation::Header);
    }

    /**
     * @param  list<array{label: string, url: string, sort_order: int, is_mega_menu?: bool, featured_image?: string, children?: list<mixed>}>  $items
     */
    private function createMenuItems(array $items, MenuLocation $location, ?int $parentId = null): void
    {
        foreach ($items as $item) {
            $children = $item['children'] ?? [];

            $menuItem = MenuItem::query()->create([
                'location' => $location,
                'link_type' => MenuItemLinkType::Custom,
                'target' => '_self',
                'is_mega_menu' => $item['is_mega_menu'] ?? false,
                'is_active' => true,
                'parent_id' => $parentId,
                'featured_image_id' => $this->externalMediaId($item['featured_image'] ?? null),
                'label' => $item['label'],
                'url' => $item['url'],
                'sort_order' => $item['sort_order'],
            ]);

            if ($children !== []) {
                $this->createMenuItems($children, $location, $menuItem->id);
            }
        }
    }

    private function seedFooterMenuItems(): void
    {
        $menuItems = [
            [
                'label' => 'Shop',
                'url' => '#',
                'sort_order' => 1,
                'children' => [
                    ['label' => 'All Products', 'url' => '/shop', 'sort_order' => 1],
                    ['label' => 'Categories', 'url' => '/categories', 'sort_order' => 2],
                    ['label' => 'Brands', 'url' => '/brands', 'sort_order' => 3],
                    ['label' => 'Flash Sales', 'url' => '/flash-sales', 'sort_order' => 4],
                    ['label' => 'Sale', 'url' => '/shop?sale=true', 'sort_order' => 5],
                ],
            ],
            [
                'label' => 'Company',
                'url' => '#',
                'sort_order' => 2,
                'children' => [
                    ['label' => 'About Us', 'url' => '/about', 'sort_order' => 1],
                    ['label' => 'Contact Us', 'url' => '/contact', 'sort_order' => 1],
                    ['label' => 'Track Order', 'url' => '/orders/track', 'sort_order' => 3],
                    ['label' => 'Blog', 'url' => '/blog', 'sort_order' => 4],
                ],
            ],
            [
                'label' => 'Legal',
                'url' => '#',
                'sort_order' => 3,
                'children' => [
                    ['label' => 'Terms of Service', 'url' => '/terms', 'sort_order' => 1],
                    ['label' => 'Privacy Policy', 'url' => '/privacy', 'sort_order' => 2],
                    ['label' => 'Cookie Policy', 'url' => '/cookies', 'sort_order' => 3],
                ],
            ],
        ];

        $this->createMenuItems($menuItems, MenuLocation::Footer);
    }

    private function seedStorefrontSections(): void
    {
        $categoryIds = Category::query()->whereNull('parent_id')->limit(5)->pluck('id');
        $brandIds = Brand::query()->where('is_active', true)->limit(5)->pluck('id')->toArray();

        $sections = [
            [
                'type' => StorefrontSectionType::HeroSlider,
                'title' => 'Hero',
                'settings' => [
                    'slides' => [
                        [
                            'image' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=2048&q=80',
                            'headline' => ['en' => 'New season, new favourites', 'ar' => 'موسم جديد، مفضلات جديدة'],
                            'subtext' => ['en' => 'Discover the pieces everyone is talking about.', 'ar' => 'اكتشف القطع التي يتحدث عنها الجميع.'],
                            'button_text' => ['en' => 'Shop now', 'ar' => 'تسوق الآن'],
                            'button_url' => '/shop',
                        ],
                        [
                            'image' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=2048&q=80',
                            'headline' => ['en' => 'Up to 50% off', 'ar' => 'خصم يصل إلى 50٪'],
                            'subtext' => ['en' => 'Limited-time deals across the store.', 'ar' => 'عروض محدودة في كل أرجاء المتجر.'],
                            'button_text' => ['en' => 'Shop the sale', 'ar' => 'تسوق التخفيضات'],
                            'button_url' => '/shop?sale=true',
                        ],
                    ],
                    'side_tiles' => [
                        [
                            'image' => 'https://images.unsplash.com/photo-1441984904996-e0b6ba687e04?w=1024&q=80',
                            'title' => ['en' => 'Trending now', 'ar' => 'الرائج الآن'],
                            'subtitle' => ['en' => 'Handpicked for you', 'ar' => 'مختارة لأجلك'],
                            'url' => '/shop?trending=true',
                        ],
                        [
                            'image' => 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=1024&q=80',
                            'title' => ['en' => 'New arrivals', 'ar' => 'وصل حديثًا'],
                            'subtitle' => ['en' => 'Fresh drops weekly', 'ar' => 'إصدارات جديدة كل أسبوع'],
                            'url' => '/shop?sort=newest',
                        ],
                    ],
                    'autoplay' => true,
                    'autoplay_speed' => 6000,
                    'transition' => 'fade',
                    'show_dots' => true,
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],

            [
                'type' => StorefrontSectionType::InfoStrip,
                'title' => 'Service highlights',
                'settings' => [
                    'items' => [
                        ['icon_name' => 'shipping', 'title' => ['en' => 'Free shipping', 'ar' => 'شحن مجاني'], 'subtitle' => ['en' => 'On orders over $50', 'ar' => 'للطلبات فوق 50 دولار']],
                        ['icon_name' => 'returns', 'title' => ['en' => '30-day returns', 'ar' => 'سياسة إرجاع 30 يومًا'], 'subtitle' => ['en' => 'Hassle-free returns', 'ar' => 'إرجاع سهل']],
                        ['icon_name' => 'shield', 'title' => ['en' => 'Secure payment', 'ar' => 'دفع آمن'], 'subtitle' => ['en' => '100% protected checkout', 'ar' => 'دفع آمن 100٪']],
                        ['icon_name' => 'support', 'title' => ['en' => '24/7 support', 'ar' => 'دعم على مدار الساعة'], 'subtitle' => ['en' => 'Here whenever you need us', 'ar' => 'نحن هنا متى احتجت إلينا']],
                    ],
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],

            [
                'type' => StorefrontSectionType::CategoryGrid,
                'title' => 'Shop by category',
                'settings' => [
                    'categories' => $categoryIds->map(fn (int $id): array => ['category_id' => $id, 'image' => null])->all(),
                ],
                'sort_order' => 3,
                'is_active' => true,
            ],

            [
                'type' => StorefrontSectionType::FeaturedProducts,
                'title' => 'Featured products',
                'settings' => [
                    'product_source' => 'latest',
                    'product_limit' => 8,
                    'category_id' => null,
                    'product_ids' => null,
                ],
                'sort_order' => 4,
                'is_active' => true,
            ],

            [
                'type' => StorefrontSectionType::ProductTabs,
                'title' => 'Best sellers',
                'settings' => [
                    'tabs' => [
                        ['label' => ['en' => 'Trending', 'ar' => 'الرائج'], 'product_source' => 'latest', 'product_limit' => 10, 'category_id' => null, 'product_ids' => null],
                        ['label' => ['en' => 'Popular', 'ar' => 'الأكثر شعبية'], 'product_source' => 'latest', 'product_limit' => 10, 'category_id' => null, 'product_ids' => null],
                    ],
                ],
                'sort_order' => 6,
                'is_active' => true,
            ],

            [
                'type' => StorefrontSectionType::PromoBanners,
                'title' => 'Promotions',
                'settings' => [
                    'banners' => [
                        [
                            'image' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=1280&q=80',
                            'title' => ['en' => 'Kitchen essentials', 'ar' => 'أساسيات المطبخ'],
                            'subtitle' => ['en' => 'Upgrade your everyday cooking', 'ar' => 'حسّن طبخك اليومي'],
                            'url' => '/shop',
                            'text_align' => 'left',
                        ],
                        [
                            'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=1280&q=80',
                            'title' => ['en' => 'New season sneakers', 'ar' => 'أحذية الموسم الجديد'],
                            'subtitle' => ['en' => 'Step into something fresh', 'ar' => 'خطوة جديدة كل يوم'],
                            'url' => '/shop',
                            'text_align' => 'center',
                        ],
                    ],
                ],
                'sort_order' => 7,
                'is_active' => true,
            ],

            [
                'type' => StorefrontSectionType::ProductCarousel,
                'title' => 'New arrivals',
                'settings' => [
                    'product_source' => 'latest',
                    'product_limit' => 12,
                    'category_id' => null,
                    'product_ids' => null,
                ],
                'sort_order' => 8,
                'is_active' => true,
            ],

            [
                'type' => StorefrontSectionType::ProductColumns,
                'title' => 'Explore',
                'settings' => [
                    'columns' => [
                        ['heading' => ['en' => 'Top rated', 'ar' => 'الأعلى تقييمًا'], 'product_source' => 'latest', 'product_limit' => 3, 'category_id' => null, 'product_ids' => null],
                        ['heading' => ['en' => 'Best sellers', 'ar' => 'الأكثر مبيعًا'], 'product_source' => 'latest', 'product_limit' => 3, 'category_id' => null, 'product_ids' => null],
                        ['heading' => ['en' => 'Latest arrivals', 'ar' => 'أحدث الوصولات'], 'product_source' => 'latest', 'product_limit' => 3, 'category_id' => null, 'product_ids' => null],
                    ],
                ],
                'sort_order' => 9,
                'is_active' => true,
            ],

            [
                'type' => StorefrontSectionType::BrandStrip,
                'title' => 'Shop by brand',
                'settings' => [
                    'brand_ids' => $brandIds,
                    'grayscale' => true,
                ],
                'sort_order' => 10,
                'is_active' => true,
            ],

            [
                'type' => StorefrontSectionType::Testimonials,
                'title' => 'What our customers say',
                'settings' => [
                    'testimonials' => [
                        ['quote' => ['en' => 'Fantastic quality and fast delivery. My new go-to store.', 'ar' => 'جودة رائعة وتوصيل سريع. متجري الأول من الآن.'], 'author_name' => ['en' => 'Amelia Clark', 'ar' => 'Amelia Clark'], 'rating' => 5],
                        ['quote' => ['en' => 'The support team went above and beyond to help me.', 'ar' => 'فريق الدعم تجاوز التوقعات لمساعدتي.'], 'author_name' => ['en' => 'James Wilson', 'ar' => 'James Wilson'], 'rating' => 5],
                        ['quote' => ['en' => 'Beautifully made products at a fair price. Highly recommend.', 'ar' => 'منتجات مصنوعة بإتقان وبسعر عادل. أنصح بها بقوة.'], 'author_name' => ['en' => 'Sofia Rossi', 'ar' => 'Sofia Rossi'], 'rating' => 5],
                    ],
                ],
                'sort_order' => 11,
                'is_active' => true,
            ],

        ];

        foreach ($sections as $section) {
            $section['settings'] = $this->resolveSettingsMedia($section['type'], $section['settings'] ?? []);

            $this->syncSectionMediaAction->handle(StorefrontSection::query()->create($section));
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function resolveSettingsMedia(StorefrontSectionType $type, array $settings): array
    {
        $map = $type->mediaFields();

        foreach ($map['fields'] as $field) {
            if ($mediaId = $this->externalMediaId($settings[$field] ?? null)) {
                $settings[$field] = $mediaId;
            }
        }

        foreach ($map['lists'] as $listKey => $imageKeys) {
            if (! is_array($settings[$listKey] ?? null)) {
                continue;
            }

            foreach ($settings[$listKey] as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                foreach ($imageKeys as $imageKey) {
                    if ($mediaId = $this->externalMediaId($item[$imageKey] ?? null)) {
                        $settings[$listKey][$index][$imageKey] = $mediaId;
                    }
                }
            }
        }

        return $settings;
    }

    private function externalMediaId(mixed $url): ?int
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        return $this->createExternalMediaAction->handle($url)->id;
    }
}
