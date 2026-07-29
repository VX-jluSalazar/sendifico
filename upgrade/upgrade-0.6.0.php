<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_6_0($module): bool
{
    $queries = [
        'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vx_sendifico_address_meta` (
            `id_vx_sendifico_address_meta` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_address` INT UNSIGNED NOT NULL,
            `id_shop` INT UNSIGNED NOT NULL,
            `country_code` VARCHAR(2) NOT NULL,
            `territory_base_id` VARCHAR(191) NOT NULL,
            `territory1_name` VARCHAR(128) NOT NULL,
            `territory2_name` VARCHAR(128) NOT NULL,
            `territory3_name` VARCHAR(128) NOT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_vx_sendifico_address_meta`),
            UNIQUE KEY `ux_vx_sendifico_address_meta_address` (`id_address`),
            KEY `idx_vx_sendifico_address_meta_shop` (`id_shop`),
            KEY `idx_vx_sendifico_address_meta_territory` (`territory_base_id`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    ];

    foreach ($queries as $query) {
        if (!Db::getInstance()->execute($query)) {
            return false;
        }
    }

    return (bool) $module->registerHook([
        'displayHeader',
        'actionFilterDeliveryOptionList',
        'actionCarrierProcess',
        'actionValidateStepComplete',
        'additionalCustomerAddressFields',
        'actionValidateCustomerAddressForm',
        'actionObjectAddressAddAfter',
        'actionObjectAddressUpdateAfter',
        'actionObjectAddressDeleteAfter',
    ]);
}
