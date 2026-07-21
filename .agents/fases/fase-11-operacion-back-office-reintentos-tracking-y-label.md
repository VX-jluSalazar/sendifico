# Fase 11 - Operacion Back Office: reintentos, tracking y label

## Objetivo
Habilitar la operacion manual desde Back Office para reintentar purchase, generar tracking y generar label bajo demanda.

## Alcance
- Acciones en ficha de pedido.
- Listado propio del modulo.
- Controles de permisos y feedback operativo.

## Actividades
1. Diseñar acciones BO sobre pedido y sobre listado del modulo.
2. Implementar reintento manual de `purchase`.
3. Implementar `generateTrackingNumber` con validacion del resultado real.
4. Implementar `generateLabelUrl` y presentacion segura del enlace temporal.
5. Mostrar historial operativo, ultimo error y estado resumido del shipment.

## Entregables
- Acciones manuales BO funcionales.
- Vista operativa del shipment por pedido.
- Listado propio del modulo con trazabilidad y filtros base.

## Dependencias
- Fase 06 y 10.
- Modelo de permisos administrativos definido.

## Criterios de salida
- El equipo operativo puede recuperar fallos sin SQL manual.
- El tracking y la label se generan solo cuando la operacion lo requiere.
- El BO refleja claramente el estado del shipment y sus errores.

## Pendientes para modo plan
- UX final del listado propio del modulo y columnas prioritarias.
- Estado de pedido exacto recomendado para generar tracking cerca del despacho.
