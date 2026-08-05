<?php

namespace Vx\Sendifico\Order;

use DateTimeImmutable;
use Order;
use Throwable;
use Vx\Sendifico\Api\SendificoApiClient;
use Vx\Sendifico\Api\SendificoApiException;
use Vx\Sendifico\Repository\ShipmentRepository;

final class ShipmentBackOfficeService
{
    public function __construct(
        private readonly ShipmentRepository $shipmentRepository,
        private readonly ShipmentTraceManager $shipmentTraceManager,
        private readonly ShipmentAutomationService $shipmentAutomationService,
        private readonly ShipmentPreparationConfigurationProvider $configurationProvider,
        private readonly SendificoApiClient $apiClient
    ) {
    }

    /**
     * @return array{status:string,message:string,trace_id:int}
     */
    public function retryPurchase(int $shipmentTraceId): array
    {
        $trace = $this->shipmentRepository->findById($shipmentTraceId);
        if ($trace === null) {
            return $this->result('error', 'No existe la traza del shipment solicitada.', $shipmentTraceId);
        }

        $order = $this->loadOrder($trace);
        if (!$order instanceof Order) {
            return $this->result('error', 'La traza no esta asociada a un pedido valido para reintentar purchase.', $shipmentTraceId);
        }

        $this->shipmentAutomationService->processOrder($order, null, 'bo_manual_retry_purchase');
        $updatedTrace = $this->shipmentRepository->findById($shipmentTraceId)
            ?? $this->shipmentRepository->findByOrderId((int) $order->id)
            ?? $trace;

        if ((int) ($updatedTrace['is_paid'] ?? 0) === 1 || ($updatedTrace['local_state'] ?? null) === ShipmentTraceState::PURCHASED) {
            return $this->result('success', 'Purchase reintentado correctamente y shipment marcado como pagado.', (int) ($updatedTrace['id_vx_sendifico_shipment'] ?? $shipmentTraceId));
        }

        $lastError = trim((string) ($updatedTrace['last_error_message'] ?? ''));
        if ($lastError !== '') {
            return $this->result('warning', 'El reintento se ejecuto, pero el shipment sigue sin completarse: ' . $lastError, (int) ($updatedTrace['id_vx_sendifico_shipment'] ?? $shipmentTraceId));
        }

        return $this->result('warning', 'El reintento se ejecuto, pero el shipment no quedo pagado.', (int) ($updatedTrace['id_vx_sendifico_shipment'] ?? $shipmentTraceId));
    }

