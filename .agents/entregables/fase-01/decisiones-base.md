# Fase 01 - Decisiones base del proyecto

## Baseline aprobado

- Modulo: `vx_sendifico`.
- Author: `Velox`.
- Plataforma objetivo: PrestaShop 8.2.1.
- PHP objetivo: 8.2.30.
- Mercado inicial: Ecuador (`EC`).
- Moneda operativa inicial: `USD`.
- Checkout objetivo: checkout clasico de PrestaShop.
- Fuente funcional: `.agents/00-discovery.md`.
- Fuente contractual API: `.agents/SOT_Sendifico_API.yml`.
- Version API Sendifico objetivo: `2026-01-01`, enviada en `x-sendifico-api-version`.

## Alcance v1 unico

El modulo v1 debe cotizar envios en checkout clasico mediante Sendifico, mostrar solo carriers persistentes que existan y esten disponibles en la cotizacion actual, cobrar al cliente la tarifa devuelta por Sendifico, y crear el shipment real solo cuando el pedido exista y entre a un estado pagado o aceptado.

Despues del pedido, el modulo debe crear `POST /shipment`, intentar `PATCH /shipment/purchase/{id}` con el rate elegido, y permitir desde Back Office reintentar purchase, generar tracking y generar label bajo demanda.

Queda fuera de v1: checkout no clasico o altamente personalizado, packing 3D avanzado, almacenamiento automatico local de PDFs de etiquetas, tracking automatizado por cron y overrides salvo justificacion tecnica posterior.

## Namespace, Composer y estructura

- Namespace PSR-4 definido: `Vx\Sendifico\`.
- Composer package sugerido: `velox/vx-sendifico`.
- Autoload esperado:

```json
{
  "autoload": {
    "psr-4": {
      "Vx\\Sendifico\\": "src/"
    }
  }
}
```

Razon: evita el prefijo reservado `PrestaShop\Module\`, deriva el namespace desde el prefijo tecnico `vx` del modulo y mantiene `Velox` como author/composer vendor.

Estructura objetivo para fase 03:

```text
vx_sendifico/
|-- vx_sendifico.php
|-- composer.json
|-- config/
|   |-- common.yml
|   |-- admin/services.yml
|   |-- front/services.yml
|   |-- routes.yml
|   `-- components/
|-- src/
|   |-- Api/
|   |-- Carrier/
|   |-- Checkout/
|   |-- Configuration/
|   |-- Controller/Admin/
|   |-- Install/
|   |-- Order/
|   |-- Package/
|   |-- Repository/
|   |-- Sendifico/
|   `-- Sync/
|-- views/
|-- translations/
|-- upgrade/
`-- tests/
```

## Reglas tecnicas congeladas

- `vx_sendifico.php` cargara `vendor/autoload.php` despues del guard `_PS_VERSION_`.
- La clase principal sera `Vx_Sendifico`.
- `install()` y `uninstall()` delegaran en `Vx\Sendifico\Install\Installer`.
- No se usara `getTabs()`; tabs y permisos se gestionaran desde `Installer`.
- `getContent()` solo redirigira a una ruta Symfony de configuracion.
- No habra SQL, `Configuration::get()` ni `Configuration::updateValue()` en la clase principal del modulo, salvo delegacion a instaladores/clases dedicadas donde aplique.
- Configuracion BO moderna con Symfony form; no `HelperForm`.
- Servicios separados por kernel:
  - `config/common.yml`: repositorios y servicios Doctrine/DBAL sin dependencia de `PrestaShopBundle`.
  - `config/admin/services.yml`: controllers, forms, grid, managers y comandos.
  - `config/front/services.yml`: solo imports necesarios para hooks/front office.
- Los servicios seran privados por defecto; `public: true` solo para servicios recuperados con `$this->get()`.
- Servicios modernos recibiran dependencias por constructor; evitar `Context::getContext()` y accesos estaticos cuando exista servicio inyectable.
- API keys no se almacenaran en codigo. Se guardaran en configuracion por tienda o mecanismo seguro equivalente.
- Logs locales guardaran payload resumido y codigos de error, no credenciales ni datos personales innecesarios.
- `uninstall()` borrara tablas propias, configuracion, mapeos, logs locales y estados/tabs creados por el modulo.

## Politicas funcionales iniciales

- Carriers PrestaShop seran persistentes y estaran mapeados a `carrierToken`.
- Un carrier solo sera ofrecido si la cotizacion Sendifico devuelve su `carrierToken` con `available: true`.
- La tarifa cobrada al cliente sera `priceTotal` de la cotizacion seleccionada.
- `quotationId` es dato de cotizacion; para purchase se debe persistir y usar el `rateId` devuelto por `POST /shipment` o una reconciliacion controlada por `carrierToken`/precio.
- `contents` debe resolverse a exactamente una categoria Sendifico por shipment.
- Si el carrito mezcla categorias Sendifico, domina la categoria que mas se repite. Si hay empate por cantidad, domina la categoria con mayor peso acumulado en el pedido.
- `parcel` usara kg y cm, con peso `> 0` y dimensiones `>= 1`.
- Pais, moneda, peso/dimensiones por defecto y demas defaults operativos seran configurables desde Back Office.
- `territoryBaseId` es opaco y se debe guardar/enviar sin parsearlo como identificador semantico.
- `extId` para Sendifico sera unico por pedido. Formato inicial sugerido: `ps-{id_shop}-order-{id_order}`.
- Lat/lng son opcionales para Sendifico, pero se intentaran capturar cuando el checkout/UX lo permita.
- `purchaseWith` sera `walletAvailable`.
- El unico estado adicional de pedido sera `Courier no pagado`.
- El checkout usara selectores encadenados separados: provincia -> canton -> ciudad. Cada selector filtrara las opciones disponibles del siguiente nivel.

## Decisiones trasladadas a fases posteriores

- Hooks exactos del checkout clasico y persistencia de seleccion: fase 08.
- Modelo de tablas definitivo: fase 06.
- Politica final de fallback lat/lng: fase 08/fase 09.
- Valores por defecto iniciales de paquete/pais/moneda para instalar en BO: fase 04.
