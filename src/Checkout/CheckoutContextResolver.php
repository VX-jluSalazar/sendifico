<?php

namespace Vx\Sendifico\Checkout;

use Address;
use Cart;
use Country;

final class CheckoutContextResolver
{
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
        $weight = (float) $cart->getTotalWeight();
        if ($weight <= 0) {
            $weight = (float) $shopConfiguration['default_weight'];
        }

        return [
            'weight' => $weight,
            'length' => (float) $shopConfiguration['default_length'],
            'width' => (float) $shopConfiguration['default_width'],
            'height' => (float) $shopConfiguration['default_height'],
        ];
    }

    public function getGoodsInsured(Cart $cart): float
    {
        return max(0.0, (float) $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING));
    }
}
