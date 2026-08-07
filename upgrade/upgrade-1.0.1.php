<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_1($module): bool
{
    $tabs = [
        [
            'class_name' => 'AdminVxSendifico',
            'label' => 'Sendifico',
            'parent_class_name' => 'CONFIGURE',
            'visible' => true,
        ],
        [
            'class_name' => 'AdminVxSendificoConfiguration',
            'label' => 'Configuración',
            'parent_class_name' => 'AdminVxSendifico',
            'visible' => true,
        ],
        [
            'class_name' => 'AdminVxSendificoOperations',
            'label' => 'Envios',
            'parent_class_name' => 'AdminVxSendifico',
            'visible' => true,
        ],
    ];

    foreach ($tabs as $tabData) {
        $tabId = (int) Tab::getIdFromClassName($tabData['class_name']);
        $parentId = (int) Tab::getIdFromClassName($tabData['parent_class_name']);
        $tab = $tabId > 0 ? new Tab($tabId) : new Tab();
        $tab->active = true;
        $tab->module = 'vx_sendifico';
        $tab->class_name = $tabData['class_name'];
        $tab->enabled = (bool) $tabData['visible'];
        $tab->id_parent = $parentId;
        if ($tabId <= 0 || (int) $tab->position <= 0) {
            $tab->position = Tab::getNewLastPosition($parentId);
        }

        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = $tabData['label'];
        }

        if ($tabId > 0 ? !$tab->update() : !$tab->save()) {
            return false;
        }
    }

    return true;
}
