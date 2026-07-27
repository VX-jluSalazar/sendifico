# Fase 07 - Resumen de implementacion

## Decision ejecutada

La provision de carriers persistentes se resuelve ahora de dos maneras:

- automaticamente durante la instalacion del modulo;
- manualmente desde Back Office o comando para reconciliacion posterior.

Razon:

- no existe endpoint Sendifico para sincronizar un catalogo oficial de couriers;
- el catalogo inicial se toma del set conocido de `carrierToken` documentado en la fuente contractual;
- la instalacion debe dejar la tienda lista con carriers persistentes ya creados y mapeados;
- la accion BO y el comando quedan como mecanismo de reconciliacion idempotente ante cambios o reparaciones.

## Modelo de mapeo agregado

Tabla nueva:

- `vx_sendifico_carrier_map`

Campos principales:

- `id_shop`
- `id_carrier`
- `id_carrier_reference`
- `carrier_token`
- `display_name`
- `is_active`
- `provision_source`
- `last_provision_at`

Reglas:

- unicidad por `id_shop + carrier_token`
- unicidad por `id_shop + id_carrier_reference`
- persistencia estable basada en `id_carrier_reference`, no en `id_carrier`

## Catalogo inicial materializado

Se agrego un catalogo local de carriers Sendifico conocidos:

- `ec_laar`
- `ec_tramaco`
- `servientrega`
- `ec_delivereo`
- `ec_yobel`
- `ec_urbano`
- `ec_gintracom`

Cada token provisiona un carrier local `Sendifico - {Courier}` y luego genera el mapeo por tienda.

## Servicios implementados

```text
src/Install/CarrierSchemaInstaller.php
src/Install/CarrierProvisionInstaller.php
src/Carrier/CarrierCatalogProvider.php
src/Carrier/CarrierProvisionService.php
src/Carrier/CarrierProvisionStatusProvider.php
src/Repository/CarrierMappingRepository.php
src/Repository/PrestaShopCarrierRepository.php
src/Command/ProvisionCarriersCommand.php
upgrade/upgrade-0.4.0.php
```

## Reglas de consistencia cerradas

- el vínculo duradero con pedidos y trazabilidad futura usará `id_carrier_reference`
- `id_carrier` se conserva solo como puntero operativo al registro vigente
- los carriers quedan asociados al modulo `vx_sendifico`
- el checkout de fase 08 deberá mostrar solo carriers cuyo `carrier_token` exista en la cotizacion actual con `available=true`
- la seleccion del cliente se documenta para persistirse en fase 08 mediante `id_cart + id_carrier_reference + carrier_token + quotationId/rateId`

## Flujo operativo disponible

- provision automatica durante `install()`
- accion BO `Provision carriers`
- comando `vx_sendifico:provision-carriers`
- resumen BO del estado de mapeo por tiendas activas

## Estrategia de upgrade

- la version del modulo pasa a `0.4.0`
- `upgrade/upgrade-0.4.0.php` crea la tabla `vx_sendifico_carrier_map`
- la reconciliacion de carriers ya creados puede ejecutarse despues con la accion BO o el comando CLI, sin depender de recrear carriers

## Validacion ejecutada

- `composer dump-autoload`
- `php -l vx_sendifico.php`
- `find src -name '*.php' -print0 | xargs -0 -n1 php -l`

## Validacion pendiente

- `vendor/websenso/prestashop-module-devtools/bin/lotr` sigue no disponible en `vendor/`
- validacion de instalacion limpia con creacion y mapeo de carriers en un solo paso
