<?php

namespace Vx\Sendifico\Order;

final class ShipmentTraceState
{
    public const QUOTED = 'quoted';
    public const SHIPMENT_PENDING = 'shipment_pending';
    public const SHIPMENT_CREATED = 'shipment_created';
    public const PURCHASED = 'purchased';
    public const PURCHASE_FAILED = 'purchase_failed';
    public const TRACKING_GENERATED = 'tracking_generated';
    public const LABEL_GENERATED = 'label_generated';
    public const BLOCKED_MISSING_DATA = 'blocked_missing_data';
    public const RECONCILIATION_REQUIRED = 'reconciliation_required';
    public const RATE_MISMATCH = 'rate_mismatch';

    public const RETRYABLE_STATES = [
        self::PURCHASE_FAILED,
        self::RECONCILIATION_REQUIRED,
        self::RATE_MISMATCH,
    ];

    private function __construct()
    {
    }
}
