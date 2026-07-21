# Fase 05 - Sincronizacion y cache de territorios y remitentes

## Objetivo
Disponibilizar localmente los territorios y direcciones remitentes de Sendifico para uso confiable en checkout y operaciones.

## Alcance
- Cliente de lectura para `territory` y `address`.
- Cache local y estrategia de refresco.
- Soporte multitienda y trazabilidad de sincronizacion.

## Actividades
1. Diseñar tablas o almacenamiento para territorios, remitentes y metadata de sync.
2. Implementar servicios de sincronizacion manual y, si aplica, comando de consola.
3. Definir expiracion, invalidacion y refresco seguro del cache.
4. Mapear remitentes disponibles con las tiendas configuradas.
5. Registrar logs y errores recuperables de sincronizacion.

## Entregables
- Cache local de territorios.
- Cache local de remitentes Sendifico.
- Servicio y flujo BO para sincronizacion controlada.

## Dependencias
- Fase 04.
- Endpoints y permisos de API ya validados.

## Criterios de salida
- El checkout y BO pueden resolver territorios sin depender de llamadas en caliente.
- Las tiendas pueden seleccionar un remitente valido de Sendifico.
- Los fallos de sync no bloquean la tienda de forma silenciosa.

## Pendientes para modo plan
- UX final del selector de territorio: un selector unico o jerarquia provincia/canton/ciudad.
- Frecuencia de sincronizacion y si existira soporte cron en v1 o solo accion manual.
