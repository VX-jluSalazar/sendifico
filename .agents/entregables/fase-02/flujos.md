# Fase 02 - Flujos nominales y alternos

## Cotizacion en checkout

1. Cliente selecciona provincia.
2. Selector de canton se filtra por provincia.
3. Selector de ciudad se filtra por provincia y canton.
4. Ciudad seleccionada define `territoryBaseId`.
5. PackageResolver calcula paquete con datos de productos y defaults BO.
6. ContentsResolver resuelve categoria dominante.
7. QuotationService llama `POST /quotation`.
8. Se guardan rates resumidos y errores, si existen.
9. CarrierAvailabilityService muestra solo carriers con `available=true`.

Alternos:

- Sin territorio: no se cotiza y no se muestran carriers Sendifico.
- Sin sender configurado: no se cotiza y se registra error de configuracion.
- API caida: se ocultan carriers Sendifico y se guarda evento tecnico.
- Rate no disponible: carrier no se muestra; `unavailableReason` se conserva para BO/debug.

## Creacion y purchase despues del pago

1. Hook/evento de cambio de estado detecta pedido pagado/aceptado.
2. Se valida que el pedido use carrier mapeado a Sendifico.
3. Se resuelve sender, recipient, parcel, contents, COD, insured y extId.
4. ShipmentService ejecuta `POST /shipment`.
5. Se guarda `shipmentId`, `extId`, request/response resumidos y rates.
6. Se busca un `rateId` disponible del mismo `carrierToken`.
7. PurchaseService ejecuta `PATCH /shipment/purchase/{id}` con `walletAvailable`.
8. Si `isPaid=true`, estado local queda `purchased`.

Alternos:

- Faltan datos requeridos: no se crea shipment; estado local `blocked_missing_data`.
- `extId` duplicado: se marca `reconciliation_required` y se intenta resolver con `GET /shipment`.
- Carrier elegido no aparece en rates del shipment: estado local `rate_mismatch`, requiere BO.
- Purchase falla: estado local `purchase_failed` y pedido en `Courier no pagado`.

## Operacion BO

### Retry purchase

1. Empleado abre pedido o listado Sendifico.
2. Accion valida permisos y CSRF.
3. PurchaseService reusa shipment remoto y rate disponible.
4. Resultado actualiza estado local y muestra flash.

### Generate tracking

1. Empleado ejecuta accion cerca del despacho.
2. TrackingService llama `PATCH /shipment/generateTrackingNumber/{id}`.
3. Solo se considera exito si la respuesta trae `trackingNumber`.
4. Se actualiza shipment local y, si aplica, tracking del pedido.

### Generate label

1. Empleado solicita label.
2. LabelService llama `POST /shipment/generateLabelUrl/{id}` con `type=carrierDefault`.
3. Se guarda URL temporal y timestamp.
4. BO redirige o presenta enlace segun `disposition`.

Alternos:

- Label no listo: mostrar instruccion para generar tracking primero.
- URL expirada: generar una nueva bajo demanda.
- API carrier caida al generar tracking: registrar error y permitir reintento.

## Estados locales

| Estado local | Significado | Relacion con pedido |
| --- | --- | --- |
| `quoted` | Hay cotizacion de checkout para carrito. | Antes de pedido. |
| `shipment_pending` | Pedido pagado detectado, falta crear shipment. | Pedido pagado/aceptado. |
| `shipment_created` | Draft remoto creado, no pagado. | Pedido mantiene estado comercial. |
| `purchased` | Shipment pagado en Sendifico. | Pedido mantiene estado comercial. |
| `purchase_failed` | Error al comprar envio. | Pedido puede pasar a `Courier no pagado`. |
| `tracking_generated` | Tracking remoto generado y guardado. | Puede actualizar tracking del pedido. |
| `label_generated` | URL temporal generada. | Sin cambio obligatorio de estado de pedido. |
| `blocked_missing_data` | Faltan datos requeridos para crear shipment. | Requiere accion BO. |
| `reconciliation_required` | Estado remoto/local ambiguo. | Requiere accion BO. |

El unico estado adicional de PrestaShop sera `Courier no pagado`; los demas estados son internos del modulo.
