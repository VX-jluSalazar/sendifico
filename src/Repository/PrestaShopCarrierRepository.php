<?php

namespace Vx\Sendifico\Repository;

use Doctrine\DBAL\Connection;
use PDO;

final class PrestaShopCarrierRepository
{
    private const SENDIFICO_WEIGHT_RANGE_MIN = 0.0;
    private const SENDIFICO_WEIGHT_RANGE_MAX = 100000.0;

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

    /**
     * PrestaShop only includes module carriers in cart packages when need_range = 1 and
     * a matching delivery range exists. Sendifico still returns the final price externally.
     *
     * @param array<int, int> $zoneIds
     */
    public function ensureExternalCarrierEligibility(int $carrierId, array $zoneIds): void
    {
        $this->connection->update(_DB_PREFIX_ . 'carrier', [
            'shipping_external' => 1,
            'need_range' => 1,
            'shipping_method' => 1,
            'range_behavior' => 0,
        ], [
            'id_carrier' => $carrierId,
        ]);

        $rangeId = $this->findOrCreateWeightRange($carrierId);
        foreach ($zoneIds as $zoneId) {
            $this->ensureDeliveryRow($carrierId, $rangeId, (int) $zoneId);
        }
    }

    private function findOrCreateWeightRange(int $carrierId): int
    {
        $rangeId = $this->connection->createQueryBuilder()
            ->select('id_range_weight')
            ->from(_DB_PREFIX_ . 'range_weight')
            ->where('id_carrier = :carrierId')
            ->andWhere('delimiter1 = :delimiter1')
            ->andWhere('delimiter2 = :delimiter2')
            ->setParameter('carrierId', $carrierId)
            ->setParameter('delimiter1', self::SENDIFICO_WEIGHT_RANGE_MIN)
            ->setParameter('delimiter2', self::SENDIFICO_WEIGHT_RANGE_MAX)
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();

        if ($rangeId !== false) {
            return (int) $rangeId;
        }

        $this->connection->insert(_DB_PREFIX_ . 'range_weight', [
            'id_carrier' => $carrierId,
            'delimiter1' => self::SENDIFICO_WEIGHT_RANGE_MIN,
            'delimiter2' => self::SENDIFICO_WEIGHT_RANGE_MAX,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    private function ensureDeliveryRow(int $carrierId, int $rangeId, int $zoneId): void
    {
        $this->normalizeDeliveryScope($carrierId, $rangeId, $zoneId);

        $deliveryId = $this->connection->createQueryBuilder()
            ->select('id_delivery')
            ->from(_DB_PREFIX_ . 'delivery')
            ->where('id_carrier = :carrierId')
            ->andWhere('id_range_weight = :rangeId')
            ->andWhere('id_zone = :zoneId')
            ->andWhere('id_range_price = 0')
            ->andWhere('id_shop IS NULL')
            ->andWhere('id_shop_group IS NULL')
            ->setParameter('carrierId', $carrierId)
            ->setParameter('rangeId', $rangeId)
            ->setParameter('zoneId', $zoneId)
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();

        if ($deliveryId !== false) {
            return;
        }

        $this->connection->executeStatement(
            'INSERT INTO `' . _DB_PREFIX_ . 'delivery`
                (id_shop, id_shop_group, id_carrier, id_range_price, id_range_weight, id_zone, price)
             VALUES (NULL, NULL, :carrierId, 0, :rangeId, :zoneId, :price)',
            [
                'carrierId' => $carrierId,
                'rangeId' => $rangeId,
                'zoneId' => $zoneId,
                'price' => 0.0,
            ]
        );
    }

    private function normalizeDeliveryScope(int $carrierId, int $rangeId, int $zoneId): void
    {
        $this->connection->executeStatement(
            'UPDATE `' . _DB_PREFIX_ . 'delivery`
             SET id_shop = NULL, id_shop_group = NULL
             WHERE id_carrier = :carrierId
               AND id_range_weight = :rangeId
               AND id_range_price = 0
               AND id_zone = :zoneId
               AND id_shop = 0
               AND id_shop_group = 0',
            [
                'carrierId' => $carrierId,
                'rangeId' => $rangeId,
                'zoneId' => $zoneId,
            ]
        );
    }
}
