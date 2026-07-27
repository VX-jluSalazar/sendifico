# Fase 05 - Resumen de implementacion

## Alcance ejecutado

- Cache local de territorios Sendifico con refresco manual.
- Cache local de direcciones remitentes por tienda.
- Metadata de sincronizacion con ultimo intento, ultimo exito, conteo y error recuperable.
- Flujo manual de sincronizacion desde Back Office y comando de consola.

## Esquema agregado

Tablas creadas por `CacheSchemaInstaller`:

- `vx_sendifico_territory`
- `vx_sendifico_sender_address`
- `vx_sendifico_sync_meta`

Estas tablas cubren solo cache de referencia y estado de sincronizacion. La trazabilidad operativa de shipments sigue reservada para la fase 06.

## Servicios implementados

```text
src/Api/
|-- SendificoApiClient.php
`-- SendificoApiException.php

src/Configuration/SendificoConnectionConfigurationProvider.php

src/Repository/
|-- TerritoryRepository.php
|-- SenderAddressRepository.php
|-- SyncMetadataRepository.php
`-- ShopRepository.php

src/Sync/
|-- TerritorySyncService.php
|-- SenderAddressSyncService.php
|-- SendificoSyncOrchestrator.php
`-- SendificoSyncStatusProvider.php

src/Command/SyncCacheCommand.php
```

## Flujo operativo

- `GET /territory` se sincroniza por pais y se reemplaza completamente el cache local.
- `GET /address` se pagina y se filtra localmente a `addressType=sender`.
- Los remitentes se guardan por `id_shop`, porque el set de direcciones depende de la API key configurada en cada tienda.
- El estado de sync se registra en `vx_sendifico_sync_meta` con `success` o `failed`.
- La pantalla de configuracion BO expone botones para sincronizar `all`, `territories` o `senders`.
- El campo `Sender reference` del BO se resolvio como selector alimentado desde el cache local de direcciones `sender` del contexto actual.
- El comando `vx_sendifico:sync-cache` permite sincronizacion controlada por CLI.

## Validaciones funcionales cerradas

- La sincronizacion falla de forma visible si la tienda no tiene API key configurada.
- El resumen BO muestra si el `sender_reference` configurado existe o no en el cache de remitentes de la tienda.
- Si no hay remitentes sincronizados, el selector BO queda deshabilitado y exige ejecutar primero `Sync senders`.
- Los fallos remotos quedan visibles como flash messages y en metadata local de sync.

## Validacion ejecutada

- `composer validate --strict`
- `composer dump-autoload`
- `php -l vx_sendifico.php`
- `find src -name '*.php' -print0 | xargs -0 -n1 php -l`
- `ddev exec sh -lc 'cd /var/www/html && php bin/console pr:mo install vx_sendifico'`
- `ddev exec sh -lc 'cd /var/www/html && php bin/console vx_sendifico:sync-cache --help'`

## Validacion pendiente

- `vendor/websenso/prestashop-module-devtools/bin/lotr` sigue no disponible en `vendor/`.
- Validacion manual de la accion BO de sincronizacion desde la pantalla de configuracion.
