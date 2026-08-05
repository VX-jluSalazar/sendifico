<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_9_0($module): bool
{
    if (!(bool) $module->registerHook([
        'displayAdminOrderSideBottom',
    ])) {
        return false;
    }

    if ((int) Tab::getIdFromClassName('AdminVxSendificoOperations') > 0) {
        return true;
    }

    $parentId = (int) Tab::getIdFromClassName('CONFIGURE');
    $tab = new Tab();
    $tab->active = true;
    $tab->module = 'vx_sendifico';
    $tab->class_name = 'AdminVxSendificoOperations';
    $tab->enabled = true;
    $tab->id_parent = $parentId;
    $tab->position = Tab::getNewLastPosition($parentId);

    foreach (Language::getLanguages(false) as $lang) {
        $tab->name[(int) $lang['id_lang']] = 'Sendifico Shipments';
    }

    return $tab->save();
}
