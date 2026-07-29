<?php

namespace Vx\Sendifico\Checkout;

use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;
use Vx\Sendifico\Configuration\ConfigurationKeys;
use Vx\Sendifico\Configuration\SendificoConnectionConfigurationProvider;

final class CheckoutConfigurationProvider
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
        $configuration = $this->connectionConfigurationProvider->getShopConfiguration($shopId);
        $addressConfiguration = $this->getAddressConfiguration($shopId);

        $configuration['allow_incomplete_checkout_address'] = $addressConfiguration['allow_incomplete_checkout_address'];
        $configuration['default_weight'] = $addressConfiguration['default_weight'];
        $configuration['default_length'] = $addressConfiguration['default_length'];
        $configuration['default_width'] = $addressConfiguration['default_width'];
        $configuration['default_height'] = $addressConfiguration['default_height'];

        return $configuration;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAddressConfiguration(int $shopId): array
    {
        $constraint = ShopConstraint::shop($shopId);

        return [
            'country' => strtoupper((string) $this->configuration->get(
                ConfigurationKeys::COUNTRY,
                ConfigurationKeys::DEFAULTS[ConfigurationKeys::COUNTRY],
                $constraint
            )),
            'currency' => strtoupper((string) $this->configuration->get(
                ConfigurationKeys::CURRENCY,
                ConfigurationKeys::DEFAULTS[ConfigurationKeys::CURRENCY],
                $constraint
            )),
            'allow_incomplete_checkout_address' => (bool) $this->configuration->get(
            ConfigurationKeys::ALLOW_INCOMPLETE_CHECKOUT_ADDRESS,
            ConfigurationKeys::DEFAULTS[ConfigurationKeys::ALLOW_INCOMPLETE_CHECKOUT_ADDRESS],
            $constraint
            ),
            'default_weight' => $this->normalizePositiveFloat(
            $this->configuration->get(
                ConfigurationKeys::DEFAULT_WEIGHT,
                ConfigurationKeys::DEFAULTS[ConfigurationKeys::DEFAULT_WEIGHT],
                $constraint
            ),
            1.0
            ),
            'default_length' => $this->normalizePositiveFloat(
            $this->configuration->get(
                ConfigurationKeys::DEFAULT_LENGTH,
                ConfigurationKeys::DEFAULTS[ConfigurationKeys::DEFAULT_LENGTH],
                $constraint
            ),
            10.0
            ),
            'default_width' => $this->normalizePositiveFloat(
            $this->configuration->get(
                ConfigurationKeys::DEFAULT_WIDTH,
                ConfigurationKeys::DEFAULTS[ConfigurationKeys::DEFAULT_WIDTH],
                $constraint
            ),
            10.0
            ),
            'default_height' => $this->normalizePositiveFloat(
            $this->configuration->get(
                ConfigurationKeys::DEFAULT_HEIGHT,
                ConfigurationKeys::DEFAULTS[ConfigurationKeys::DEFAULT_HEIGHT],
                $constraint
            ),
            10.0
            ),
        ];
    }

    private function normalizePositiveFloat(mixed $value, float $fallback): float
    {
        $normalized = (float) $value;

        return $normalized > 0 ? $normalized : $fallback;
    }
}
