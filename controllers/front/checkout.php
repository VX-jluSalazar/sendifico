<?php

use Vx\Sendifico\Checkout\CheckoutHookHandler;

class Vx_SendificoCheckoutModuleFrontController extends ModuleFrontController
{
    public $ajax = true;

    public function postProcess(): void
    {
        $this->ajax = true;
        $this->display_header = false;
        $this->display_footer = false;

        if (Tools::getValue('action') !== 'saveTerritory') {
            $this->renderJson([
                'success' => false,
                'message' => 'La acción solicitada no es válida.',
            ], 400);

            return;
        }

        if ((string) Tools::getValue('token') !== Tools::getToken(false)) {
            $this->renderJson([
                'success' => false,
                'message' => 'La sesión del checkout expiró. Recarga la página e intenta nuevamente.',
            ], 403);

            return;
        }

        $cart = $this->context->cart;
        if (!$cart instanceof Cart || (int) $cart->id <= 0) {
            $this->renderJson([
                'success' => false,
                'message' => 'No se encontró un carrito activo para guardar el territorio.',
            ], 400);

            return;
        }

        $hookHandler = $this->getCheckoutHookHandler();
        if ($hookHandler === null) {
            $this->renderJson([
                'success' => false,
                'message' => 'La integración de checkout no está disponible en este momento.',
            ], 500);

            return;
        }

        $result = $hookHandler->saveRecipientTerritory(
            $cart,
            Tools::getValue('territory_base_id') !== false ? (string) Tools::getValue('territory_base_id') : null
        );

        $this->renderJson($result, !empty($result['success']) ? 200 : 400);
    }

    private function getCheckoutHookHandler(): ?CheckoutHookHandler
    {
        if (!$this->module instanceof Module) {
            return null;
        }

        $service = $this->module->get(CheckoutHookHandler::class);

        return $service instanceof CheckoutHookHandler ? $service : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function renderJson(array $payload, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        $this->ajaxRender(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
    }
}
