<?php

namespace Vx\Sendifico\Repository;

use Doctrine\DBAL\Connection;

final class CountryRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function findIsoCodeById(int $countryId): ?string
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('iso_code')
            ->from(_DB_PREFIX_ . 'country')
            ->where('id_country = :countryId')
            ->setParameter('countryId', $countryId)
            ->setMaxResults(1)
            ->execute();
        $value = $statement->fetchColumn();

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function findIdByIsoCode(string $isoCode): ?int
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('id_country')
            ->from(_DB_PREFIX_ . 'country')
            ->where('iso_code = :isoCode')
            ->setParameter('isoCode', strtoupper($isoCode))
            ->setMaxResults(1)
            ->execute();
        $value = $statement->fetchColumn();

        return $value !== false ? (int) $value : null;
    }
}
