# Fase 12 - Politica de seguridad y observabilidad

## Credenciales y secretos

- La API key se almacena exclusivamente en `Configuration` por contexto de tienda/grupo, usando las claves centralizadas de `ConfigurationKeys`.
- La API key nunca debe persistirse en tablas de trazabilidad, eventos, fixtures, capturas de request/response ni mensajes de error.
- Los snapshots tecnicos pasan por `Vx\Sendifico\Observability\TraceSanitizer` antes de almacenarse.
- Las URLs temporales de label y tracking se consideran sensibles en eventos/snapshots resumidos; el BO conserva solo los campos operativos dedicados necesarios para accion humana.

## Acciones Back Office

- La pagina de configuracion usa Symfony Form, que cubre CSRF del formulario principal.
- Las acciones manuales no cubiertas por formulario completo validan token CSRF especifico:
  - `vx_sendifico_sync_{type}`
  - `vx_sendifico_provision_carriers`
  - `vx_sendifico_retry_purchase_{traceId}`
  - `vx_sendifico_generate_tracking_{traceId}`
  - `vx_sendifico_generate_label_{traceId}`
- Las rutas BO declaran `@AdminSecurity` con permisos `read` o `update` sobre el `_legacy_controller`.

## Logs y trazabilidad

- `vx_sendifico_shipment` conserva el estado agregado de la operacion por cart/order/shipment.
- `vx_sendifico_shipment_event` conserva eventos historicos resumidos.
- Los campos JSON (`request_snapshot`, `response_snapshot`, `payload_summary`, `response_summary`) se sanitizan antes de persistir:
  - secretos y tokens: reemplazo por `[redacted]`;
  - PII basica (`email`, `phone`, `street`, nombres completos): reemplazo por `[redacted]`;
  - strings largos: truncado a 500 caracteres;
  - arrays profundos: truncado a partir de profundidad 6.

## Catalogo operativo de errores

- `configuration_missing`: configuracion insuficiente para operar.
- `shipment_validation_failed`: datos de pedido o paquete incompletos antes de llamar a Sendifico.
- `shipment_create_failed`: fallo remoto o red al crear shipment.
- `rate_mismatch`: no se encontro rate remoto compatible con el carrier elegido.
- `purchase_failed`: shipment creado pero purchase no pudo completarse.
- `tracking_generate_failed`: fallo al generar tracking manualmente.
- `label_generate_failed`: fallo al generar URL de label.

## Reglas de soporte

- Cliente: no se muestran errores tecnicos de API; el checkout solo filtra carriers no disponibles.
- BO: se muestran mensajes accionables, sin secretos ni payloads completos.
- Soporte tecnico: diagnostico usando `local_state`, `last_error_code`, eventos recientes y `remote_message_code`.
