<?php

namespace Vx\Sendifico\Repository;

use Doctrine\DBAL\Connection;

final class AddressMetadataRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function upsert(array $data): void
    {
        if (!$this->tableExists()) {
            return;
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('id_vx_sendifico_address_meta')
            ->from($this->getTableName())
            ->where('id_address = :idAddress')
            ->setParameter('idAddress', (int) $data['id_address'])
            ->setMaxResults(1)
            ->execute();
        $existingId = $statement->fetchColumn();

        $payload = $data;
        $payload['updated_at'] = date('Y-m-d H:i:s');

        if ($existingId !== false) {
            unset($payload['created_at']);

            $this->connection->update($this->getTableName(), $payload, [
                'id_vx_sendifico_address_meta' => (int) $existingId,
            ]);

            return;
        }

        $payload['created_at'] = $payload['created_at'] ?? date('Y-m-d H:i:s');
        $this->connection->insert($this->getTableName(), $payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByAddressId(int $addressId): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->getTableName())
            ->where('id_address = :idAddress')
            ->setParameter('idAddress', $addressId)
            ->setMaxResults(1)
            ->execute();
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function deleteByAddressId(int $addressId): void
    {
        if (!$this->tableExists()) {
            return;
        }

        $this->connection->delete($this->getTableName(), ['id_address' => $addressId]);
    }

    private function getTableName(): string
    {
        return _DB_PREFIX_ . 'vx_sendifico_address_meta';
    }

    private function tableExists(): bool
    {
        $statement = $this->connection->executeQuery('SHOW TABLES LIKE ?', [$this->getTableName()]);

        return $statement->fetchColumn() !== false;
    }
}
