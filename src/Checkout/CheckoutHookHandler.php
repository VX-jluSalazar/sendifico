<?php

namespace Vx\Sendifico\Checkout;

use Address;
use Cart;

final class CheckoutHookHandler
{
    public function __construct(
        private readonly CheckoutQuotationService $checkoutQuotationService,
        private readonly AddressTerritoryFormService $addressTerritoryFormService
    ) {
    }

    /**
     * @return array<int, \FormField>
     */
    public function buildAdditionalAddressFields(int $shopId, ?int $addressId = null): array
    {
        return $this->addressTerritoryFormService->buildAdditionalFields($shopId, $addressId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAddressFrontendConfiguration(int $shopId): array
    {
        return $this->addressTerritoryFormService->getFrontendConfiguration($shopId);
    }

    public function validateAddressForm(mixed $form, array $submittedValues, int $shopId): bool
    {
        return $this->addressTerritoryFormService->validateAddressForm($form, $submittedValues, $shopId);
    }

    public function persistAddressMetadata(Address $address, array $submittedValues, int $shopId): void
    {
        $this->addressTerritoryFormService->persistAddressMetadata($address, $submittedValues, $shopId);
    }

    public function deleteAddressMetadata(int $addressId): void
    {
        $this->addressTerritoryFormService->deleteAddressMetadata($addressId);
    }

    /**
     * @param array<int|string, mixed> $deliveryOptionList
     */
    public function filterDeliveryOptionList(Cart $cart, array &$deliveryOptionList): void
    {
        $this->checkoutQuotationService->filterDeliveryOptionList($cart, $deliveryOptionList);
    }

    public function syncSelectedCarrier(Cart $cart): void
    {
        $this->checkoutQuotationService->syncSelectedCarrier($cart);
    }

    public function validateSelectedCarrier(Cart $cart): bool
    {
        return $this->checkoutQuotationService->validateSelectedCarrier($cart);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRateForCarrier(Cart $cart, int $carrierId): ?array
    {
        return $this->checkoutQuotationService->getRateForCarrier($cart, $carrierId);
    }
}
