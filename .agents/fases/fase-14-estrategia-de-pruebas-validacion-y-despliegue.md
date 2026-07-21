# Fase 14 - Estrategia de pruebas, validacion y despliegue

## Objetivo
Definir como validar la integracion antes de puesta en produccion y como reducir riesgo operativo en el despliegue.

## Alcance
- Pruebas unitarias, integracion y smoke tests.
- Validacion manual de checkout y BO.
- Checklist de release y soporte post-despliegue.

## Actividades
1. Definir cobertura minima para mapeo de payloads, validadores, package resolver y contents resolver.
2. Diseñar pruebas de integracion para quotation, shipment, purchase, tracking y label con dobles o fixtures.
3. Preparar casos manuales sobre checkout clasico, cambio de direccion, seleccion de carrier, pedido pagado y operaciones BO.
4. Establecer validaciones de instalacion, upgrade y multitienda.
5. Crear checklist de despliegue, monitoreo inicial y rollback funcional.

## Entregables
- Estrategia de pruebas del modulo.
- Set inicial de fixtures y escenarios.
- Checklist de release y validacion post-instalacion.

## Dependencias
- Todas las fases previas, al menos en baseline funcional.

## Criterios de salida
- Existe un camino claro para validar cambios sin depender solo de pruebas manuales ad hoc.
- Las rutas criticas del negocio tienen cobertura priorizada.
- El despliegue cuenta con checklist y señales de monitoreo inicial.

## Pendientes para modo plan
- Disponibilidad de entorno de pruebas con Sendifico real o sandbox.
- Criterio de mocks vs pruebas contra API real para cada endpoint.
