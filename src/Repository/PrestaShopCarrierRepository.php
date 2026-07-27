<?php

namespace Vx\Sendifico\Repository;

use Doctrine\DBAL\Connection;
use PDO;

final class PrestaShopCarrierRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * @return array{id_carrier:int, id_reference:int, name:string}|null
     */
    public function findLatestModuleCarrierByName(string $name): ?array
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('id_carrier', 'id_reference', 'name')
            ->from(_DB_PREFIX_ . 'carrier')
            ->where('external_module_name = :moduleName')
            ->andWhere('name = :name')
            ->andWhere('deleted = 0')
            ->setParameter('moduleName', 'vx_sendifico')
            ->setParameter('name', $name)
            ->orderBy('id_carrier', 'DESC')
            ->setMaxResults(1)
            ->execute();

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? [
            'id_carrier' => (int) $row['id_carrier'],
            'id_reference' => (int) $row['id_reference'],
            'name' => (string) $row['name'],
        ] : null;
    }

    /**
     * @return array{id_carrier:int, id_reference:int, name:string}|null
     */
    public function findLatestCarrierByReference(int $idReference): ?array
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('id_carrier', 'id_reference', 'name')
            ->from(_DB_PREFIX_ . 'carrier')
            ->where('id_reference = :idReference')
            ->andWhere('deleted = 0')
            ->setParameter('idReference', $idReference)
            ->orderBy('id_carrier', 'DESC')
            ->setMaxResults(1)
            ->execute();

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? [
            'id_carrier' => (int) $row['id_carrier'],
            'id_reference' => (int) $row['id_reference'],
            'name' => (string) $row['name'],
        ] : null;
    }

    /**
     * @return array{id_carrier:int, id_reference:int, name:string}|null
     */
    public function findCarrierById(int $idCarrier): ?array
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('id_carrier', 'id_reference', 'name')
            ->from(_DB_PREFIX_ . 'carrier')
            ->where('id_carrier = :idCarrier')
            ->setParameter('idCarrier', $idCarrier)
            ->setMaxResults(1)
            ->execute();

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? [
            'id_carrier' => (int) $row['id_carrier'],
            'id_reference' => (int) $row['id_reference'],
            'name' => (string) $row['name'],
        ] : null;
    }

    /**
     * @return array<int, int>
     */
    public function getLanguageIds(): array
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('id_lang')
            ->from(_DB_PREFIX_ . 'lang')
            ->where('active = 1')
            ->orderBy('id_lang', 'ASC')
            ->execute();

        $rows = $statement->fetchAll(PDO::FETCH_COLUMN);

        return array_map('intval', $rows);
    }

    /**
     * @return array<int, int>
     */
    public function getGroupIds(): array
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('id_group')
            ->from(_DB_PREFIX_ . 'group')
            ->orderBy('id_group', 'ASC')
            ->execute();

        $rows = $statement->fetchAll(PDO::FETCH_COLUMN);

        return array_map('intval', $rows);
    }

    /**
     * @return array<int, int>
     */
    public function getZoneIds(): array
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('id_zone')
            ->from(_DB_PREFIX_ . 'zone')
            ->where('active = 1')
            ->orderBy('id_zone', 'ASC')
            ->execute();

        $rows = $statement->fetchAll(PDO::FETCH_COLUMN);

        return array_map('intval', $rows);
    }
}
