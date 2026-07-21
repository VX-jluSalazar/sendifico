# Fase 02 - Diseno funcional y arquitectura

## Objetivo
Definir la arquitectura del modulo y los contratos internos entre configuracion, checkout, persistencia, operaciones BO y cliente API.

## Alcance
- Modelo de componentes y responsabilidades.
- Flujo end-to-end desde cotizacion hasta label.
- Politicas de error, idempotencia, trazabilidad y multitienda.

## Actividades
1. Diseñar los bounded contexts del modulo: configuracion, quotation, shipment, BO operations, sync y logging.
2. Definir servicios principales: API client, quotation service, shipment service, territory sync, sender sync, package resolver, contents resolver y repositories.
3. Diseñar contratos DTO internos para quotation, shipment draft, purchase, tracking y label.
4. Resolver reglas transversales: timeouts, retries, headers obligatorios, currency, country y logging resumido.
5. Documentar el flujo de estados local y la relacion con estados de pedido de PrestaShop.

## Entregables
- Diagrama de arquitectura logica.
- Mapa de servicios y dependencias.
- Especificacion de flujos nominales y alternos.
- Politica de errores e idempotencia.

## Dependencias
- Fase 01 cerrada.
- Contrato API validado como fuente de verdad.

## Criterios de salida
- Cada responsabilidad tiene una capa definida.
- El flujo de datos entre checkout, pedido, BO y Sendifico es consistente.
- Las reglas transversales quedaron documentadas antes de modelar tablas o hooks.

## Pendientes para modo plan
- Sin pendientes bloqueantes desde fase 02. `Courier no pagado` queda como unico estado adicional.
