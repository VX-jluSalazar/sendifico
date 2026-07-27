<?php

namespace Vx\Sendifico\Configuration;

use PrestaShop\PrestaShop\Adapter\Shop\Context as ShopContext;
use Vx\Sendifico\Repository\SenderAddressRepository;
use Vx\Sendifico\Repository\ShopRepository;

class SendificoFormDataProvider
{
    public function __construct(
        private readonly SendificoDataConfiguration $dataConfiguration,
        private readonly ShopContext $shopContext,
        private readonly SenderAddressRepository $senderAddressRepository,
        private readonly ShopRepository $shopRepository
    ) {
    }

    public function getData(): array
    {
        return $this->dataConfiguration->getConfiguration();
    }

    public function saveData(array $data): array
    {
        $errors = $this->getBusinessValidationErrors($data);
        if ($errors !== []) {
            return $errors;
        }

        return $this->dataConfiguration->updateConfiguration($data);
    }

    public function getConfigurationWarnings(): array
    {
        $data = $this->getData();
        $warnings = [];

        if ($data['api_key'] === '') {
            $warnings[] = 'La API key de Sendifico no esta configurada para este contexto.';
        }

        if ($data['sender_reference'] === '') {
            $warnings[] = 'No hay remitente configurado para la tienda o grupo actual.';
        }

        if ($data['auto_purchase_enabled'] && $data['sender_reference'] === '') {
            $warnings[] = 'La compra automatica esta habilitada, pero falta el remitente requerido para crear shipments.';
        }

        return $warnings;
    }

    public function getShopContextLabel(): string
    {
        if ($this->shopContext->isShopContext()) {
            return sprintf('Tienda #%d', (int) $this->shopContext->getContextShopID());
        }

        if ($this->shopContext->isGroupShopContext()) {
            return sprintf('Grupo de tiendas #%d', (int) $this->shopContext->getContextShopGroup()->id);
        }

        return 'Todas las tiendas';
    }

    /**
     * @return array<string, string>
     */
    public function getSenderChoices(): array
    {
        $shopIds = array_map('intval', $this->shopContext->getContextListShopID());
        $senders = $this->senderAddressRepository->findSendersByShopIds($shopIds);
        $shopNames = $this->shopRepository->getShopNamesByIds($shopIds);
        $choices = [];

        foreach ($senders as $sender) {
            $reference = (string) $sender['remote_address_id'];
            $shopId = (int) $sender['id_shop'];
            $shopLabel = $shopNames[$shopId] ?? sprintf('Tienda #%d', $shopId);
            $label = sprintf(
                '%s | %s | %s',
                (string) $sender['full_name'],
                (string) $sender['territory_base_id'],
                $shopLabel
            );

            if (!isset($choices[$label])) {
                $choices[$label] = $reference;
            }
        }

        return $choices;
    }

    private function getBusinessValidationErrors(array $data): array
    {
        $errors = [];

        if (($data['country'] ?? '') !== 'EC') {
            $errors[] = 'El pais operativo debe ser EC en esta version del modulo.';
        }

        if (($data['currency'] ?? '') !== 'USD') {
            $errors[] = 'La moneda operativa debe ser USD para mantener consistencia con la API de Sendifico.';
        }

        if (!empty($data['auto_purchase_enabled']) && trim((string) ($data['api_key'] ?? '')) === '') {
            $errors[] = 'No se puede habilitar la compra automatica sin una API key configurada en este contexto.';
        }

        foreach (['default_weight', 'default_length', 'default_width', 'default_height'] as $field) {
            if ((float) ($data[$field] ?? 0) <= 0) {
                $errors[] = sprintf('El valor de "%s" debe ser mayor que 0.', $field);
            }
        }

        return $errors;
    }
}
