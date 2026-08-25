<?php

declare(strict_types=1);

namespace App\Enums;

enum TaxCategory: string
{
    case Standard = 'standard';
    case FoodAndBeverages = 'food_and_beverages';
    case BooksAndEducationalMaterials = 'books_and_educational_materials';
    case ClothingAndApparel = 'clothing_and_apparel';
    case Electronics = 'electronics';
    case MedicalAndHealth = 'medical_and_health';
    case LuxuryItems = 'luxury_items';
    case DigitalServices = 'digital_services';
    case Automotive = 'automotive';
    case HomeAndGarden = 'home_and_garden';
    case SportsAndFitness = 'sports_and_fitness';
    case BeautyAndPersonalCare = 'beauty_and_personal_care';
    case ToysAndGames = 'toys_and_games';
    case PetSupplies = 'pet_supplies';
    case JewelryAndAccessories = 'jewelry_and_accessories';
    case ArtAndCraftSupplies = 'art_and_craft_supplies';
    case OfficeSupplies = 'office_supplies';
    case MusicalInstruments = 'musical_instruments';
    case BabyAndMaternity = 'baby_and_maternity';
    case OutdoorAndRecreation = 'outdoor_and_recreation';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Standard => __('Standard'),
            self::FoodAndBeverages => __('Food & beverages'),
            self::BooksAndEducationalMaterials => __('Books & educational materials'),
            self::ClothingAndApparel => __('Clothing & apparel'),
            self::Electronics => __('Electronics'),
            self::MedicalAndHealth => __('Medical & health'),
            self::LuxuryItems => __('Luxury items'),
            self::DigitalServices => __('Digital services'),
            self::Automotive => __('Automotive'),
            self::HomeAndGarden => __('Home & garden'),
            self::SportsAndFitness => __('Sports & fitness'),
            self::BeautyAndPersonalCare => __('Beauty & personal care'),
            self::ToysAndGames => __('Toys & games'),
            self::PetSupplies => __('Pet supplies'),
            self::JewelryAndAccessories => __('Jewelry & accessories'),
            self::ArtAndCraftSupplies => __('Art & craft supplies'),
            self::OfficeSupplies => __('Office supplies'),
            self::MusicalInstruments => __('Musical instruments'),
            self::BabyAndMaternity => __('Baby & maternity'),
            self::OutdoorAndRecreation => __('Outdoor & recreation'),
        };
    }

    public function englishLabel(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::FoodAndBeverages => 'Food & beverages',
            self::BooksAndEducationalMaterials => 'Books & educational materials',
            self::ClothingAndApparel => 'Clothing & apparel',
            self::Electronics => 'Electronics',
            self::MedicalAndHealth => 'Medical & health',
            self::LuxuryItems => 'Luxury items',
            self::DigitalServices => 'Digital services',
            self::Automotive => 'Automotive',
            self::HomeAndGarden => 'Home & garden',
            self::SportsAndFitness => 'Sports & fitness',
            self::BeautyAndPersonalCare => 'Beauty & personal care',
            self::ToysAndGames => 'Toys & games',
            self::PetSupplies => 'Pet supplies',
            self::JewelryAndAccessories => 'Jewelry & accessories',
            self::ArtAndCraftSupplies => 'Art & craft supplies',
            self::OfficeSupplies => 'Office supplies',
            self::MusicalInstruments => 'Musical instruments',
            self::BabyAndMaternity => 'Baby & maternity',
            self::OutdoorAndRecreation => 'Outdoor & recreation',
        };
    }
}
