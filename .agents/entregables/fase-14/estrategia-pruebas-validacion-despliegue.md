# Fase 14 - Estrategia de pruebas, validacion y despliegue

## Cobertura minima automatizada

- Unitarias:
  - `PackageResolver`: pesos/dimensiones default y fallbacks.
  - `ContentsResolver` y `ContentsMappingParser`: mapeos producto/categoria/default.
  - `ShipmentPayloadValidator`: campos requeridos antes de shipment.
  - `ShipmentRateMatcher`: matching por carrier token y precio.
  - `TraceSanitizer`: mascara secretos/PII y trunca payloads.
- Integracion con dobles:
  - quotation: payload checkout, respuesta con carriers disponibles/no disponibles.
  - shipment: creacion idempotente por `extId`.
  - purchase: pagado, no pagado y fallo retryable.
  - tracking y label: actualizacion de campos BO.

## Fixtures iniciales

Los fixtures de `tests/Fixtures/sendifico/` documentan shapes minimos esperados para quotation y shipment. No contienen credenciales ni PII real.

## Smoke tests manuales

1. Instalar modulo con DDEV: `ddev exec sh -lc 'cd /var/www/html && php bin/console pr:mo install vx_sendifico'`.
2. Configurar API key, pais `EC`, moneda `USD`, remitente y auto purchase por tienda.
3. Ejecutar sincronizacion de territorios y remitentes desde BO.
4. Ejecutar provisionamiento de carriers y comprobar mapeos por tienda.
5. Checkout clasico:
   - crear/editar direccion con territorio Sendifico;
   - confirmar que solo aparecen carriers Sendifico cotizados;
   - seleccionar carrier y completar pago.
6. Pedido:
   - validar shipment creado tras existir pedido;
   - validar purchase en estado pagado/aceptado;
   - forzar retry BO para caso fallido;
   - generar tracking y label desde panel BO.
7. Multitienda:
   - repetir configuracion con dos tiendas;
   - verificar que configuracion, remitentes y mapeos no se mezclan;
   - duplicar tienda y revisar que la nueva tienda recibe configuracion/mapeos base, no trazas historicas.

## Validacion tecnica obligatoria

- `vendor/websenso/prestashop-module-devtools/bin/lotr` desde `modules/vx_sendifico`.
- Instalacion en runtime real con DDEV desde `~/Sites/prestashop`: `ddev exec sh -lc 'cd /var/www/html && php bin/console pr:mo install vx_sendifico'`.
- Si el modulo ya esta instalado, desinstalar/reinstalar en entorno de pruebas antes de declarar release.

## Checklist de release

- No hay API keys, URLs temporales ni datos de cliente en git.
- `composer validate --strict` pasa.
- Lint PHP pasa en `vx_sendifico.php`, `src/` y `upgrade/`.
- `lotr` pasa completo.
- Instalacion y reinstalacion pasan con DDEV.
- Las rutas BO cargan con empleado con permiso `read` y bloquean acciones `update` sin permiso/token.
- Primeros pedidos reales se monitorean por `local_state`, `last_error_code` y eventos recientes.

## Rollback funcional

- Desactivar auto purchase en configuracion.
- Deshabilitar carriers Sendifico locales si el checkout debe dejar de ofertarlos.
- Mantener trazas para soporte hasta confirmar cierre operativo.
- Desinstalar modulo solo cuando se acepte la eliminacion destructiva de tablas propias.
