<?php

namespace Vx\Sendifico\Order;

use Order;
use OrderState;
use Throwable;
use Vx\Sendifico\Api\SendificoApiClient;
use Vx\Sendifico\Api\SendificoApiException;
use Vx\Sendifico\Repository\CarrierMappingRepository;
use Vx\Sendifico\Repository\ShipmentRepository;

final class ShipmentAutomationService
{
    public function __construct(
        private readonly ShipmentPreparationConfigurationProvider $configurationProvider,
        private readonly ShipmentPayloadPreparer $shipmentPayloadPreparer,
        private readonly SendificoApiClient $apiClient,
        private readonly ShipmentRepository $shipmentRepository,
        private readonly ShipmentTraceManager $shipmentTraceManager,
        private readonly CarrierMappingRepository $carrierMappingRepository,
        private readonly ShipmentRateMatcher $shipmentRateMatcher,
        private readonly ShipmentOrderStateService $shipmentOrderStateService
    ) {
    }

    public function processOrder(Order $order, ?OrderState $triggerState = null, string $triggerSource = 'unknown'): void
    {
        if ((int) $order->id <= 0 || (int) $order->id_shop <= 0) {
            return;
        }

        try {
            $configuration = $this->configurationProvider->getShopConfiguration((int) $order->id_shop);
        } catch (Throwable $exception) {
            $this->upsertTrace(
                $order,
                null,
                [
                    'local_state' => ShipmentTraceState::BLOCKED_MISSING_DATA,
                    'last_error_code' => 'configuration_missing',
                    'last_error_message' => $exception->getMessage(),
                ],
                $this->buildEvent($order, 'shipment_configuration_blocked', [
                    'local_state_after' => ShipmentTraceState::BLOCKED_MISSING_DATA,
                    'response_summary' => ['message' => $exception->getMessage()],
                ])
            );

            return;
        }

        if (empty($configuration['auto_purchase_enabled'])) {
            return;
        }

        $mapping = $this->carrierMappingRepository->findOneByShopIdAndCarrierId((int) $order->id_shop, (int) $order->id_carrier);
        if ($mapping === null) {
            return;
        }

        $existingTrace = $this->findTraceForOrder($order);
        if ($existingTrace !== null && ((int) ($existingTrace['is_paid'] ?? 0) === 1 || ($existingTrace['local_state'] ?? null) === ShipmentTraceState::PURCHASED)) {
            return;
        }

        $prepared = $this->shipmentPayloadPreparer->prepareFromOrder($order);
        $shipmentValidationErrors = is_array($prepared['shipment_validation_errors'] ?? null) ? $prepared['shipment_validation_errors'] : [];
        if ($shipmentValidationErrors !== []) {
            $this->upsertTrace(
                $order,
                $existingTrace,
                [
                    'id_carrier' => (int) $mapping['id_carrier'],
                    'id_carrier_reference' => (int) $mapping['id_carrier_reference'],
                    'carrier_token' => (string) $mapping['carrier_token'],
                    'ext_id' => (string) ($prepared['payload']['extId'] ?? ''),
                    'sender_reference' => (string) ($configuration['sender_reference'] ?? ''),
                    'recipient_territory_base_id' => (string) ($prepared['payload']['recipientTerritoryBaseId'] ?? ''),
                    'request_snapshot' => $prepared['payload'],
                    'response_snapshot' => null,
                    'local_state' => ShipmentTraceState::BLOCKED_MISSING_DATA,
                    'last_error_code' => (string) ($shipmentValidationErrors[0]['code'] ?? 'shipment_validation_failed'),
                    'last_error_message' => $this->implodeValidationMessages($shipmentValidationErrors),
                ],
                $this->buildEvent($order, 'shipment_validation_failed', [
                    'local_state_after' => ShipmentTraceState::BLOCKED_MISSING_DATA,
                    'payload_summary' => $prepared['payload'],
                    'response_summary' => $shipmentValidationErrors,
                    'is_retryable' => 0,
                ])
            );

            return;
        }

        $trace = $this->upsertTrace(
            $order,
            $existingTrace,
            [
                'id_carrier' => (int) $mapping['id_carrier'],
                'id_carrier_reference' => (int) $mapping['id_carrier_reference'],
                'carrier_token' => (string) $mapping['carrier_token'],
                'ext_id' => (string) ($prepared['payload']['extId'] ?? ''),
                'sender_address_id' => (int) ($prepared['payload']['senderAddressId'] ?? 0),
                'sender_reference' => (string) ($configuration['sender_reference'] ?? ''),
                'recipient_territory_base_id' => (string) ($prepared['payload']['recipientTerritoryBaseId'] ?? ''),
                'currency' => (string) ($prepared['payload']['goodsCurrency'] ?? $configuration['currency']),
                'local_state' => ShipmentTraceState::SHIPMENT_PENDING,
                'last_error_code' => null,
                'last_error_message' => null,
            ],
            $this->buildEvent($order, 'shipment_automation_triggered', [
                'local_state_before' => $existingTrace['local_state'] ?? null,
                'local_state_after' => ShipmentTraceState::SHIPMENT_PENDING,
                'payload_summary' => [
                    'trigger_source' => $triggerSource,
                    'trigger_state_id' => $triggerState !== null ? (int) $triggerState->id : null,
                    'trigger_state_name' => $triggerState !== null ? $triggerState->name : null,
                ],
            ])
        );

        if ((int) ($trace['remote_shipment_id'] ?? 0) <= 0) {
            $trace = $this->createShipment($order, $configuration, $prepared['payload'], $trace);
        }

        if ((int) ($trace['remote_shipment_id'] ?? 0) <= 0) {
            return;
        }

        if ((int) ($trace['is_paid'] ?? 0) === 1) {
            return;
        }

        $this->purchaseShipment($order, $configuration, $trace);
    }

