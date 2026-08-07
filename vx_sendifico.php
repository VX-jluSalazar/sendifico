<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Vx\Sendifico\Checkout\CheckoutHookHandler;
use Vx\Sendifico\Install\Installer;
use Vx\Sendifico\Install\ShopDataDuplicator;
use Vx\Sendifico\Order\ShipmentBackOfficeViewProvider;
use Vx\Sendifico\Order\OrderLifecycleHookHandler;

class Vx_Sendifico extends Module
{
    /** @var int|null */
    public $id_carrier;

    /**
     * @var array<string, mixed>
     */
    private array $lastAddressSubmission = [];

    public function __construct()
    {
        $this->name = 'vx_sendifico';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.1';
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

    public function hookDisplayHeader(): void
    {
        if (!isset($this->context->controller) || !in_array($this->context->controller->php_self, ['order', 'address'], true)) {
            return;
        }

        $hookHandler = $this->getCheckoutHookHandler();
        if ($hookHandler === null) {
            return;
        }

        Media::addJsDef([
            'vxSendificoAddressForm' => $hookHandler->getAddressFrontendConfiguration((int) $this->context->shop->id),
        ]);

        $this->context->controller->registerJavascript(
            'module-vx-sendifico-checkout',
            'modules/' . $this->name . '/views/js/checkout-territory.js',
            ['position' => 'bottom', 'priority' => 150]
        );
    }

    public function hookDisplayAfterCarrier(array $params): string
    {
        return '';
    }

    public function hookActionFilterDeliveryOptionList(array $params): void
    {
        $hookHandler = $this->getCheckoutHookHandler();
        $cart = $this->context->cart;
        if ($hookHandler === null || !$cart instanceof Cart || !isset($params['delivery_option_list']) || !is_array($params['delivery_option_list'])) {
            return;
        }

        $hookHandler->filterDeliveryOptionList($cart, $params['delivery_option_list']);
    }

    public function hookActionCarrierProcess(array $params): void
    {
        $hookHandler = $this->getCheckoutHookHandler();
        $cart = $params['cart'] ?? $this->context->cart;
        if ($hookHandler === null || !$cart instanceof Cart || (int) $cart->id <= 0) {
            return;
        }

        $hookHandler->syncSelectedCarrier($cart);
    }

    public function hookActionValidateStepComplete(array $params): void
    {
        $hookHandler = $this->getCheckoutHookHandler();
        $cart = $this->context->cart;
        if ($hookHandler === null || !$cart instanceof Cart || !array_key_exists('completed', $params)) {
            return;
        }

        $params['completed'] = $hookHandler->validateSelectedCarrier($cart);
    }

    /**
     * PrestaShop already namespaces additional address fields by module name when executing the hook with $array_return = true.
     * Returning an extra wrapper here breaks CustomerAddressFormatter, which expects a flat list of FormField objects per module.
     *
     * @param array<string, mixed> $params
     *
     * @return array<int, FormField>
     */
    public function hookAdditionalCustomerAddressFields(array $params): array
    {
        $hookHandler = $this->getCheckoutHookHandler();
        if ($hookHandler === null) {
            return [];
        }

        $addressId = (int) Tools::getValue('id_address');

        return $hookHandler->buildAdditionalAddressFields((int) $this->context->shop->id, $addressId > 0 ? $addressId : null);
    }

    public function hookActionValidateCustomerAddressForm(array $params): bool
    {
        $hookHandler = $this->getCheckoutHookHandler();
        if ($hookHandler === null || !isset($params['form'])) {
            return true;
        }

        $this->lastAddressSubmission = Tools::getAllValues();

        return $hookHandler->validateAddressForm($params['form'], $this->lastAddressSubmission, (int) $this->context->shop->id);
    }

    public function hookActionObjectAddressAddAfter(array $params): void
    {
        $this->persistAddressMetadataFromHook($params);
    }

    public function hookActionObjectAddressUpdateAfter(array $params): void
    {
        $this->persistAddressMetadataFromHook($params);
    }

    public function hookActionObjectAddressDeleteAfter(array $params): void
    {
        $hookHandler = $this->getCheckoutHookHandler();
        $address = $params['object'] ?? null;
        if ($hookHandler === null || !$address instanceof Address || (int) $address->id <= 0) {
            return;
        }

        $hookHandler->deleteAddressMetadata((int) $address->id);
    }

    public function hookActionShopDataDuplication(array $params): void
    {
        $sourceShopId = (int) ($params['old_id_shop'] ?? $params['id_shop_old'] ?? 0);
        $targetShopId = (int) ($params['new_id_shop'] ?? $params['id_shop_new'] ?? 0);

        (new ShopDataDuplicator())->duplicate($sourceShopId, $targetShopId);
    }

    public function hookActionValidateOrder(array $params): void
    {
        $hookHandler = $this->getOrderLifecycleHookHandler();
        $order = $params['order'] ?? null;
        $orderState = $params['orderStatus'] ?? null;

        if ($hookHandler === null || !$order instanceof Order) {
            return;
        }

        $hookHandler->handleValidatedOrder($order, $orderState instanceof OrderState ? $orderState : null);
    }

    public function hookActionOrderStatusPostUpdate(array $params): void
    {
        $hookHandler = $this->getOrderLifecycleHookHandler();
        $orderState = $params['newOrderStatus'] ?? null;
        $orderId = isset($params['id_order']) ? (int) $params['id_order'] : 0;

        if ($hookHandler === null || $orderId <= 0) {
            return;
        }

        $hookHandler->handleOrderStatusPostUpdate($orderId, $orderState instanceof OrderState ? $orderState : null);
    }

    public function hookDisplayAdminOrderSideBottom(array $params): string
    {
        $orderId = isset($params['id_order']) ? (int) $params['id_order'] : 0;
        if ($orderId <= 0) {
            return '';
        }

        $viewProvider = $this->getShipmentBackOfficeViewProvider();
        $container = SymfonyContainer::getInstance();
        if ($viewProvider === null || $container === null || !$container->has('twig') || !$container->has('router') || !$container->has('security.csrf.token_manager')) {
            return '';
        }

        $overview = $viewProvider->getOrderOverview($orderId);
        if ($overview === null) {
            return '';
        }

        $traceId = (int) ($overview['trace']['id_vx_sendifico_shipment'] ?? 0);
        if ($traceId <= 0) {
            return '';
        }

        $router = $container->get('router');
        $tokenManager = $container->get('security.csrf.token_manager');

        return $container->get('twig')->render('@Modules/vx_sendifico/views/templates/admin/order_shipment_panel.html.twig', [
            'shipmentOverview' => $overview,
            'shipmentsListUrl' => $router->generate('vx_sendifico_shipments_index', ['id_order' => $orderId]),
            'actionUrls' => [
                'retry_purchase' => $router->generate('vx_sendifico_shipments_retry_purchase', ['shipmentTraceId' => $traceId]),
                'generate_tracking' => $router->generate('vx_sendifico_shipments_generate_tracking', ['shipmentTraceId' => $traceId]),
                'generate_label' => $router->generate('vx_sendifico_shipments_generate_label', ['shipmentTraceId' => $traceId]),
            ],
            'actionTokens' => [
                'retry_purchase' => $tokenManager->getToken('vx_sendifico_retry_purchase_' . $traceId)->getValue(),
                'generate_tracking' => $tokenManager->getToken('vx_sendifico_generate_tracking_' . $traceId)->getValue(),
                'generate_label' => $tokenManager->getToken('vx_sendifico_generate_label_' . $traceId)->getValue(),
            ],
        ]);
    }

    public function getOrderShippingCostExternal($params)
    {
        $hookHandler = $this->getCheckoutHookHandler();
        if ($hookHandler === null || !$params instanceof Cart || (int) $params->id <= 0 || (int) $this->id_carrier <= 0) {
            return false;
        }

        $rate = $hookHandler->getRateForCarrier($params, (int) $this->id_carrier);
        if ($rate === null || ($rate['available'] ?? false) !== true) {
            return false;
        }

        return isset($rate['priceTotal']) ? (float) $rate['priceTotal'] : false;
    }

    public function getOrderShippingCost($params, $shipping_cost)
    {
        return $this->getOrderShippingCostExternal($params);
    }

    private function getCheckoutHookHandler(): ?CheckoutHookHandler
    {
        try {
            $service = $this->get(CheckoutHookHandler::class);
        } catch (\Throwable) {
            return null;
        }

        return $service instanceof CheckoutHookHandler ? $service : null;
    }

    private function getOrderLifecycleHookHandler(): ?OrderLifecycleHookHandler
    {
        $container = SymfonyContainer::getInstance();
        if ($container === null || !$container->has(OrderLifecycleHookHandler::class)) {
            return null;
        }

        $service = $this->get(OrderLifecycleHookHandler::class);

        return $service instanceof OrderLifecycleHookHandler ? $service : null;
    }

    private function getShipmentBackOfficeViewProvider(): ?ShipmentBackOfficeViewProvider
    {
        $container = SymfonyContainer::getInstance();
        if ($container === null || !$container->has(ShipmentBackOfficeViewProvider::class)) {
            return null;
        }

        $service = $this->get(ShipmentBackOfficeViewProvider::class);

        return $service instanceof ShipmentBackOfficeViewProvider ? $service : null;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function persistAddressMetadataFromHook(array $params): void
    {
        $hookHandler = $this->getCheckoutHookHandler();
        $address = $params['object'] ?? null;
        if ($hookHandler === null || !$address instanceof Address || (int) $address->id <= 0) {
            return;
        }

        $submittedValues = $this->lastAddressSubmission !== [] ? $this->lastAddressSubmission : Tools::getAllValues();
        $hookHandler->persistAddressMetadata($address, $submittedValues, (int) $this->context->shop->id);
    }
}
