<?php

namespace Vx\Sendifico\Configuration;

use PrestaShop\PrestaShop\Adapter\Shop\Context as ShopContext;

class SendificoFormDataProvider
{
    public function __construct(
        private readonly SendificoDataConfiguration $dataConfiguration,
        private readonly ShopContext $shopContext
    ) {
    }

    public function getData(): array
    {
        return $this->dataConfiguration->getConfiguration();
    }

    public function saveData(array $data): array
    {
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
}
