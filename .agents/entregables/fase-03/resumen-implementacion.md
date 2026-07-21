# Fase 03 - Resumen de implementacion

## Esqueleto creado

- Archivo principal: `vx_sendifico.php`.
- Clase principal: `Vx_Sendifico`.
- Composer package: `velox/vx-sendifico`.
- Namespace PSR-4: `Vx\Sendifico\`.
- Author: `Velox`.
- Version inicial: `0.1.0`.

## Estructura base

```text
config/
|-- common.yml
|-- routes.yml
|-- admin/services.yml
|-- front/services.yml
`-- components/controller/controllers.yml

src/
|-- Controller/Admin/ConfigurationController.php
`-- Install/
    |-- ConfigurationInstaller.php
    `-- Installer.php

views/templates/admin/configuration.html.twig
translations/
upgrade/
tests/
vendor/autoload.php
```

## Decisiones implementadas

- `vx_sendifico.php` carga `vendor/autoload.php` tras el guard `_PS_VERSION_`.
- `install()` y `uninstall()` delegan en `Vx\Sendifico\Install\Installer`.
- `Installer` instala configuracion inicial y tab oculto `AdminVxSendificoConfiguration`.
- `getContent()` redirige a la ruta Symfony `vx_sendifico_configuration`.
- No se crea `config/services.yml` raiz.
- Servicios separados por kernel:
  - `config/common.yml`
  - `config/admin/services.yml`
  - `config/front/services.yml`
- Pantalla BO minima creada como placeholder; la configuracion completa queda para fase 04.
- No se crean tablas en esta fase; el modelo se cierra en fase 06.

## Configuracion inicial instalada

| Key | Valor |
| --- | --- |
| `VX_SENDIFICO_API_VERSION` | `2026-01-01` |
| `VX_SENDIFICO_COUNTRY` | `EC` |
| `VX_SENDIFICO_CURRENCY` | `USD` |
| `VX_SENDIFICO_PURCHASE_WITH` | `walletAvailable` |
| `VX_SENDIFICO_DEFAULT_WEIGHT` | `1.000` |
| `VX_SENDIFICO_DEFAULT_LENGTH` | `10.000` |
| `VX_SENDIFICO_DEFAULT_WIDTH` | `10.000` |
| `VX_SENDIFICO_DEFAULT_HEIGHT` | `10.000` |

## Validacion ejecutada

- `composer dump-autoload`: OK.
- `php -l` sobre archivos PHP principales: OK.
- `composer validate --strict`: OK.
- Autoload PSR-4 para `Vx\Sendifico\Install\Installer`: OK.
- `php bin/console pr:mo install vx_sendifico`: exit code 0 en la instalacion local.

## Validacion pendiente

- `vendor/websenso/prestashop-module-devtools/bin/lotr` no pudo ejecutarse porque `prestashop-module-devtools` no esta instalado en `vendor/`.
- La instalacion local de PrestaShop no tiene `app/config/parameters.yml`; la consola devuelve codigo 0 pero no imprime mensaje de exito limpio, y la salida normal esta contaminada por deprecations de dependencias bajo PHP 8.2.
