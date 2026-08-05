<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use Vx\Sendifico\Install\OrderStateInstaller;

function upgrade_module_0_8_0($module): bool
{
    return (bool) $module->registerHook([
        'actionValidateOrder',
        'actionOrderStatusPostUpdate',
    ]) && (new OrderStateInstaller())->install();
}