    /**
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $trace
     *
     * @return array<string, mixed>
     */
    private function createShipment(Order $order, array $configuration, array $payload, array $trace): array
    {
        $requestPayload = [
            'extId' => $payload['extId'],
            'senderAddressId' => $payload['senderAddressId'],
            'recipientAddress' => $payload['recipientAddress'],
            'parcel' => $payload['parcel'],
            'contents' => $payload['contents'],
            'goodsCollection' => $payload['goodsCollection'],
            'goodsInsured' => $payload['goodsInsured'],
            'goodsCurrency' => $payload['goodsCurrency'],
        ];

        try {
            $response = $this->apiClient->createShipment($configuration, $requestPayload);

            $this->shipmentTraceManager->updateShipmentTrace((int) $trace['id_vx_sendifico_shipment'], [
                'remote_shipment_id' => isset($response['shipmentId']) ? (int) $response['shipmentId'] : null,
                'ext_id' => (string) ($response['extId'] ?? $payload['extId']),
                'request_snapshot' => $requestPayload,
                'response_snapshot' => $response,
                'remote_status' => (string) ($response['status'] ?? ''),
                'is_paid' => !empty($response['isPaid']) ? 1 : 0,
                'local_state' => !empty($response['isPaid']) ? ShipmentTraceState::PURCHASED : ShipmentTraceState::SHIPMENT_CREATED,
                'last_error_code' => null,
                'last_error_message' => null,
            ], $this->buildEvent($order, 'shipment_create', [
                'endpoint' => '/shipment',
                'http_method' => 'POST',
                'http_status' => 201,
                'local_state_before' => $trace['local_state'] ?? ShipmentTraceState::SHIPMENT_PENDING,
                'local_state_after' => !empty($response['isPaid']) ? ShipmentTraceState::PURCHASED : ShipmentTraceState::SHIPMENT_CREATED,
                'payload_summary' => $requestPayload,
                'response_summary' => $this->summarizeShipmentResponse($response),
            ]));
        } catch (SendificoApiException $exception) {
            $localState = $exception->getCode() === 409
                ? ShipmentTraceState::RECONCILIATION_REQUIRED
                : ShipmentTraceState::SHIPMENT_PENDING;

            $this->shipmentTraceManager->updateShipmentTrace((int) $trace['id_vx_sendifico_shipment'], [
                'request_snapshot' => $requestPayload,
                'local_state' => $localState,
                'last_error_code' => $exception->getRemoteMessageCode() ?? 'shipment_create_failed',
                'last_error_message' => $exception->getMessage(),
            ], $this->buildEvent($order, 'shipment_create_failed', [
                'endpoint' => '/shipment',
                'http_method' => 'POST',
                'http_status' => $exception->getCode() > 0 ? (int) $exception->getCode() : null,
                'remote_message_code' => $exception->getRemoteMessageCode(),
                'local_state_before' => $trace['local_state'] ?? ShipmentTraceState::SHIPMENT_PENDING,
                'local_state_after' => $localState,
                'payload_summary' => $requestPayload,
                'response_summary' => $exception->getResponsePayload(),
                'is_retryable' => $localState !== ShipmentTraceState::RECONCILIATION_REQUIRED ? 1 : 0,
            ]));
        }

        return $this->shipmentRepository->findByOrderId((int) $order->id) ?? $trace;
    }

