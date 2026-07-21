# Fase 07 - Estrategia de carriers persistentes mapeados a Sendifico

## Objetivo
Definir y materializar la relacion entre carriers persistentes de PrestaShop y `carrierToken` de Sendifico.

## Alcance
- Creacion y mantenimiento de carriers internos.
- Mapeo carrier-token por tienda.
- Reglas de visibilidad basadas en cotizacion.

## Actividades
1. Definir modelo de mapeo entre `id_carrier`, `id_reference`, `carrierToken` y metadata operativa.
2. Decidir si los carriers se crean desde instalacion, sync o accion BO.
3. Implementar servicios de provision y reconciliacion de carriers.
4. Definir reglas para ocultar carriers no presentes en la cotizacion actual.
5. Documentar como persistir y recuperar la eleccion del `rateId` del checkout.

## Entregables
- Estrategia de carriers persistentes.
- Servicio de sincronizacion o provision de carriers.
- Reglas de mapeo y consistencia por tienda.

## Dependencias
- Fase 04, 05 y 06.

## Criterios de salida
- Cada opcion de envio visible en checkout se puede asociar de forma estable a un carrier local.
- La seleccion del cliente conserva el `carrierToken` y `rateId` correctos.
- El modulo soporta cambios futuros de catalogo de couriers sin romper pedidos previos.

## Pendientes para modo plan
- Momento exacto de creacion de carriers persistentes.
- Politica ante couriers nuevos o inactivos devueltos por Sendifico.
