# Fase 08 - Resumen de implementacion

## Alcance implementado
- Se integró `vx_sendifico` con el checkout clásico mediante los hooks `displayAfterCarrier`, `displayHeader`, `actionFilterDeliveryOptionList`, `actionCarrierProcess` y `actionValidateStepComplete`.
- Se añadió un selector de territorio de entrega en el paso de transporte usando el cache local de territorios sincronizados en fase 05.
- Se creó un endpoint AJAX front del módulo para guardar el territorio seleccionado por carrito y forzar el recálculo de tarifas del checkout.
- Se implementó la cotización contra `POST /quotation` usando:
  - `senderAddress.territoryBaseId` desde el remitente configurado en BO,
  - `recipientAddress.territoryBaseId` desde el selector del checkout,
  - `parcel.weight` desde el peso real del carrito o el default configurado,
  - `parcel.length`, `width`, `height` desde la configuración del módulo,
  - `goodsInsured` desde el total del carrito sin shipping,
  - `goodsCollection = 0` en esta fase.
- Se reutilizó `ps_vx_sendifico_shipment` como persistencia temporal por `id_cart` para guardar:
  - territorio seleccionado,
  - request/response snapshot de `/quotation`,
  - carrier token seleccionado,
  - `selected_quotation_id`,
  - `quoted_price_total`,
  - errores de cotización y estado local.
- Se filtraron dinámicamente las delivery options del checkout para dejar visibles solo los carriers mapeados que llegaron disponibles en la cotización vigente.
- Se resolvió el precio de cada carrier externo directamente desde la cotización persistida, de modo que PrestaShop cobra exactamente el `priceTotal` devuelto por Sendifico.
- Se persiste la conciliación de la selección del cliente mediante `id_carrier`, `id_carrier_reference`, `carrier_token` y `selected_quotation_id` cuando el checkout procesa el carrier elegido.

## Archivos principales
- `src/Checkout/CheckoutConfigurationProvider.php`
- `src/Checkout/CheckoutContextResolver.php`
- `src/Checkout/CheckoutQuotationService.php`
- `src/Checkout/CheckoutHookHandler.php`
- `controllers/front/checkout.php`
- `views/templates/hook/checkout_territory_selector.tpl`
- `views/js/checkout-territory.js`
- `vx_sendifico.php`
- `src/Api/SendificoApiClient.php`
- `src/Repository/TerritoryRepository.php`
- `src/Repository/CarrierMappingRepository.php`
- `src/Repository/ShipmentRepository.php`
- `src/Install/Installer.php`
- `upgrade/upgrade-0.5.0.php`

## Comportamiento operativo
- Si falta API key, remitente o territorio de entrega, el checkout no expone carriers Sendifico y deja el estado trazado en `local_state`.
- Si la cotización falla, los carriers Sendifico se ocultan y el mensaje de error queda visible debajo del selector de territorio.
- Si el carrito cambia y la traza quedó más vieja que `cart.date_upd`, la cotización se recalcula automáticamente al reconstruir las opciones de entrega.

## Limitaciones conocidas de este corte
- La cotización del checkout usa `goodsCollection = 0` porque el método de pago todavía no está seleccionado en el paso de transporte. La variación por COD deberá reconciliarse en fases posteriores del flujo de shipment/purchase.
- El selector de territorio recarga la página tras guardar la selección. Es funcional, pero no es todavía una actualización parcial del paso de transporte.

## Validación esperada
- `lotr` en el módulo debe quedar limpio.
- `pr:mo install vx_sendifico` o upgrade desde `0.4.0` debe registrar los hooks nuevos y dejar operativo el selector en checkout.
