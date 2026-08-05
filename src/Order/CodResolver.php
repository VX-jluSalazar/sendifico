<?php

namespace Vx\Sendifico\Order;

use Order;

final class CodResolver
{
    /**
     * @param array<string, mixed> $configuration
     */
    public function resolveFromOrder(Order $order, array $configuration): float
    {
        if (!$this->isCodPaymentModule((string) $order->module, $configuration)) {
            return 0.0;
        }

        return round(max(0.0, (float) $order->total_paid_tax_incl), 2);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public function isCodPaymentModule(string $moduleName, array $configuration): bool
    {
        $normalizedModule = trim($moduleName);
        if ($normalizedModule === '') {
            return false;
        }

        $modules = is_array($configuration['cod_payment_methods'] ?? null) ? $configuration['cod_payment_methods'] : [];

        return in_array($normalizedModule, $modules, true);
    }
}
