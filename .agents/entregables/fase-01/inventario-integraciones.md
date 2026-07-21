# Fase 01 - Inventario de puntos de integracion

## PrestaShop

| Punto | Uso previsto | Datos principales | Riesgo | Fase |
| --- | --- | --- | --- | --- |
| Modulo principal `vx_sendifico.php` | Bootstrap, metadatos, hooks, redireccion a configuracion. | Nombre `vx_sendifico`, author `Velox`, version, tabs ocultos, hooks. | Bajo | Fase 03 |
| Installer | Registro de hooks, tablas, estados de pedido, tabs, configuracion inicial. | Hooks, schema SQL, valores por tienda. | Medio | Fase 03/Fase 13 |
| Configuracion BO | API key, pais, moneda, sender por tienda, COD, defaults paquete, politica uninstall. | `x-api-key`, `EC`, `USD`, `senderAddressId`, metodos de pago COD, peso/dimensiones defaults. | Alto | Fase 04 |
| Checkout clasico | Selector de territorio, cotizacion, filtrado de carriers, persistencia de seleccion. | Provincia, canton, ciudad, `territoryBaseId`, carrito, direccion, carrier elegido, tarifa. | Alto | Fase 08 |
| Carrier PrestaShop | Carriers persistentes mapeados a Sendifico. | `id_carrier`, `id_reference`, `carrierToken`, shop. | Alto | Fase 07 |
| Cart/Package | Calculo de peso, dimensiones, valor asegurado, categoria `contents` y COD. | Productos, cantidades, dimensiones, pesos, categorias Sendifico, precios, metodo de pago. | Medio | Fase 09 |
| Order lifecycle | Disparar creacion y purchase cuando el pedido sea pagado/aceptado. | `id_order`, estado actual, `id_cart`, total, carrier. | Alto | Fase 10 |
| Order states | Estado informativo para fallo operativo de courier. | "Courier no pagado" como unico estado adicional. | Medio | Fase 10/Fase 13 |
| BO order page | Acciones manuales sobre shipment desde ficha de pedido. | Retry purchase, generate tracking, generate label, trazabilidad. | Medio | Fase 11 |
| BO listing propio | Operacion y soporte de shipments locales. | Filtros por estado, pedido, carrier, errores, fechas. | Medio | Fase 11 |
| Logs PrestaShop | Diagnostico tecnico y operativo. | Codigo error, endpoint, status HTTP, resumen payload. | Medio | Fase 12 |
| Multitienda | Aislar configuracion, carriers, territorios y shipments por tienda. | `id_shop`, `id_shop_group`, contexto shop. | Alto | Fase 06/Fase 13 |

## Sendifico API

Headers obligatorios en todas las llamadas:

- `x-api-key`: credencial privada.
- `x-sendifico-api-version`: `2026-01-01`.
- `x-sendifico-country`: `EC`.
- `Content-Type: application/json` cuando haya body.

| Endpoint | Uso en modulo | Datos requeridos | Persistencia local minima | Fase |
| --- | --- | --- | --- | --- |
| `GET /territory` | Sincronizar territorios para selector checkout. | Headers version/pais. | `territoryBaseId`, niveles, `searchableText`, activo/sync timestamp. | Fase 05 |
| `GET /address` | Sincronizar direcciones Sendifico y seleccionar remitente. | Headers version/pais, paginacion. | `addressId`, `addressType`, direccion resumida, shop configurada. | Fase 05 |
| `POST /quotation` | Cotizar en checkout sin crear shipment. | Sender/recipient `territoryBaseId`, `country`, `parcel`, `goodsCurrency`, opcional COD/insured. | Cotizacion por carrito: `carrierToken`, `quotationId`, precio, disponibilidad, error. | Fase 08 |
| `POST /shipment` | Crear draft real despues del pedido. | `senderAddressId`, `recipientAddress` o `recipientAddressId`, `parcel`, `contents`, `goodsCollection`, `goodsInsured`, `goodsCurrency`, `extId`. | `shipmentId`, `extId`, `rates`, estado, request/response resumido. | Fase 10 |
| `PATCH /shipment/purchase/{id}` | Comprar envio con rate elegido. | `preferredRateObjectId`, `purchaseWith=walletAvailable`. | Rate usado, `isPaid`, precio final, error si falla. | Fase 10/Fase 11 |
| `PATCH /shipment/generateTrackingNumber/{id}` | Generar tracking manual BO. | Path `shipmentId`; sin body. | `trackingNumber`, `trackingCarrierUrl`, timestamp/error. | Fase 11 |
| `POST /shipment/generateLabelUrl/{id}` | Generar URL temporal de label bajo demanda. | `type=carrierDefault`, `disposition=inline|attachment`. | URL temporal, timestamp, disposition, error. | Fase 11 |
| `GET /shipment` | Diagnostico/reconciliacion en reintentos ambiguos. | Paginacion y filtros disponibles segun contrato. | Datos usados para reconciliar `extId` y estado remoto. | Fase 10/Fase 11 |
| `GET /shipment/{id}` | Refrescar detalle remoto desde BO. | `shipmentId`. | Estado remoto, paid, tracking, incidentes. | Fase 11 |
| `PATCH /shipment/{id}` | Editar draft no pagado si fase posterior lo requiere. | Campos parciales permitidos solo antes de pago. | Nueva rate list y auditoria. | Fuera de flujo nominal v1 |
| `PATCH /shipment/annulAndRefund/{id}` | Cancelacion/refund eventual. | Motivo de anulacion. | Estado remoto/local y razon. | No requerido v1 inicial |

## Datos cruzados criticos

| Dato | Origen | Destino | Regla |
| --- | --- | --- | --- |
| `territoryBaseId` | Sendifico `GET /territory` / seleccion checkout | `POST /quotation`, `POST /shipment` | Opaque, no derivar desde ciudad libre. |
| `senderAddressId` | Sendifico `GET /address` configurado por tienda | `POST /shipment` | Obligatorio para crear shipment. |
| `carrierToken` | Sendifico quotation/rates | Carrier PrestaShop local | Mapeo persistente por shop. |
| `quotationId` | `POST /quotation` | Trazabilidad checkout | No sirve directamente como `rateId` de purchase. |
| `rateId` | `POST /shipment` rates | `PATCH /shipment/purchase/{id}` | Debe corresponder al carrier elegido y estar disponible. |
| `priceTotal` | Sendifico rates/quotation | Coste carrier PrestaShop y auditoria | Debe ser el importe cobrado al cliente en checkout. |
| `extId` | Pedido PrestaShop | Sendifico shipment | Unico por cuenta Sendifico; recomendado `ps-{id_shop}-order-{id_order}`. |
| `trackingNumber` | Sendifico tracking | Pedido/BO | Solo actualizar si la API devuelve un valor efectivo. |
| `labelUrl` | Sendifico label | BO | Temporal; regenerable, no tratar como archivo permanente. |
