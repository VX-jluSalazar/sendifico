# Fase 04 - Resumen de implementacion

## Alcance ejecutado

- Configuracion Back Office moderna basada en Symfony form ya operativa para `vx_sendifico`.
- Persistencia multitienda por contexto usando `AbstractMultistoreConfiguration`.
- Validaciones de formato en el formulario y validaciones de negocio antes de persistir.
- UX minima de ayuda para contexto multitienda y configuraciones incompletas.

## Componentes implementados

```text
src/Configuration/
|-- ConfigurationKeys.php
|-- SendificoDataConfiguration.php
`-- SendificoFormDataProvider.php

src/Form/Admin/Type/SendificoConfigurationType.php
src/Controller/Admin/ConfigurationController.php
views/templates/admin/configuration.html.twig
config/components/form/sendifico_configuration_form.yml
```

## Parametros por tienda/grupo/global

Las claves gestionadas en fase 04 son:

- `VX_SENDIFICO_API_KEY`
- `VX_SENDIFICO_API_VERSION`
- `VX_SENDIFICO_COUNTRY`
- `VX_SENDIFICO_CURRENCY`
- `VX_SENDIFICO_COD_PAYMENT_METHODS`
- `VX_SENDIFICO_SENDER_REFERENCE`
- `VX_SENDIFICO_PURCHASE_WITH`
- `VX_SENDIFICO_AUTO_PURCHASE_ENABLED`
- `VX_SENDIFICO_ALLOW_INCOMPLETE_CHECKOUT_ADDRESS`
- `VX_SENDIFICO_ENABLE_DEBUG_LOGS`
- `VX_SENDIFICO_LOG_RETENTION_DAYS`
- `VX_SENDIFICO_DEFAULT_WEIGHT`
- `VX_SENDIFICO_DEFAULT_LENGTH`
- `VX_SENDIFICO_DEFAULT_WIDTH`
- `VX_SENDIFICO_DEFAULT_HEIGHT`

## Reglas de validacion cerradas

- `country` debe mantenerse en `EC` para el alcance v1.
- `currency` debe mantenerse en `USD`.
- `api_version` requiere formato `YYYY-MM-DD`.
- `purchase_with` solo admite `walletAvailable` o `cash`.
- Peso y dimensiones por defecto deben ser decimales positivos.
- No se permite habilitar compra automatica sin `api_key` en el contexto actual.
- La falta de `sender_reference` queda como advertencia operativa para permitir guardar primero la API key y luego sincronizar remitentes.

## Permisos y UX

- El controlador requiere permiso `read` para visualizar y valida permiso `update` al guardar.
- La plantilla informa el contexto multitienda actual.
- Se muestran advertencias accionables cuando falta API key o remitente.
- En instalaciones nuevas `VX_SENDIFICO_AUTO_PURCHASE_ENABLED` inicia desactivado para evitar bloqueo circular antes de sincronizar remitentes.
- La configuracion queda separada por secciones: credenciales, remitente, checkout, operaciones y logs.

## Validacion ejecutada

- `composer validate --strict`: OK.
- `composer dump-autoload`: OK.
- `php -l vx_sendifico.php`: OK.
- `find src -name '*.php' -print0 | xargs -0 -n1 php -l`: OK.

## Validacion pendiente

- `vendor/websenso/prestashop-module-devtools/bin/lotr` sigue no disponible porque `prestashop-module-devtools` no esta instalado en `vendor/`.
- Prueba de instalacion real via DDEV pendiente en esta fase:
  `ddev exec sh -lc 'cd /var/www/html && php bin/console pr:mo install vx_sendifico'`
