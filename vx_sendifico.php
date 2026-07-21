<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Vx\Sendifico\Install\Installer;

class Vx_Sendifico extends Module
{
    public function __construct()
    {
        $this->name = 'vx_sendifico';
        $this->tab = 'shipping_logistics';
        $this->version = '0.1.0';
        $this->author = 'Velox';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.2.1', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Velox Sendifico', [], 'Modules.Vxsendifico.Admin');
        $this->description = $this->trans(
            'Connects PrestaShop checkout and order operations with Sendifico shipping.',
            [],
            'Modules.Vxsendifico.Admin'
        );
    }

    public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }

        return (new Installer())->install($this);
    }

    public function uninstall(): bool
    {
        return (new Installer())->uninstall() && parent::uninstall();
    }

    public function getContent(): void
    {
        $container = SymfonyContainer::getInstance();
        if ($container === null) {
            return;
        }

        Tools::redirectAdmin($container->get('router')->generate('vx_sendifico_configuration'));
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }
}
