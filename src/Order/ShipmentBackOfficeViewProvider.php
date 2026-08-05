<?php

namespace Vx\Sendifico\Order;

use Vx\Sendifico\Repository\ShipmentEventRepository;
use Vx\Sendifico\Repository\ShipmentRepository;

final class ShipmentBackOfficeViewProvider
{
    public function __construct(
        private readonly ShipmentRepository $shipmentRepository,
        private readonly ShipmentEventRepository $shipmentEventRepository
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getOrderOverview(int $orderId): ?array
    {
        $trace = $this->shipmentRepository->findByOrderId($orderId);
        if ($trace === null) {
            return null;
        }

        return $this->buildOverview($trace, 15);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTraceOverview(int $shipmentTraceId): ?array
    {
        $trace = $this->shipmentRepository->findById($shipmentTraceId);
        if ($trace === null) {
            return null;
        }

        return $this->buildOverview($trace, 25);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function getListing(array $filters, int $page = 1, int $limit = 20): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $total = $this->shipmentRepository->countForAdmin($filters);
        $rows = $this->shipmentRepository->searchForAdmin($filters, $page, $limit);

        return [
            'filters' => $filters,
            'rows' => array_map(fn (array $row): array => $this->normalizeTrace($row), $rows),
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'page_count' => max(1, (int) ceil($total / $limit)),
        ];
    }

    /**
     * @param array<string, mixed> $trace
     *
     * @return array<string, mixed>
     */
    private function buildOverview(array $trace, int $eventLimit): array
    {
        $normalizedTrace = $this->normalizeTrace($trace);
        $events = $this->shipmentEventRepository->findByShipmentTraceId((int) $trace['id_vx_sendifico_shipment'], $eventLimit);

        return [
            'trace' => $normalizedTrace,
            'events' => array_map(fn (array $event): array => $this->normalizeEvent($event), $events),
        ];
    }

    /**
     * @param array<string, mixed> $trace
     *
     * @return array<string, mixed>
     */
    private function normalizeTrace(array $trace): array
    {
        $responseSnapshot = $this->decodeJson($trace['response_snapshot'] ?? null);
        $requestSnapshot = $this->decodeJson($trace['request_snapshot'] ?? null);
        $labelExpiresAt = $this->normalizeDateString($trace['label_url_expires_at'] ?? null);

        return [
            'id_vx_sendifico_shipment' => (int) ($trace['id_vx_sendifico_shipment'] ?? 0),
            'id_shop' => isset($trace['id_shop']) ? (int) $trace['id_shop'] : null,
            'id_cart' => isset($trace['id_cart']) ? (int) $trace['id_cart'] : null,
            'id_order' => isset($trace['id_order']) ? (int) $trace['id_order'] : null,
            'id_carrier' => isset($trace['id_carrier']) ? (int) $trace['id_carrier'] : null,
            'remote_shipment_id' => isset($trace['remote_shipment_id']) ? (int) $trace['remote_shipment_id'] : null,
            'ext_id' => (string) ($trace['ext_id'] ?? ''),
            'carrier_token' => (string) ($trace['carrier_token'] ?? ''),
            'selected_rate_id' => isset($trace['selected_rate_id']) ? (int) $trace['selected_rate_id'] : null,
            'quoted_price_total' => isset($trace['quoted_price_total']) ? (float) $trace['quoted_price_total'] : null,
            'purchased_price_total' => isset($trace['purchased_price_total']) ? (float) $trace['purchased_price_total'] : null,
            'currency' => (string) ($trace['currency'] ?? ''),
            'local_state' => (string) ($trace['local_state'] ?? ''),
            'remote_status' => (string) ($trace['remote_status'] ?? ''),
            'is_paid' => (int) ($trace['is_paid'] ?? 0) === 1,
            'retry_count' => (int) ($trace['retry_count'] ?? 0),
            'last_error_code' => (string) ($trace['last_error_code'] ?? ''),
            'last_error_message' => (string) ($trace['last_error_message'] ?? ''),
            'latest_tracking_number' => (string) ($trace['latest_tracking_number'] ?? ''),
            'latest_tracking_url' => (string) ($trace['latest_tracking_url'] ?? ''),
            'latest_label_url' => (string) ($trace['latest_label_url'] ?? ''),
            'label_url_expires_at' => $labelExpiresAt,
            'label_url_is_active' => $labelExpiresAt !== null && strtotime($labelExpiresAt) !== false && strtotime($labelExpiresAt) > time(),
            'recipient_territory_base_id' => (string) ($trace['recipient_territory_base_id'] ?? ''),
            'request_snapshot' => $requestSnapshot,
            'response_snapshot' => $responseSnapshot,
            'updated_at' => (string) ($trace['updated_at'] ?? ''),
            'created_at' => (string) ($trace['created_at'] ?? ''),
            'actions' => [
                'can_retry_purchase' => $this->canRetryPurchase($trace),
                'can_generate_tracking' => $this->canGenerateTracking($trace),
                'can_generate_label' => $this->canGenerateLabel($trace),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $event
     *
     * @return array<string, mixed>
     */
    private function normalizeEvent(array $event): array
    {
        return [
            'event_type' => (string) ($event['event_type'] ?? ''),
            'endpoint' => (string) ($event['endpoint'] ?? ''),
            'http_method' => (string) ($event['http_method'] ?? ''),
            'http_status' => isset($event['http_status']) ? (int) $event['http_status'] : null,
            'remote_message_code' => (string) ($event['remote_message_code'] ?? ''),
            'local_state_before' => (string) ($event['local_state_before'] ?? ''),
            'local_state_after' => (string) ($event['local_state_after'] ?? ''),
            'is_retryable' => (int) ($event['is_retryable'] ?? 0) === 1,
            'payload_summary' => $this->decodeJson($event['payload_summary'] ?? null),
            'response_summary' => $this->decodeJson($event['response_summary'] ?? null),
            'created_at' => (string) ($event['created_at'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $trace
     */
    private function canRetryPurchase(array $trace): bool
    {
        if ((int) ($trace['id_order'] ?? 0) <= 0 || (int) ($trace['is_paid'] ?? 0) === 1) {
            return false;
        }

        return in_array((string) ($trace['local_state'] ?? ''), [
            ShipmentTraceState::QUOTED,
            ShipmentTraceState::SHIPMENT_PENDING,
            ShipmentTraceState::SHIPMENT_CREATED,
            ShipmentTraceState::PURCHASE_FAILED,
            ShipmentTraceState::RATE_MISMATCH,
            ShipmentTraceState::RECONCILIATION_REQUIRED,
        ], true);
    }

    /**
     * @param array<string, mixed> $trace
     */
    private function canGenerateTracking(array $trace): bool
    {
        return (int) ($trace['remote_shipment_id'] ?? 0) > 0
            && (int) ($trace['is_paid'] ?? 0) === 1;
    }

    /**
     * @param array<string, mixed> $trace
     */
    private function canGenerateLabel(array $trace): bool
    {
        if ((int) ($trace['remote_shipment_id'] ?? 0) <= 0) {
            return false;
        }

        if (trim((string) ($trace['latest_tracking_number'] ?? '')) !== '') {
            return true;
        }

        return in_array((string) ($trace['local_state'] ?? ''), [
            ShipmentTraceState::TRACKING_GENERATED,
            ShipmentTraceState::LABEL_GENERATED,
        ], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeDateString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
