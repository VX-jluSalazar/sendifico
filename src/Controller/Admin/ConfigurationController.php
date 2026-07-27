<?php

namespace Vx\Sendifico\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vx\Sendifico\Carrier\CarrierProvisionService;
use Vx\Sendifico\Carrier\CarrierProvisionStatusProvider;
use Vx\Sendifico\Configuration\SendificoFormDataProvider;
use Vx\Sendifico\Form\Admin\Type\SendificoConfigurationType;
use Vx\Sendifico\Sync\SendificoSyncOrchestrator;
use Vx\Sendifico\Sync\SendificoSyncStatusProvider;

class ConfigurationController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly SendificoFormDataProvider $formDataProvider,
        private readonly TranslatorInterface $translator,
        private readonly SendificoSyncOrchestrator $syncOrchestrator,
        private readonly SendificoSyncStatusProvider $syncStatusProvider,
        private readonly CarrierProvisionService $carrierProvisionService,
        private readonly CarrierProvisionStatusProvider $carrierProvisionStatusProvider,
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    /**
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
     */
    public function index(Request $request): Response
    {
        $form = $this->formFactory->create(SendificoConfigurationType::class, $this->formDataProvider->getData(), [
            'sender_choices' => $this->formDataProvider->getSenderChoices(),
        ]);
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
            'syncOverview' => $this->syncStatusProvider->getCurrentContextOverview(),
            'carrierOverview' => $this->carrierProvisionStatusProvider->getOverview(),
            'syncTokens' => [
                'all' => $this->csrfTokenManager->getToken('vx_sendifico_sync_all')->getValue(),
                'territories' => $this->csrfTokenManager->getToken('vx_sendifico_sync_territories')->getValue(),
                'senders' => $this->csrfTokenManager->getToken('vx_sendifico_sync_senders')->getValue(),
                'carriers' => $this->csrfTokenManager->getToken('vx_sendifico_provision_carriers')->getValue(),
            ],
        ]);
    }

    /**
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))")
     */
    public function sync(Request $request, string $type): RedirectResponse
    {
        $this->assertUpdatePermission($request);

        if (!in_array($type, ['all', 'territories', 'senders'], true)) {
            $this->addFlash('error', 'Tipo de sincronizacion no soportado.');

            return $this->redirectToRoute('vx_sendifico_configuration');
        }

        $token = new CsrfToken('vx_sendifico_sync_' . $type, (string) $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $results = $this->syncOrchestrator->syncCurrentContext($type);
        foreach ($results as $result) {
            $flashType = $result['status'] === 'success' ? 'success' : 'warning';
            $this->addFlash($flashType, sprintf('%s: %s', $result['label'], $result['message']));
        }

        return $this->redirectToRoute('vx_sendifico_configuration');
    }

    /**
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))")
     */
    public function provisionCarriers(Request $request): RedirectResponse
    {
        $this->assertUpdatePermission($request);

        $token = new CsrfToken('vx_sendifico_provision_carriers', (string) $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $results = $this->carrierProvisionService->provisionAll();
        foreach ($results as $result) {
            $flashType = $result['status'] === 'success' ? 'success' : 'warning';
            $this->addFlash($flashType, sprintf('%s: %s', $result['label'], $result['message']));
        }

        return $this->redirectToRoute('vx_sendifico_configuration');
    }

    private function assertUpdatePermission(Request $request): void
    {
        $legacyController = (string) $request->attributes->get('_legacy_controller');
        if (!$this->isGranted('update', $legacyController)) {
            throw $this->createAccessDeniedException('You do not have permission to edit this.');
        }
    }
}
