<?php

namespace Vx\Sendifico\Order;

use Cart;

final class ContentsResolver
{
    /**
     * @param array<string, mixed> $configuration
     *
     * @return array<string, mixed>
     */
    public function resolveCart(Cart $cart, array $configuration): array
    {
        $products = $cart->getProducts();

        return $this->resolveProducts(is_array($products) ? $products : [], $configuration);
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @param array<string, mixed> $configuration
     *
     * @return array<string, mixed>
     */
    public function resolveProducts(array $products, array $configuration): array
    {
        $productMap = is_array($configuration['content_product_map'] ?? null) ? $configuration['content_product_map'] : [];
        $categoryMap = is_array($configuration['content_category_map'] ?? null) ? $configuration['content_category_map'] : [];
        $defaultContents = trim((string) ($configuration['default_contents'] ?? ''));

        $scores = [];
        $lines = [];

        foreach ($products as $product) {
            $productId = (int) ($product['id_product'] ?? 0);
            $categoryId = (int) ($product['id_category_default'] ?? 0);
            $quantity = max(1, (int) ($product['cart_quantity'] ?? $product['quantity'] ?? 1));
            $weight = max(0.0, (float) ($product['weight'] ?? 0));

            $content = $productMap[$productId] ?? $categoryMap[$categoryId] ?? $defaultContents;
            $source = isset($productMap[$productId]) ? 'product' : (isset($categoryMap[$categoryId]) ? 'category' : 'default');

            $lines[] = [
                'id_product' => $productId,
                'id_category_default' => $categoryId,
                'quantity' => $quantity,
                'weight_total' => round($weight * $quantity, 3),
                'resolved_contents' => $content,
                'resolution_source' => $source,
            ];

            if ($content === '' || !ContentsCatalog::isSupported($content)) {
                continue;
            }

            if (!isset($scores[$content])) {
                $scores[$content] = [
                    'quantity' => 0,
                    'weight_total' => 0.0,
                    'sources' => [],
                ];
            }

            $scores[$content]['quantity'] += $quantity;
            $scores[$content]['weight_total'] += $weight * $quantity;
            $scores[$content]['sources'][$source] = true;
        }

        $dominantContent = $this->resolveDominantContent($scores);

        return [
            'contents' => $dominantContent !== null ? [$dominantContent] : [],
            'dominant_content' => $dominantContent,
            'scores' => $this->normalizeScores($scores),
            'lines' => $lines,
            'default_contents' => $defaultContents,
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $scores
     */
    private function resolveDominantContent(array $scores): ?string
    {
        $selected = null;

        foreach ($scores as $content => $score) {
            if ($selected === null) {
                $selected = $content;

                continue;
            }

            $current = $scores[$selected];
            if ((int) $score['quantity'] > (int) $current['quantity']) {
                $selected = $content;

                continue;
            }

            if ((int) $score['quantity'] === (int) $current['quantity'] && (float) $score['weight_total'] > (float) $current['weight_total']) {
                $selected = $content;

                continue;
            }

            if (
                (int) $score['quantity'] === (int) $current['quantity']
                && (float) $score['weight_total'] === (float) $current['weight_total']
                && strcmp($content, $selected) < 0
            ) {
                $selected = $content;
            }
        }

        return $selected;
    }

    /**
     * @param array<string, array<string, mixed>> $scores
     *
     * @return array<string, array<string, mixed>>
     */
    private function normalizeScores(array $scores): array
    {
        $normalized = [];

        foreach ($scores as $content => $score) {
            $normalized[$content] = [
                'quantity' => (int) $score['quantity'],
                'weight_total' => round((float) $score['weight_total'], 3),
                'sources' => array_keys($score['sources']),
            ];
        }

        return $normalized;
    }
}
