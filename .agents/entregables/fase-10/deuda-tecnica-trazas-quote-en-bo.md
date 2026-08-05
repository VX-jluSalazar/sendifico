# Deuda tecnica - Trazas de quote en BO sobre carts ya convertidos en pedido

Fecha de registro: 2026-08-05
Fase relacionada: 10 - Creacion de shipment y purchase al confirmar pago

## Situacion actual

La implementacion actual permite que, al abrir un pedido en Back Office y cambiar el carrier para inspeccionar precios, PrestaShop vuelva a ejecutar la logica de cotizacion del modulo sobre el `cart` historico asociado al pedido.

Como `CheckoutQuotationService` trabaja por `id_cart` y persiste trazas pendientes cuando recalcula una cotizacion, hoy puede crear una nueva fila en `ps_vx_sendifico_shipment` con:

- el mismo `id_cart` de un pedido ya existente;
- `id_order = NULL`;
- `local_state = quoted`;
- snapshots de `POST /quotation`, aunque ya exista una traza de pedido o incluso un shipment remoto creado para ese mismo carrito convertido.

## Impacto operativo

- La trazabilidad queda mas ruidosa y puede dar la impresion falsa de que existen dos flujos distintos para el mismo checkout.
- Operacion puede confundir una traza tardia de quote BO con una traza real previa a la conversion del pedido.
- Se dificulta leer el historial tecnico de un `id_cart` cuando ya hubo `POST /shipment` o `PATCH /shipment/purchase/{id}`.
- A futuro puede complicar reglas de reconciliacion, reportes o reintentos si se asume que un `id_cart` solo tiene una traza relevante.

## Causa tecnica

El recalculo de carriers en BO sigue invocando `getOrderShippingCostExternal()` del modulo para el `cart` historico del pedido.

Desde ahi se reutiliza la ruta de cotizacion de checkout, que:

- consulta por `findPendingByCartId($cartId)`;
- si no encuentra una pendiente reutilizable, vuelve a persistir una traza de quote;
- no bloquea el caso donde ese `id_cart` ya esta vinculado a una fila con `id_order` no nulo.

## Decision vigente

Por ahora se conserva este comportamiento porque:

- no rompe el flujo nominal de cotizacion ni de purchase;
- el problema observado es de limpieza y claridad de trazabilidad, no de integridad del pedido ni de cobro duplicado;
- la automatizacion de fase 10 sigue siendo idempotente por `id_order` y `extId`.

## Lineas futuras recomendadas

### Opcion A - Bloqueo de quotes BO para carts ya convertidos

Antes de persistir una nueva traza de quote, verificar si existe una fila en `ps_vx_sendifico_shipment` para ese `id_cart` con `id_order IS NOT NULL`.

Si existe:

- no crear nueva fila pendiente;
- devolver la informacion necesaria para el recalculo de carrier sin ensuciar trazabilidad.

### Opcion B - Reutilizacion explicita de la traza del pedido

Cuando el `cart` ya pertenece a un pedido, reutilizar la traza mas reciente asociada a `id_order` en vez de persistir una nueva fila `quoted`.

Esto mantendria una sola cadena de trazabilidad funcional por pedido convertido.

### Opcion C - Diferenciar contexto FO vs BO

Introducir un guard para que la cotizacion de checkout solo persista trazas cuando la accion proviene realmente del checkout FO y no de recalculos administrativos del BO.

## Recomendacion

La opcion mas segura es combinar A y C:

1. detectar si el `cart` ya fue convertido en pedido;
2. evitar nuevas trazas `quoted` en ese caso;
3. no persistir recalculos de carriers provocados solo por inspeccion administrativa en BO.

## Criterio de resolucion

Se considera resuelta esta deuda cuando:

- abrir un pedido en BO y cambiar carriers para inspeccionar precio no crea nuevas filas `quoted` con `id_order = NULL`;
- la trazabilidad por `id_cart` y `id_order` permanece unica o claramente reconciliada;
- el recalculo de carrier en BO sigue funcionando sin degradar la operacion del pedido.

## Estado

- Tipo: deuda tecnica documentada
- Prioridad sugerida: media
- Momento sugerido: antes de fase 11 o junto con ajustes de operacion BO y reintentos
