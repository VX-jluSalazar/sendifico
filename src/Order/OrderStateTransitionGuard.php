<?php

namespace Vx\Sendifico\Order;

use OrderState;

final class OrderStateTransitionGuard
{
    public function shouldTriggerAutomation(?OrderState $orderState, ?int $unpaidOrderStateId = null): bool
    {
        if (!$orderState instanceof OrderState || (int) $orderState->id <= 0) {
            return false;
        }

        if ($unpaidOrderStateId !== null && $unpaidOrderStateId > 0 && (int) $orderState->id === $unpaidOrderStateId) {
            return false;
        }

        return (bool) $orderState->paid || (bool) $orderState->logable;
    }
}
