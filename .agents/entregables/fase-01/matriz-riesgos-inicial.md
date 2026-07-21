# Fase 01 - Matriz de riesgos inicial

| Area | Riesgo | Impacto | Probabilidad | Mitigacion inicial | Fase de cierre |
| --- | --- | --- | --- | --- | --- |
| Checkout clasico | El checkout default no expone todos los puntos necesarios para selector territorio y recalculo dinamico de carriers. | Alto | Media | Checkout default confirmado; validar hooks/eventos concretos antes de implementar y mantener integracion sin overrides como primera opcion. | Fase 08 |
| Carriers | Desfase entre carrier persistente PrestaShop y `carrierToken` devuelto por Sendifico. | Alto | Media | Crear tabla de mapeo por tienda, validar duplicados, desactivar carriers no mapeados o no disponibles en cada cotizacion. | Fase 07 |
| Precio | La tarifa cotizada en checkout cambia cuando se crea `POST /shipment`. | Alto | Media | Persistir cotizacion seleccionada, comparar `carrierToken` y precio contra rates del shipment, bloquear purchase o marcar revision si hay divergencia relevante. | Fase 10 |
| Purchase | Fallo de wallet o rate no disponible despues del pago del pedido. | Alto | Media | Usar `walletAvailable`; estado operativo local `purchase_failed`, estado informativo "Courier no pagado", reintento manual BO con trazabilidad. | Fase 10/Fase 11 |
| Territorios | Direccion PrestaShop no contiene un territorio Sendifico valido. | Alto | Alta | Selector explicito de territorio en checkout y cache local de `GET /territory`; no crear shipment sin `territoryBaseId`. | Fase 05/Fase 08 |
| Remitentes | Tienda sin `senderAddressId` configurado o direccion sender eliminada en Sendifico. | Alto | Media | Sincronizar `GET /address`, filtrar `addressType=sender`, validar configuracion por tienda antes de cotizar/crear. | Fase 05 |
| Multitienda | Configuracion, carriers o shipments cruzan datos entre tiendas. | Alto | Media | Toda tabla propia debe incluir `id_shop` cuando aplique; configuracion por tienda; mapeos carrier por tienda. | Fase 06/Fase 13 |
| Contents | Carrito contiene productos con categorias Sendifico distintas y la API acepta solo una. | Medio | Alta | Domina la categoria mas repetida; si hay empate domina la de mayor peso acumulado; registrar decision usada por shipment. | Fase 09 |
| Paquete | Dimensiones/peso incompletos en productos producen cotizaciones invalidas o caras. | Medio | Alta | Defaults configurables desde BO, minimos API (`weight > 0`, dimensiones `>= 1`), alertas de datos incompletos. | Fase 09 |
| COD | Deteccion de contraentrega por metodo de pago falla o excede limites de courier. | Medio | Media | Configurar metodos COD, enviar `goodsCollection`, mostrar solo rates `available: true`, guardar `unavailableReason`. | Fase 04/Fase 08 |
| Seguridad | Credenciales Sendifico filtradas en logs, configuracion o excepciones. | Alto | Baja | Enmascarar headers, no loguear `x-api-key`, guardar payload resumido y errores codificados. | Fase 12 |
| Idempotencia | Reintento ambiguo de `POST /shipment` choca con `extId` unico. | Alto | Media | Persistir intento local antes/despues de llamada, manejar `409 pApiShipmentExtIdAlreadyUsed`, consultar `GET /shipment` si es necesario. | Fase 10 |
| Label | URL de label expira y no debe persistirse como recurso permanente. | Bajo | Alta | Generar bajo demanda, guardar timestamp y ultima URL solo como dato temporal/operativo. | Fase 11 |
| Tracking | Tracking generado demasiado temprano puede caducar o ser anulado por carrier. | Medio | Media | Accion manual BO cerca del despacho; no automatizar en v1. | Fase 11 |
| Instalacion | El modulo falla en consola por uso de container no disponible en install. | Alto | Media | Instaladores sin `SymfonyContainer::getInstance()`; SQL schema permitido solo en instalador dedicado. | Fase 03/Fase 13 |
