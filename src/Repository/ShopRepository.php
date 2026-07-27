<?php

namespace Vx\Sendifico\Repository;

use Doctrine\DBAL\Connection;

final class ShopRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * @return array<int, int>
     */
    public function getActiveShopIds(): array
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('id_shop')
            ->from(_DB_PREFIX_ . 'shop')
            ->where('active = 1')
            ->orderBy('id_shop', 'ASC')
            ->execute();
        $rows = $statement->fetchAll(\PDO::FETCH_COLUMN);

        return array_map('intval', $rows);
    }

    /**
     * @param array<int, int> $shopIds
     *
     * @return array<int, string>
     */
    public function getShopNamesByIds(array $shopIds): array
    {
        if ($shopIds === []) {
            return [];
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('s.id_shop', 's.name')
            ->from(_DB_PREFIX_ . 'shop', 's')
            ->where($queryBuilder->expr()->in('s.id_shop', array_map('intval', $shopIds)));

        $statement = $queryBuilder->execute();
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $shopNames = [];
        foreach ($rows as $row) {
            $shopNames[(int) $row['id_shop']] = (string) $row['name'];
        }

        return $shopNames;
    }
}
