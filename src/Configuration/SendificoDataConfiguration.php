<?php

namespace Vx\Sendifico\Configuration;

use PrestaShop\PrestaShop\Core\Configuration\AbstractMultistoreConfiguration;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SendificoDataConfiguration extends AbstractMultistoreConfiguration
{
    private const CONFIGURATION_FIELDS = [
        'api_key',
        'api_version',
        'country',
        'currency',
        'cod_payment_methods',
        'sender_reference',
        'purchase_with',
        'auto_purchase_enabled',
        'allow_incomplete_checkout_address',
        'enable_debug_logs',
        'log_retention_days',
        'default_weight',
        'default_length',
        'default_width',
        'default_height',
    ];

    public function getConfiguration(): array
    {
        $shopConstraint = $this->getShopConstraint();

        return [
            'api_key' => (string) $this->configuration->get(ConfigurationKeys::API_KEY, '', $shopConstraint),
            'api_version' => (string) $this->configuration->get(ConfigurationKeys::API_VERSION, ConfigurationKeys::DEFAULTS[ConfigurationKeys::API_VERSION], $shopConstraint),
            'country' => (string) $this->configuration->get(ConfigurationKeys::COUNTRY, ConfigurationKeys::DEFAULTS[ConfigurationKeys::COUNTRY], $shopConstraint),
            'currency' => (string) $this->configuration->get(ConfigurationKeys::CURRENCY, ConfigurationKeys::DEFAULTS[ConfigurationKeys::CURRENCY], $shopConstraint),
            'cod_payment_methods' => (string) $this->configuration->get(ConfigurationKeys::COD_PAYMENT_METHODS, '', $shopConstraint),
            'sender_reference' => (string) $this->configuration->get(ConfigurationKeys::SENDER_REFERENCE, '', $shopConstraint),
            'purchase_with' => (string) $this->configuration->get(ConfigurationKeys::PURCHASE_WITH, ConfigurationKeys::DEFAULTS[ConfigurationKeys::PURCHASE_WITH], $shopConstraint),
            'auto_purchase_enabled' => (bool) $this->configuration->get(ConfigurationKeys::AUTO_PURCHASE_ENABLED, true, $shopConstraint),
            'allow_incomplete_checkout_address' => (bool) $this->configuration->get(ConfigurationKeys::ALLOW_INCOMPLETE_CHECKOUT_ADDRESS, true, $shopConstraint),
            'enable_debug_logs' => (bool) $this->configuration->get(ConfigurationKeys::ENABLE_DEBUG_LOGS, false, $shopConstraint),
            'log_retention_days' => (int) $this->configuration->get(ConfigurationKeys::LOG_RETENTION_DAYS, 30, $shopConstraint),
            'default_weight' => (string) $this->configuration->get(ConfigurationKeys::DEFAULT_WEIGHT, ConfigurationKeys::DEFAULTS[ConfigurationKeys::DEFAULT_WEIGHT], $shopConstraint),
            'default_length' => (string) $this->configuration->get(ConfigurationKeys::DEFAULT_LENGTH, ConfigurationKeys::DEFAULTS[ConfigurationKeys::DEFAULT_LENGTH], $shopConstraint),
            'default_width' => (string) $this->configuration->get(ConfigurationKeys::DEFAULT_WIDTH, ConfigurationKeys::DEFAULTS[ConfigurationKeys::DEFAULT_WIDTH], $shopConstraint),
            'default_height' => (string) $this->configuration->get(ConfigurationKeys::DEFAULT_HEIGHT, ConfigurationKeys::DEFAULTS[ConfigurationKeys::DEFAULT_HEIGHT], $shopConstraint),
        ];
    }

    public function updateConfiguration(array $configuration): array
    {
        if ($this->validateConfiguration($configuration)) {
            $shopConstraint = $this->getShopConstraint();

            $normalizedConfiguration = $configuration;
            $normalizedConfiguration['country'] = strtoupper((string) $normalizedConfiguration['country']);
            $normalizedConfiguration['currency'] = strtoupper((string) $normalizedConfiguration['currency']);
            $normalizedConfiguration['api_version'] = trim((string) $normalizedConfiguration['api_version']);
            $normalizedConfiguration['api_key'] = trim((string) $normalizedConfiguration['api_key']);
            $normalizedConfiguration['sender_reference'] = trim((string) $normalizedConfiguration['sender_reference']);
            $normalizedConfiguration['cod_payment_methods'] = $this->normalizeCodPaymentMethods((string) $normalizedConfiguration['cod_payment_methods']);
            $normalizedConfiguration['default_weight'] = $this->normalizeDecimal((string) $normalizedConfiguration['default_weight']);
            $normalizedConfiguration['default_length'] = $this->normalizeDecimal((string) $normalizedConfiguration['default_length']);
            $normalizedConfiguration['default_width'] = $this->normalizeDecimal((string) $normalizedConfiguration['default_width']);
            $normalizedConfiguration['default_height'] = $this->normalizeDecimal((string) $normalizedConfiguration['default_height']);

            $this->updateConfigurationValue(ConfigurationKeys::API_KEY, 'api_key', $normalizedConfiguration, $shopConstraint);
            $this->updateConfigurationValue(ConfigurationKeys::API_VERSION, 'api_version', $normalizedConfiguration, $shopConstraint);
            $this->updateConfigurationValue(ConfigurationKeys::COUNTRY, 'country', $normalizedConfiguration, $shopConstraint);
            $this->updateConfigurationValue(ConfigurationKeys::CURRENCY, 'currency', $normalizedConfiguration, $shopConstraint);
            $this->updateConfigurationValue(ConfigurationKeys::COD_PAYMENT_METHODS, 'cod_payment_methods', $normalizedConfiguration, $shopConstraint);
            $this->updateConfigurationValue(ConfigurationKeys::SENDER_REFERENCE, 'sender_reference', $normalizedConfiguration, $shopConstraint);
            $this->updateConfigurationValue(ConfigurationKeys::PURCHASE_WITH, 'purchase_with', $normalizedConfiguration, $shopConstraint);
            $this->updateConfigurationValue(ConfigurationKeys::AUTO_PURCHASE_ENABLED, 'auto_purchase_enabled', $normalizedConfiguration, $shopConstraint);
            $this->updateConfigurationValue(ConfigurationKeys::ALLOW_INCOMPLETE_CHECKOUT_ADDRESS, 'allow_incomplete_checkout_address', $normalizedConfiguration, $shopConstraint);
            $this->updateConfigurationValue(ConfigurationKeys::ENABLE_DEBUG_LOGS, 'enable_debug_logs', $normalizedConfiguration, $shopConstraint);
            $this->updateConfigurationValue(ConfigurationKeys::LOG_RETENTION_DAYS, 'log_retention_days', $normalizedConfiguration, $shopConstraint);
            $this->updateConfigurationValue(ConfigurationKeys::DEFAULT_WEIGHT, 'default_weight', $normalizedConfiguration, $shopConstraint);
            $this->updateConfigurationValue(ConfigurationKeys::DEFAULT_LENGTH, 'default_length', $normalizedConfiguration, $shopConstraint);
            $this->updateConfigurationValue(ConfigurationKeys::DEFAULT_WIDTH, 'default_width', $normalizedConfiguration, $shopConstraint);
            $this->updateConfigurationValue(ConfigurationKeys::DEFAULT_HEIGHT, 'default_height', $normalizedConfiguration, $shopConstraint);
        }

        return [];
    }

    protected function buildResolver(): OptionsResolver
    {
        return (new OptionsResolver())
            ->setDefined(self::CONFIGURATION_FIELDS)
            ->setAllowedTypes('api_key', 'string')
            ->setAllowedTypes('api_version', 'string')
            ->setAllowedTypes('country', 'string')
            ->setAllowedTypes('currency', 'string')
            ->setAllowedTypes('cod_payment_methods', 'string')
            ->setAllowedTypes('sender_reference', 'string')
            ->setAllowedTypes('purchase_with', 'string')
            ->setAllowedTypes('auto_purchase_enabled', 'bool')
            ->setAllowedTypes('allow_incomplete_checkout_address', 'bool')
            ->setAllowedTypes('enable_debug_logs', 'bool')
            ->setAllowedTypes('log_retention_days', 'int')
            ->setAllowedTypes('default_weight', 'string')
            ->setAllowedTypes('default_length', 'string')
            ->setAllowedTypes('default_width', 'string')
            ->setAllowedTypes('default_height', 'string')
            ->setAllowedValues('purchase_with', ConfigurationKeys::PURCHASE_WITH_CHOICES);
    }

    private function normalizeCodPaymentMethods(string $value): string
    {
        $parts = preg_split('/[\r\n,;]+/', $value) ?: [];
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, static fn (string $item): bool => $item != '');

        return implode("\n", array_values(array_unique($parts)));
    }

    private function normalizeDecimal(string $value): string
    {
        return number_format((float) $value, 3, '.', '');
    }
}
