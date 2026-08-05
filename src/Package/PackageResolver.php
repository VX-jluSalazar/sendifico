<?php

namespace Vx\Sendifico\Package;

use Cart;

final class PackageResolver
{
    private const MIN_WEIGHT = 1.0;
    private const MIN_DIMENSION = 1.0;

    /**
     * @param array<string, mixed> $configuration
     *
     * @return array<string, mixed>
     */
    public function resolveCart(Cart $cart, array $configuration): array
    {
        return $this->resolveProducts(
            is_array($cart->getProducts()) ? $cart->getProducts() : [],
            $configuration
        );
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @param array<string, mixed> $configuration
     *
     * @return array<string, mixed>
     */
    public function resolveProducts(array $products, array $configuration): array
    {
        $defaultWeight = max(self::MIN_WEIGHT, (float) ($configuration['default_weight'] ?? self::MIN_WEIGHT));
        $defaultLength = max(self::MIN_DIMENSION, (float) ($configuration['default_length'] ?? 10.0));
        $defaultWidth = max(self::MIN_DIMENSION, (float) ($configuration['default_width'] ?? 10.0));
        $defaultHeight = max(self::MIN_DIMENSION, (float) ($configuration['default_height'] ?? 10.0));

        $totalWeight = 0.0;
        $totalVolume = 0.0;
        $maxLength = 0.0;
        $maxWidth = 0.0;
        $maxHeight = 0.0;
        $lines = [];

        foreach ($products as $product) {
            $quantity = max(1, (int) ($product['cart_quantity'] ?? $product['quantity'] ?? 1));
            $weight = $this->normalizePositiveFloat($product['weight'] ?? null, $defaultWeight);
            $length = $this->normalizePositiveFloat($product['depth'] ?? null, $defaultLength);
            $width = $this->normalizePositiveFloat($product['width'] ?? null, $defaultWidth);
            $height = $this->normalizePositiveFloat($product['height'] ?? null, $defaultHeight);

            $totalWeight += $weight * $quantity;
            $totalVolume += ($length * $width * $height) * $quantity;
            $maxLength = max($maxLength, $length);
            $maxWidth = max($maxWidth, $width);
            $maxHeight = max($maxHeight, $height);

            $lines[] = [
                'id_product' => (int) ($product['id_product'] ?? 0),
                'id_product_attribute' => (int) ($product['id_product_attribute'] ?? 0),
                'quantity' => $quantity,
                'weight' => $this->round($weight),
                'length' => $this->round($length),
                'width' => $this->round($width),
                'height' => $this->round($height),
            ];
        }

        $resolvedLength = max($defaultLength, $maxLength, self::MIN_DIMENSION);
        $resolvedWidth = max($defaultWidth, $maxWidth, self::MIN_DIMENSION);
        $baseArea = max(self::MIN_DIMENSION, $resolvedLength * $resolvedWidth);
        $stackedHeight = $totalVolume > 0 ? $totalVolume / $baseArea : $defaultHeight;
        $resolvedHeight = max($defaultHeight, $maxHeight, $stackedHeight, self::MIN_DIMENSION);

        return [
            'weight' => $this->round(max($defaultWeight, $totalWeight, self::MIN_WEIGHT)),
            'length' => $this->round($resolvedLength),
            'width' => $this->round($resolvedWidth),
            'height' => $this->round($resolvedHeight),
            'lines' => $lines,
            'heuristic' => [
                'total_volume' => $this->round($totalVolume),
                'stacked_height' => $this->round($stackedHeight),
                'default_weight' => $this->round($defaultWeight),
                'default_length' => $this->round($defaultLength),
                'default_width' => $this->round($defaultWidth),
                'default_height' => $this->round($defaultHeight),
            ],
        ];
    }

    private function normalizePositiveFloat(mixed $value, float $fallback): float
    {
        $normalized = (float) $value;

        return $normalized > 0 ? $normalized : $fallback;
    }

    private function round(float $value): float
    {
        return round($value, 3);
    }
}
