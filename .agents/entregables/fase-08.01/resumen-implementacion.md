# Fase 08.01 - Resumen de implementacion

## Alcance implementado
- Se retiró la dependencia funcional del selector único de territorio en shipping.
- La captura territorial de Sendifico ahora se integra en el formulario nativo de direcciones del checkout y de la cuenta cliente.
- Se agregó el campo extra `canton` y un hidden `territory_base_id` mediante `additionalCustomerAddressFields`.
- Se implementó validación del formulario de direcciones con `actionValidateCustomerAddressForm` para asegurar que la combinación `estado + canton + ciudad` resuelva un territorio válido de Sendifico.
- Se agregó persistencia propia por `id_address` en `ps_vx_sendifico_address_meta`.
- La cotización de checkout ahora usa la metadata territorial de la dirección de entrega seleccionada en lugar de un selector temporal por carrito.
- Se mantiene el filtrado de carriers y la tarifa exacta usando la respuesta de `/quotation`.

## Cambios principales
- `src/Checkout/AddressTerritoryFormService.php`
- `src/Repository/AddressMetadataRepository.php`
- `src/Repository/CountryRepository.php`
- `src/Repository/StateRepository.php`
- `src/Checkout/CheckoutQuotationService.php`
- `src/Checkout/CheckoutHookHandler.php`
- `src/Install/CacheSchemaInstaller.php`
- `vx_sendifico.php`
- `views/js/checkout-territory.js`
- `upgrade/upgrade-0.6.0.php`

## Hooks usados
- `displayHeader`
- `additionalCustomerAddressFields`
- `actionValidateCustomerAddressForm`
- `actionObjectAddressAddAfter`
- `actionObjectAddressUpdateAfter`
- `actionObjectAddressDeleteAfter`
- `actionFilterDeliveryOptionList`
- `actionCarrierProcess`
- `actionValidateStepComplete`

## Modelo de datos agregado
Tabla nueva:

- `ps_vx_sendifico_address_meta`

Columnas clave:

- `id_address`
- `id_shop`
- `country_code`
- `territory_base_id`
- `territory1_name`
- `territory2_name`
- `territory3_name`

## Comportamiento operativo
- Si el país de la dirección coincide con el país configurado para Sendifico, el form entra en modo jerárquico territorial.
- `id_state` sigue siendo nativo de PrestaShop.
- `canton` se resuelve como campo extra del módulo.
- `city` se mejora por JS para comportarse como selector dependiente.
- Al guardar la dirección, el módulo valida y persiste el `territory_base_id` correspondiente.
- Si la dirección no tiene metadata territorial válida, el checkout no expone carriers Sendifico.

## Limitaciones conocidas
- La mejora del campo `city` es progresiva por JavaScript; sin JS, el flujo sigue dependiendo de la validación server-side.
- El orden visual exacto del campo extra depende del ajuste DOM hecho por JS sobre el formulario renderizado por el theme clásico.
- La cotización en shipping sigue usando `goodsCollection = 0` hasta que el flujo COD quede reconciliado en fases posteriores.

## Nota tecnica
- En esta instalacion, el contenedor `front` de PrestaShop no expone el servicio Symfony `translator` para autowiring ni para referencia directa `@translator`.
- Por compatibilidad, `Vx\Sendifico\Checkout\AddressTerritoryFormService` resuelve el traductor desde `prestashop.adapter.legacy.context` con `getContext().getTranslator()` mediante expression language en la definicion del servicio.
- Esta decision mantiene inyeccion de dependencias y evita llamadas estaticas dentro de la clase, pero acopla el servicio al `LegacyContext` del front office.
- Si en una instalacion futura el servicio `translator` queda disponible en el kernel `front`, este wiring puede simplificarse a una inyeccion directa del traductor.

## Validación ejecutada
- `php -l vx_sendifico.php`
- `find src config controllers upgrade -name '*.php' -print0 | xargs -0 -n1 php -l`
- `composer dump-autoload`
- `ddev exec sh -lc 'cd /var/www/html && php bin/console pr:mo upgrade vx_sendifico'`
- ciclo limpio `uninstall/install` en DDEV
