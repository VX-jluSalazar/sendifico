<?php

namespace Vx\Sendifico\Install;

use Db;

final class CacheSchemaInstaller
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
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vx_sendifico_territory` (
                `id_vx_sendifico_territory` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `country_code` VARCHAR(2) NOT NULL,
                `territory_base_id` VARCHAR(191) NOT NULL,
                `territory1_name` VARCHAR(128) NOT NULL,
                `territory2_name` VARCHAR(128) NOT NULL,
                `territory3_name` VARCHAR(128) NOT NULL,
                `searchable_text` VARCHAR(255) NOT NULL,
                `synced_at` DATETIME NOT NULL,
                PRIMARY KEY (`id_vx_sendifico_territory`),
                UNIQUE KEY `ux_vx_sendifico_territory_base` (`territory_base_id`),
                KEY `idx_vx_sendifico_territory_country` (`country_code`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vx_sendifico_sender_address` (
                `id_vx_sendifico_sender_address` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_shop` INT UNSIGNED NOT NULL,
                `remote_address_id` BIGINT UNSIGNED NOT NULL,
                `address_type` VARCHAR(16) NOT NULL,
                `full_name` VARCHAR(191) NOT NULL,
                `company` VARCHAR(191) NULL,
                `email` VARCHAR(191) NULL,
                `street_line1` VARCHAR(191) NOT NULL,
                `reference` VARCHAR(255) NULL,
                `territory_base_id` VARCHAR(191) NOT NULL,
                `country_code` VARCHAR(2) NOT NULL,
                `zip_code` VARCHAR(32) NULL,
                `lat` DECIMAL(10,7) NULL,
                `lng` DECIMAL(10,7) NULL,
                `phone` VARCHAR(32) NOT NULL,
                `object_created` DATETIME NULL,
                `synced_at` DATETIME NOT NULL,
                PRIMARY KEY (`id_vx_sendifico_sender_address`),
                UNIQUE KEY `ux_vx_sendifico_sender_shop_remote` (`id_shop`, `remote_address_id`),
                KEY `idx_vx_sendifico_sender_shop` (`id_shop`),
                KEY `idx_vx_sendifico_sender_territory` (`territory_base_id`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vx_sendifico_sync_meta` (
                `id_vx_sendifico_sync_meta` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `sync_type` VARCHAR(32) NOT NULL,
                `scope_key` VARCHAR(64) NOT NULL,
                `id_shop` INT UNSIGNED NULL,
                `country_code` VARCHAR(2) NULL,
                `last_attempt_at` DATETIME NOT NULL,
                `last_success_at` DATETIME NULL,
                `status` VARCHAR(16) NOT NULL,
                `item_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `error_message` TEXT NULL,
                `api_version` VARCHAR(16) NULL,
                PRIMARY KEY (`id_vx_sendifico_sync_meta`),
                UNIQUE KEY `ux_vx_sendifico_sync_scope` (`sync_type`, `scope_key`),
                KEY `idx_vx_sendifico_sync_shop` (`id_shop`),
                KEY `idx_vx_sendifico_sync_country` (`country_code`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getUninstallQueries(): array
    {
        return [
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'vx_sendifico_sync_meta`',
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'vx_sendifico_sender_address`',
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'vx_sendifico_territory`',
        ];
    }
}
