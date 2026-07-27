<?php

namespace Vx\Sendifico\Repository;

use Doctrine\DBAL\Connection;

final class SyncMetadataRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * @param array{sync_type:string, scope_key:string, id_shop:int|null, country_code:string|null, last_attempt_at:string, last_success_at:string|null, status:string, item_count:int, error_message:string|null, api_version:string|null} $metadata
     */
    public function upsert(array $metadata): void
    {
        if (!$this->tableExists()) {
            return;
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('id_vx_sendifico_sync_meta')
            ->from($this->getTableName())
            ->where('sync_type = :syncType')
            ->andWhere('scope_key = :scopeKey')
            ->setParameter('syncType', $metadata['sync_type'])
            ->setParameter('scopeKey', $metadata['scope_key'])
            ->setMaxResults(1)
            ->execute();
        $existingId = $statement->fetchColumn();

        if ($existingId !== false) {
            $this->connection->update($this->getTableName(), $metadata, [
                'id_vx_sendifico_sync_meta' => (int) $existingId,
            ]);

            return;
        }

        $this->connection->insert($this->getTableName(), $metadata);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOne(string $syncType, string $scopeKey): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->getTableName())
            ->where('sync_type = :syncType')
            ->andWhere('scope_key = :scopeKey')
            ->setParameter('syncType', $syncType)
            ->setParameter('scopeKey', $scopeKey)
            ->setMaxResults(1)
            ->execute();
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    private function getTableName(): string
    {
        return _DB_PREFIX_ . 'vx_sendifico_sync_meta';
    }

    private function tableExists(): bool
    {
        $statement = $this->connection->executeQuery('SHOW TABLES LIKE ?', [$this->getTableName()]);

        return $statement->fetchColumn() !== false;
    }
}
