<?php

namespace Vx\Sendifico\Sync;

use Vx\Sendifico\Api\SendificoApiClient;
use Vx\Sendifico\Repository\SyncMetadataRepository;
use Vx\Sendifico\Repository\TerritoryRepository;

final class TerritorySyncService
{
    public function __construct(
        private readonly SendificoApiClient $apiClient,
        private readonly TerritoryRepository $territoryRepository,
        private readonly SyncMetadataRepository $syncMetadataRepository
    ) {
    }

    /**
     * @param array{api_key:string, api_version:string, country:string} $connection
     *
     * @return array{status:string, item_count:int, message:string}
     */
    public function syncCountry(array $connection): array
    {
        $attemptedAt = date('Y-m-d H:i:s');

        try {
            $territories = $this->apiClient->getTerritories($connection);
            $this->territoryRepository->replaceByCountry($connection['country'], $territories, $attemptedAt);

            $this->syncMetadataRepository->upsert([
                'sync_type' => 'territories',
                'scope_key' => $connection['country'],
                'id_shop' => null,
                'country_code' => $connection['country'],
                'last_attempt_at' => $attemptedAt,
                'last_success_at' => $attemptedAt,
                'status' => 'success',
                'item_count' => count($territories),
                'error_message' => null,
                'api_version' => $connection['api_version'],
            ]);

            return [
                'status' => 'success',
                'item_count' => count($territories),
                'message' => sprintf('Se sincronizaron %d territorios.', count($territories)),
            ];
        } catch (\Throwable $exception) {
            $this->syncMetadataRepository->upsert([
                'sync_type' => 'territories',
                'scope_key' => $connection['country'],
                'id_shop' => null,
                'country_code' => $connection['country'],
                'last_attempt_at' => $attemptedAt,
                'last_success_at' => null,
                'status' => 'failed',
                'item_count' => 0,
                'error_message' => mb_substr($exception->getMessage(), 0, 65535),
                'api_version' => $connection['api_version'],
            ]);

            return [
                'status' => 'failed',
                'item_count' => 0,
                'message' => $exception->getMessage(),
            ];
        }
    }
}
