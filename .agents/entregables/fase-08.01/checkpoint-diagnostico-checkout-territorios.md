# Checkpoint - Diagnostico de territorios en checkout

Fecha: 2026-07-29

## Contexto
Se implemento la subfase 08.01 para mover la captura territorial de Sendifico al formulario de direcciones del checkout clasico.

El objetivo funcional era:

- `territory1Name` -> `state`
- `territory2Name` -> `canton`
- `territory3Name` -> `city`

## Estado actual
- El campo custom `canton` si se renderiza en el formulario de direccion.
- El hidden `sendifico_territory_base_id` tambien se renderiza.
- El asset `modules/vx_sendifico/views/js/checkout-territory.js` si carga en checkout.
- `window.vxSendificoAddressForm` si existe en runtime.
- `window.vxSendificoAddressForm.enabled` retorna `true`.
- El formulario `.js-address-form form` si existe en el checkout.

## Sintoma observado
- El selector `canton` aparece vacio, salvo por el placeholder.
- `city` sigue siendo un input texto nativo y no se transforma a selector dependiente.
- El enhancer JS no llega a marcar el formulario con `data-vx-sendifico-enhanced="1"` porque aborta antes de inicializar la jerarquia.

## Hallazgo principal
El formulario real del checkout para Ecuador no renderiza el campo nativo `id_state`.

Pruebas realizadas en navegador:

- `document.querySelector('.js-address-form form [name="id_state"]')` -> `null`
- `document.querySelector('.js-address-form').innerHTML.includes('id_state')` -> `false`

El DOM real del formulario muestra:

- `Country` con `name="id_country"`
- `City` con `name="city"`
- `Canton` con `name="sendifico_canton"`
- `Territory` hidden con `name="sendifico_territory_base_id"`
- no existe `id_state`

## Impacto tecnico
La implementacion actual depende de `id_state` para resolver el primer nivel territorial.

El JS actual aborta en esta condicion:

```js
if (!countrySelect || !stateSelect || !cantonSelect || !territoryInput || !cityInput) {
  return;
}
```

Como `stateSelect` no existe, el flujo nunca entra en modo Sendifico:

- no se cargan cantones,
- no se crea la jerarquia `provincia -> canton -> ciudad`,
- `city` no se convierte en selector,
- no se puede resolver `territory_base_id`.

## Verificacion adicional
Se ejecuto una consulta diagnostica en DDEV para revisar estados de Ecuador y devolvio arreglo vacio para `id_country = 81`.

Conclusion operativa:

- la tienda no tiene provincias de Ecuador cargadas como estados de PrestaShop,
- por tanto el diseño actual basado en `territory1Name -> id_state` no es viable en esta instalacion sin poblar `ps_state`.

## Decision de arquitectura pendiente
Hay dos caminos viables para continuar:

### Opcion A
Mantener el diseño actual y poblar provincias de Ecuador en `ps_state`.

Ventajas:

- mantiene alineacion con el modelo nativo de PrestaShop,
- reutiliza `id_state`,
- conserva mejor compatibilidad con el ecosistema del core.

Desventajas:

- depende de carga y mantenimiento de estados,
- agrega una precondicion de datos maestros en la tienda.

### Opcion B
Eliminar la dependencia de `id_state` y mover tambien `territory1Name` a un campo custom del modulo.

Nuevo mapeo sugerido:

- `territory1Name` -> `provincia` custom
- `territory2Name` -> `canton` custom
- `territory3Name` -> `city` nativo mejorado o custom/select

Ventajas:

- no depende de `ps_state`,
- es compatible con la instalacion actual,
- simplifica la resolucion jerarquica desde la cache local de Sendifico.

Desventajas:

- se aleja del modelo nativo de direcciones de PrestaShop,
- requiere rehacer parte de la validacion y del JS.

## Recomendacion actual
Para esta instalacion concreta, la opcion mas pragmatica es la Opcion B.

Razon:

- el bloqueo actual no es del JS sino del modelo de datos base de la tienda,
- sin `id_state`, el flujo actual no puede completarse de forma confiable,
- mover `territory1Name` a un campo custom evita depender de que Ecuador este parametrizado como estados de PrestaShop.

## Actualizacion posterior
Se cargo Ecuador en `ps_state` usando provincias, por lo que PrestaShop ya puede renderizar el campo nativo `id_state` y el flujo actual vuelve a ser viable bajo la Opcion A.

Con esta decision operativa, el mapeo vigente queda asi:

- `ps_state.name` -> `territory1Name`
- `sendifico_canton` -> `territory2Name`
- `city` -> `territory3Name`
- `sendifico_territory_base_id` -> `territoryBaseId`

La relacion entre provincia PrestaShop y territorio Sendifico se hace por nombre normalizado. El modulo normaliza mayusculas, tildes, espacios y separadores antes de comparar, pero no corrige abreviaturas, typos o diferencias semanticas entre ambos catalogos.

Riesgo pendiente:

- si Sendifico cambia `territory1Name`,
- si se edita `ps_state.name`,
- o si ambos catalogos usan nombres distintos para la misma provincia,

la jerarquia puede dejar de resolver cantones, ciudades o `territory_base_id`.

Mitigacion recomendada para una fase posterior:

- agregar una tabla o configuracion explicita de equivalencias `id_state -> territory1_name` o `id_state -> territory1_key`,
- usar el mapeo explicito como fuente primaria,
- dejar la comparacion por nombre solo como fallback o diagnostico.

## Cambios ya realizados durante el diagnostico
- Se corrigio el retorno del hook `additionalCustomerAddressFields` para no envolver doblemente los `FormField`.
- Se ajusto el wiring del traductor en `front` usando `prestashop.adapter.legacy.context`.
- Se endurecio el JS para reintentar inicializacion cuando PrestaShop refresca el formulario de direcciones.
- Se ajustaron nombres de campos para tolerar variantes prefijadas y no prefijadas.

Estos cambios no resuelven el problema principal porque el bloqueo real es la ausencia de `id_state` en el formulario.

## Siguiente paso sugerido
Si se retoma esta linea de trabajo, la siguiente implementacion deberia ser:

1. agregar campo custom `provincia`,
2. rehacer el arbol jerarquico del front como `provincia -> canton -> ciudad`,
3. dejar de depender de `StateRepository` para la validacion principal,
4. resolver `territory_base_id` solo con metadata local de Sendifico,
5. actualizar fase 08.01 y su resumen con el nuevo mapeo funcional.

## Actualizacion de estabilizacion
Con Ecuador cargado en `ps_state`, se mantuvo el mapeo basado en `id_state`.

Se corrigio un problema posterior al editar direcciones existentes:

- el backend ahora renderiza el canton persistido como opcion inicial del select,
- el JS usa el `territory_base_id` guardado para rehidratar canton y ciudad,
- `canton` se marca requerido solo en modo Sendifico,
- la validacion server-side sigue exigiendo `estado + canton + ciudad + territory_base_id` validos para el pais configurado.
