<?php

namespace Vx\Sendifico\Carrier;

use Vx\Sendifico\Repository\CarrierMappingRepository;
use Vx\Sendifico\Repository\ShopRepository;

final class CarrierProvisionStatusProvider
{
    public function __construct(
        private readonly CarrierCatalogProvider $catalogProvider,
        private readonly CarrierMappingRepository $carrierMappingRepository,
        private readonly ShopRepository $shopRepository
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getOverview(): array
    {
        $shopIds = $this->shopRepository->getActiveShopIds();
        $shopNames = $this->shopRepository->getShopNamesByIds($shopIds);
        $mappings = $this->carrierMappingRepository->findByShopIds($shopIds);
        $rows = [];

        foreach ($this->catalogProvider->getCatalog() as $token => $definition) {
            $carrierMappings = array_values(array_filter($mappings, static fn (array $mapping): bool => $mapping['carrier_token'] === $token));
            $shopLabels = array_map(function (array $mapping) use ($shopNames): string {
                $shopId = (int) $mapping['id_shop'];

                return $shopNames[$shopId] ?? sprintf('Tienda #%d', $shopId);
            }, $carrierMappings);

            $rows[] = [
                'carrier_token' => $token,
                'display_name' => $definition['display_name'],
                'mapped_shop_count' => count($carrierMappings),
                'mapped_shops' => $shopLabels,
                'is_fully_mapped' => count($carrierMappings) === count($shopIds),
            ];
        }

        return $rows;
    }
}
