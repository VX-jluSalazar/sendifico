<?php

namespace Vx\Sendifico\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

final class ShipmentRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $payload = $this->normalizePayload($data);
        $this->connection->insert($this->getTableName(), $payload);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $shipmentTraceId, array $data): void
    {
        if (!$this->tableExists()) {
            return;
        }

        $payload = $this->normalizePayload($data, false);
        if ($payload === []) {
            return;
        }

        $payload['updated_at'] = date('Y-m-d H:i:s');

        $this->connection->update($this->getTableName(), $payload, [
            'id_vx_sendifico_shipment' => $shipmentTraceId,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByOrderId(int $orderId): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $queryBuilder = $this->baseSelectQueryBuilder();
        $statement = $queryBuilder
            ->where('id_order = :orderId')
            ->setParameter('orderId', $orderId)
            ->setMaxResults(1)
            ->execute();

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByRemoteShipmentId(int $remoteShipmentId): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $queryBuilder = $this->baseSelectQueryBuilder();
        $statement = $queryBuilder
            ->where('remote_shipment_id = :remoteShipmentId')
            ->setParameter('remoteShipmentId', $remoteShipmentId)
            ->setMaxResults(1)
            ->execute();

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByExtId(int $shopId, string $extId): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $queryBuilder = $this->baseSelectQueryBuilder();
        $statement = $queryBuilder
            ->where('id_shop = :shopId')
            ->andWhere('ext_id = :extId')
            ->setParameter('shopId', $shopId)
            ->setParameter('extId', $extId)
            ->setMaxResults(1)
            ->execute();

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPendingByCartId(int $cartId): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $queryBuilder = $this->baseSelectQueryBuilder();
        $statement = $queryBuilder
            ->where('id_cart = :cartId')
            ->andWhere('id_order IS NULL')
            ->setParameter('cartId', $cartId)
            ->orderBy('updated_at', 'DESC')
            ->setMaxResults(1)
            ->execute();

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findRetryableShipments(int $limit = 50): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $states = ['purchase_failed', 'reconciliation_required', 'rate_mismatch'];
        $quotedStates = array_map(static fn (string $state): string => "'" . pSQL($state) . "'", $states);

        $queryBuilder = $this->baseSelectQueryBuilder();
        $statement = $queryBuilder
            ->where('local_state IN (' . implode(', ', $quotedStates) . ')')
            ->andWhere('(next_retry_at IS NULL OR next_retry_at <= :now)')
            ->setParameter('now', date('Y-m-d H:i:s'))
            ->orderBy('updated_at', 'ASC')
            ->setMaxResults($limit)
            ->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    private function baseSelectQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->getTableName());
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data, bool $withCreateTimestamp = true): array
    {
        $payload = [];

        foreach ($data as $column => $value) {
            if (in_array($column, ['request_snapshot', 'response_snapshot'], true) && is_array($value)) {
                $payload[$column] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                continue;
            }

            $payload[$column] = $value;
        }

        if ($withCreateTimestamp) {
            $payload['created_at'] = $payload['created_at'] ?? date('Y-m-d H:i:s');
        }

        $payload['updated_at'] = $payload['updated_at'] ?? date('Y-m-d H:i:s');

        return $payload;
    }

    private function getTableName(): string
    {
        return _DB_PREFIX_ . 'vx_sendifico_shipment';
    }

    private function tableExists(): bool
    {
        $statement = $this->connection->executeQuery('SHOW TABLES LIKE ?', [$this->getTableName()]);

        return $statement->fetchColumn() !== false;
    }
}
