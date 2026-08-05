<?php

namespace Vx\Sendifico\Checkout;

use Address;
use Cart;
use Country;
use Vx\Sendifico\Package\PackageResolver;

final class CheckoutContextResolver
{
    public function __construct(
        private readonly PackageResolver $packageResolver
    ) {
    }

    public function getDeliveryAddress(Cart $cart): ?Address
    {
        if ((int) $cart->id_address_delivery <= 0) {
            return null;
        }

        $address = new Address((int) $cart->id_address_delivery);

        return $address->id > 0 ? $address : null;
    }

    public function getDeliveryCountryIsoCode(Cart $cart): ?string
    {
        $address = $this->getDeliveryAddress($cart);
        if ($address === null || (int) $address->id_country <= 0) {
            return null;
        }

        $country = new Country((int) $address->id_country);

        return $country->id > 0 ? (string) $country->iso_code : null;
    }

    /**
     * @param array<string, mixed> $shopConfiguration
     *
     * @return array<string, float>
     */
    public function buildParcel(Cart $cart, array $shopConfiguration): array
    {
        $parcel = $this->packageResolver->resolveCart($cart, $shopConfiguration);

        return [
            'weight' => (float) $parcel['weight'],
            'length' => (float) $parcel['length'],
            'width' => (float) $parcel['width'],
            'height' => (float) $parcel['height'],
        ];
    }

    public function getGoodsInsured(Cart $cart): float
    {
        return max(0.0, (float) $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING));
    }
}
