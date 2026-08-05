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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByCountry(string $countryCode): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->getTableName())
            ->where('country_code = :countryCode')
            ->setParameter('countryCode', $countryCode)
            ->orderBy('territory1_name', 'ASC')
            ->addOrderBy('territory2_name', 'ASC')
            ->addOrderBy('territory3_name', 'ASC')
            ->execute();

        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOneByBaseId(string $countryCode, string $territoryBaseId): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->getTableName())
            ->where('country_code = :countryCode')
            ->andWhere('territory_base_id = :territoryBaseId')
            ->setParameter('countryCode', $countryCode)
            ->setParameter('territoryBaseId', $territoryBaseId)
            ->setMaxResults(1)
            ->execute();

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOneByNames(string $countryCode, string $territory1Name, string $territory2Name, string $territory3Name): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->getTableName())
            ->where('country_code = :countryCode')
            ->andWhere('LOWER(territory1_name) = LOWER(:territory1Name)')
            ->andWhere('LOWER(territory2_name) = LOWER(:territory2Name)')
            ->andWhere('LOWER(territory3_name) = LOWER(:territory3Name)')
            ->setParameter('countryCode', $countryCode)
            ->setParameter('territory1Name', $territory1Name)
            ->setParameter('territory2Name', $territory2Name)
            ->setParameter('territory3Name', $territory3Name)
            ->setMaxResults(1)
            ->execute();

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
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
