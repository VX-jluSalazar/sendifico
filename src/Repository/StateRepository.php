<?php

namespace Vx\Sendifico\Repository;

use Doctrine\DBAL\Connection;

final class StateRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function findNameById(int $stateId): ?string
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('name')
            ->from(_DB_PREFIX_ . 'state')
            ->where('id_state = :stateId')
            ->setParameter('stateId', $stateId)
            ->setMaxResults(1)
            ->execute();
        $value = $statement->fetchColumn();

        return is_string($value) && $value !== '' ? $value : null;
    }
}
