# Fase 09 - Resumen de implementacion

## Alcance implementado
- Se incorporo un `PackageResolver` reutilizable para transformar el carrito en un `parcel` consistente para quote y shipment.
- La heuristica de paquete usa:
  - peso total por linea con fallback al peso default por producto faltante,
  - `depth -> length`, `width -> width`, `height -> height`,
  - defaults configurables cuando falten dimensiones,
  - altura derivada por apilamiento usando volumen total / area base.
- Se incorporo un `ContentsResolver` para resolver exactamente un `contents` Sendifico por shipment.
- El resolver soporta mapeos configurables por producto y por categoria, con prioridad:
  1. producto,
  2. categoria default del producto,
  3. `default_contents`.
- Si un carrito mezcla multiples `contents`, domina:
  1. el de mayor cantidad total,
  2. en empate, el de mayor peso acumulado,
  3. en empate absoluto, orden lexicografico estable.
- Se incorporo `CodResolver` para detectar contraentrega segun los modulos de pago configurados en BO y convertirla en `goodsCollection`.
- Se incorporo `ShipmentPayloadPreparer` para preparar un borrador reusable del payload de `POST /shipment` a partir de `Cart` u `Order`.
- Se incorporo `ShipmentPayloadValidator` con dos niveles:
  - `validateQuotationPayload()`
  - `validateShipmentPayload()`
- Se extendio la configuracion BO con:
  - `default_contents`
  - `content_product_map`
  - `content_category_map`

## Archivos principales
- `src/Package/PackageResolver.php`
- `src/Order/ContentsCatalog.php`
- `src/Order/ContentsMappingParser.php`
- `src/Order/ContentsResolver.php`
- `src/Order/CodResolver.php`
- `src/Order/ShipmentPreparationConfigurationProvider.php`
- `src/Order/ShipmentPayloadValidator.php`
- `src/Order/ShipmentPayloadPreparer.php`
- `src/Checkout/CheckoutContextResolver.php`
- `src/Form/Admin/Type/SendificoConfigurationType.php`
- `src/Configuration/SendificoDataConfiguration.php`
- `src/Configuration/SendificoFormDataProvider.php`

## Decisiones operativas
- `contents` siempre se reduce a una sola categoria Sendifico para cumplir el contrato de `POST /shipment`.
- `lat` y `lng` siguen sin resolverse automaticamente; se envian `null` en el payload preparado y quedan como pendiente funcional documentado.
- Las validaciones de quote permiten omitir `contents` y `senderAddressId`, pero exigen territorios, `parcel` y moneda validos.
- Las validaciones de shipment exigen ademas:
  - `senderAddressId`
  - `recipientAddress` completo
  - `contents`

## Resultado esperado para fase 10
- El flujo de creacion de shipment puede reutilizar el payload preparado sin recalcular heuristicas.
- Los errores de datos faltantes quedan detectables antes de llamar a `POST /shipment`.
