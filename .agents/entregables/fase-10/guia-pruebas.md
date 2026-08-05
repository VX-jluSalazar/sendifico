# Fase 10 - Guia de pruebas

## Objetivo

Validar la automatizacion de:
- `POST /shipment`
- `PATCH /shipment/purchase/{id}`
- trazabilidad local
- estado informativo `Courier no pagado`
- comportamiento idempotente ante repeticion de hooks

Fecha de referencia de esta guia: **2026-08-05**.

## Precondiciones generales

- Modulo `vx_sendifico` actualizado a la version que incluye fase 10.
- Cache limpia.
- API key valida en BO.
- Remitente sincronizado y configurado por tienda.
- Territorios sincronizados.
- Carriers Sendifico provisionados.
- Checkout clasico operativo.
- Fase 08 y fase 09 ya validadas:
  - cotizacion visible en checkout,
  - carrier Sendifico seleccionable,
  - `contents` y `parcel` resolubles.

## Datos base recomendados

- Tienda `id_shop=1`.
- Un cliente con direccion EC valida y `territory_base_id` persistido en `ps_vx_sendifico_address_meta`.
- Un producto simple con peso/dimensiones completas.
- Un producto con dimensiones faltantes para probar fallback.
- Configuracion de `default_contents`.
- Al menos un modulo de pago no COD.
- Al menos un modulo de pago COD listado en `VX_SENDIFICO_COD_PAYMENT_METHODS`.

## Comandos utiles

Desde `~/Sites/prestashop`:

```bash
ddev exec sh -lc 'cd /var/www/html && php bin/console pr:mo upgrade vx_sendifico'
```

```bash
ddev exec sh -lc 'cd /var/www/html && php bin/console cache:clear --no-warmup'
```

```bash
ddev exec mysql -udb -pdb db -e "SELECT id_vx_sendifico_shipment, id_shop, id_cart, id_order, remote_shipment_id, ext_id, carrier_token, selected_rate_id, selected_quotation_id, quoted_price_total, purchased_price_total, local_state, remote_status, is_paid, retry_count, last_error_code, last_error_message, updated_at FROM ps_vx_sendifico_shipment ORDER BY updated_at DESC LIMIT 20;"
```

```bash
ddev exec mysql -udb -pdb db -e "SELECT id_vx_sendifico_shipment_event, id_vx_sendifico_shipment, id_order, event_type, endpoint, http_method, http_status, remote_message_code, local_state_before, local_state_after, is_retryable, created_at FROM ps_vx_sendifico_shipment_event ORDER BY created_at DESC LIMIT 50;"
```

```bash
ddev exec mysql -udb -pdb db -e "SELECT id_order_state, module_name, invoice, delivery, paid, logable, hidden FROM ps_order_state WHERE module_name = 'vx_sendifico';"
```

## Caso 1 - Flujo nominal con payment accepted

### Pasos

1. Crear carrito con productos validos.
2. Entrar al checkout y seleccionar un carrier Sendifico disponible.
3. Confirmar pedido con un metodo de pago no COD.
4. Forzar o esperar el estado pagado/aceptado del pedido.

### Resultado esperado

- Se crea fila en `ps_vx_sendifico_shipment`.
- `remote_shipment_id` queda poblado.
- `selected_rate_id` queda poblado.
- `local_state = purchased`.
- `is_paid = 1`.
- `remote_status` poblado.
- `carrier_token` coincide con el elegido en checkout.
- Existe evento `shipment_create`.
- Existe evento `shipment_purchase`.
- El pedido **no** cambia a `Courier no pagado`.

## Caso 2 - Flujo nominal desde `actionValidateOrder`

### Pasos

1. Repetir un pedido nominal donde el hook relevante dispare al validar el pedido.
2. Confirmar pedido en un flujo donde el estado inicial ya sea logable o pagado.

### Resultado esperado

- La automatizacion corre aunque luego no haya transicion adicional.
- En eventos se ve `shipment_automation_triggered` con origen `actionValidateOrder`.

## Caso 3 - Flujo nominal desde `actionOrderStatusPostUpdate`

### Pasos

1. Crear un pedido.
2. Cambiar el estado manualmente en BO a uno pagado o aceptado/logable.

### Resultado esperado

- Se dispara la automatizacion.
- En eventos se ve `shipment_automation_triggered` con origen `actionOrderStatusPostUpdate`.

