<?php

namespace Vx\Sendifico\Order;

final class ContentsCatalog
{
    public const VALUES = [
        'agriculturalSupply',
        'audioAndVideoAccessory',
        'babyProduct',
        'beverage',
        'book',
        'camera',
        'cellularPhone',
        'clothes',
        'clothingAccessory',
        'computer',
        'decoration',
        'documents',
        'dron',
        'electromenor',
        'food',
        'householdAppliance',
        'jewelryAndWatch',
        'kitchenware',
        'medicalEquipment',
        'medicalSupply',
        'musicalInstrument',
        'party',
        'personalCareProduct',
        'petAccessory',
        'petFood',
        'printer',
        'shoeAndFootwear',
        'spareparts',
        'sportingArticle',
        'stationery',
        'television',
        'toy',
        'vehicleAccessory',
        'videoGame',
    ];

    /**
     * @return array<string, string>
     */
    public static function getFormChoices(): array
    {
        $choices = [];

        foreach (self::VALUES as $value) {
            $choices[$value] = $value;
        }

        return $choices;
    }

    public static function isSupported(string $value): bool
    {
        return in_array($value, self::VALUES, true);
    }

    private function __construct()
    {
    }
}
