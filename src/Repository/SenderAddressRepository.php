<?php

namespace Vx\Sendifico\Repository;

use Doctrine\DBAL\Connection;

final class SenderAddressRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $addresses
     */
    public function replaceByShop(int $shopId, string $countryCode, array $addresses, string $syncedAt): void
    {
        if (!$this->tableExists()) {
            return;
        }

        $this->connection->beginTransaction();

        try {
            $this->connection->delete($this->getTableName(), ['id_shop' => $shopId]);

            foreach ($addresses as $address) {
                $this->connection->insert($this->getTableName(), [
                    'id_shop' => $shopId,
                    'remote_address_id' => (int) $address['addressId'],
                    'address_type' => (string) $address['addressType'],
                    'full_name' => (string) $address['fullName'],
                    'company' => $this->nullableString($address['company'] ?? null),
                    'email' => $this->nullableString($address['email'] ?? null),
                    'street_line1' => (string) $address['streetLine1'],
                    'reference' => $this->nullableString($address['reference'] ?? null),
                    'territory_base_id' => (string) $address['territoryBaseId'],
                    'country_code' => $countryCode,
                    'zip_code' => $this->nullableString($address['zip'] ?? null),
                    'lat' => isset($address['lat']) ? (float) $address['lat'] : null,
                    'lng' => isset($address['lng']) ? (float) $address['lng'] : null,
                    'phone' => (string) $address['phone'],
                    'object_created' => $this->normalizeDateTime($address['objectCreated'] ?? null),
                    'synced_at' => $syncedAt,
                ]);
            }

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }
    }

    public function countByShopId(int $shopId): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($this->getTableName())
            ->where('id_shop = :shopId')
            ->setParameter('shopId', $shopId)
            ->execute();

        return (int) $statement->fetchColumn();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByRemoteAddressId(int $shopId, string $remoteAddressId): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->getTableName())
            ->where('id_shop = :shopId')
            ->andWhere('remote_address_id = :remoteAddressId')
            ->setParameter('shopId', $shopId)
            ->setParameter('remoteAddressId', (int) $remoteAddressId)
            ->setMaxResults(1)
            ->execute();
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<int, int> $shopIds
     *
     * @return array<int, array<string, mixed>>
     */
    public function findSendersByShopIds(array $shopIds): array
    {
        if (!$this->tableExists() || $shopIds === []) {
            return [];
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from($this->getTableName())
            ->where($queryBuilder->expr()->in('id_shop', array_map('intval', $shopIds)))
            ->andWhere('address_type = :addressType')
            ->setParameter('addressType', 'sender')
            ->orderBy('full_name', 'ASC')
            ->addOrderBy('remote_address_id', 'ASC');

        $statement = $queryBuilder->execute();
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    private function getTableName(): string
    {
        return _DB_PREFIX_ . 'vx_sendifico_sender_address';
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function tableExists(): bool
    {
        $statement = $this->connection->executeQuery('SHOW TABLES LIKE ?', [$this->getTableName()]);

        return $statement->fetchColumn() !== false;
    }
}