## Caso 4 - Carrier del pedido no mapeado a Sendifico

### Pasos

1. Crear pedido con un carrier no Sendifico.
2. Cambiar el pedido a estado pagado/aceptado.

### Resultado esperado

- No se crea shipment local nuevo.
- No se llama API de Sendifico.
- No se genera error operativo del modulo.

## Caso 5 - `auto_purchase_enabled = false`

### Pasos

1. Desactivar `Enable automatic purchase` en BO.
2. Crear pedido con carrier Sendifico.
3. Pasar el pedido a estado pagado/aceptado.

### Resultado esperado

- No se crea ni compra shipment automaticamente.
- No aparece nueva traza de fase 10.

## Caso 6 - Datos faltantes bloquean shipment

### Variantes

- remitente no configurado
- `territory_base_id` faltante
- `senderAddressId` invalido
- `contents` irresoluble
- recipient sin telefono

### Pasos

1. Preparar una variante con dato faltante.
2. Crear pedido con carrier Sendifico.
3. Llevar pedido a estado pagado/aceptado.

### Resultado esperado

- Se crea o actualiza traza local.
- `local_state = blocked_missing_data`.
- `remote_shipment_id` queda `NULL`.
- `last_error_code` y `last_error_message` quedan poblados.
- Existe evento `shipment_validation_failed` o `shipment_configuration_blocked`.
- No se ejecuta purchase.

## Caso 7 - `contents` dominante por cantidad

### Pasos

1. Configurar mapeos de categoria o producto con dos `contents` distintos.
2. Crear carrito donde un `contents` tenga mayor cantidad total.
3. Confirmar pedido.

### Resultado esperado

- El payload de shipment usa exactamente un `contents`.
- Gana el `contents` con mayor cantidad.
- El valor puede verificarse en `request_snapshot`.

## Caso 8 - `contents` dominante por peso en empate

### Pasos

1. Crear carrito con empate en cantidad entre dos grupos.
2. Hacer que uno tenga mayor peso acumulado.
3. Confirmar pedido.

### Resultado esperado

- Gana el `contents` del grupo con mayor peso acumulado.

## Caso 9 - COD resuelto correctamente

### Pasos

1. Configurar un modulo de pago COD en `VX_SENDIFICO_COD_PAYMENT_METHODS`.
2. Crear pedido usando ese modulo.
3. Llevarlo a estado pagado/aceptado.

### Resultado esperado

- `goodsCollection` en `request_snapshot` es igual a `order.total_paid_tax_incl`.
- Shipment y purchase usan ese valor.

## Caso 10 - No COD usa `goodsCollection = 0`

### Pasos

1. Crear pedido con modulo no COD.
2. Llevarlo a estado pagado/aceptado.

### Resultado esperado

- `goodsCollection = 0` en `request_snapshot`.

## Caso 11 - Fallback de peso y dimensiones

### Pasos

1. Crear pedido con producto sin dimensiones y/o peso.
2. Confirmar pedido.

### Resultado esperado

- `parcel` se resuelve con defaults BO.
- No falla validacion local por campos faltantes.

## Caso 12 - `rateId` conciliado con el carrier elegido

### Pasos

1. Hacer checkout con un carrier Sendifico concreto.
2. Confirmar pedido.
3. Revisar `response_snapshot` del create remoto.

### Resultado esperado

- `selected_rate_id` coincide con un rate disponible del mismo `carrier_token`.
- Si existe `quoted_price_total`, se prefiere rate con precio equivalente.

## Caso 13 - `rate_mismatch`

### Pasos

1. Forzar escenario donde el shipment remoto no devuelve un rate disponible del `carrier_token` elegido.
2. Confirmar pedido.

### Resultado esperado

- `local_state = rate_mismatch`.
- `selected_rate_id` queda vacio o sin match valido.
- Existe evento `shipment_rate_mismatch`.
- No se ejecuta purchase.

## Caso 14 - Purchase fallido por fondos insuficientes

### Pasos

1. Usar wallet sin saldo suficiente.
2. Confirmar pedido Sendifico.

### Resultado esperado

- El create del shipment si ocurre.
- El purchase falla.
- `local_state = purchase_failed`.
- `is_paid = 0`.
- `retry_count >= 1`.
- `last_error_code` refleja el error remoto, por ejemplo `paymentIntentExecuteTransactionWalletInsufficientFunds`.
- El pedido cambia a `Courier no pagado`.

