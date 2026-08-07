<?php

namespace Vx\Sendifico\Install;

use Language;
use Module;
use Tab;

class Installer
{
    private array $hooks = [
        'displayHeader',
        'actionFilterDeliveryOptionList',
        'actionCarrierProcess',
        'actionValidateStepComplete',
        'actionValidateOrder',
        'actionOrderStatusPostUpdate',
        'additionalCustomerAddressFields',
        'actionValidateCustomerAddressForm',
        'actionObjectAddressAddAfter',
        'actionObjectAddressUpdateAfter',
        'actionObjectAddressDeleteAfter',
        'actionShopDataDuplication',
        'displayAdminOrderSideBottom',
    ];

    private array $tabs = [
        [
            'name' => 'vx_sendifico',
            'class_name' => 'AdminVxSendifico',
            'label' => 'Sendifico',
            'parent_class_name' => 'CONFIGURE',
            'visible' => true,
        ],
        [
            'name' => 'vx_sendifico',
            'class_name' => 'AdminVxSendificoConfiguration',
            'label' => 'Configuración',
            'parent_class_name' => 'AdminVxSendifico',
            'visible' => true,
        ],
        [
            'name' => 'vx_sendifico',
            'class_name' => 'AdminVxSendificoOperations',
            'label' => 'Envios',
            'parent_class_name' => 'AdminVxSendifico',
            'visible' => true,
        ],
    ];

    public function install(Module $module): bool
    {
        return $this->registerHooks($module)
            && (new CacheSchemaInstaller())->install()
            && (new TraceabilitySchemaInstaller())->install()
            && (new CarrierSchemaInstaller())->install()
            && (new ConfigurationInstaller())->install()
            && (new OrderStateInstaller())->install()
            && $this->installTabs()
            && (new CarrierProvisionInstaller())->install();
    }

    public function uninstall(): bool
    {
        return (new OrderStateInstaller())->uninstall()
            && (new ConfigurationInstaller())->uninstall()
            && (new CarrierSchemaInstaller())->uninstall()
            && (new TraceabilitySchemaInstaller())->uninstall()
            && (new CacheSchemaInstaller())->uninstall()
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
            $tabId = (int) Tab::getIdFromClassName($data['class_name']);
            $parentId = (int) Tab::getIdFromClassName($data['parent_class_name']);
            $tab = $tabId > 0 ? new Tab($tabId) : new Tab();
            $tab->active = true;
            $tab->module = $data['name'];
            $tab->class_name = $data['class_name'];
            $tab->enabled = (bool) $data['visible'];
            $tab->id_parent = $parentId;
            if ($tabId <= 0 || (int) $tab->position <= 0) {
                $tab->position = Tab::getNewLastPosition($parentId);
            }

            foreach (Language::getLanguages(false) as $lang) {
                $tab->name[(int) $lang['id_lang']] = $data['label'];
            }

            if ($tabId > 0 ? !$tab->update() : !$tab->save()) {
                return false;
            }
        }

        return true;
    }

    private function uninstallTabs(): bool
    {
        foreach (array_reverse($this->tabs) as $data) {
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
