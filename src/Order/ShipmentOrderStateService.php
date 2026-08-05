<?php

namespace Vx\Sendifico\Order;

use Order;
use OrderHistory;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use Vx\Sendifico\Configuration\ConfigurationKeys;

final class ShipmentOrderStateService
{
    public function __construct(
        private readonly ConfigurationInterface $configuration
    ) {
    }

    public function markCourierUnpaid(Order $order): void
    {
        $orderStateId = (int) $this->configuration->get(ConfigurationKeys::UNPAID_ORDER_STATE_ID, 0);
        if ($orderStateId <= 0 || (int) $order->current_state === $orderStateId) {
            return;
        }

        $history = new OrderHistory();
        $history->id_order = (int) $order->id;
        $history->changeIdOrderState($orderStateId, $order, false);
        $history->addWithemail(false);
    }
}