## Caso 15 - Purchase fallido por rate ya no disponible

### Pasos

1. Forzar cambio remoto entre create y purchase.
2. Confirmar pedido.

### Resultado esperado

- `local_state = purchase_failed` o `rate_mismatch` segun el fallo real.
- El pedido cambia a `Courier no pagado`.
- Queda traza retryable.

## Caso 16 - `extId` duplicado / conflicto 409

### Pasos

1. Reusar manualmente un `extId` ya consumido en Sendifico para la misma cuenta.
2. Disparar la automatizacion.

### Resultado esperado

- `local_state = reconciliation_required`.
- `last_error_code` refleja conflicto remoto.
- Existe evento `shipment_create_failed`.
- No se crea un segundo shipment remoto.

## Caso 17 - Idempotencia ante repeticion del hook

### Pasos

1. Ejecutar caso nominal completo.
2. Volver a disparar el mismo hook cambiando otra vez a estado pagado/logable o repitiendo validacion.

### Resultado esperado

- Si la traza ya esta `purchased`, no se duplica shipment remoto.
- No se crea una segunda compra.
- `remote_shipment_id` permanece unico.

## Caso 18 - Idempotencia con shipment creado pero no pagado

### Pasos

1. Forzar fallo en purchase despues de un create exitoso.
2. Repetir el hook.

### Resultado esperado

- No se vuelve a ejecutar `POST /shipment`.
- Se reutiliza `remote_shipment_id`.
- Solo se reintenta purchase.

## Caso 19 - Estado `Courier no pagado`

### Validaciones

- Existe en `ps_order_state`.
- `module_name = 'vx_sendifico'`.
- `send_email = false`.
- no debe marcarse como pagado.
- se aplica solo cuando falla el purchase.

## Caso 20 - Pedido ya marcado como `purchased`

### Pasos

1. Completar un flujo nominal.
2. Repetir cambio de estado o volver a ejecutar automatizacion.

### Resultado esperado

- El servicio sale sin repetir create/purchase.
- La traza sigue en `purchased`.

## Caso 21 - Traza local minima correcta

### Verificar en `ps_vx_sendifico_shipment`

- `id_shop`
- `id_cart`
- `id_order`
- `id_carrier`
- `id_carrier_reference`
- `remote_shipment_id`
- `ext_id`
- `carrier_token`
- `selected_rate_id`
- `selected_quotation_id` si venia de checkout
- `quoted_price_total`
- `purchased_price_total`
- `currency`
- `local_state`
- `remote_status`
- `is_paid`
- `retry_count`
- `last_error_code`
- `last_error_message`
- `request_snapshot`
- `response_snapshot`

## Caso 22 - Eventos de auditoria correctos

### Verificar en `ps_vx_sendifico_shipment_event`

- `shipment_automation_triggered`
- `shipment_create`
- `shipment_purchase`
- `shipment_validation_failed`
- `shipment_create_failed`
- `shipment_purchase_failed`
- `shipment_rate_mismatch`

### Campos a revisar

- `endpoint`
- `http_method`
- `http_status`
- `remote_message_code`
- `local_state_before`
- `local_state_after`
- `is_retryable`

## Caso 23 - Compatibilidad multitienda

### Pasos

1. Repetir prueba nominal en otra tienda o contexto si existe.

### Resultado esperado

- `id_shop` correcto en traza y eventos.
- `sender_reference` y configuracion leidos por tienda.
- `extId` usa el `id_shop` correcto.

## Caso 24 - Pedido sin carrier seleccionado Sendifico

### Pasos

1. Crear pedido con `id_carrier = 0` o carrier invalido para el flujo.

### Resultado esperado

- No se intenta automatizacion util.
- No se crea shipment remoto espurio.

## Caso 25 - Sanidad de snapshots

### Resultado esperado

- `request_snapshot` no contiene `x-api-key`.
- `response_snapshot` conserva datos utiles del shipment/rates.
- `last_error_message` es suficiente para operacion.

## Cierre de salida de fase 10

La fase 10 puede considerarse validada cuando:

- el caso nominal funciona con purchase exitoso,
- los fallos de validacion bloquean sin llamar API,
- los fallos de purchase dejan `Courier no pagado`,
- la repeticion del hook no duplica shipments,
- la traza local y eventos permiten diagnostico BO.
