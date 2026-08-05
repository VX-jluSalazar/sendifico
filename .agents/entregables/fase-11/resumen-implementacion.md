# Fase 11 - Resumen de implementacion

## Alcance implementado
- Se agrego una superficie operativa BO propia en `AdminVxSendificoOperations`.
- Se implemento un listado del modulo con filtros base por:
  - `id_order`
  - `id_cart`
  - `local_state`
  - `is_paid`
  - `retryable`
- Se agrego una tarjeta operativa en la ficha del pedido usando `displayAdminOrderSideBottom`.
- Se implementaron acciones manuales BO para:
  - reintentar `purchase`
  - generar tracking
  - generar label URL temporal

## Servicios y capas agregadas
- `ShipmentBackOfficeService`
  - encapsula acciones manuales BO;
  - reutiliza `ShipmentAutomationService` para retry de purchase;
  - persiste tracking y label en la traza local.
- `ShipmentBackOfficeViewProvider`
  - prepara resumen de shipment por pedido;
  - resuelve historial operativo desde `ps_vx_sendifico_shipment_event`;
  - prepara filas del listado del modulo.
- `ShipmentOperationsController`
  - expone listado y endpoints POST para acciones BO.

## Integracion API agregada
- `GET /shipment/{id}`
- `PATCH /shipment/generateTrackingNumber/{id}`
- `POST /shipment/generateLabelUrl/{id}`

## Trazabilidad local actualizada
- En tracking exitoso se persisten:
  - `latest_tracking_number`
  - `latest_tracking_url`
  - `remote_status`
  - `local_state = tracking_generated`
- En label exitosa se persisten:
  - `latest_label_url`
  - `label_url_expires_at`
  - `local_state = label_generated`
- Cada accion manual genera evento en `ps_vx_sendifico_shipment_event`.

## Superficie BO
- Nueva ruta/listado:
  - `/vx-sendifico/shipments`
- Nueva tarjeta en pedido:
  - muestra estado local/remoto, tracking, label temporal, ultimo error e historial resumido.

## Validacion ejecutada
- `php -l` sobre archivos tocados
- `ddev exec sh -lc 'cd /var/www/html && php bin/console cache:clear --no-warmup'`

## Riesgos residuales
- `reconciliation_required` sigue sin resolverse automaticamente cuando no existe `remote_shipment_id`; el retry manual puede reiterar el conflicto si el `extId` ya existe remoto.
- La label URL sigue siendo temporal por contrato; el modulo la muestra para uso operativo, pero no descarga ni archiva el PDF.
