# Fase 09 - Resolucion de paquete, contents y validaciones previas

## Objetivo
Transformar el carrito o pedido en un payload valido para Sendifico antes de crear el shipment.

## Alcance
- Calculo de peso y dimensiones.
- Resolucion de `contents`.
- Validaciones previas a creacion de shipment.

## Actividades
1. Diseñar heuristica de paquete para peso, largo, ancho y alto.
2. Definir estrategia de fallback cuando falten dimensiones de producto.
3. Diseñar mapeo configurable producto o categoria hacia un unico `contents`.
4. Implementar validadores de datos obligatorios para destinatario, sender, parcel y moneda.
5. Separar validaciones de quotation frente a validaciones estrictas de shipment.

## Entregables
- Servicio de package resolver.
- Servicio de contents resolver.
- Matriz de validaciones previas y errores operativos.

## Dependencias
- Fase 04, 05 y 06.
- Definiciones funcionales de datos obligatorios.

## Criterios de salida
- El modulo genera un payload consistente para `POST /shipment`.
- Los errores de datos incompletos se detectan antes de llamar a la API.
- La logica queda reusable para reintentos BO.

## Pendientes para modo plan
- Regla exacta para resolver `contents` cuando existan categorias Sendifico distintas.
- Heuristica final para dimensiones del paquete.
