# Fase 08.01 - Integracion de territorio en la seccion de direcciones del checkout

## Objetivo
Reemplazar el selector unico de territorio de Sendifico en la seccion de shipping por una integracion nativa dentro del formulario de direcciones del checkout clasico.

## Entregable
- Resumen de implementacion: `.agents/entregables/fase-08.01/resumen-implementacion.md`

## Contexto
La implementacion actual de fase 08 resuelve el territorio de entrega mediante un selector propio renderizado en el paso de transportistas. Funciona para cotizar, pero no modela correctamente la captura de direccion ni acompana el flujo natural del checkout.

La nueva meta es capturar el territorio durante la edicion o seleccion de la direccion de entrega, siguiendo este mapeo:

- `territory1Name` -> `estado`
- `territory2Name` -> `canton`
- `territory3Name` -> `ciudad`

## Viabilidad
La implementacion es viable sin overrides pesados del core, usando hooks y JS del front office.

Viabilidad por componente:

- `estado`: viable usando `id_state` nativo de PrestaShop.
- `ciudad`: viable usando `city` nativo de PrestaShop.
- `canton`: no existe como campo nativo; requiere campo extra del modulo.
- `territory_base_id`: no existe como campo nativo; debe resolverse y persistirse en almacenamiento propio del modulo.

## Base tecnica del core
Puntos relevantes del checkout clasico y formulario de direcciones:

- `CustomerAddressFormatter` permite agregar campos extra con `additionalCustomerAddressFields`.
- `CustomerAddressForm` ejecuta `actionSubmitCustomerAddressForm` antes de persistir la direccion.
- `CheckoutAddressesStep` reutiliza ese mismo formulario dentro del checkout.

Esto permite intervenir el formulario sin reemplazar el flujo completo del paso de direcciones.

## Alcance propuesto
- Integrar la captura del territorio en el formulario de direcciones del checkout.
- Resolver `territory_base_id` a partir de `estado + canton + ciudad`.
- Persistir la metadata territorial vinculada a `id_address`.
- Reutilizar esa metadata al seleccionar direcciones existentes para cotizar en shipping.
- Invalidar y recalcular la cotizacion si cambia la direccion o su metadata territorial.

## Arquitectura recomendada

### 1. Captura en el formulario de direccion
- Mantener `id_state` como selector nativo.
- Mantener `city` como campo nativo, idealmente convertido por JS a selector dependiente cuando aplique Sendifico.
- Agregar un campo extra del modulo para `canton`.
- Agregar un campo hidden del modulo para `territory_base_id`.

### 2. Resolucion territorial
- Cargar desde cache local los territorios del pais configurado.
- Filtrar opciones por jerarquia:
  - `estado` filtra cantones disponibles.
  - `canton` filtra ciudades disponibles.
  - `ciudad` permite resolver un unico `territory_base_id`.
- Si no existe una coincidencia unica, la direccion no debe guardarse como valida para Sendifico.

### 3. Persistencia
Crear una tabla propia del modulo para metadata territorial por direccion, por ejemplo:

- `id_vx_sendifico_address_meta`
- `id_address`
- `id_shop`
- `country_code`
- `territory_base_id`
- `territory1_name`
- `territory2_name`
- `territory3_name`
- `created_at`
- `updated_at`

Esto evita alterar `ps_address` y mantiene compatibilidad con el modelo nativo de PrestaShop.

### 4. Integracion con quotation
- Al cotizar, usar `territory_base_id` desde la metadata de la direccion seleccionada.
- Si la direccion no tiene metadata territorial valida, no mostrar carriers Sendifico.
- Si el cliente edita o cambia direccion, invalidar la cotizacion anterior.

## UX recomendada
- El cliente completa la direccion dentro del paso de direcciones, no en shipping.
- `estado`, `canton` y `ciudad` deben verse como parte de una misma jerarquia.
- Si el pais o la tienda no usan Sendifico para ese flujo, el comportamiento debe degradar sin romper el formulario.
- Si hay ambiguedad o faltan datos, el error debe aparecer en el formulario de direccion, no mas tarde en shipping.

## Riesgos
- `canton` requiere persistencia propia del modulo porque PrestaShop no lo modela nativamente.
- El hook de campos extra no garantiza por si solo la posicion visual exacta del campo en medio de los nativos.
- Si se quiere que `canton` aparezca exactamente entre `estado` y `ciudad`, probablemente se necesitara JS de reordenamiento o personalizacion del template.
- El mismo formulario de direccion tambien se usa fuera del checkout; hay que decidir si la integracion aplica globalmente o solo en contexto checkout.

## Decision recomendada
Proceder con esta refactorizacion.

Es una mejor alineacion funcional que el selector unico en shipping porque:

- mueve la captura del territorio al momento correcto del checkout,
- deja la direccion mas coherente con la realidad operativa de Sendifico,
- reduce friccion posterior en cotizacion y creacion de shipment.

## Actividades propuestas
1. Disenar el almacenamiento de metadata territorial por `id_address`.
2. Implementar repositorio y esquema de persistencia.
3. Agregar campos extra al formulario de direccion.
4. Implementar JS dependiente para `estado -> canton -> ciudad`.
5. Resolver y validar `territory_base_id` al guardar direccion.
6. Integrar la lectura de metadata territorial con la cotizacion de fase 08.
7. Retirar el selector unico de shipping una vez que la captura en direcciones quede estable.

## Criterios de salida
- El checkout captura territorio desde la seccion de direcciones.
- La direccion guardada queda asociada a un `territory_base_id` valido.
- Shipping cotiza usando la metadata territorial de la direccion seleccionada.
- El selector unico de territorio en shipping deja de ser necesario.

## Dependencias
- Fase 05 para cache de territorios.
- Fase 06 para trazabilidad.
- Fase 08 para servicio de quotation y filtrado de carriers.

## Nota de implementacion
Esta subfase implica un ajuste de modelo de datos del modulo. No es solo un cambio visual de checkout.
