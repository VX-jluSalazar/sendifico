<?php

namespace Vx\Sendifico\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationController extends FrameworkBundleAdminController
{
    public function indexAction(): Response
    {
        return $this->render('@Modules/vx_sendifico/views/templates/admin/configuration.html.twig');
    }
}
