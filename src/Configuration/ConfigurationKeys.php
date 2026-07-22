<?php

namespace Vx\Sendifico\Configuration;

final class ConfigurationKeys
{
    public const API_KEY = 'VX_SENDIFICO_API_KEY';
    public const API_VERSION = 'VX_SENDIFICO_API_VERSION';
    public const COUNTRY = 'VX_SENDIFICO_COUNTRY';
    public const CURRENCY = 'VX_SENDIFICO_CURRENCY';
    public const COD_PAYMENT_METHODS = 'VX_SENDIFICO_COD_PAYMENT_METHODS';
    public const SENDER_REFERENCE = 'VX_SENDIFICO_SENDER_REFERENCE';
    public const PURCHASE_WITH = 'VX_SENDIFICO_PURCHASE_WITH';
    public const AUTO_PURCHASE_ENABLED = 'VX_SENDIFICO_AUTO_PURCHASE_ENABLED';
    public const ALLOW_INCOMPLETE_CHECKOUT_ADDRESS = 'VX_SENDIFICO_ALLOW_INCOMPLETE_CHECKOUT_ADDRESS';
    public const ENABLE_DEBUG_LOGS = 'VX_SENDIFICO_ENABLE_DEBUG_LOGS';
    public const LOG_RETENTION_DAYS = 'VX_SENDIFICO_LOG_RETENTION_DAYS';
    public const DEFAULT_WEIGHT = 'VX_SENDIFICO_DEFAULT_WEIGHT';
    public const DEFAULT_LENGTH = 'VX_SENDIFICO_DEFAULT_LENGTH';
    public const DEFAULT_WIDTH = 'VX_SENDIFICO_DEFAULT_WIDTH';
    public const DEFAULT_HEIGHT = 'VX_SENDIFICO_DEFAULT_HEIGHT';

    public const DEFAULTS = [
        self::API_KEY => '',
        self::API_VERSION => '2026-01-01',
        self::COUNTRY => 'EC',
        self::CURRENCY => 'USD',
        self::COD_PAYMENT_METHODS => '',
        self::SENDER_REFERENCE => '',
        self::PURCHASE_WITH => 'walletAvailable',
        self::AUTO_PURCHASE_ENABLED => true,
        self::ALLOW_INCOMPLETE_CHECKOUT_ADDRESS => true,
        self::ENABLE_DEBUG_LOGS => false,
        self::LOG_RETENTION_DAYS => 30,
        self::DEFAULT_WEIGHT => '1.000',
        self::DEFAULT_LENGTH => '10.000',
        self::DEFAULT_WIDTH => '10.000',
        self::DEFAULT_HEIGHT => '10.000',
    ];

    public const PURCHASE_WITH_CHOICES = [
        'walletAvailable',
        'cash',
    ];

    private function __construct()
    {
    }
}
