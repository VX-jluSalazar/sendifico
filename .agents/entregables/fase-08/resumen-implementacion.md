# Fase 08 - Resumen de implementacion

## Alcance implementado
- Se integró `vx_sendifico` con el checkout clásico mediante los hooks `displayAfterCarrier`, `displayHeader`, `actionFilterDeliveryOptionList`, `actionCarrierProcess` y `actionValidateStepComplete`.
- Se añadió un selector de territorio de entrega en el paso de transporte usando el cache local de territorios sincronizados en fase 05.
- Se creó un endpoint AJAX front del módulo para guardar el territorio seleccionado por carrito y forzar el recálculo de tarifas del checkout.
- Se implementó la cotización contra `POST /quotation` usando:
  - `senderAddress.territoryBaseId` desde el remitente configurado en BO,
  - `recipientAddress.territoryBaseId` desde el selector del checkout,
  - `parcel.weight` desde el mayor valor entre peso real del carrito, default configurado y minimo operativo de 1 kg,
  - `parcel.length`, `width`, `height` desde la configuración del módulo con minimo operativo de 1 cm,
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
- Se reparó la elegibilidad de los carriers externos Sendifico para PrestaShop: quedan con `need_range = 1`, rango de peso amplio y filas `ps_delivery` por zona activa, aunque el precio final siempre se toma desde Sendifico.
- Se agregó upgrade `0.6.1` para reprovisionar carriers existentes y aplicar esa reparación sin reinstalar el módulo.

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
- `upgrade/upgrade-0.6.1.php`

## Comportamiento operativo
- Si falta API key, remitente o territorio de entrega, el checkout no expone carriers Sendifico y deja el estado trazado en `local_state`.
- Si la cotización falla, los carriers Sendifico se ocultan y el mensaje de error queda visible debajo del selector de territorio.
- Si el carrito cambia y la traza quedó más vieja que `cart.date_upd`, la cotización se recalcula automáticamente al reconstruir las opciones de entrega.
- Tras la subfase 08.01, el territorio de entrega operativo debe provenir de la metadata guardada por dirección en `ps_vx_sendifico_address_meta`, no del selector temporal de shipping.
- Para Ecuador, la tienda debe tener las provincias cargadas como `ps_state`; el módulo relaciona el `id_state` seleccionado con `territory1Name` de Sendifico comparando el nombre normalizado.
- Solo se muestran carriers Sendifico cuyo `carrierToken` esté mapeado localmente y cuya tarifa venga con `available: true` en la respuesta vigente de `/quotation`.
- Si Sendifico responde `available: false`, ese carrier se elimina de las delivery options y tampoco se acepta como selección válida.

## Limitaciones conocidas de este corte
- La cotización del checkout usa `goodsCollection = 0` porque el método de pago todavía no está seleccionado en el paso de transporte. La variación por COD deberá reconciliarse en fases posteriores del flujo de shipment/purchase.
- El selector de territorio recarga la página tras guardar la selección. Es funcional, pero no es todavía una actualización parcial del paso de transporte.
- El vínculo entre provincia PrestaShop y `territory1Name` de Sendifico depende de nombres equivalentes tras normalización. Diferencias reales de escritura o abreviaturas pueden impedir resolver el `territory_base_id`.
- Aunque el contrato documenta `weight > 0`, la validacion real de `/quotation` rechaza pesos menores a 1 kg con `pApiQuotationParcelBadRequest`; por eso checkout normaliza el peso minimo a 1 kg.
- PrestaShop descarta carriers de módulo antes de llamar al módulo si no tienen rango elegible; por eso los carriers Sendifico mantienen rangos locales de elegibilidad aunque su tarifa sea externa.

## Validación ejecutada
- `composer validate --strict`
- `php -l vx_sendifico.php`
- `find src config controllers upgrade -name '*.php' -print0 | xargs -0 -n1 php -l`
- `ddev exec node --check /var/www/html/modules/vx_sendifico/views/js/checkout-territory.js`
- `ddev exec sh -lc 'cd /var/www/html && php bin/console vx_sendifico:provision-carriers'`
- `ddev exec sh -lc 'cd /var/www/html && php bin/console pr:mo upgrade vx_sendifico'`
- `ddev exec sh -lc 'cd /var/www/html && php bin/console cache:clear --no-warmup'`
