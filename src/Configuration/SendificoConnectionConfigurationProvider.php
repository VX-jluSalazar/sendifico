<?php

namespace Vx\Sendifico\Configuration;

use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;
use RuntimeException;

final class SendificoConnectionConfigurationProvider
{
    public function __construct(
        private readonly ConfigurationInterface $configuration
    ) {
    }

    /**
     * @return array{api_key:string, api_version:string, country:string, currency:string, sender_reference:string}
     */
    public function getShopConfiguration(int $shopId): array
    {
        $constraint = ShopConstraint::shop($shopId);
        $apiKey = trim((string) $this->configuration->get(ConfigurationKeys::API_KEY, '', $constraint));
        $apiVersion = trim((string) $this->configuration->get(ConfigurationKeys::API_VERSION, ConfigurationKeys::DEFAULTS[ConfigurationKeys::API_VERSION], $constraint));
        $country = strtoupper((string) $this->configuration->get(ConfigurationKeys::COUNTRY, ConfigurationKeys::DEFAULTS[ConfigurationKeys::COUNTRY], $constraint));
        $currency = strtoupper((string) $this->configuration->get(ConfigurationKeys::CURRENCY, ConfigurationKeys::DEFAULTS[ConfigurationKeys::CURRENCY], $constraint));
        $senderReference = trim((string) $this->configuration->get(ConfigurationKeys::SENDER_REFERENCE, '', $constraint));

        if ($apiKey === '') {
            throw new RuntimeException(sprintf('La tienda #%d no tiene API key configurada.', $shopId));
        }

        return [
            'api_key' => $apiKey,
            'api_version' => $apiVersion,
            'country' => $country,
            'currency' => $currency,
            'sender_reference' => $senderReference,
        ];
    }
}
