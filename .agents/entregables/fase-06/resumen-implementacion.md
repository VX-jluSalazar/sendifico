# Fase 06 - Resumen de implementacion

## Alcance ejecutado

- Esquema local de trazabilidad para shipments y eventos operativos.
- Repositorios DBAL para consultar shipments locales, reintentos y auditoria de eventos.
- Manager de persistencia para crear y actualizar trazas con su evento asociado.
- Estrategia de upgrade de esquema para instalaciones existentes.

## Modelo de datos agregado

Tablas nuevas:

- `vx_sendifico_shipment`
- `vx_sendifico_shipment_event`

### `vx_sendifico_shipment`

Campos principales:

- relacion operativa: `id_shop`, `id_shop_group`, `id_cart`, `id_order`, `id_carrier`, `id_carrier_reference`
- identidad remota: `remote_shipment_id`, `ext_id`
- resolucion operativa: `sender_address_id`, `sender_reference`, `recipient_territory_base_id`, `carrier_token`
- pricing y seleccion: `selected_rate_id`, `selected_quotation_id`, `quoted_price_total`, `purchased_price_total`, `currency`
- estado: `local_state`, `remote_status`, `is_paid`
- retry y error: `retry_count`, `next_retry_at`, `last_error_code`, `last_error_message`
- tracking y label: `latest_tracking_number`, `latest_tracking_url`, `latest_label_url`, `label_url_expires_at`
- snapshots resumidos: `request_snapshot`, `response_snapshot`

Indices relevantes:

- unico por `id_shop + ext_id`
- unico por `remote_shipment_id`
- indices por `id_order`, `id_cart`, `local_state`, `next_retry_at`

### `vx_sendifico_shipment_event`

Cada evento guarda:

- `event_type`
- `endpoint`, `http_method`, `http_status`
- `remote_message_code`
- `local_state_before`, `local_state_after`
- `duration_ms`
- `payload_summary`, `response_summary`
- `is_retryable`
- referencias de contexto: `id_vx_sendifico_shipment`, `id_shop`, `id_cart`, `id_order`

## Estados internos cerrados

Se consolidaron como base de persistencia:

- `quoted`
- `shipment_pending`
- `shipment_created`
- `purchased`
- `purchase_failed`
- `tracking_generated`
- `label_generated`
- `blocked_missing_data`
- `reconciliation_required`
- `rate_mismatch`

Estados retryables iniciales:

- `purchase_failed`
- `reconciliation_required`
- `rate_mismatch`

## Capa de persistencia implementada

```text
src/Install/TraceabilitySchemaInstaller.php
src/Order/ShipmentTraceState.php
src/Order/ShipmentTraceManager.php
src/Repository/ShipmentRepository.php
src/Repository/ShipmentEventRepository.php
upgrade/upgrade-0.3.0.php
```

Consultas operativas listas:

- buscar shipment por `id_order`
- buscar shipment por `remote_shipment_id`
- buscar shipment por `id_shop + ext_id`
- obtener shipments retryables por estado y `next_retry_at`
- listar eventos por shipment local

## Estrategia de upgrade

- La version del modulo pasa a `0.3.0`.
- `upgrade/upgrade-0.3.0.php` crea el esquema de trazabilidad para tiendas ya instaladas.
- `Installer` aplica el nuevo instalador tanto en `install()` como en `uninstall()`.

## Validacion ejecutada

- `composer dump-autoload`
- `php -l vx_sendifico.php`
- `find src -name '*.php' -print0 | xargs -0 -n1 php -l`

## Validacion pendiente

- `vendor/websenso/prestashop-module-devtools/bin/lotr` sigue no disponible en `vendor/`.
- Upgrade real en contenedor hacia `0.3.0` y verificacion de tablas nuevas.
