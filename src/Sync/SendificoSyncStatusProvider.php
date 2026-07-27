<?php

namespace Vx\Sendifico\Sync;

use PrestaShop\PrestaShop\Adapter\Shop\Context as ShopContext;
use Vx\Sendifico\Configuration\SendificoConnectionConfigurationProvider;
use Vx\Sendifico\Repository\SenderAddressRepository;
use Vx\Sendifico\Repository\SyncMetadataRepository;
use Vx\Sendifico\Repository\TerritoryRepository;

final class SendificoSyncStatusProvider
{
    public function __construct(
        private readonly ShopContext $shopContext,
        private readonly SendificoConnectionConfigurationProvider $configurationProvider,
        private readonly TerritoryRepository $territoryRepository,
        private readonly SenderAddressRepository $senderAddressRepository,
        private readonly SyncMetadataRepository $syncMetadataRepository
    ) {
    }

    /**
     * @return array{territories:array<int, array<string, mixed>>, senders:array<int, array<string, mixed>>}
     */
    public function getCurrentContextOverview(): array
    {
        $territories = [];
        $senders = [];
        $countries = [];

        foreach ($this->shopContext->getContextListShopID() as $shopId) {
            $shopId = (int) $shopId;

            try {
                $configuration = $this->configurationProvider->getShopConfiguration($shopId);
            } catch (\Throwable $exception) {
                $senders[] = [
                    'label' => sprintf('Tienda #%d', $shopId),
                    'status' => 'failed',
                    'count' => 0,
                    'last_success_at' => null,
                    'error_message' => $exception->getMessage(),
                    'selected_sender_reference' => null,
                    'selected_sender_found' => false,
                    'selected_sender_label' => null,
                ];

                continue;
            }

            if (!isset($countries[$configuration['country']])) {
                $metadata = $this->syncMetadataRepository->findOne('territories', $configuration['country']);
                $territories[] = [
                    'label' => sprintf('Territorios %s', $configuration['country']),
                    'status' => $metadata['status'] ?? 'pending',
                    'count' => $this->territoryRepository->countByCountry($configuration['country']),
                    'last_success_at' => $metadata['last_success_at'] ?? null,
                    'error_message' => $metadata['error_message'] ?? null,
                ];
                $countries[$configuration['country']] = true;
            }

            $selectedSender = $configuration['sender_reference'] !== ''
                ? $this->senderAddressRepository->findByRemoteAddressId($shopId, $configuration['sender_reference'])
                : null;
            $metadata = $this->syncMetadataRepository->findOne('sender_addresses', sprintf('shop:%d', $shopId));

            $senders[] = [
                'label' => sprintf('Tienda #%d', $shopId),
                'status' => $metadata['status'] ?? 'pending',
                'count' => $this->senderAddressRepository->countByShopId($shopId),
                'last_success_at' => $metadata['last_success_at'] ?? null,
                'error_message' => $metadata['error_message'] ?? null,
                'selected_sender_reference' => $configuration['sender_reference'] !== '' ? $configuration['sender_reference'] : null,
                'selected_sender_found' => $selectedSender !== null,
                'selected_sender_label' => $selectedSender !== null
                    ? sprintf('%s (%s)', $selectedSender['full_name'], $selectedSender['remote_address_id'])
                    : null,
            ];
        }

        return [
            'territories' => $territories,
            'senders' => $senders,
        ];
    }
}
