<?php

namespace Vx\Sendifico\Repository;

use Doctrine\DBAL\Connection;
use PDO;

final class CarrierMappingRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function upsert(array $data): void
    {
        if (!$this->tableExists()) {
            return;
        }

        $existingId = $this->connection->createQueryBuilder()
            ->select('id_vx_sendifico_carrier_map')
            ->from($this->getTableName())
            ->where('id_shop = :shopId')
            ->andWhere('carrier_token = :carrierToken')
            ->setParameter('shopId', (int) $data['id_shop'])
            ->setParameter('carrierToken', (string) $data['carrier_token'])
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();

        $payload = $data;
        $payload['updated_at'] = date('Y-m-d H:i:s');

        if ($existingId !== false) {
            unset($payload['created_at']);

            $this->connection->update($this->getTableName(), $payload, [
                'id_vx_sendifico_carrier_map' => (int) $existingId,
            ]);

            return;
        }

        $payload['created_at'] = $payload['created_at'] ?? date('Y-m-d H:i:s');
        $this->connection->insert($this->getTableName(), $payload);
    }

    /**
     * @param array<int, int> $shopIds
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByShopIds(array $shopIds): array
    {
        if (!$this->tableExists() || $shopIds === []) {
            return [];
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from($this->getTableName())
            ->where($queryBuilder->expr()->in('id_shop', array_map('intval', $shopIds)))
            ->orderBy('display_name', 'ASC');

        $statement = $queryBuilder->execute();
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    private function getTableName(): string
    {
        return _DB_PREFIX_ . 'vx_sendifico_carrier_map';
    }

    private function tableExists(): bool
    {
        $statement = $this->connection->executeQuery('SHOW TABLES LIKE ?', [$this->getTableName()]);

        return $statement->fetchColumn() !== false;
    }
}