    /**
     * @return array{status:string,message:string,trace_id:int}
     */
    public function generateTrackingNumber(int $shipmentTraceId): array
    {
        $trace = $this->shipmentRepository->findById($shipmentTraceId);
        if ($trace === null) {
            return $this->result('error', 'No existe la traza del shipment solicitada.', $shipmentTraceId);
        }

        if ((int) ($trace['remote_shipment_id'] ?? 0) <= 0) {
            return $this->result('error', 'La traza no tiene remote_shipment_id para generar tracking.', $shipmentTraceId);
        }

        try {
            $configuration = $this->configurationProvider->getShopConfiguration((int) $trace['id_shop']);
            $response = $this->apiClient->generateTrackingNumber($configuration, (int) $trace['remote_shipment_id']);
            $trackingNumber = trim((string) ($response['trackingNumber'] ?? ''));

            if ($trackingNumber === '') {
                throw new \RuntimeException('Sendifico no devolvio trackingNumber despues de generateTrackingNumber.');
            }

            $localState = ShipmentTraceState::TRACKING_GENERATED;
            $this->shipmentTraceManager->updateShipmentTrace((int) $trace['id_vx_sendifico_shipment'], [
                'response_snapshot' => $response,
                'remote_status' => (string) ($response['status'] ?? $trace['remote_status']),
                'is_paid' => !empty($response['isPaid']) ? 1 : (int) ($trace['is_paid'] ?? 0),
                'carrier_token' => (string) ($response['preferredCarrierToken'] ?? $trace['carrier_token']),
                'purchased_price_total' => isset($response['priceTotal']) ? (float) $response['priceTotal'] : $trace['purchased_price_total'],
                'latest_tracking_number' => $trackingNumber,
                'latest_tracking_url' => (string) ($response['trackingCarrierUrl'] ?? ''),
                'local_state' => $localState,
                'last_error_code' => null,
                'last_error_message' => null,
            ], $this->buildEvent($trace, 'shipment_generate_tracking', [
                'endpoint' => sprintf('/shipment/generateTrackingNumber/%d', (int) $trace['remote_shipment_id']),
                'http_method' => 'PATCH',
                'http_status' => 200,
                'local_state_before' => $trace['local_state'] ?? null,
                'local_state_after' => $localState,
                'response_summary' => $this->summarizeShipmentResponse($response),
            ]));

            return $this->result('success', sprintf('Tracking generado correctamente: %s', $trackingNumber), $shipmentTraceId);
        } catch (SendificoApiException $exception) {
            $this->shipmentTraceManager->updateShipmentTrace((int) $trace['id_vx_sendifico_shipment'], [
                'last_error_code' => $exception->getRemoteMessageCode() ?? 'tracking_generate_failed',
                'last_error_message' => $exception->getMessage(),
            ], $this->buildEvent($trace, 'shipment_generate_tracking_failed', [
                'endpoint' => sprintf('/shipment/generateTrackingNumber/%d', (int) $trace['remote_shipment_id']),
                'http_method' => 'PATCH',
                'http_status' => $exception->getCode() > 0 ? (int) $exception->getCode() : null,
                'remote_message_code' => $exception->getRemoteMessageCode(),
                'local_state_before' => $trace['local_state'] ?? null,
                'local_state_after' => $trace['local_state'] ?? null,
                'response_summary' => $exception->getResponsePayload(),
            ]));

            return $this->result('warning', 'No fue posible generar tracking: ' . $exception->getMessage(), $shipmentTraceId);
        } catch (Throwable $exception) {
            $this->shipmentTraceManager->updateShipmentTrace((int) $trace['id_vx_sendifico_shipment'], [
                'last_error_code' => 'tracking_generate_failed',
                'last_error_message' => $exception->getMessage(),
            ], $this->buildEvent($trace, 'shipment_generate_tracking_failed', [
                'endpoint' => sprintf('/shipment/generateTrackingNumber/%d', (int) $trace['remote_shipment_id']),
                'http_method' => 'PATCH',
                'local_state_before' => $trace['local_state'] ?? null,
                'local_state_after' => $trace['local_state'] ?? null,
                'response_summary' => ['message' => $exception->getMessage()],
            ]));

            return $this->result('warning', 'No fue posible generar tracking: ' . $exception->getMessage(), $shipmentTraceId);
        }
    }