    /**
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $trace
     */
    private function purchaseShipment(Order $order, array $configuration, array $trace): void
    {
        $snapshot = $this->decodeSnapshot($trace['response_snapshot'] ?? null);
        $rates = is_array($snapshot['rates'] ?? null) ? $snapshot['rates'] : [];

        $selectedRateId = (int) ($trace['selected_rate_id'] ?? 0);
        if ($selectedRateId <= 0) {
            $matchedRate = $this->shipmentRateMatcher->match(
                $rates,
                (string) ($trace['carrier_token'] ?? ''),
                isset($trace['quoted_price_total']) ? (float) $trace['quoted_price_total'] : null
            );

            if ($matchedRate === null || !isset($matchedRate['rateId'])) {
                $this->shipmentTraceManager->updateShipmentTrace((int) $trace['id_vx_sendifico_shipment'], [
                    'local_state' => ShipmentTraceState::RATE_MISMATCH,
                    'last_error_code' => 'rate_mismatch',
                    'last_error_message' => 'No existe un rate disponible en el shipment remoto para el carrier elegido en checkout.',
                ], $this->buildEvent($order, 'shipment_rate_mismatch', [
                    'local_state_before' => $trace['local_state'] ?? ShipmentTraceState::SHIPMENT_CREATED,
                    'local_state_after' => ShipmentTraceState::RATE_MISMATCH,
                    'payload_summary' => [
                        'carrier_token' => $trace['carrier_token'] ?? null,
                        'quoted_price_total' => $trace['quoted_price_total'] ?? null,
                    ],
                    'response_summary' => $rates,
                    'is_retryable' => 1,
                ]));

                return;
            }

            $selectedRateId = (int) $matchedRate['rateId'];
            $this->shipmentRepository->update((int) $trace['id_vx_sendifico_shipment'], [
                'selected_rate_id' => $selectedRateId,
            ]);
            $trace = $this->shipmentRepository->findByOrderId((int) $order->id) ?? $trace;
        }

        $requestPayload = [
            'preferredRateObjectId' => $selectedRateId,
            'purchaseWith' => (string) ($configuration['purchase_with'] ?? 'walletAvailable'),
        ];

        try {
            $response = $this->apiClient->purchaseShipment($configuration, (int) $trace['remote_shipment_id'], $requestPayload);
            $isPaid = !empty($response['isPaid']);
            $localState = $isPaid ? ShipmentTraceState::PURCHASED : ShipmentTraceState::PURCHASE_FAILED;

            $this->shipmentTraceManager->updateShipmentTrace((int) $trace['id_vx_sendifico_shipment'], [
                'selected_rate_id' => $selectedRateId,
                'request_snapshot' => $requestPayload,
                'response_snapshot' => $response,
                'remote_status' => (string) ($response['status'] ?? ''),
                'is_paid' => $isPaid ? 1 : 0,
                'purchased_price_total' => isset($response['priceTotal']) ? (float) $response['priceTotal'] : null,
                'carrier_token' => (string) ($response['preferredCarrierToken'] ?? $trace['carrier_token']),
                'local_state' => $localState,
                'last_error_code' => null,
                'last_error_message' => null,
            ], $this->buildEvent($order, 'shipment_purchase', [
                'endpoint' => sprintf('/shipment/purchase/%d', (int) $trace['remote_shipment_id']),
                'http_method' => 'PATCH',
                'http_status' => 200,
                'local_state_before' => $trace['local_state'] ?? ShipmentTraceState::SHIPMENT_CREATED,
                'local_state_after' => $localState,
                'payload_summary' => $requestPayload,
                'response_summary' => $this->summarizeShipmentResponse($response),
            ]));

            if (!$isPaid) {
                $this->shipmentOrderStateService->markCourierUnpaid($order);
            }
        } catch (SendificoApiException $exception) {
            $retryCount = (int) ($trace['retry_count'] ?? 0) + 1;

            $this->shipmentTraceManager->updateShipmentTrace((int) $trace['id_vx_sendifico_shipment'], [
                'selected_rate_id' => $selectedRateId,
                'request_snapshot' => $requestPayload,
                'local_state' => ShipmentTraceState::PURCHASE_FAILED,
                'retry_count' => $retryCount,
                'last_error_code' => $exception->getRemoteMessageCode() ?? 'purchase_failed',
                'last_error_message' => $exception->getMessage(),
            ], $this->buildEvent($order, 'shipment_purchase_failed', [
                'endpoint' => sprintf('/shipment/purchase/%d', (int) $trace['remote_shipment_id']),
                'http_method' => 'PATCH',
                'http_status' => $exception->getCode() > 0 ? (int) $exception->getCode() : null,
                'remote_message_code' => $exception->getRemoteMessageCode(),
                'local_state_before' => $trace['local_state'] ?? ShipmentTraceState::SHIPMENT_CREATED,
                'local_state_after' => ShipmentTraceState::PURCHASE_FAILED,
                'payload_summary' => $requestPayload,
                'response_summary' => $exception->getResponsePayload(),
                'is_retryable' => 1,
            ]));

            $this->shipmentOrderStateService->markCourierUnpaid($order);
        }
    }

