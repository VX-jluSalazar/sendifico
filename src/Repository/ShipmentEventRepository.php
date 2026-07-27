<?php

namespace Vx\Sendifico\Repository;

use Doctrine\DBAL\Connection;
use PDO;

final class ShipmentEventRepository
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

        $payload = $data;

        foreach (['payload_summary', 'response_summary'] as $column) {
            if (isset($payload[$column]) && is_array($payload[$column])) {
                $payload[$column] = json_encode($payload[$column], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        $payload['created_at'] = $payload['created_at'] ?? date('Y-m-d H:i:s');

        $this->connection->insert($this->getTableName(), $payload);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByShipmentTraceId(int $shipmentTraceId, int $limit = 100): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->getTableName())
            ->where('id_vx_sendifico_shipment = :shipmentTraceId')
            ->setParameter('shipmentTraceId', $shipmentTraceId)
            ->orderBy('created_at', 'DESC')
            ->setMaxResults($limit)
            ->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    private function getTableName(): string
    {
        return _DB_PREFIX_ . 'vx_sendifico_shipment_event';
    }

    private function tableExists(): bool
    {
        $statement = $this->connection->executeQuery('SHOW TABLES LIKE ?', [$this->getTableName()]);

        return $statement->fetchColumn() !== false;
    }
}
