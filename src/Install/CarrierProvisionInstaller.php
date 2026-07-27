<?php

namespace Vx\Sendifico\Install;

use Carrier;
use Db;
use Exception;
use Vx\Sendifico\Carrier\CarrierCatalogProvider;

final class CarrierProvisionInstaller
{
    public function install(): bool
    {
        $shopIds = $this->getActiveShopIds();
        if ($shopIds === []) {
            return true;
        }

        $catalogProvider = new CarrierCatalogProvider();
        foreach ($catalogProvider->getCatalog() as $definition) {
            $carrier = $this->resolveOrCreateCarrier($definition, $shopIds);

            if (!$this->upsertMappings($shopIds, $definition, $carrier)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array{token:string, carrier_name:string, display_name:string, delay:string} $definition
     * @param array<int, int> $shopIds
     *
     * @return array{id_carrier:int, id_reference:int, name:string}
     */
    private function resolveOrCreateCarrier(array $definition, array $shopIds): array
    {
        $existingCarrier = $this->findLatestModuleCarrierByName($definition['carrier_name']);
        if ($existingCarrier !== null) {
            return $existingCarrier;
        }

        $carrier = new Carrier();
        $carrier->name = $definition['carrier_name'];
        $carrier->active = true;
        $carrier->deleted = false;
        $carrier->is_module = true;
        $carrier->external_module_name = 'vx_sendifico';
        $carrier->shipping_external = true;
        $carrier->need_range = false;
        $carrier->range_behavior = false;
        $carrier->shipping_handling = false;
        $carrier->is_free = false;
        $carrier->grade = 0;
        $carrier->position = (int) Carrier::getHigherPosition() + 1;
        $carrier->url = '';
        $carrier->max_width = 0;
        $carrier->max_height = 0;
        $carrier->max_depth = 0;
        $carrier->max_weight = 0;
        $carrier->id_tax_rules_group = 0;
        $carrier->id_shop_list = $shopIds;

        foreach ($this->getLanguageIds() as $languageId) {
            $carrier->delay[$languageId] = $definition['delay'];
        }

        if (!$carrier->add()) {
            throw new Exception(sprintf('No fue posible crear el carrier local para %s.', $definition['display_name']));
        }

        $groupIds = $this->getGroupIds();
        if ($groupIds !== []) {
            $carrier->setGroups($groupIds);
        }

        foreach ($this->getZoneIds() as $zoneId) {
            $carrier->addZone((int) $zoneId);
        }

        $createdCarrier = $this->findCarrierById((int) $carrier->id);
        if ($createdCarrier === null) {
            throw new Exception(sprintf('El carrier local %s fue creado pero no pudo recargarse por id_carrier.', $definition['display_name']));
        }

        return $createdCarrier;
    }

    /**
     * @param array<int, int> $shopIds
     * @param array{token:string, carrier_name:string, display_name:string, delay:string} $definition
     * @param array{id_carrier:int, id_reference:int, name:string} $carrier
     */
    private function upsertMappings(array $shopIds, array $definition, array $carrier): bool
    {
        $now = date('Y-m-d H:i:s');

        foreach ($shopIds as $shopId) {
            $existingMapId = (int) Db::getInstance()->getValue(
                'SELECT id_vx_sendifico_carrier_map
                FROM `' . _DB_PREFIX_ . 'vx_sendifico_carrier_map`
                WHERE id_shop = ' . (int) $shopId . '
                  AND carrier_token = "' . pSQL($definition['token']) . '"'
            );

            $data = [
                'id_shop' => (int) $shopId,
                'id_carrier' => (int) $carrier['id_carrier'],
                'id_carrier_reference' => (int) $carrier['id_reference'],
                'carrier_token' => pSQL($definition['token']),
                'display_name' => pSQL($definition['display_name']),
                'is_active' => 1,
                'provision_source' => pSQL('install'),
                'last_provision_at' => pSQL($now),
                'updated_at' => pSQL($now),
            ];

            if ($existingMapId > 0) {
                if (!Db::getInstance()->update('vx_sendifico_carrier_map', $data, 'id_vx_sendifico_carrier_map = ' . $existingMapId)) {
                    return false;
                }

                continue;
            }

            $data['created_at'] = pSQL($now);
            if (!Db::getInstance()->insert('vx_sendifico_carrier_map', $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, int>
     */
    private function getActiveShopIds(): array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT id_shop
            FROM `' . _DB_PREFIX_ . 'shop`
            WHERE active = 1
            ORDER BY id_shop ASC'
        );

        return array_map(static fn (array $row): int => (int) $row['id_shop'], is_array($rows) ? $rows : []);
    }

    /**
     * @return array{id_carrier:int, id_reference:int, name:string}|null
     */
    private function findLatestModuleCarrierByName(string $name): ?array
    {
        $row = Db::getInstance()->getRow(
            'SELECT id_carrier, id_reference, name
            FROM `' . _DB_PREFIX_ . 'carrier`
            WHERE external_module_name = "vx_sendifico"
              AND name = "' . pSQL($name) . '"
              AND deleted = 0
            ORDER BY id_carrier DESC'
        );

        return is_array($row) ? [
            'id_carrier' => (int) $row['id_carrier'],
            'id_reference' => (int) $row['id_reference'],
            'name' => (string) $row['name'],
        ] : null;
    }

    /**
     * @return array{id_carrier:int, id_reference:int, name:string}|null
     */
    private function findCarrierById(int $idCarrier): ?array
    {
        $row = Db::getInstance()->getRow(
            'SELECT id_carrier, id_reference, name
            FROM `' . _DB_PREFIX_ . 'carrier`
            WHERE id_carrier = ' . (int) $idCarrier
        );

        return is_array($row) ? [
            'id_carrier' => (int) $row['id_carrier'],
            'id_reference' => (int) $row['id_reference'],
            'name' => (string) $row['name'],
        ] : null;
    }

    /**
     * @return array<int, int>
     */
    private function getLanguageIds(): array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT id_lang
            FROM `' . _DB_PREFIX_ . 'lang`
            WHERE active = 1
            ORDER BY id_lang ASC'
        );

        return array_map(static fn (array $row): int => (int) $row['id_lang'], is_array($rows) ? $rows : []);
    }

    /**
     * @return array<int, int>
     */
    private function getGroupIds(): array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT id_group
            FROM `' . _DB_PREFIX_ . 'group`
            ORDER BY id_group ASC'
        );

        return array_map(static fn (array $row): int => (int) $row['id_group'], is_array($rows) ? $rows : []);
    }

    /**
     * @return array<int, int>
     */
    private function getZoneIds(): array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT id_zone
            FROM `' . _DB_PREFIX_ . 'zone`
            WHERE active = 1
            ORDER BY id_zone ASC'
        );

        return array_map(static fn (array $row): int => (int) $row['id_zone'], is_array($rows) ? $rows : []);
    }
}
