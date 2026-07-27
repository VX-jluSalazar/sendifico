# Deuda tecnica - Catalogo de carriers Sendifico

Fecha de registro: 2026-07-27
Fase relacionada: 07 - Estrategia de carriers persistentes mapeados a Sendifico

## Situacion actual

La implementacion actual de carriers Sendifico usa un catalogo local fijo definido en:

- `src/Carrier/CarrierCatalogProvider.php`

Esto implica que:

- los `carrierToken` conocidos se mantienen en codigo;
- la accion BO `Provision carriers` y el comando `vx_sendifico:provision-carriers` solo trabajan sobre ese catalogo local;
- si Sendifico agrega un courier nuevo, cambia un `carrierToken`, cambia el nombre comercial de un courier o retira uno existente, el modulo no lo detecta automaticamente.

## Impacto operativo

- Para registrar un courier nuevo actualmente hay que modificar codigo y desplegar una nueva version del modulo.
- El catalogo local puede quedar desalineado respecto al comportamiento real observado en `POST /quotation`.
- No existe hoy una pantalla BO para altas, bajas o edicion del catalogo de carriers Sendifico.
- No existe reconciliacion automatica entre carriers provisionados localmente y tokens observados en cotizaciones reales.

## Decision vigente

Por ahora se mantiene una estrategia conservadora:

- el catalogo local es la fuente de verdad del modulo para provision de carriers persistentes;
- los carriers ya provisionados no deben eliminarse automaticamente para no afectar pedidos historicos;
- la visibilidad real en checkout seguira dependiendo de la cotizacion actual de Sendifico en fases posteriores.

## Lineas futuras recomendadas

### Opcion A - Discovery asistido desde `/quotation`

Implementar un registro de `carrierToken` observados en cotizaciones reales para:

- descubrir tokens nuevos no presentes en el catalogo local;
- mostrar en BO tokens `discovered_unmapped`;
- permitir aprovisionar manualmente esos tokens desde Back Office.

Nota:

- `POST /quotation` no debe tratarse como catalogo autoritativo de couriers, solo como fuente heuristica de descubrimiento.

### Opcion B - Catalogo configurable desde BO

Crear una interfaz de administracion para:

- alta manual de `carrierToken`;
- edicion de `display_name`, `delay` y estado activo;
- baja logica o desactivacion del token en el catalogo local.

## Recomendacion

La mejor evolucion para este modulo es combinar ambas:

1. mantener un catalogo local persistente y editable;
2. registrar tokens descubiertos desde `/quotation`;
3. permitir promover esos tokens descubiertos al catalogo administrable;
4. no borrar automaticamente carriers historicos ya provisionados.

## Estado

- Tipo: deuda tecnica documentada
- Prioridad sugerida: media
- Momento sugerido: antes o durante una futura ampliacion de fase 07 o al iniciar una fase dedicada a operacion BO avanzada
