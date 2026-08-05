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
    public function findById(int $shipmentTraceId): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $queryBuilder = $this->baseSelectQueryBuilder();
        $statement = $queryBuilder
            ->where('id_vx_sendifico_shipment = :shipmentTraceId')
            ->setParameter('shipmentTraceId', $shipmentTraceId)
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

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchForAdmin(array $filters, int $page = 1, int $limit = 20): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        $queryBuilder = $this->baseSelectQueryBuilder();
        $this->applyAdminFilters($queryBuilder, $filters);

        $statement = $queryBuilder
            ->orderBy('updated_at', 'DESC')
            ->addOrderBy('id_vx_sendifico_shipment', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countForAdmin(array $filters): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($this->getTableName());
        $this->applyAdminFilters($queryBuilder, $filters);

        return (int) $queryBuilder->execute()->fetchColumn();
    }

    private function baseSelectQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->getTableName());
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyAdminFilters(QueryBuilder $queryBuilder, array $filters): void
    {
        if (isset($filters['id_order']) && (int) $filters['id_order'] > 0) {
            $queryBuilder
                ->andWhere('id_order = :adminOrderId')
                ->setParameter('adminOrderId', (int) $filters['id_order']);
        }

        if (isset($filters['id_cart']) && (int) $filters['id_cart'] > 0) {
            $queryBuilder
                ->andWhere('id_cart = :adminCartId')
                ->setParameter('adminCartId', (int) $filters['id_cart']);
        }

        if (isset($filters['local_state']) && is_string($filters['local_state']) && trim($filters['local_state']) !== '') {
            $queryBuilder
                ->andWhere('local_state = :adminLocalState')
                ->setParameter('adminLocalState', trim($filters['local_state']));
        }

        if (array_key_exists('is_paid', $filters) && $filters['is_paid'] !== '' && $filters['is_paid'] !== null) {
            $queryBuilder
                ->andWhere('is_paid = :adminIsPaid')
                ->setParameter('adminIsPaid', (int) $filters['is_paid']);
        }

        if (!empty($filters['retryable'])) {
            $states = array_map(
                static fn (string $state): string => "'" . pSQL($state) . "'",
                \Vx\Sendifico\Order\ShipmentTraceState::RETRYABLE_STATES
            );

            $queryBuilder->andWhere('local_state IN (' . implode(', ', $states) . ')');
        }
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
