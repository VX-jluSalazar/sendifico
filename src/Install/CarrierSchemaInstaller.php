<?php

namespace Vx\Sendifico\Install;

use Db;

final class CarrierSchemaInstaller
{
    public function install(): bool
    {
        foreach ($this->getInstallQueries() as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    public function uninstall(): bool
    {
        foreach ($this->getUninstallQueries() as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function getInstallQueries(): array
    {
        return [
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vx_sendifico_carrier_map` (
                `id_vx_sendifico_carrier_map` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_shop` INT UNSIGNED NOT NULL,
                `id_carrier` INT UNSIGNED NOT NULL,
                `id_carrier_reference` INT UNSIGNED NOT NULL,
                `carrier_token` VARCHAR(64) NOT NULL,
                `display_name` VARCHAR(191) NOT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `provision_source` VARCHAR(32) NOT NULL,
                `last_provision_at` DATETIME NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id_vx_sendifico_carrier_map`),
                UNIQUE KEY `ux_vx_sendifico_carrier_shop_token` (`id_shop`, `carrier_token`),
                UNIQUE KEY `ux_vx_sendifico_carrier_shop_reference` (`id_shop`, `id_carrier_reference`),
                KEY `idx_vx_sendifico_carrier_shop` (`id_shop`),
                KEY `idx_vx_sendifico_carrier_reference` (`id_carrier_reference`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getUninstallQueries(): array
    {
        return [
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'vx_sendifico_carrier_map`',
        ];
    }
}
