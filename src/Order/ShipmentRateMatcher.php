<?php

namespace Vx\Sendifico\Order;

final class ShipmentRateMatcher
{
    private const PRICE_TOLERANCE = 0.01;

    /**
     * @param array<int, array<string, mixed>> $rates
     *
     * @return array<string, mixed>|null
     */
    public function match(array $rates, string $carrierToken, ?float $quotedPriceTotal = null): ?array
    {
        $availableRates = array_values(array_filter(
            $rates,
            static fn (array $rate): bool => (string) ($rate['carrierToken'] ?? '') === $carrierToken
                && (($rate['available'] ?? false) === true)
        ));

        if ($availableRates === []) {
            return null;
        }

        if ($quotedPriceTotal !== null) {
            foreach ($availableRates as $rate) {
                if (!isset($rate['priceTotal'])) {
                    continue;
                }

                if (abs((float) $rate['priceTotal'] - $quotedPriceTotal) <= self::PRICE_TOLERANCE) {
                    return $rate;
                }
            }
        }

        return $availableRates[0];
    }
}
