<?php

namespace Vx\Sendifico\Order;

use Order;
use OrderState;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use Vx\Sendifico\Configuration\ConfigurationKeys;

final class OrderLifecycleHookHandler
{
    public function __construct(
        private readonly ShipmentAutomationService $shipmentAutomationService,
        private readonly OrderStateTransitionGuard $orderStateTransitionGuard,
        private readonly ConfigurationInterface $configuration
    ) {
    }

    public function handleValidatedOrder(Order $order, ?OrderState $orderState = null): void
    {
        if (!$this->orderStateTransitionGuard->shouldTriggerAutomation($orderState, $this->getUnpaidOrderStateId())) {
            return;
        }

        $this->shipmentAutomationService->processOrder($order, $orderState, 'actionValidateOrder');
    }

    public function handleOrderStatusPostUpdate(int $orderId, ?OrderState $orderState = null): void
    {
        if ($orderId <= 0 || !$this->orderStateTransitionGuard->shouldTriggerAutomation($orderState, $this->getUnpaidOrderStateId())) {
            return;
        }

        $order = new Order($orderId);
        if ((int) $order->id <= 0) {
            return;
        }

        $this->shipmentAutomationService->processOrder($order, $orderState, 'actionOrderStatusPostUpdate');
    }

    private function getUnpaidOrderStateId(): int
    {
        return (int) $this->configuration->get(ConfigurationKeys::UNPAID_ORDER_STATE_ID, 0);
    }
}
