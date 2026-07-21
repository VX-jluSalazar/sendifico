# Fase 06 - Modelo de datos y persistencia de trazabilidad

## Objetivo
Definir y crear la persistencia local del modulo para shipments, eventos operativos, errores y metadata de reintento.

## Alcance
- Esquema de tablas propias.
- Repositories y managers de persistencia.
- Auditoria tecnica y operativa.

## Actividades
1. Diseñar la tabla principal de relacion pedido-shipment Sendifico.
2. Diseñar tablas auxiliares para logs operativos, intentos y snapshots resumidos.
3. Definir estados internos locales del shipment y transiciones permitidas.
4. Implementar repositorios para consultas BO y reintentos.
5. Preparar scripts de instalacion y futuras migraciones.

## Entregables
- Modelo de datos del modulo.
- Capa de persistencia local.
- Estrategia de upgrade de esquema.

## Dependencias
- Fase 02 y 03.
- Reglas de trazabilidad definidas.

## Criterios de salida
- Toda operacion critica deja rastro local consultable.
- Existe soporte para reintentos y conciliacion operativa.
- El modelo cubre shipment, purchase, tracking, label y errores.

## Pendientes para modo plan
- Esquema exacto de columnas y nivel de detalle del payload resumido.
- Retencion de logs y politica de purga.
