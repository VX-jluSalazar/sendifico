<?php

namespace Vx\Sendifico\Order;

use Address;
use Cart;
use Customer;
use Order;
use Vx\Sendifico\Repository\AddressMetadataRepository;
use Vx\Sendifico\Repository\SenderAddressRepository;

final class ShipmentPayloadPreparer
{
    public function __construct(
        private readonly ShipmentPreparationConfigurationProvider $configurationProvider,
        private readonly SenderAddressRepository $senderAddressRepository,
        private readonly AddressMetadataRepository $addressMetadataRepository,
        private readonly \Vx\Sendifico\Package\PackageResolver $packageResolver,
        private readonly ContentsResolver $contentsResolver,
        private readonly CodResolver $codResolver,
        private readonly ShipmentPayloadValidator $payloadValidator
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareFromOrder(Order $order): array
    {
        $cart = new Cart((int) $order->id_cart);

        return $this->prepare($cart, $order);
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareFromCart(Cart $cart): array
    {
        return $this->prepare($cart, null);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepare(Cart $cart, ?Order $order): array
    {
        $shopId = (int) $cart->id_shop;
        $configuration = $this->configurationProvider->getShopConfiguration($shopId);
        $address = (int) $cart->id_address_delivery > 0 ? new Address((int) $cart->id_address_delivery) : null;
        $address = $address instanceof Address && (int) $address->id > 0 ? $address : null;
        $customerId = $order !== null ? (int) $order->id_customer : (int) $cart->id_customer;
        $customer = $customerId > 0 ? new Customer($customerId) : null;
        $customer = $customer instanceof Customer && (int) $customer->id > 0 ? $customer : null;

        $senderReference = trim((string) ($configuration['sender_reference'] ?? ''));
        $senderAddress = $senderReference !== ''
            ? $this->senderAddressRepository->findByRemoteAddressId($shopId, $senderReference)
            : null;
        $addressMetadata = $address !== null ? $this->addressMetadataRepository->findByAddressId((int) $address->id) : null;

        $package = $this->packageResolver->resolveCart($cart, $configuration);
        $contents = $this->contentsResolver->resolveCart($cart, $configuration);
        $goodsInsured = $order !== null
            ? round(max(0.0, (float) $order->total_products_wt), 2)
            : round(max(0.0, (float) $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING)), 2);
        $goodsCollection = $order !== null ? $this->codResolver->resolveFromOrder($order, $configuration) : 0.0;

        $payload = [
            'extId' => $order !== null ? sprintf('ps-%d-order-%d', $shopId, (int) $order->id) : null,
            'senderAddressId' => $senderAddress !== null ? (int) ($senderAddress['remote_address_id'] ?? 0) : 0,
            'recipientAddress' => $this->buildRecipientAddress($address, $customer, $configuration, $addressMetadata),
            'parcel' => [
                'weight' => (float) $package['weight'],
                'length' => (float) $package['length'],
                'width' => (float) $package['width'],
                'height' => (float) $package['height'],
            ],
            'contents' => $contents['contents'],
            'goodsCollection' => round($goodsCollection, 2),
            'goodsInsured' => $goodsInsured,
            'goodsCurrency' => (string) $configuration['currency'],
            'senderTerritoryBaseId' => $senderAddress !== null ? (string) ($senderAddress['territory_base_id'] ?? '') : '',
            'recipientTerritoryBaseId' => is_array($addressMetadata) ? (string) ($addressMetadata['territory_base_id'] ?? '') : '',
        ];

        return [
            'success' => $this->payloadValidator->validateShipmentPayload($payload) === [],
            'payload' => $payload,
            'package' => $package,
            'contents' => $contents,
            'quotation_validation_errors' => $this->payloadValidator->validateQuotationPayload($payload),
            'shipment_validation_errors' => $this->payloadValidator->validateShipmentPayload($payload),
            'context' => [
                'id_shop' => $shopId,
                'id_cart' => (int) $cart->id,
                'id_order' => $order !== null ? (int) $order->id : null,
                'sender_reference' => $senderReference,
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null $addressMetadata
     * @param array<string, mixed> $configuration
     *
     * @return array<string, mixed>
     */
    private function buildRecipientAddress(?Address $address, ?Customer $customer, array $configuration, ?array $addressMetadata): array
    {
        if ($address === null) {
            return [];
        }

        $fullName = trim(sprintf('%s %s', (string) $address->firstname, (string) $address->lastname));
        $phone = trim((string) ($address->phone_mobile ?: $address->phone));

        return [
            'fullName' => $fullName,
            'company' => $this->nullableString($address->company),
            'email' => $customer !== null ? $this->nullableString($customer->email) : null,
            'streetLine1' => trim((string) $address->address1),
            'reference' => $this->nullableString($address->address2),
            'territoryBaseId' => is_array($addressMetadata) ? (string) ($addressMetadata['territory_base_id'] ?? '') : '',
            'country' => (string) $configuration['country'],
            'zip' => $this->nullableString($address->postcode),
            'lat' => null,
            'lng' => null,
            'phone' => $phone,
        ];
    }

    private function nullableString(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
