# Fase 02 - Arquitectura logica

## Contextos del modulo

```text
Checkout
  -> Quotation
  -> Carrier mapping
  -> Local quotation cache

Order lifecycle
  -> Shipment orchestration
  -> Sendifico shipment draft
  -> Purchase
  -> Local shipment trace

Back Office operations
  -> Retry purchase
  -> Generate tracking
  -> Generate label URL
  -> Shipment detail/listing

Sync
  -> Territories
  -> Sender addresses

Configuration
  -> API credentials
  -> Country/currency
  -> Sender per shop
  -> Package defaults
  -> COD payment modules

Support
  -> Logs
  -> Error mapping
  -> Idempotency/reconciliation
```

## Capas

| Capa | Responsabilidad | Reglas |
| --- | --- | --- |
| Module shell | Bootstrap, hooks, install/uninstall, redirect to BO route. | Sin SQL ni logica de negocio. |
| Controller/Admin | Pantallas y acciones BO. | Valida permisos/CSRF y delega en servicios. |
| Checkout | Captura territorio, solicita cotizacion y filtra carriers. | No crea shipments. |
| Application services | Orquestan casos de uso: quote, create shipment, purchase, tracking, label. | Trabajan con DTOs internos y repositorios. |
| Sendifico client | HTTP, headers, serializacion, errores remotos. | No conoce PrestaShop salvo configuracion recibida. |
| Repositories | Persistencia local y queries. | Unico punto para DBAL/SQL fuera del Installer. |
| Resolvers | Paquete, contents, COD, sender, territory. | Deterministicos y testeables. |

## Flujo end-to-end

1. Checkout obtiene provincia, canton y ciudad desde cache local de territorios.
2. La ciudad seleccionada resuelve un `territoryBaseId`.
3. Quotation service arma `POST /quotation` con sender territory, recipient territory, parcel, COD/insured y `USD`.
4. Carrier service filtra carriers persistentes por `carrierToken` disponible.
5. El cliente elige carrier y se persiste la seleccion de quotation/rate candidato para el carrito.
6. Al crear el pedido y pasar a estado pagado/aceptado, shipment service arma `POST /shipment`.
7. La respuesta de shipment devuelve `rates[]`; el modulo busca el `rateId` equivalente al carrier elegido.
8. Purchase service ejecuta `PATCH /shipment/purchase/{id}` con `purchaseWith=walletAvailable`.
9. Si purchase falla, se guarda error local y el pedido pasa/queda marcado con `Courier no pagado`.
10. Back Office permite reintentar purchase, generar tracking y generar label URL bajo demanda.

## Multitienda

- Toda configuracion operativa se lee por contexto de tienda.
- Sender address se configura por tienda.
- Carrier mapping incluye tienda y `id_reference` para sobrevivir a regeneraciones de carrier.
- Shipments locales guardan `id_shop`.
- Territorios pueden cachearse por pais; la disponibilidad operativa sigue condicionada por tienda/configuracion.
