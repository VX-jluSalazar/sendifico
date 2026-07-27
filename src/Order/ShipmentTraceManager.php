<?php

namespace Vx\Sendifico\Order;

use Vx\Sendifico\Repository\ShipmentEventRepository;
use Vx\Sendifico\Repository\ShipmentRepository;

final class ShipmentTraceManager
{
    public function __construct(
        private readonly ShipmentRepository $shipmentRepository,
        private readonly ShipmentEventRepository $shipmentEventRepository
    ) {
    }

    /**
     * @param array<string, mixed> $shipmentData
     * @param array<string, mixed> $eventData
     */
    public function createShipmentTrace(array $shipmentData, array $eventData = []): int
    {
        $shipmentTraceId = $this->shipmentRepository->create($shipmentData);

        if ($shipmentTraceId > 0 && $eventData !== []) {
            $eventData['id_vx_sendifico_shipment'] = $shipmentTraceId;
            $this->shipmentEventRepository->create($eventData);
        }

        return $shipmentTraceId;
    }

    /**
     * @param array<string, mixed> $shipmentData
     * @param array<string, mixed> $eventData
     */
    public function updateShipmentTrace(int $shipmentTraceId, array $shipmentData, array $eventData = []): void
    {
        $this->shipmentRepository->update($shipmentTraceId, $shipmentData);

        if ($eventData !== []) {
            $eventData['id_vx_sendifico_shipment'] = $shipmentTraceId;
            $this->shipmentEventRepository->create($eventData);
        }
    }
}
