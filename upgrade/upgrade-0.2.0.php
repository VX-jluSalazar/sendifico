<?php

use Vx\Sendifico\Install\CacheSchemaInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_2_0($module): bool
{
    return (new CacheSchemaInstaller())->install();
}
