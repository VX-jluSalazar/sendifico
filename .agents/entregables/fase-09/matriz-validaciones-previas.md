# Fase 09 - Matriz de validaciones previas

## Quotation

| Campo | Regla local | Accion |
| --- | --- | --- |
| `senderTerritoryBaseId` | Obligatorio | No cotizar si falta. |
| `recipientTerritoryBaseId` | Obligatorio | No cotizar si falta. |
| `parcel.weight` | `> 0` | No cotizar si falla. |
| `parcel.length/width/height` | `>= 1` | No cotizar si falla. |
| `goodsCurrency` | ISO 3 uppercase | No cotizar si falla. |
| `goodsInsured` | `>= 0` | Normalizar a `0` si llega negativo. |
| `goodsCollection` | `>= 0` | En checkout se mantiene `0` en fase 08/09. |

## Shipment

| Campo | Regla local | Accion |
| --- | --- | --- |
| `senderAddressId` | Obligatorio y `> 0` | No llamar `POST /shipment`. |
| `recipientAddress.fullName` | Obligatorio | Bloqueo previo. |
| `recipientAddress.streetLine1` | Obligatorio | Bloqueo previo. |
| `recipientAddress.territoryBaseId` | Obligatorio | Bloqueo previo. |
| `recipientAddress.country` | Obligatorio | Bloqueo previo. |
| `recipientAddress.phone` | Obligatorio | Bloqueo previo. |
| `parcel.*` | Reglas de quotation | Bloqueo previo. |
| `contents` | Array no vacio con exactamente 1 valor valido | Bloqueo previo. |
| `goodsCurrency` | ISO 3 uppercase | Bloqueo previo. |
| `extId` | Recomendado, no obligatorio por contrato | Fase 10 lo generara como `ps-{id_shop}-order-{id_order}`. |

## Reglas de resolucion

| Tema | Regla |
| --- | --- |
| Peso | Suma de pesos por linea; si un producto no tiene peso, usa default por unidad. |
| Dimensiones | Si faltan, usa defaults por unidad. |
| Altura final | `max(defaultHeight, maxHeight, totalVolume / (length * width))`. |
| `contents` producto | `content_product_map` tiene prioridad maxima. |
| `contents` categoria | Si no hay mapeo por producto, usa `content_category_map` sobre `id_category_default`. |
| Fallback `contents` | Si no hay mapeos, usa `default_contents`. |
| Dominancia multi-contents | Mayor cantidad total; empate por mayor peso acumulado. |
| COD | Si `order.module` esta en `cod_payment_methods`, `goodsCollection = total_paid_tax_incl`; si no, `0`. |
| `goodsInsured` | En pedido usa `total_products_wt`; en carrito usa total sin shipping. |

## Pendientes abiertos

| Tema | Estado |
| --- | --- |
| `lat` / `lng` | Pendiente funcional. El payload preparado los deja en `null`. |
| Recipient saved address vs inline address | Se deja inline por defecto; usar `recipientAddressId` queda fuera de este corte. |
| Reglas de mezcla avanzada de productos volumetricos | Fuera de este corte; la heuristica actual es deterministica y reusable. |
