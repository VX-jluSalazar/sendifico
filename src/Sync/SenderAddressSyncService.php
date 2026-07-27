<?php

namespace Vx\Sendifico\Sync;

use Vx\Sendifico\Api\SendificoApiClient;
use Vx\Sendifico\Repository\SenderAddressRepository;
use Vx\Sendifico\Repository\SyncMetadataRepository;

final class SenderAddressSyncService
{
    public function __construct(
        private readonly SendificoApiClient $apiClient,
        private readonly SenderAddressRepository $senderAddressRepository,
        private readonly SyncMetadataRepository $syncMetadataRepository
    ) {
    }

    /**
     * @param array{api_key:string, api_version:string, country:string, sender_reference:string} $connection
     *
     * @return array{status:string, item_count:int, message:string}
     */
    public function syncShop(int $shopId, array $connection): array
    {
        $attemptedAt = date('Y-m-d H:i:s');
        $scopeKey = sprintf('shop:%d', $shopId);

        try {
            $page = 1;
            $pageCount = 1;
            $senders = [];

            do {
                $response = $this->apiClient->getAddresses($connection, $page, 100);
                foreach ($response['data'] as $address) {
                    if (($address['addressType'] ?? '') !== 'sender') {
                        continue;
                    }

                    $senders[] = $address;
                }

                $pageCount = max(1, (int) $response['pageCount']);
                ++$page;
            } while ($page <= $pageCount);

            $this->senderAddressRepository->replaceByShop($shopId, $connection['country'], $senders, $attemptedAt);

            $this->syncMetadataRepository->upsert([
                'sync_type' => 'sender_addresses',
                'scope_key' => $scopeKey,
                'id_shop' => $shopId,
                'country_code' => $connection['country'],
                'last_attempt_at' => $attemptedAt,
                'last_success_at' => $attemptedAt,
                'status' => 'success',
                'item_count' => count($senders),
                'error_message' => null,
                'api_version' => $connection['api_version'],
            ]);

            return [
                'status' => 'success',
                'item_count' => count($senders),
                'message' => sprintf('Se sincronizaron %d remitentes.', count($senders)),
            ];
        } catch (\Throwable $exception) {
            $this->syncMetadataRepository->upsert([
                'sync_type' => 'sender_addresses',
                'scope_key' => $scopeKey,
                'id_shop' => $shopId,
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
