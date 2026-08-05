# Fase 10 - Resumen de implementacion

## Alcance implementado
- Se agregaron los hooks:
  - `actionValidateOrder`
  - `actionOrderStatusPostUpdate`
- Se implemento `OrderLifecycleHookHandler` para disparar la automatizacion cuando el pedido entra en un estado pagado o aceptado/logable.
- Se implemento `ShipmentAutomationService` como orquestador idempotente por `id_order` y `extId`.
- El flujo automatizado:
  1. valida que la tienda tenga auto purchase habilitado,
  2. valida que el carrier del pedido este mapeado a Sendifico,
  3. prepara el payload final de shipment desde fase 09,
  4. crea el draft remoto con `POST /shipment`,
  5. resuelve el `rateId` compatible con el `carrierToken` elegido en checkout,
  6. ejecuta `PATCH /shipment/purchase/{id}` con `purchaseWith`,
  7. actualiza la traza local y, si falla el purchase, marca el pedido con el estado `Courier no pagado`.
- Se extendio el cliente API con:
  - `createShipment()`
  - `purchaseShipment()`
- Se mejoro `SendificoApiException` para conservar `remoteMessageCode` y `responsePayload` resumido.
- Se implemento `ShipmentRateMatcher` para conciliar el carrier del checkout con los `rates[]` devueltos por `POST /shipment`.
- Se implemento `OrderStateInstaller` para crear y mantener el estado de pedido `Courier no pagado`.

## Idempotencia aplicada
- Si la traza local del pedido ya esta `purchased` o `is_paid = 1`, el hook no repite la operacion.
- Si ya existe `remote_shipment_id`, el servicio no vuelve a crear shipment y pasa directo al intento de purchase.
- Si el create remoto devuelve conflicto `409`, el estado local pasa a `reconciliation_required`.
- El `extId` usado sigue la politica `ps-{id_shop}-order-{id_order}`.

## Estados locales usados
- `shipment_pending`
- `shipment_created`
- `purchased`
- `purchase_failed`
- `blocked_missing_data`
- `reconciliation_required`
- `rate_mismatch`

## Estado PrestaShop agregado
- `Courier no pagado`
  - visible para operacion,
  - sin email automatico,
  - pensado para retry manual posterior.

## Archivos principales
- `src/Order/OrderLifecycleHookHandler.php`
- `src/Order/ShipmentAutomationService.php`
- `src/Order/ShipmentRateMatcher.php`
- `src/Order/ShipmentOrderStateService.php`
- `src/Order/OrderStateTransitionGuard.php`
- `src/Install/OrderStateInstaller.php`
- `src/Api/SendificoApiClient.php`
- `src/Api/SendificoApiException.php`
- `vx_sendifico.php`
- `src/Install/Installer.php`
- `upgrade/upgrade-0.8.0.php`

## Validacion ejecutada
- `composer validate --strict`
- `composer dump-autoload`
- `find src config upgrade -name '*.php' -print0 | xargs -0 -n1 php -l`
- `ddev exec sh -lc 'cd /var/www/html && php bin/console pr:mo upgrade vx_sendifico'`
- `ddev exec sh -lc 'cd /var/www/html && php bin/console cache:clear --no-warmup'`

## Riesgos residuales
- La reconciliacion remota de `409 pApiShipmentExtIdAlreadyUsed` queda marcada como `reconciliation_required`, pero no consulta todavia `GET /shipment`.
- El retry manual de purchase queda para fase 11; la base local ya conserva `remote_shipment_id`, `selected_rate_id` y error operativo para soportarlo.
- La vista de pedido en BO puede recalcular cotizaciones sobre el `cart` historico y generar trazas `quoted` adicionales con `id_order = NULL`; queda documentado en `.agents/entregables/fase-10/deuda-tecnica-trazas-quote-en-bo.md`.
