<?php

namespace Vx\Sendifico\Install;

use Configuration;
use Language;
use OrderState;
use Vx\Sendifico\Configuration\ConfigurationKeys;

final class OrderStateInstaller
{
    private const STATE_COLOR = '#d9534f';

    public function install(): bool
    {
        $existingId = (int) Configuration::get(ConfigurationKeys::UNPAID_ORDER_STATE_ID);
        if ($existingId > 0 && $this->isExistingState($existingId)) {
            return true;
        }

        $orderState = new OrderState();
        $orderState->color = self::STATE_COLOR;
        $orderState->send_email = false;
        $orderState->module_name = 'vx_sendifico';
        $orderState->invoice = false;
        $orderState->unremovable = false;
        $orderState->hidden = false;
        $orderState->logable = false;
        $orderState->delivery = false;
        $orderState->shipped = false;
        $orderState->paid = false;
        $orderState->pdf_invoice = false;
        $orderState->pdf_delivery = false;
        $orderState->deleted = false;

        foreach (Language::getLanguages(false) as $language) {
            $orderState->name[(int) $language['id_lang']] = 'Courier no pagado';
        }

        if (!$orderState->add()) {
            return false;
        }

        Configuration::updateValue(ConfigurationKeys::UNPAID_ORDER_STATE_ID, (int) $orderState->id);

        return true;
    }

    public function uninstall(): bool
    {
        $orderStateId = (int) Configuration::get(ConfigurationKeys::UNPAID_ORDER_STATE_ID);
        if ($orderStateId > 0 && $this->isExistingState($orderStateId)) {
            $orderState = new OrderState($orderStateId);
            $orderState->delete();
        }

        Configuration::deleteByName(ConfigurationKeys::UNPAID_ORDER_STATE_ID);

        return true;
    }

    private function isExistingState(int $orderStateId): bool
    {
        $orderState = new OrderState($orderStateId);

        return (int) $orderState->id > 0;
    }
}
