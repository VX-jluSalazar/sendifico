<?php

use Vx\Sendifico\Install\TraceabilitySchemaInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_3_0($module): bool
{
    return (new TraceabilitySchemaInstaller())->install();
}
