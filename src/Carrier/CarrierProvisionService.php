<?php

namespace Vx\Sendifico\Carrier;

use Carrier;
use Exception;
use Vx\Sendifico\Repository\CarrierMappingRepository;
use Vx\Sendifico\Repository\PrestaShopCarrierRepository;
use Vx\Sendifico\Repository\ShopRepository;

final class CarrierProvisionService
{
    public function __construct(
        private readonly CarrierCatalogProvider $catalogProvider,
        private readonly CarrierMappingRepository $carrierMappingRepository,
        private readonly PrestaShopCarrierRepository $prestaShopCarrierRepository,
        private readonly ShopRepository $shopRepository
    ) {
    }

    /**
     * @return array<int, array{label:string, status:string, message:string}>
     */
    public function provisionAll(): array
    {
        $results = [];
        $activeShopIds = $this->shopRepository->getActiveShopIds();

        foreach ($this->catalogProvider->getCatalog() as $token => $definition) {
            try {
                $carrierRow = $this->resolveOrCreateCarrier($definition);
                foreach ($activeShopIds as $shopId) {
                    $this->carrierMappingRepository->upsert([
                        'id_shop' => $shopId,
                        'id_carrier' => (int) $carrierRow['id_carrier'],
                        'id_carrier_reference' => (int) $carrierRow['id_reference'],
                        'carrier_token' => $token,
                        'display_name' => $definition['display_name'],
                        'is_active' => 1,
                        'provision_source' => 'bo_manual',
                        'last_provision_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                $results[] = [
                    'label' => $definition['display_name'],
                    'status' => 'success',
                    'message' => sprintf(
                        'Carrier provisionado con id_reference %d y mapeado a %d tiendas.',
                        (int) $carrierRow['id_reference'],
                        count($activeShopIds)
                    ),
                ];
            } catch (\Throwable $exception) {
                $results[] = [
                    'label' => $definition['display_name'],
                    'status' => 'failed',
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * @param array{token:string, carrier_name:string, display_name:string, delay:string} $definition
     *
     * @return array{id_carrier:int, id_reference:int, name:string}
     */
    private function resolveOrCreateCarrier(array $definition): array
    {
        $existingCarrier = $this->prestaShopCarrierRepository->findLatestModuleCarrierByName($definition['carrier_name']);
        if ($existingCarrier !== null) {
            return $existingCarrier;
        }

        $carrier = new Carrier();
        $carrier->name = $definition['carrier_name'];
        $carrier->active = true;
        $carrier->deleted = false;
        $carrier->is_module = true;
        $carrier->external_module_name = 'vx_sendifico';
        $carrier->shipping_external = true;
        $carrier->need_range = false;
        $carrier->range_behavior = false;
        $carrier->shipping_handling = false;
        $carrier->is_free = false;
        $carrier->grade = 0;
        $carrier->position = (int) Carrier::getHigherPosition() + 1;
        $carrier->url = '';
        $carrier->max_width = 0;
        $carrier->max_height = 0;
        $carrier->max_depth = 0;
        $carrier->max_weight = 0;
        $carrier->id_tax_rules_group = 0;
        $carrier->id_shop_list = $this->shopRepository->getActiveShopIds();

        foreach ($this->prestaShopCarrierRepository->getLanguageIds() as $languageId) {
            $carrier->delay[$languageId] = $definition['delay'];
        }

        if (!$carrier->add()) {
            throw new Exception(sprintf('No fue posible crear el carrier local para %s.', $definition['display_name']));
        }

        $groupIds = $this->prestaShopCarrierRepository->getGroupIds();
        if ($groupIds !== []) {
            $carrier->setGroups($groupIds);
        }

        foreach ($this->prestaShopCarrierRepository->getZoneIds() as $zoneId) {
            $carrier->addZone((int) $zoneId);
        }

        $createdCarrier = $this->prestaShopCarrierRepository->findCarrierById((int) $carrier->id);
        if ($createdCarrier === null) {
            throw new Exception(sprintf('El carrier local %s fue creado pero no pudo recargarse por id_carrier.', $definition['display_name']));
        }

        return $createdCarrier;
    }
}