    /**
     * @param array<string, mixed>|null $existingTrace
     * @param array<string, mixed> $shipmentData
     * @param array<string, mixed> $eventData
     *
     * @return array<string, mixed>
     */
    private function upsertTrace(Order $order, ?array $existingTrace, array $shipmentData, array $eventData): array
    {
        $baseData = [
            'id_shop' => (int) $order->id_shop,
            'id_shop_group' => (int) $order->id_shop_group > 0 ? (int) $order->id_shop_group : null,
            'id_cart' => (int) $order->id_cart > 0 ? (int) $order->id_cart : null,
            'id_order' => (int) $order->id,
            'currency' => $shipmentData['currency'] ?? 'USD',
        ];

        if ($existingTrace !== null) {
            $this->shipmentTraceManager->updateShipmentTrace((int) $existingTrace['id_vx_sendifico_shipment'], array_merge($baseData, $shipmentData), $eventData);

            return $this->shipmentRepository->findByOrderId((int) $order->id) ?? $existingTrace;
        }

        $pendingTrace = (int) $order->id_cart > 0 ? $this->shipmentRepository->findPendingByCartId((int) $order->id_cart) : null;
        if ($pendingTrace !== null) {
            $this->shipmentTraceManager->updateShipmentTrace((int) $pendingTrace['id_vx_sendifico_shipment'], array_merge($baseData, $shipmentData), $eventData);

            return $this->shipmentRepository->findByOrderId((int) $order->id) ?? $pendingTrace;
        }

        $shipmentTraceId = $this->shipmentTraceManager->createShipmentTrace(array_merge($baseData, $shipmentData), $eventData);

        return $this->shipmentRepository->findByOrderId((int) $order->id)
            ?? $this->shipmentRepository->findByExtId((int) $order->id_shop, (string) ($shipmentData['ext_id'] ?? ''))
            ?? ['id_vx_sendifico_shipment' => $shipmentTraceId] + array_merge($baseData, $shipmentData);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findTraceForOrder(Order $order): ?array
    {
        $trace = $this->shipmentRepository->findByOrderId((int) $order->id);
        if ($trace !== null) {
            return $trace;
        }

        $extId = sprintf('ps-%d-order-%d', (int) $order->id_shop, (int) $order->id);

        return $this->shipmentRepository->findByExtId((int) $order->id_shop, $extId);
    }

    /**
     * @param array<int, array<string, string>> $errors
     */
    private function implodeValidationMessages(array $errors): string
    {
        $messages = array_map(static fn (array $error): string => (string) ($error['message'] ?? ''), $errors);
        $messages = array_values(array_filter($messages, static fn (string $message): bool => $message !== ''));

        return implode(' | ', $messages);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function buildEvent(Order $order, string $eventType, array $context = []): array
    {
        return array_merge([
            'id_shop' => (int) $order->id_shop,
            'id_cart' => (int) $order->id_cart > 0 ? (int) $order->id_cart : null,
            'id_order' => (int) $order->id,
            'event_type' => $eventType,
            'is_retryable' => 0,
        ], $context);
    }

    /**
     * @return array<string, mixed>
     */
    private function summarizeShipmentResponse(array $response): array
    {
        return [
            'shipmentId' => $response['shipmentId'] ?? null,
            'extId' => $response['extId'] ?? null,
            'status' => $response['status'] ?? null,
            'isPaid' => $response['isPaid'] ?? null,
            'preferredCarrierToken' => $response['preferredCarrierToken'] ?? null,
            'priceTotal' => $response['priceTotal'] ?? null,
            'rates' => $response['rates'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSnapshot(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
