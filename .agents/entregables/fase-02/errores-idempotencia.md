# Fase 02 - Politica de errores, idempotencia y trazabilidad

## Headers y versionado

Todas las llamadas a Sendifico deben enviar:

- `x-api-key`: configurado por tienda.
- `x-sendifico-api-version`: `2026-01-01`.
- `x-sendifico-country`: configurado en BO, default `EC`.
- `Content-Type: application/json` cuando aplique.

La respuesta debe validar el echo de version/pais cuando el header este presente. Diferencias se registran como warning tecnico.

## Timeouts y reintentos

- Timeout inicial HTTP: configurable desde BO, default sugerido 10 segundos.
- `POST /quotation`: no reintento automatico agresivo en checkout; maximo 1 retry corto si el fallo es de red.
- `POST /shipment`: no repetir ciegamente si la respuesta es ambigua; usar `extId` y reconciliacion.
- `PATCH /shipment/purchase/{id}`: reintento manual desde BO; automatico solo si la fase 10 define condiciones seguras.
- Tracking y label: reintento manual.

## Idempotencia

| Operacion | Clave | Politica |
| --- | --- | --- |
| Quotation | `id_cart`, `id_shop`, hash de destino/paquete/COD | Reemplazable; no crea objeto remoto. |
| Shipment create | `extId=ps-{id_shop}-order-{id_order}` | Unico por cuenta Sendifico; si hay 409, reconciliar con `GET /shipment`. |
| Purchase | `shipmentId` + `rateId` | No crear nuevo shipment para reintentar purchase. |
| Tracking | `shipmentId` | API es idempotente; validar tracking efectivo. |
| Label | `shipmentId` + type/disposition | URL temporal regenerable. |

## Logging resumido

Guardar por evento:

- `id_shop`, `id_cart`, `id_order`, `shipmentId` si existe.
- Endpoint y metodo.
- HTTP status.
- Codigo `message` de Sendifico si existe.
- Estado local antes/despues.
- Payload resumido sin API key y minimizando PII.
- Timestamp y duracion.

No guardar:

- `x-api-key`.
- Headers de autorizacion.
- Payload completo con datos personales salvo que se defina una retencion y mascara explicita en fase 12.

## Clasificacion de errores

| Tipo | Ejemplos | Accion |
| --- | --- | --- |
| Configuracion | API key faltante, sender faltante, country/currency invalido. | Bloquear cotizacion/shipment y mostrar error BO. |
| Validacion local | Sin territorio, paquete invalido, contents sin categoria. | No llamar API; pedir correccion. |
| Validacion Sendifico | `pApiQuotationParcelBadRequest`, `pApiShipmentContentsRequired`. | Registrar y marcar como dato invalido. |
| Autenticacion | `invalidApiKey`. | Bloquear operaciones hasta corregir BO. |
| Negocio remoto | rate no disponible, COD limitado, wallet sin saldo. | Ocultar carrier o marcar purchase failed. |
| Red/transitorio | timeout, 5xx, carrier caido. | Retry segun operacion o accion BO. |
| Conflicto | `pApiShipmentExtIdAlreadyUsed`. | Reconciliar, no crear shipment nuevo automaticamente. |

## Estado `Courier no pagado`

- Se crea como estado de pedido informativo del modulo.
- Se usa cuando el pedido ya esta pagado/aceptado en PrestaShop, pero Sendifico purchase falla.
- No sustituye los estados comerciales normales salvo que la fase 10 confirme la transicion exacta.
- No debe enviar email al cliente por defecto.
- Debe ser visible para operacion BO y permitir retry purchase.
