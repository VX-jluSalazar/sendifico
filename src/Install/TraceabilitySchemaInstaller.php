<?php

namespace Vx\Sendifico\Install;

use Db;

final class TraceabilitySchemaInstaller
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
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vx_sendifico_shipment` (
                `id_vx_sendifico_shipment` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_shop` INT UNSIGNED NOT NULL,
                `id_shop_group` INT UNSIGNED NULL,
                `id_cart` INT UNSIGNED NULL,
                `id_order` INT UNSIGNED NULL,
                `id_carrier` INT UNSIGNED NULL,
                `id_carrier_reference` INT UNSIGNED NULL,
                `remote_shipment_id` BIGINT UNSIGNED NULL,
                `ext_id` VARCHAR(128) NULL,
                `sender_address_id` BIGINT UNSIGNED NULL,
                `sender_reference` VARCHAR(64) NULL,
                `recipient_territory_base_id` VARCHAR(191) NULL,
                `carrier_token` VARCHAR(64) NULL,
                `selected_rate_id` BIGINT UNSIGNED NULL,
                `selected_quotation_id` BIGINT UNSIGNED NULL,
                `quoted_price_total` DECIMAL(20,6) NULL,
                `purchased_price_total` DECIMAL(20,6) NULL,
                `currency` VARCHAR(3) NOT NULL,
                `local_state` VARCHAR(32) NOT NULL,
                `remote_status` VARCHAR(32) NULL,
                `is_paid` TINYINT(1) NOT NULL DEFAULT 0,
                `retry_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `next_retry_at` DATETIME NULL,
                `last_error_code` VARCHAR(191) NULL,
                `last_error_message` TEXT NULL,
                `latest_tracking_number` VARCHAR(128) NULL,
                `latest_tracking_url` TEXT NULL,
                `latest_label_url` TEXT NULL,
                `label_url_expires_at` DATETIME NULL,
                `request_snapshot` LONGTEXT NULL,
                `response_snapshot` LONGTEXT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id_vx_sendifico_shipment`),
                UNIQUE KEY `ux_vx_sendifico_shipment_shop_ext` (`id_shop`, `ext_id`),
                UNIQUE KEY `ux_vx_sendifico_shipment_remote` (`remote_shipment_id`),
                KEY `idx_vx_sendifico_shipment_order` (`id_order`),
                KEY `idx_vx_sendifico_shipment_cart` (`id_cart`),
                KEY `idx_vx_sendifico_shipment_state` (`local_state`),
                KEY `idx_vx_sendifico_shipment_retry` (`next_retry_at`),
                KEY `idx_vx_sendifico_shipment_shop_state` (`id_shop`, `local_state`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vx_sendifico_shipment_event` (
                `id_vx_sendifico_shipment_event` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_vx_sendifico_shipment` INT UNSIGNED NULL,
                `id_shop` INT UNSIGNED NOT NULL,
                `id_cart` INT UNSIGNED NULL,
                `id_order` INT UNSIGNED NULL,
                `event_type` VARCHAR(32) NOT NULL,
                `endpoint` VARCHAR(128) NULL,
                `http_method` VARCHAR(16) NULL,
                `http_status` SMALLINT UNSIGNED NULL,
                `remote_message_code` VARCHAR(191) NULL,
                `local_state_before` VARCHAR(32) NULL,
                `local_state_after` VARCHAR(32) NULL,
                `duration_ms` INT UNSIGNED NULL,
                `payload_summary` LONGTEXT NULL,
                `response_summary` LONGTEXT NULL,
                `is_retryable` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id_vx_sendifico_shipment_event`),
                KEY `idx_vx_sendifico_event_shipment` (`id_vx_sendifico_shipment`),
                KEY `idx_vx_sendifico_event_order` (`id_order`),
                KEY `idx_vx_sendifico_event_type` (`event_type`),
                KEY `idx_vx_sendifico_event_created` (`created_at`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getUninstallQueries(): array
    {
        return [
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'vx_sendifico_shipment_event`',
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'vx_sendifico_shipment`',
        ];
    }
}
