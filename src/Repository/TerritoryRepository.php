<?php

namespace Vx\Sendifico\Repository;

use Doctrine\DBAL\Connection;

final class TerritoryRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $territories
     */
    public function replaceByCountry(string $countryCode, array $territories, string $syncedAt): void
    {
        if (!$this->tableExists()) {
            return;
        }

        $this->connection->beginTransaction();

        try {
            $this->connection->delete($this->getTableName(), ['country_code' => $countryCode]);

            foreach ($territories as $territory) {
                $this->connection->insert($this->getTableName(), [
                    'country_code' => $countryCode,
                    'territory_base_id' => (string) $territory['territoryBaseId'],
                    'territory1_name' => (string) $territory['territory1Name'],
                    'territory2_name' => (string) $territory['territory2Name'],
                    'territory3_name' => (string) $territory['territory3Name'],
                    'searchable_text' => (string) $territory['searchableText'],
                    'synced_at' => $syncedAt,
                ]);
            }

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }
    }

    public function countByCountry(string $countryCode): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($this->getTableName())
            ->where('country_code = :countryCode')
            ->setParameter('countryCode', $countryCode)
            ->execute();

        return (int) $statement->fetchColumn();
    }

    private function getTableName(): string
    {
        return _DB_PREFIX_ . 'vx_sendifico_territory';
    }

    private function tableExists(): bool
    {
        $statement = $this->connection->executeQuery('SHOW TABLES LIKE ?', [$this->getTableName()]);

        return $statement->fetchColumn() !== false;
    }
}
