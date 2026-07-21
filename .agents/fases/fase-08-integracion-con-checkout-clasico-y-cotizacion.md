# Fase 08 - Integracion con checkout clasico y cotizacion

## Objetivo
Integrar el modulo con el checkout clasico para resolver territorio, cotizar y mostrar solo carriers validos de Sendifico.

## Alcance
- Captura de territorio y datos de entrega.
- Llamada a `POST /quotation`.
- Filtro y refresco de transportistas en checkout.

## Actividades
1. Identificar hooks y puntos tecnicos exactos del checkout clasico para inyectar UI y recalculo.
2. Diseñar la UX del selector de territorio y su persistencia temporal.
3. Implementar el servicio de quotation y su adaptador al contexto del carrito.
4. Mapear respuesta de Sendifico a opciones de carrier visibles y precio exacto.
5. Persistir la seleccion del rate para uso posterior al crear shipment.

## Entregables
- Integracion funcional de quotation en checkout.
- Selector de territorio.
- Filtro dinamico de carriers y tarifa exacta.

## Dependencias
- Fase 05, 06 y 07.
- Checkout clasico disponible y validado.

## Criterios de salida
- El cliente solo ve carriers devueltos por la cotizacion actual.
- El precio cobrado coincide con la tarifa de Sendifico.
- Si la cotizacion no es posible, el comportamiento de fallback queda controlado y trazado.

## Pendientes para modo plan
- Hooks exactos para capturar territorio, cotizar, recalcular y persistir seleccion.
- Politica precisa de fallback cuando faltan `lat` y `lng`.
- Comportamiento cuando faltan datos criticos pero se permite cotizar.
