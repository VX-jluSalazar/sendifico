<?php

namespace Vx\Sendifico\Install;

use Configuration;
use Shop;

class ConfigurationInstaller
{
    private const DEFAULTS = [
        'VX_SENDIFICO_API_VERSION' => '2026-01-01',
        'VX_SENDIFICO_COUNTRY' => 'EC',
        'VX_SENDIFICO_CURRENCY' => 'USD',
        'VX_SENDIFICO_PURCHASE_WITH' => 'walletAvailable',
        'VX_SENDIFICO_DEFAULT_WEIGHT' => '1.000',
        'VX_SENDIFICO_DEFAULT_LENGTH' => '10.000',
        'VX_SENDIFICO_DEFAULT_WIDTH' => '10.000',
        'VX_SENDIFICO_DEFAULT_HEIGHT' => '10.000',
    ];

    public function install(): bool
    {
        $result = true;
        $shopGroups = [];

        foreach (Shop::getContextListShopID() as $shopId) {
            $shopId = (int) $shopId;
            $groupId = (int) Shop::getGroupFromShop($shopId, true);
            if (!in_array($groupId, $shopGroups, true)) {
                $shopGroups[] = $groupId;
            }

            foreach (self::DEFAULTS as $key => $value) {
                $result = Configuration::updateValue($key, $value, false, $groupId, $shopId) && $result;
            }
        }

        foreach ($shopGroups as $groupId) {
            foreach (self::DEFAULTS as $key => $value) {
                $result = Configuration::updateValue($key, $value, false, (int) $groupId) && $result;
            }
        }

        foreach (self::DEFAULTS as $key => $value) {
            $result = Configuration::updateValue($key, $value) && $result;
        }

        return (bool) $result;
    }

    public function uninstall(): bool
    {
        $result = true;

        foreach (array_keys(self::DEFAULTS) as $key) {
            $result = Configuration::deleteByName($key) && $result;
        }

        return (bool) $result;
    }
}
