<?php

namespace Vx\Sendifico\Install;

use Language;
use Module;
use Tab;

class Installer
{
    private array $hooks = [];

    private array $tabs = [
        [
            'name' => 'vx_sendifico',
            'class_name' => 'AdminVxSendificoConfiguration',
            'label' => 'Velox Sendifico',
            'parent_class_name' => 'CONFIGURE',
            'visible' => false,
        ],
    ];

    public function install(Module $module): bool
    {
        return $this->registerHooks($module)
            && (new ConfigurationInstaller())->install()
            && $this->installTabs();
    }

    public function uninstall(): bool
    {
        return (new ConfigurationInstaller())->uninstall()
            && $this->uninstallTabs();
    }

    private function registerHooks(Module $module): bool
    {
        if ($this->hooks === []) {
            return true;
        }

        return (bool) $module->registerHook($this->hooks);
    }

    private function installTabs(): bool
    {
        foreach ($this->tabs as $data) {
            if ((int) Tab::getIdFromClassName($data['class_name']) > 0) {
                continue;
            }

            $parentId = (int) Tab::getIdFromClassName($data['parent_class_name']);
            $tab = new Tab();
            $tab->active = true;
            $tab->module = $data['name'];
            $tab->class_name = $data['class_name'];
            $tab->enabled = (bool) $data['visible'];
            $tab->id_parent = $parentId;
            $tab->position = Tab::getNewLastPosition($parentId);

            foreach (Language::getLanguages(false) as $lang) {
                $tab->name[(int) $lang['id_lang']] = $data['label'];
            }

            if (!$tab->save()) {
                return false;
            }
        }

        return true;
    }

    private function uninstallTabs(): bool
    {
        foreach ($this->tabs as $data) {
            $tabId = (int) Tab::getIdFromClassName($data['class_name']);
            if ($tabId <= 0) {
                continue;
            }

            $tab = new Tab($tabId);
            if (!$tab->delete()) {
                return false;
            }
        }

        return true;
    }
}
