# Fase 02 - Mapa de servicios y dependencias

## Servicios principales

| Servicio | Namespace sugerido | Depende de | Responsabilidad |
| --- | --- | --- | --- |
| ApiClient | `Vx\Sendifico\Api\SendificoApiClient` | HTTP client, configuration, logger | Ejecutar requests Sendifico con headers obligatorios. |
| ApiErrorMapper | `Vx\Sendifico\Api\ApiErrorMapper` | Ninguna | Convertir codigos remotos en errores operativos internos. |
| QuotationService | `Vx\Sendifico\Checkout\QuotationService` | ApiClient, PackageResolver, CodResolver, SenderResolver, QuotationRepository | Cotizar carrito y persistir resultado resumido. |
| CarrierAvailabilityService | `Vx\Sendifico\Carrier\CarrierAvailabilityService` | CarrierMappingRepository, QuotationRepository | Filtrar carriers PrestaShop por `carrierToken`. |
| CarrierMappingService | `Vx\Sendifico\Carrier\CarrierMappingService` | CarrierMappingRepository | Crear/actualizar mapeos carrier-token por tienda. |
| ShipmentService | `Vx\Sendifico\Order\ShipmentService` | ApiClient, ShipmentRepository, resolvers | Crear draft remoto y guardar trazabilidad local. |
| PurchaseService | `Vx\Sendifico\Order\PurchaseService` | ApiClient, ShipmentRepository, logger | Comprar shipment con `walletAvailable` y manejar reintentos. |
| TrackingService | `Vx\Sendifico\Order\TrackingService` | ApiClient, ShipmentRepository | Generar tracking manual y validar que exista numero. |
| LabelService | `Vx\Sendifico\Order\LabelService` | ApiClient, ShipmentRepository | Generar URL temporal de label bajo demanda. |
| TerritorySyncService | `Vx\Sendifico\Sync\TerritorySyncService` | ApiClient, TerritoryRepository | Sincronizar `GET /territory`. |
| SenderAddressSyncService | `Vx\Sendifico\Sync\SenderAddressSyncService` | ApiClient, SenderAddressRepository | Sincronizar `GET /address` filtrando senders. |
| PackageResolver | `Vx\Sendifico\Package\PackageResolver` | Configuration, product data | Calcular kg/cm con defaults BO. |
| ContentsResolver | `Vx\Sendifico\Package\ContentsResolver` | Product/category mapping | Resolver una categoria Sendifico por pedido. |
| CodResolver | `Vx\Sendifico\Order\CodResolver` | Configuration, payment module/order | Resolver `goodsCollection`. |
| ShipmentStateService | `Vx\Sendifico\Order\ShipmentStateService` | ShipmentRepository, order history adapter | Mapear estado local y `Courier no pagado`. |

## Repositorios previstos

| Repositorio | Tabla prevista | Kernel |
| --- | --- | --- |
| TerritoryRepository | `vx_sendifico_territory` | common |
| SenderAddressRepository | `vx_sendifico_sender_address` | common |
| CarrierMappingRepository | `vx_sendifico_carrier_map` | common |
| QuotationRepository | `vx_sendifico_quotation` / `vx_sendifico_quotation_rate` | common |
| ShipmentRepository | `vx_sendifico_shipment` / `vx_sendifico_shipment_event` | common |
| ConfigurationRepository opcional | `configuration` via adapter | admin/front segun uso |

## DTOs internos

| DTO | Campos clave |
| --- | --- |
| `QuoteRequestData` | shop, cart, sender territory, recipient territory, parcel, COD, insured, currency. |
| `QuoteRateData` | quotationId, carrierToken, priceSubtotal, priceTotal, currency, estimateDays, available, unavailableReason. |
| `SelectedCarrierData` | idCart, idCarrier/idReference, carrierToken, quotationId, quotedPriceTotal, currency. |
| `ShipmentDraftData` | shipmentId, extId, rates, remoteStatus, isPaid, sender/recipient summary. |
| `PurchaseRequestData` | shipmentId, preferredRateObjectId, purchaseWith=`walletAvailable`. |
| `TrackingData` | shipmentId, trackingNumber, trackingCarrierUrl, generatedAt. |
| `LabelData` | shipmentId, type, disposition, url, generatedAt, expiresPolicy. |
| `ApiErrorData` | endpoint, statusCode, message, retryable, safeSummary. |

## Service file placement

- Common repositories and low-level DB services: `config/common.yml` via `config/components/repository/*.yml`.
- Admin controllers, forms, grid, BO actions and commands: `config/admin/services.yml`.
- Front-office services needed by hooks/checkout: `config/front/services.yml`.
