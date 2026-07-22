<?php

namespace Vx\Sendifico\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vx\Sendifico\Configuration\SendificoFormDataProvider;
use Vx\Sendifico\Form\Admin\Type\SendificoConfigurationType;

class ConfigurationController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly SendificoFormDataProvider $formDataProvider,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
     */
    public function index(Request $request): Response
    {
        $form = $this->formFactory->create(SendificoConfigurationType::class, $this->formDataProvider->getData());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->assertUpdatePermission($request);

            if ($form->isValid()) {
                $saveErrors = $this->formDataProvider->saveData($form->getData());
                if ($saveErrors === []) {
                    $this->addFlash('success', $this->translator->trans('Configuracion guardada correctamente.', [], 'Modules.Vxsendifico.Admin'));

                    return $this->redirectToRoute('vx_sendifico_configuration');
                }

                foreach ($saveErrors as $saveError) {
                    $this->addFlash('error', (string) $saveError);
                }
            }
        }

        return $this->render('@Modules/vx_sendifico/views/templates/admin/configuration.html.twig', [
            'layoutHeaderToolbarBtn' => [],
            'layoutTitle' => $this->translator->trans('Velox Sendifico', [], 'Modules.Vxsendifico.Admin'),
            'requireBulkActions' => false,
            'showContentHeader' => true,
            'enableSidebar' => true,
            'requireFilterStatus' => false,
            'configurationForm' => $form->createView(),
            'configurationWarnings' => $this->formDataProvider->getConfigurationWarnings(),
            'shopContextLabel' => $this->formDataProvider->getShopContextLabel(),
        ]);
    }

    private function assertUpdatePermission(Request $request): void
    {
        $legacyController = (string) $request->attributes->get('_legacy_controller');
        if (
            !$this->isGranted('update', $legacyController)
            || !$this->isGranted('create', $legacyController)
            || !$this->isGranted('delete', $legacyController)
        ) {
            throw $this->createAccessDeniedException('You do not have permission to edit this.');
        }
    }
}
