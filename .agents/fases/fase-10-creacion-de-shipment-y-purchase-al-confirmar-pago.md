# Fase 10 - Creacion de shipment y purchase al confirmar pago

## Objetivo
Automatizar la creacion del shipment y el intento de compra del envio cuando el pedido entra en estado pagado o aceptado.

## Alcance
- Escucha de cambio de estado del pedido.
- `POST /shipment`.
- `PATCH /shipment/purchase/{id}` con trazabilidad e idempotencia.

## Actividades
1. Identificar el hook exacto para disparar la logica al entrar en estado pagado o aceptado.
2. Construir el payload final usando sender configurado, direccion del pedido, parcel y contents.
3. Crear el shipment y persistir `shipmentId`, rates relevantes y metadata.
4. Ejecutar purchase con el `preferredRateObjectId` seleccionado en checkout.
5. Registrar resultado, reintentos permitidos y actualizacion de estado de pedido si falla.

## Entregables
- Automatizacion de creacion de shipment.
- Automatizacion de purchase.
- Trazabilidad completa del resultado por pedido.

## Dependencias
- Fase 06, 07, 08 y 09.

## Criterios de salida
- Un pedido pagado puede crear y comprar su envio sin intervencion manual en el caso nominal.
- La operacion es idempotente frente a repeticion del hook o reintentos controlados.
- Los fallos quedan visibles para operacion BO sin impactar al cliente.

## Pendientes para modo plan
- Estado exacto o conjunto de estados del pedido que disparan la automatizacion.
- Politica final cuando `purchase` falla: estado informativo, reintentos automaticos o solo manuales.
