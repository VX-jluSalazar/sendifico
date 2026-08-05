<?php

namespace Vx\Sendifico\Checkout;

use Cart;
use Throwable;
use Vx\Sendifico\Api\SendificoApiClient;
use Vx\Sendifico\Order\ShipmentTraceState;
use Vx\Sendifico\Repository\AddressMetadataRepository;
use Vx\Sendifico\Repository\CarrierMappingRepository;
use Vx\Sendifico\Repository\SenderAddressRepository;
use Vx\Sendifico\Repository\ShipmentRepository;
use Vx\Sendifico\Repository\TerritoryRepository;

final class CheckoutQuotationService
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $quoteCache = [];

    public function __construct(
        private readonly CheckoutConfigurationProvider $checkoutConfigurationProvider,
        private readonly CheckoutContextResolver $checkoutContextResolver,
        private readonly SendificoApiClient $apiClient,
        private readonly TerritoryRepository $territoryRepository,
        private readonly AddressMetadataRepository $addressMetadataRepository,
        private readonly SenderAddressRepository $senderAddressRepository,
        private readonly CarrierMappingRepository $carrierMappingRepository,
        private readonly ShipmentRepository $shipmentRepository
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function ensureQuote(Cart $cart): array
    {
        $cartId = (int) $cart->id;
        if (isset($this->quoteCache[$cartId])) {
            return $this->quoteCache[$cartId];
        }

        try {
            $shopConfiguration = $this->checkoutConfigurationProvider->getShopConfiguration((int) $cart->id_shop);
        } catch (Throwable $exception) {
            return $this->quoteCache[$cartId] = $this->failQuote(
                $cart,
                null,
                ShipmentTraceState::BLOCKED_MISSING_DATA,
                'configuration_missing',
                $exception->getMessage()
            );
        }

        $deliveryAddress = $this->checkoutContextResolver->getDeliveryAddress($cart);
        $deliveryAddressId = $deliveryAddress !== null ? (int) $deliveryAddress->id : 0;
        $addressMetadata = $deliveryAddressId > 0 ? $this->addressMetadataRepository->findByAddressId($deliveryAddressId) : null;
        $row = $this->shipmentRepository->findPendingByCartId($cartId);
        $recipientTerritoryBaseId = is_array($addressMetadata) ? trim((string) ($addressMetadata['territory_base_id'] ?? '')) : '';
        if ($recipientTerritoryBaseId === '') {
            return $this->quoteCache[$cartId] = $this->failQuote(
                $cart,
                $shopConfiguration,
                ShipmentTraceState::BLOCKED_MISSING_DATA,
                'recipient_territory_missing',
                'La direccion de entrega seleccionada no tiene territorio Sendifico valido.'
            );
        }

        $senderReference = trim((string) ($shopConfiguration['sender_reference'] ?? ''));
        if ($senderReference === '') {
            return $this->quoteCache[$cartId] = $this->failQuote(
                $cart,
                $shopConfiguration,
                ShipmentTraceState::BLOCKED_MISSING_DATA,
                'sender_missing',
                'La tienda actual no tiene remitente configurado para cotizar con Sendifico.'
            );
        }

        $senderAddress = $this->senderAddressRepository->findByRemoteAddressId((int) $cart->id_shop, $senderReference);
        if ($senderAddress === null) {
            return $this->quoteCache[$cartId] = $this->failQuote(
                $cart,
                $shopConfiguration,
                ShipmentTraceState::BLOCKED_MISSING_DATA,
                'sender_not_synced',
                'El remitente configurado no existe en el cache local. Sincroniza remitentes antes de cotizar.'
            );
        }

        if ($row !== null && $this->isReusableQuoteRow($cart, $row, $senderReference, $recipientTerritoryBaseId)) {
            return $this->quoteCache[$cartId] = [
                'success' => true,
                'status' => ShipmentTraceState::QUOTED,
                'message' => null,
                'rates' => $this->extractRatesFromRow($row),
                'row' => $row,
            ];
        }

        $requestPayload = [
            'senderAddress' => [
                'territoryBaseId' => (string) $senderAddress['territory_base_id'],
                'country' => (string) $shopConfiguration['country'],
            ],
            'recipientAddress' => [
                'territoryBaseId' => $recipientTerritoryBaseId,
                'country' => (string) $shopConfiguration['country'],
            ],
            'parcel' => $this->checkoutContextResolver->buildParcel($cart, $shopConfiguration),
            'goodsCollection' => 0.0,
            'goodsInsured' => $this->checkoutContextResolver->getGoodsInsured($cart),
            'goodsCurrency' => (string) $shopConfiguration['currency'],
        ];

        try {
            $quotation = $this->apiClient->createQuotation($shopConfiguration, $requestPayload);
            $payload = $this->buildBaseTracePayload($cart, $shopConfiguration);
            $payload['sender_address_id'] = (int) $senderAddress['remote_address_id'];
            $payload['sender_reference'] = $senderReference;
            $payload['recipient_territory_base_id'] = $recipientTerritoryBaseId;
            $payload['request_snapshot'] = $requestPayload;
            $payload['response_snapshot'] = $quotation;
            $payload['local_state'] = ShipmentTraceState::QUOTED;
            $payload['last_error_code'] = null;
            $payload['last_error_message'] = null;
            $payload['quoted_price_total'] = null;

            $this->upsertPendingTrace($cartId, $payload);
            $row = $this->shipmentRepository->findPendingByCartId($cartId);

            return $this->quoteCache[$cartId] = [
                'success' => true,
                'status' => ShipmentTraceState::QUOTED,
                'message' => null,
                'rates' => $quotation['data'] ?? [],
                'row' => $row,
            ];
        } catch (Throwable $exception) {
            return $this->quoteCache[$cartId] = $this->failQuote(
                $cart,
                $shopConfiguration,
                ShipmentTraceState::QUOTE_FAILED,
                'quotation_failed',
                $exception->getMessage(),
                [
                    'sender_reference' => $senderReference,
                    'sender_address_id' => (int) $senderAddress['remote_address_id'],
                    'recipient_territory_base_id' => $recipientTerritoryBaseId,
                    'request_snapshot' => $requestPayload,
                ]
            );
        }
    }

    /**
     * @return array<int, string>
     */
    public function getAvailableCarrierTokens(Cart $cart): array
    {
        $quote = $this->ensureQuote($cart);
        if (!$quote['success']) {
            return [];
        }

        $tokens = [];
        foreach ($quote['rates'] as $rate) {
            if (($rate['available'] ?? false) !== true) {
                continue;
            }

            $tokens[] = (string) ($rate['carrierToken'] ?? '');
        }

        return array_values(array_filter(array_unique($tokens)));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRateForCarrier(Cart $cart, int $carrierId): ?array
    {
        $mapping = $this->carrierMappingRepository->findOneByShopIdAndCarrierId((int) $cart->id_shop, $carrierId);
        if ($mapping === null) {
            return null;
        }

        $quote = $this->ensureQuote($cart);
        if (!$quote['success']) {
            return null;
        }

        foreach ($quote['rates'] as $rate) {
            if ((string) ($rate['carrierToken'] ?? '') !== (string) $mapping['carrier_token']) {
                continue;
            }

            return is_array($rate) ? $rate : null;
        }

        return null;
    }

    public function syncSelectedCarrier(Cart $cart): void
    {
        if ((int) $cart->id_carrier <= 0) {
            return;
        }

        $mapping = $this->carrierMappingRepository->findOneByShopIdAndCarrierId((int) $cart->id_shop, (int) $cart->id_carrier);
        if ($mapping === null) {
            return;
        }

        $rate = $this->getRateForCarrier($cart, (int) $cart->id_carrier);
        if ($rate === null || ($rate['available'] ?? false) !== true) {
            return;
        }

        $row = $this->shipmentRepository->findPendingByCartId((int) $cart->id);
        if ($row === null) {
            return;
        }

        $this->shipmentRepository->update((int) $row['id_vx_sendifico_shipment'], [
            'id_carrier' => (int) $mapping['id_carrier'],
            'id_carrier_reference' => (int) $mapping['id_carrier_reference'],
            'carrier_token' => (string) $mapping['carrier_token'],
            'selected_quotation_id' => isset($rate['quotationId']) ? (int) $rate['quotationId'] : null,
            'quoted_price_total' => isset($rate['priceTotal']) ? (float) $rate['priceTotal'] : null,
            'currency' => (string) ($rate['currency'] ?? $row['currency']),
            'local_state' => ShipmentTraceState::QUOTED,
            'last_error_code' => null,
            'last_error_message' => null,
        ]);
    }

    public function validateSelectedCarrier(Cart $cart): bool
    {
        if ((int) $cart->id_carrier <= 0) {
            return false;
        }

        $rate = $this->getRateForCarrier($cart, (int) $cart->id_carrier);
        if ($rate === null || ($rate['available'] ?? false) !== true) {
            return false;
        }

        $this->syncSelectedCarrier($cart);

        return true;
    }

    /**
     * @param array<int|string, mixed> $deliveryOptionList
     */
    public function filterDeliveryOptionList(Cart $cart, array &$deliveryOptionList): void
    {
        $availableTokens = $this->getAvailableCarrierTokens($cart);

        foreach ($deliveryOptionList as $addressId => &$options) {
            foreach ($options as $optionKey => &$option) {
                foreach ($option['carrier_list'] as $carrierId => $carrierData) {
                    $mapping = $this->carrierMappingRepository->findOneByShopIdAndCarrierId((int) $cart->id_shop, (int) $carrierId);
                    if ($mapping === null) {
                        continue;
                    }

                    if (!in_array((string) $mapping['carrier_token'], $availableTokens, true)) {
                        unset($option['carrier_list'][$carrierId]);
                    }
                }

                if ($option['carrier_list'] === []) {
                    unset($options[$optionKey]);
                }
            }

            if ($options === []) {
                unset($deliveryOptionList[$addressId]);
            }
        }
    }

    /**
     * @param array<string, mixed>|null $shopConfiguration
     * @param array<string, mixed> $extraPayload
     *
     * @return array<string, mixed>
     */
    private function failQuote(
        Cart $cart,
        ?array $shopConfiguration,
        string $state,
        string $errorCode,
        string $message,
        array $extraPayload = []
    ): array {
        $payload = $this->buildBaseTracePayload($cart, $shopConfiguration);
        $payload = array_merge($payload, $extraPayload, [
            'local_state' => $state,
            'last_error_code' => $errorCode,
            'last_error_message' => $message,
            'response_snapshot' => null,
        ]);

        $this->upsertPendingTrace((int) $cart->id, $payload);

        return [
            'success' => false,
            'status' => $state,
            'message' => $message,
            'rates' => [],
            'row' => $this->shipmentRepository->findPendingByCartId((int) $cart->id),
        ];
    }

    /**
     * @param array<string, mixed>|null $shopConfiguration
     *
     * @return array<string, mixed>
     */
    private function buildBaseTracePayload(Cart $cart, ?array $shopConfiguration): array
    {
        return [
            'id_shop' => (int) $cart->id_shop,
            'id_shop_group' => (int) $cart->id_shop_group > 0 ? (int) $cart->id_shop_group : null,
            'id_cart' => (int) $cart->id,
            'currency' => (string) ($shopConfiguration['currency'] ?? 'USD'),
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractRatesFromRow(array $row): array
    {
        $payload = $this->decodeSnapshot($row['response_snapshot'] ?? null);
        $rates = $payload['data'] ?? [];

        return is_array($rates) ? $rates : [];
    }

    private function isReusableQuoteRow(Cart $cart, array $row, string $senderReference, string $recipientTerritoryBaseId): bool
    {
        if (($row['local_state'] ?? null) !== ShipmentTraceState::QUOTED) {
            return false;
        }

        if ((string) ($row['sender_reference'] ?? '') !== $senderReference) {
            return false;
        }

        if ((string) ($row['recipient_territory_base_id'] ?? '') !== $recipientTerritoryBaseId) {
            return false;
        }

        if (!isset($row['response_snapshot']) || trim((string) $row['response_snapshot']) === '') {
            return false;
        }

        $cartUpdatedAt = strtotime((string) $cart->date_upd);
        $traceUpdatedAt = strtotime((string) ($row['updated_at'] ?? ''));

        return $traceUpdatedAt !== false && $cartUpdatedAt !== false && $traceUpdatedAt >= $cartUpdatedAt;
    }

    private function upsertPendingTrace(int $cartId, array $payload): void
    {
        $existing = $this->shipmentRepository->findPendingByCartId($cartId);
        if ($existing !== null) {
            $this->shipmentRepository->update((int) $existing['id_vx_sendifico_shipment'], $payload);

            return;
        }

        $payload['local_state'] = $payload['local_state'] ?? ShipmentTraceState::QUOTE_PENDING;
        $this->shipmentRepository->create($payload);
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
