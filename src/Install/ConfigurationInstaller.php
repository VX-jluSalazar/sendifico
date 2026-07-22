<?php

namespace Vx\Sendifico\Install;

use Configuration;
use Shop;
use Vx\Sendifico\Configuration\ConfigurationKeys;

class ConfigurationInstaller
{
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

            foreach (ConfigurationKeys::DEFAULTS as $key => $value) {
                $result = Configuration::updateValue($key, $value, false, $groupId, $shopId) && $result;
            }
        }

        foreach ($shopGroups as $groupId) {
            foreach (ConfigurationKeys::DEFAULTS as $key => $value) {
                $result = Configuration::updateValue($key, $value, false, (int) $groupId) && $result;
            }
        }

        foreach (ConfigurationKeys::DEFAULTS as $key => $value) {
            $result = Configuration::updateValue($key, $value) && $result;
        }

        return (bool) $result;
    }

    public function uninstall(): bool
    {
        $result = true;

        foreach (array_keys(ConfigurationKeys::DEFAULTS) as $key) {
            $result = Configuration::deleteByName($key) && $result;
        }

        return (bool) $result;
    }
}
