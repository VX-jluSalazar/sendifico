<?php

namespace Vx\Sendifico\Sync;

use PrestaShop\PrestaShop\Adapter\Shop\Context as ShopContext;
use Vx\Sendifico\Configuration\SendificoConnectionConfigurationProvider;

final class SendificoSyncOrchestrator
{
    public function __construct(
        private readonly ShopContext $shopContext,
        private readonly SendificoConnectionConfigurationProvider $configurationProvider,
        private readonly TerritorySyncService $territorySyncService,
        private readonly SenderAddressSyncService $senderAddressSyncService
    ) {
    }

    /**
     * @return array<int, array{label:string, status:string, item_count:int, message:string}>
     */
    public function syncCurrentContext(string $type): array
    {
        $results = [];
        $countries = [];

        foreach ($this->shopContext->getContextListShopID() as $shopId) {
            $shopId = (int) $shopId;

            try {
                $connection = $this->configurationProvider->getShopConfiguration($shopId);
            } catch (\Throwable $exception) {
                $results[] = [
                    'label' => sprintf('Tienda #%d', $shopId),
                    'status' => 'failed',
                    'item_count' => 0,
                    'message' => $exception->getMessage(),
                ];

                continue;
            }

            if ($type !== 'senders' && !isset($countries[$connection['country']])) {
                $territoryResult = $this->territorySyncService->syncCountry($connection);
                $results[] = [
                    'label' => sprintf('Territorios %s', $connection['country']),
                    'status' => $territoryResult['status'],
                    'item_count' => $territoryResult['item_count'],
                    'message' => $territoryResult['message'],
                ];
                $countries[$connection['country']] = true;
            }

            if ($type !== 'territories') {
                $senderResult = $this->senderAddressSyncService->syncShop($shopId, $connection);
                $results[] = [
                    'label' => sprintf('Remitentes tienda #%d', $shopId),
                    'status' => $senderResult['status'],
                    'item_count' => $senderResult['item_count'],
                    'message' => $senderResult['message'],
                ];
            }
        }

        return $results;
    }
}
