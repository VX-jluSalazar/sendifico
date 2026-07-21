# Fase 13 - Instalacion, actualizaciones, desinstalacion y compatibilidad multitienda

## Objetivo
Preparar el ciclo de vida del modulo para instalacion inicial, upgrades futuros y operacion segura en multitienda.

## Alcance
- Instalador y scripts de upgrade.
- Creacion de tablas, carriers, configuracion y estados.
- Politica de desinstalacion destructiva y duplicacion de datos por tienda.

## Actividades
1. Implementar instalacion de esquema, configuraciones default, carriers y estados de pedido.
2. Diseñar scripts `upgrade` para cambios de datos y esquema.
3. Definir comportamiento de duplicacion de datos al crear nuevas tiendas.
4. Implementar desinstalacion destructiva: borrar tablas propias, configuracion, logs, mapeos, tabs y estados creados por el modulo.
5. Verificar compatibilidad de todas las fases en contexto multitienda.

## Entregables
- Instalador completo del modulo.
- Base de scripts de upgrade.
- Politica documentada de desinstalacion destructiva y multitienda.

## Dependencias
- Fase 03, 04, 05, 06 y 07.

## Criterios de salida
- El modulo puede instalarse y reinstalarse de forma predecible.
- Los upgrades futuros tienen un patron definido.
- La operacion multitienda no mezcla configuraciones ni datos incorrectamente.

## Pendientes para modo plan
- Sin pendientes de politica de desinstalacion desde fase 01.
- Regla exacta para propagar configuracion o caches entre tiendas nuevas.
