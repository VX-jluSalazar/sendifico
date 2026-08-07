<?php

namespace Vx\Sendifico\Install;

use Configuration;
use Db;
use Shop;
use Vx\Sendifico\Configuration\ConfigurationKeys;

final class ShopDataDuplicator
{
    public function duplicate(int $sourceShopId, int $targetShopId): bool
    {
        if ($sourceShopId <= 0 || $targetShopId <= 0 || $sourceShopId === $targetShopId) {
            return true;
        }

        return $this->duplicateConfiguration($sourceShopId, $targetShopId)
            && $this->duplicateCarrierMappings($sourceShopId, $targetShopId)
            && $this->duplicateSenderCache($sourceShopId, $targetShopId)
            && (new CarrierProvisionInstaller())->install();
    }

    private function duplicateConfiguration(int $sourceShopId, int $targetShopId): bool
    {
        $targetGroupId = (int) Shop::getGroupFromShop($targetShopId, true);
        $result = true;

        foreach (ConfigurationKeys::DEFAULTS as $key => $defaultValue) {
            $value = Configuration::get($key, null, null, $sourceShopId);
            if ($value === false || $value === null) {
                $value = $defaultValue;
            }

            $result = Configuration::updateValue($key, $value, false, $targetGroupId, $targetShopId) && $result;
        }

        return (bool) $result;
    }

    private function duplicateCarrierMappings(int $sourceShopId, int $targetShopId): bool
    {
        if (!$this->tableExists('vx_sendifico_carrier_map')) {
            return true;
        }

        Db::getInstance()->delete('vx_sendifico_carrier_map', 'id_shop = ' . (int) $targetShopId);

        return (bool) Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'vx_sendifico_carrier_map`
                (id_shop, id_carrier, id_carrier_reference, carrier_token, display_name, is_active, provision_source, last_provision_at, created_at, updated_at)
             SELECT ' . (int) $targetShopId . ', id_carrier, id_carrier_reference, carrier_token, display_name, is_active, "shop_duplication", NOW(), NOW(), NOW()
             FROM `' . _DB_PREFIX_ . 'vx_sendifico_carrier_map`
             WHERE id_shop = ' . (int) $sourceShopId
        );
    }

    private function duplicateSenderCache(int $sourceShopId, int $targetShopId): bool
    {
        if (!$this->tableExists('vx_sendifico_sender_address')) {
            return true;
        }

        Db::getInstance()->delete('vx_sendifico_sender_address', 'id_shop = ' . (int) $targetShopId);

        return (bool) Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'vx_sendifico_sender_address`
                (id_shop, remote_address_id, address_type, full_name, company, email, street_line1, reference, territory_base_id, country_code, zip_code, lat, lng, phone, object_created, synced_at)
             SELECT ' . (int) $targetShopId . ', remote_address_id, address_type, full_name, company, email, street_line1, reference, territory_base_id, country_code, zip_code, lat, lng, phone, object_created, synced_at
             FROM `' . _DB_PREFIX_ . 'vx_sendifico_sender_address`
             WHERE id_shop = ' . (int) $sourceShopId
        );
    }

    private function tableExists(string $tableName): bool
    {
        return (bool) Db::getInstance()->executeS('SHOW TABLES LIKE "' . pSQL(_DB_PREFIX_ . $tableName) . '"');
    }
}
