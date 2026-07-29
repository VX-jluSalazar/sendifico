<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_5_0($module): bool
{
    return (bool) $module->registerHook([
        'displayHeader',
        'displayAfterCarrier',
        'actionFilterDeliveryOptionList',
        'actionCarrierProcess',
        'actionValidateStepComplete',
    ]);
}
