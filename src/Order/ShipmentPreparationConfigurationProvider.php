<?php

namespace Vx\Sendifico\Order;

use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;
use Vx\Sendifico\Configuration\ConfigurationKeys;
use Vx\Sendifico\Configuration\SendificoConnectionConfigurationProvider;

final class ShipmentPreparationConfigurationProvider
{
    public function __construct(
        private readonly SendificoConnectionConfigurationProvider $connectionConfigurationProvider,
        private readonly ConfigurationInterface $configuration
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getShopConfiguration(int $shopId): array
    {
        $constraint = ShopConstraint::shop($shopId);
        $connection = $this->connectionConfigurationProvider->getShopConfiguration($shopId);

        $connection['cod_payment_methods'] = $this->normalizeCodPaymentMethods((string) $this->configuration->get(
            ConfigurationKeys::COD_PAYMENT_METHODS,
            ConfigurationKeys::DEFAULTS[ConfigurationKeys::COD_PAYMENT_METHODS],
            $constraint
        ));
        $connection['purchase_with'] = (string) $this->configuration->get(
            ConfigurationKeys::PURCHASE_WITH,
            ConfigurationKeys::DEFAULTS[ConfigurationKeys::PURCHASE_WITH],
            $constraint
        );
        $connection['auto_purchase_enabled'] = (bool) $this->configuration->get(
            ConfigurationKeys::AUTO_PURCHASE_ENABLED,
            ConfigurationKeys::DEFAULTS[ConfigurationKeys::AUTO_PURCHASE_ENABLED],
            $constraint
        );
        $connection['default_weight'] = $this->normalizePositiveFloat($this->configuration->get(
            ConfigurationKeys::DEFAULT_WEIGHT,
            ConfigurationKeys::DEFAULTS[ConfigurationKeys::DEFAULT_WEIGHT],
            $constraint
        ), 1.0);
        $connection['default_length'] = $this->normalizePositiveFloat($this->configuration->get(
            ConfigurationKeys::DEFAULT_LENGTH,
            ConfigurationKeys::DEFAULTS[ConfigurationKeys::DEFAULT_LENGTH],
            $constraint
        ), 10.0);
        $connection['default_width'] = $this->normalizePositiveFloat($this->configuration->get(
            ConfigurationKeys::DEFAULT_WIDTH,
            ConfigurationKeys::DEFAULTS[ConfigurationKeys::DEFAULT_WIDTH],
            $constraint
        ), 10.0);
        $connection['default_height'] = $this->normalizePositiveFloat($this->configuration->get(
            ConfigurationKeys::DEFAULT_HEIGHT,
            ConfigurationKeys::DEFAULTS[ConfigurationKeys::DEFAULT_HEIGHT],
            $constraint
        ), 10.0);
        $connection['default_contents'] = trim((string) $this->configuration->get(
            ConfigurationKeys::DEFAULT_CONTENTS,
            ConfigurationKeys::DEFAULTS[ConfigurationKeys::DEFAULT_CONTENTS],
            $constraint
        ));
        $connection['content_product_map'] = ContentsMappingParser::parse((string) $this->configuration->get(
            ConfigurationKeys::CONTENT_PRODUCT_MAP,
            ConfigurationKeys::DEFAULTS[ConfigurationKeys::CONTENT_PRODUCT_MAP],
            $constraint
        ));
        $connection['content_category_map'] = ContentsMappingParser::parse((string) $this->configuration->get(
            ConfigurationKeys::CONTENT_CATEGORY_MAP,
            ConfigurationKeys::DEFAULTS[ConfigurationKeys::CONTENT_CATEGORY_MAP],
            $constraint
        ));

        return $connection;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeCodPaymentMethods(string $value): array
    {
        $parts = preg_split('/[\r\n,;]+/', $value) ?: [];
        $parts = array_map(static fn (string $item): string => trim($item), $parts);
        $parts = array_filter($parts, static fn (string $item): bool => $item !== '');

        return array_values(array_unique($parts));
    }

    private function normalizePositiveFloat(mixed $value, float $fallback): float
    {
        $normalized = (float) $value;

        return $normalized > 0 ? $normalized : $fallback;
    }
}
