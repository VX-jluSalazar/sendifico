<?php

namespace Vx\Sendifico\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vx\Sendifico\Order\ShipmentBackOfficeService;
use Vx\Sendifico\Order\ShipmentBackOfficeViewProvider;
use Vx\Sendifico\Order\ShipmentTraceState;

final class ShipmentOperationsController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly ShipmentBackOfficeViewProvider $viewProvider,
        private readonly ShipmentBackOfficeService $backOfficeService,
        private readonly TranslatorInterface $translator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    /**
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
     */
    public function index(Request $request): Response
    {
        $filters = [
            'id_order' => max(0, (int) $request->query->get('id_order', 0)),
            'id_cart' => max(0, (int) $request->query->get('id_cart', 0)),
            'local_state' => trim((string) $request->query->get('local_state', '')),
            'is_paid' => (string) $request->query->get('is_paid', ''),
            'retryable' => (int) $request->query->get('retryable', 0) === 1,
        ];
        $page = max(1, (int) $request->query->get('page', 1));
        $listing = $this->viewProvider->getListing($filters, $page, 20);

        return $this->render('@Modules/vx_sendifico/views/templates/admin/shipments.html.twig', [
            'layoutHeaderToolbarBtn' => [],
            'layoutTitle' => $this->translator->trans('Sendifico Shipments', [], 'Modules.Vxsendifico.Admin'),
            'requireBulkActions' => false,
            'showContentHeader' => true,
            'enableSidebar' => true,
            'requireFilterStatus' => false,
            'shipmentsListing' => $listing,
            'stateChoices' => [
                '' => $this->translator->trans('All states', [], 'Modules.Vxsendifico.Admin'),
                ShipmentTraceState::QUOTED => ShipmentTraceState::QUOTED,
                ShipmentTraceState::SHIPMENT_PENDING => ShipmentTraceState::SHIPMENT_PENDING,
                ShipmentTraceState::SHIPMENT_CREATED => ShipmentTraceState::SHIPMENT_CREATED,
                ShipmentTraceState::PURCHASED => ShipmentTraceState::PURCHASED,
                ShipmentTraceState::PURCHASE_FAILED => ShipmentTraceState::PURCHASE_FAILED,
                ShipmentTraceState::TRACKING_GENERATED => ShipmentTraceState::TRACKING_GENERATED,
                ShipmentTraceState::LABEL_GENERATED => ShipmentTraceState::LABEL_GENERATED,
                ShipmentTraceState::RECONCILIATION_REQUIRED => ShipmentTraceState::RECONCILIATION_REQUIRED,
                ShipmentTraceState::RATE_MISMATCH => ShipmentTraceState::RATE_MISMATCH,
                ShipmentTraceState::BLOCKED_MISSING_DATA => ShipmentTraceState::BLOCKED_MISSING_DATA,
            ],
            'actionTokens' => $this->buildActionTokens($listing['rows']),
        ]);
    }

    /**
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))")
     */
    public function retryPurchase(Request $request, int $shipmentTraceId): RedirectResponse
    {
        $this->assertValidToken($request, 'vx_sendifico_retry_purchase_' . $shipmentTraceId);
        $result = $this->backOfficeService->retryPurchase($shipmentTraceId);
        $this->addFlash($this->flashType($result['status']), $result['message']);

        return $this->redirectBack($request);
    }

    /**
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))")
     */
    public function generateTracking(Request $request, int $shipmentTraceId): RedirectResponse
    {
        $this->assertValidToken($request, 'vx_sendifico_generate_tracking_' . $shipmentTraceId);
        $result = $this->backOfficeService->generateTrackingNumber($shipmentTraceId);
        $this->addFlash($this->flashType($result['status']), $result['message']);

        return $this->redirectBack($request);
    }

    /**
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))")
     */
    public function generateLabel(Request $request, int $shipmentTraceId): RedirectResponse
    {
        $this->assertValidToken($request, 'vx_sendifico_generate_label_' . $shipmentTraceId);
        $result = $this->backOfficeService->generateLabelUrl($shipmentTraceId);
        $this->addFlash($this->flashType($result['status']), $result['message']);

        return $this->redirectBack($request);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, string>>
     */
    private function buildActionTokens(array $rows): array
    {
        $tokens = [];

        foreach ($rows as $row) {
            $traceId = (int) ($row['id_vx_sendifico_shipment'] ?? 0);
            if ($traceId <= 0) {
                continue;
            }

            $tokens[$traceId] = [
                'retry_purchase' => $this->csrfTokenManager->getToken('vx_sendifico_retry_purchase_' . $traceId)->getValue(),
                'generate_tracking' => $this->csrfTokenManager->getToken('vx_sendifico_generate_tracking_' . $traceId)->getValue(),
                'generate_label' => $this->csrfTokenManager->getToken('vx_sendifico_generate_label_' . $traceId)->getValue(),
            ];
        }

        return $tokens;
    }

    private function assertValidToken(Request $request, string $tokenId): void
    {
        $token = new CsrfToken($tokenId, (string) $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }

    private function flashType(string $status): string
    {
        return match ($status) {
            'success' => 'success',
            'warning' => 'warning',
            default => 'error',
        };
    }

    private function redirectBack(Request $request): RedirectResponse
    {
        $referer = (string) $request->headers->get('referer', '');
        if ($referer !== '') {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('vx_sendifico_shipments_index');
    }
}
