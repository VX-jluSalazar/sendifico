# Fase 13 - Ciclo de vida y multitienda

## Instalacion

El instalador del modulo registra hooks, crea tablas propias, inicializa configuracion por tienda/grupo/global, crea estado operativo de pedido, tabs BO y carriers persistentes mapeados a Sendifico.

Hooks registrados:

- Checkout y direccion: quotation, seleccion de carrier y metadatos de territorio.
- Pedido: creacion/purchase tras validacion o cambio de estado.
- BO: panel de operaciones en pedido.
- Multitienda: `actionShopDataDuplication`.

## Upgrades

La serie de `upgrade/upgrade-*.php` mantiene cambios incrementales por version. La version `1.0.0` registra `actionShopDataDuplication` para tiendas existentes que actualizan desde `0.9.0`. La version `1.0.1` normaliza la navegacion BO para dejar un menu padre `Sendifico` con `Configuración` y `Envios` como hijos visibles.

Patron de upgrade:

- cambios de esquema: idempotentes, `ALTER` protegido por comprobacion previa;
- hooks nuevos: registrar por version;
- configuracion nueva: crear valor default global/grupo/tienda sin sobreescribir valores existentes;
- datos operativos: nunca borrar trazas en upgrade.

## Duplicacion de tiendas

Al duplicar tienda, `ShopDataDuplicator`:

- copia la configuracion Sendifico desde la tienda origen a la tienda destino;
- replica el cache de remitentes como punto de partida operativo;
- replica mapeos de carriers con `provision_source = shop_duplication`;
- reejecuta el provisionamiento de carriers para asegurar elegibilidad externa y mapeos faltantes.

No se duplican trazas de shipments, eventos ni metadata de direcciones, porque pertenecen a pedidos, carts y direcciones concretas de la tienda original.

## Desinstalacion destructiva

`uninstall()` elimina:

- estado de pedido creado por el modulo;
- configuraciones `VX_SENDIFICO_*`;
- tablas propias de carriers, cache, metadatos de direccion, shipments y eventos;
- tabs BO del modulo.

Los carriers locales se mantienen como registros de PrestaShop para evitar romper pedidos historicos que ya referencien `id_carrier` o `id_carrier_reference`.