    /**
     * @return array{status:string,message:string,trace_id:int}
     */
    public function generateLabelUrl(int $shipmentTraceId): array
    {
        $trace = $this->shipmentRepository->findById($shipmentTraceId);
        if ($trace === null) {
            return $this->result('error', 'No existe la traza del shipment solicitada.', $shipmentTraceId);
        }

        if ((int) ($trace['remote_shipment_id'] ?? 0) <= 0) {
            return $this->result('error', 'La traza no tiene remote_shipment_id para generar label.', $shipmentTraceId);
        }

        $requestPayload = [
            'type' => 'carrierDefault',
            'disposition' => 'inline',
        ];

        try {
            $configuration = $this->configurationProvider->getShopConfiguration((int) $trace['id_shop']);
            $response = $this->apiClient->generateLabelUrl($configuration, (int) $trace['remote_shipment_id'], $requestPayload);
            $downloadUrl = trim((string) ($response['downloadUrl'] ?? ''));
            if ($downloadUrl === '') {
                throw new \RuntimeException('Sendifico no devolvio downloadUrl despues de generateLabelUrl.');
            }

            $this->shipmentTraceManager->updateShipmentTrace((int) $trace['id_vx_sendifico_shipment'], [
                'latest_label_url' => $downloadUrl,
                'label_url_expires_at' => $this->normalizeExpiresAt($response['expiresAt'] ?? null),
                'local_state' => ShipmentTraceState::LABEL_GENERATED,
                'last_error_code' => null,
                'last_error_message' => null,
            ], $this->buildEvent($trace, 'shipment_generate_label', [
                'endpoint' => sprintf('/shipment/generateLabelUrl/%d', (int) $trace['remote_shipment_id']),
                'http_method' => 'POST',
                'http_status' => 200,
                'local_state_before' => $trace['local_state'] ?? null,
                'local_state_after' => ShipmentTraceState::LABEL_GENERATED,
                'payload_summary' => $requestPayload,
                'response_summary' => $response,
            ]));

            return $this->result('success', 'Label generada correctamente. El enlace temporal ya esta disponible.', $shipmentTraceId);
        } catch (SendificoApiException $exception) {
            $this->shipmentTraceManager->updateShipmentTrace((int) $trace['id_vx_sendifico_shipment'], [
                'last_error_code' => $exception->getRemoteMessageCode() ?? 'label_generate_failed',
                'last_error_message' => $exception->getMessage(),
            ], $this->buildEvent($trace, 'shipment_generate_label_failed', [
                'endpoint' => sprintf('/shipment/generateLabelUrl/%d', (int) $trace['remote_shipment_id']),
                'http_method' => 'POST',
                'http_status' => $exception->getCode() > 0 ? (int) $exception->getCode() : null,
                'remote_message_code' => $exception->getRemoteMessageCode(),
                'local_state_before' => $trace['local_state'] ?? null,
                'local_state_after' => $trace['local_state'] ?? null,
                'payload_summary' => $requestPayload,
                'response_summary' => $exception->getResponsePayload(),
            ]));

            return $this->result('warning', 'No fue posible generar la label: ' . $exception->getMessage(), $shipmentTraceId);
        } catch (Throwable $exception) {
            $this->shipmentTraceManager->updateShipmentTrace((int) $trace['id_vx_sendifico_shipment'], [
                'last_error_code' => 'label_generate_failed',
                'last_error_message' => $exception->getMessage(),
            ], $this->buildEvent($trace, 'shipment_generate_label_failed', [
                'endpoint' => sprintf('/shipment/generateLabelUrl/%d', (int) $trace['remote_shipment_id']),
                'http_method' => 'POST',
                'local_state_before' => $trace['local_state'] ?? null,
                'local_state_after' => $trace['local_state'] ?? null,
                'payload_summary' => $requestPayload,
                'response_summary' => ['message' => $exception->getMessage()],
            ]));

            return $this->result('warning', 'No fue posible generar la label: ' . $exception->getMessage(), $shipmentTraceId);
        }
    }

    /**
     * @param array<string, mixed> $trace
     *
     * @return array<string, mixed>
     */
    private function buildEvent(array $trace, string $eventType, array $context = []): array
    {
        return array_merge([
            'id_shop' => (int) ($trace['id_shop'] ?? 0),
            'id_cart' => isset($trace['id_cart']) ? (int) $trace['id_cart'] : null,
            'id_order' => isset($trace['id_order']) ? (int) $trace['id_order'] : null,
            'event_type' => $eventType,
            'is_retryable' => 0,
        ], $context);
    }

    /**
     * @param array<string, mixed> $trace
     */
    private function loadOrder(array $trace): ?Order
    {
        $orderId = (int) ($trace['id_order'] ?? 0);
        if ($orderId <= 0) {
            return null;
        }

        $order = new Order($orderId);

        return (int) $order->id > 0 ? $order : null;
    }

    /**
     * @return array{status:string,message:string,trace_id:int}
     */
    private function result(string $status, string $message, int $traceId): array
    {
        return [
            'status' => $status,
            'message' => $message,
            'trace_id' => $traceId,
        ];
    }

    private function normalizeExpiresAt(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($value))->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $response
     *
     * @return array<string, mixed>
     */
    private function summarizeShipmentResponse(array $response): array
    {
        return [
            'shipmentId' => $response['shipmentId'] ?? null,
            'status' => $response['status'] ?? null,
            'isPaid' => $response['isPaid'] ?? null,
            'preferredCarrierToken' => $response['preferredCarrierToken'] ?? null,
            'priceTotal' => $response['priceTotal'] ?? null,
            'trackingNumber' => $response['trackingNumber'] ?? null,
            'trackingCarrierUrl' => $response['trackingCarrierUrl'] ?? null,
        ];
    }
}
