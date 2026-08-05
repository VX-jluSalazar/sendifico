<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use Vx\Sendifico\Install\CarrierProvisionInstaller;

function upgrade_module_0_6_2($module): bool
{
    return (new CarrierProvisionInstaller())->install();
}
