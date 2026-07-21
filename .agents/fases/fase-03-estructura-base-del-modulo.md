# Fase 03 - Estructura base del modulo

## Objetivo
Construir el esqueleto instalable del modulo con estructura moderna y convenciones alineadas a PrestaShop 8.2.1.

## Alcance
- Archivo principal del modulo.
- Composer, autoload y namespace.
- Instalador, servicios base y estructura de carpetas.

## Actividades
1. Crear `vx_sendifico.php` con bootstrap moderno y carga de `vendor/autoload.php`.
2. Crear `composer.json` y definir PSR-4 del namespace acordado.
3. Preparar `src/Install/Installer.php` y separacion de responsabilidades de instalacion.
4. Crear `config/admin/services.yml`, `config/front/services.yml` y `config/common.yml`.
5. Preparar carpetas base: `src/`, `config/`, `views/`, `translations/`, `upgrade/`, `tests/`.

## Entregables
- Modulo instalable en estado baseline.
- Estructura de directorios consistente.
- Servicios base y parametros comunes definidos.

## Dependencias
- Fase 01 y 02.

## Criterios de salida
- El modulo instala sin errores estructurales.
- El contenedor de servicios compila.
- El namespace y la organizacion de carpetas quedan congelados para fases siguientes.

## Pendientes para modo plan
- Sin pendientes bloqueantes desde fase 01.
