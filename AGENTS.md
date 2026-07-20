# Repository Guidelines

## Project Structure & Module Organization
This repository currently contains planning and API reference material for a PrestaShop module integration with Sendifico. The committed files are under `.agents/`:

- `.agents/00-discovery.md`: integration notes and shipping flow decisions.
- `.agents/SOT_Sendifico_API.yml`: source-of-truth OpenAPI contract for Sendifico.

When implementation starts, keep PrestaShop module entrypoints at the repository root, place business logic in clearly named classes, and group admin or front-office assets under dedicated folders such as `views/`, `controllers/`, and `src/`.

## Build, Test, and Development Commands
There is no build pipeline in the repository yet. Use these commands for current work:

- `git status`: inspect local changes before editing.
- `git log --oneline`: review recent history and naming patterns.
- `rg --files .agents`: list reference files quickly.
- `sed -n '1,120p' .agents/SOT_Sendifico_API.yml`: inspect the API contract in chunks.

If you add tooling later, document the exact local commands here and keep them runnable from the repository root.

## Coding Style & Naming Conventions
Follow PrestaShop and modern PHP conventions:

- Use 4-space indentation and UTF-8 text files.
- Prefer `StudlyCase` for classes, `camelCase` for methods and variables, and `snake_case` only where PrestaShop configuration keys or database fields require it.
- Keep files focused: one class per file, explicit names such as `SendificoShipmentService.php`.
- Treat `.agents/SOT_Sendifico_API.yml` as authoritative for request fields, enum values, and endpoint behavior.

## Testing Guidelines
No automated tests are present yet. When adding code, introduce tests alongside the first executable features:

- Put unit tests in `tests/` and mirror source names, for example `tests/SendificoShipmentServiceTest.php`.
- Cover payload mapping, status transitions, and error handling for Sendifico API calls.
- Record any required fixtures or sample payloads near the tests, not in ad hoc notes.

## Commit & Pull Request Guidelines
Current history uses short commit subjects (`first commit`, `update`). Improve this with imperative, specific messages such as `Add shipment payload mapper`.

Pull requests should include:

- A short summary of the user-visible or integration impact.
- Notes on API endpoints or order states affected.
- Test evidence or a clear statement that tests are still pending.
- Screenshots only when admin or checkout UI changes are introduced.

## Security & Configuration Tips
Never commit API keys, wallet credentials, or production customer data. Keep Sendifico secrets in environment-specific configuration, and verify country/version headers against `.agents/SOT_Sendifico_API.yml` before releasing.

• # Resumen técnico del módulo

  ## Información confirmada

  ### Objetivo del módulo

  Desarrollar un módulo nuevo de PrestaShop 8.2.1 para integrar la tienda con Sendifico mediante su API, con foco en:

  - cotización de envíos en checkout,
  - obtención y uso de territorios de entrega de Sendifico,
  - selección de couriers disponibles,
  - creación y gestión operativa del shipment después del pedido,
  - compra del envío, generación de tracking y generación de label desde Back Office.

  ### Alcance funcional

  El módulo debe:

  - mostrar en checkout clásico las opciones de transporte disponibles según cotización Sendifico,
  - cobrar al cliente exactamente la tarifa devuelta por Sendifico,
  - trabajar sobre PrestaShop 8.2.1 con PHP > 8,
  - soportar multitienda desde el diseño,
  - usar una dirección remitente de Sendifico configurada por tienda,
  - resolver el territorio de entrega mediante selección explícita en checkout,
  - permitir reintento de purchase desde BO si falla la compra del envío,
  - permitir generateTrackingNumber manual desde BO,
  - permitir generateLabelUrl bajo demanda desde BO,
  - registrar trazabilidad técnica y operativa en tablas propias y logs.

  ### Alcance técnico

  Arquitectura prevista para el módulo:

  - módulo nuevo con identidad técnica sendifico,
  - namespace base vendorizado tipo Vendor\Sendifico,
  - integración principal con checkout clásico de PrestaShop,
  - configuración moderna en BO,
  - servicios desacoplados para:
      - cliente API Sendifico,
      - cotización,
      - sincronización/cache de territorios,
      - sincronización de remitentes,
      - resolución de paquete,
      - resolución de categoría contents,
      - persistencia y trazabilidad,
      - acciones operativas BO,

  - carriers persistentes en PrestaShop mapeados a carrierToken,
  - cada carrier solo se muestra si aparece en la cotización actual de Sendifico,
  - creación real de shipment solo después de que el pedido exista,
  - purchase automático al entrar el pedido en estado pagado/aceptado,
  - tracking y label operados manualmente desde BO.

  ### Flujo principal

  Flujo acordado para v1:

  1. El cliente completa o selecciona dirección en checkout.
  2. El checkout usa territorios Sendifico sincronizados/cacheados para resolver el destino.
  3. El módulo cotiza con Sendifico usando POST /quotation.
  4. Se muestran solo los carriers persistentes cuyo carrierToken esté presente y disponible en la respuesta.
  5. El cliente selecciona uno de esos transportistas y completa el pedido.
  6. Cuando el pedido pasa a pagado/aceptado:
      - se crea POST /shipment,
      - se usa la dirección remitente configurada,
      - se resuelve contents,
      - se calcula el paquete,
      - se intenta PATCH /shipment/purchase/{id} con la tarifa elegida.

  7. Si purchase falla, el pedido puede quedar marcado con el estado informativo Courier no pagado.
  8. Desde BO se podrá:
      - reintentar purchase,
      - ejecutar generateTrackingNumber,
      - ejecutar generateLabelUrl,
      - revisar el detalle y trazabilidad del envío.

  ### Actores y sistemas involucrados

  Actores:

  - cliente en checkout,
  - empleado BO con permisos del módulo,
  - operador logístico interno,
  - Sendifico API.

  Sistemas:

  - PrestaShop 8.2.1,
  - checkout clásico,
  - módulo sendifico,
  - API Sendifico,
  - carriers persistentes internos de PrestaShop.

  ### Hooks y eventos relevantes

  Confirmados a nivel funcional, pendientes de aterrizar al hook exacto:

  - evento del checkout al cambiar/confirmar dirección,
  - recálculo de transportistas disponibles,
  - paso del pedido a estado pagado/aceptado,
  - acciones en ficha de pedido BO,
  - acciones en listado propio del módulo BO.

  ### Componentes que deberán crearse o modificarse

  Previstos:

  - clase principal del módulo,
  - instalador/desinstalador,
  - configuración BO,
  - servicios DI,
  - cliente API Sendifico,
  - sincronizador de territorios,
  - sincronizador de direcciones remitentes,
  - estrategia de mapeo carrierToken -> carrier PrestaShop,
  - estrategia de mapeo producto/categoría -> contents,
  - cálculo de paquete,
  - persistencia local de shipments,
  - acciones BO sobre pedido,
  - listado BO propio del módulo,
  - estados de pedido necesarios,
  - tablas propias del módulo,
  - scripts de instalación y actualización.

  ### Datos que se almacenarán

  Se confirmó que habrá tabla propia completa. Como mínimo deberá persistirse:

  - relación pedido <-> shipment Sendifico,
  - shipmentId,
  - extId,
  - carrierToken y/o rateId seleccionado,
  - importes relevantes,
  - estado interno/local,
  - timestamps de cotización, creación, purchase, tracking y label,
  - errores operativos resumidos,
  - datos de trazabilidad necesarios para reintentos.

  ### Integraciones externas

  Confirmado:

  - la integración externa principal es Sendifico,
  - la fuente contractual es .agents/SOT_Sendifico_API.yml:1,
  - mercado objetivo inicial: Ecuador EC,
  - moneda operativa inicial: USD,
  - autenticación por API key y headers obligatorios de versión/país.

  ### Restricciones

  Confirmadas:

  - no comenzar con overrides salvo justificación sólida,
  - no almacenar credenciales en código,
  - multitienda considerada desde el diseño,
  - contents admite exactamente una categoría Sendifico por shipment,
  - se permite cotización aunque falten datos críticos,
  - no se permite crear shipment si faltan datos requeridos,
  - el error posterior al pago debe gestionarse internamente desde BO, sin impacto directo al cliente.

  ### Riesgos

  Identificados:

  - integración delicada con checkout clásico para recalcular y mostrar carriers filtrados dinámicamente,
  - necesidad de mantener consistente el mapeo entre carriers persistentes y carrierToken,
  - posible diferencia entre cotización en checkout y condiciones reales al crear/pagar shipment,
  - dependencia de datos de producto incompletos para dimensiones y contents,
  - necesidad de diseño cuidadoso para multitienda y cache de territorios,
  - riesgo operativo si falla purchase por saldo insuficiente u otra causa API.

  ### Decisiones técnicas acordadas

  Acordado hasta ahora:

  - usar la skill prestashop-module-development como base de convenciones,
  - sendifico será un módulo nuevo,
  - integración enfocada en checkout clásico,
  - usar carriers persistentes mapeados,
  - mostrar solo carriers presentes en la respuesta de cotización,
  - usar quotation en checkout y crear shipment solo después del pedido,
  - ejecutar purchase al entrar en estado pagado/aceptado,
  - permitir reintento manual de purchase en BO,
  - ejecutar tracking manualmente en BO,
  - generar label solo bajo demanda en BO,
  - sincronizar/cachear territorios localmente,
  - configurar remitente desde direcciones sender de Sendifico,
  - detectar COD por método de pago configurable,
  - mantener logs técnicos con payload resumido,
  - usar tabla propia completa,
  - política de desinstalación configurable.

  ### Aspectos fuera de alcance

  Fuera de alcance para v1, salvo que lo corrijas:

  - checkout no clásico o altamente custom,
  - algoritmo avanzado de packing 3D,
  - descarga/almacenamiento local automático de PDFs de etiquetas,
  - automatización por cron de tracking,

  ———

  ## Suposiciones

  Estas no están totalmente cerradas, pero son las que tomaría si el resumen se aprueba:

  - el namespace concreto final será algo como Vendor\Sendifico, pendiente de definir el vendor real,
  - el estado Courier no pagado será un estado de pedido local del módulo, no necesariamente el único estado operativo adicional,
  - Creative Elements no altera el checkout nativo de forma que obligue a una integración distinta,
  - el módulo podrá crear y administrar sus propios carriers persistentes sin conflicto con la configuración actual de la tienda.

  ———

  ## Decisiones pendientes

  Estos puntos todavía requieren confirmación o refinamiento antes del plan técnico final:

  1. La versión exacta de PHP objetivo.
     Ahora solo está confirmado > 8.

  2. La regla exacta para resolver contents cuando haya productos con categorías Sendifico distintas.
     Está confirmado el mapeo configurable, pero no la política final de “dominante”.

  3. La regla exacta de cálculo heurístico de dimensiones del paquete.
     Está orientada pero no cerrada formalmente.

  4. Los hooks y puntos técnicos exactos del checkout clásico que se usarán para:
      - capturar territorio,
      - cotizar,
      - recalcular transportistas,
      - persistir selección del carrier/rate.

  5. El detalle del modelo de datos local.
     Está confirmado que habrá tabla propia completa, pero no el esquema exacto.

  6. La UX exacta del selector de territorio.
     Está claro que será explícito en checkout, pero no si será:
      - un selector único de territorio,
      - o selectores separados provincia/cantón/ciudad con resolución interna.

  7. La política precisa de fallback para lat/lng.
     Confirmado “opcional con fallback”, pero no el fallback exacto.

    Indice general propuesto del plan técnico:

  1. Preparación y diagnóstico
  2. Diseño funcional y arquitectura del módulo
  3. Estructura base del módulo sendifico
  4. Configuración Back Office y parámetros por tienda
  5. Sincronización y caché de territorios y remitentes
  6. Modelo de datos y persistencia de trazabilidad
  7. Estrategia de carriers persistentes mapeados a Sendifico
  8. Integración con checkout clásico y cotización
  9. Resolución de paquete, contents y validaciones previas
  10. Creación de shipment y purchase al confirmar pago
  11. Operación Back Office: reintentos, tracking y label
  12. Seguridad, permisos, logs y manejo de errores
  13. Instalación, actualizaciones, desinstalación y compatibilidad multitienda
  14. Estrategia de pruebas, validación y despliegue

    Si ese índice te sirve, en el siguiente mensaje desarrollo la Fase 1 — Preparación y diagnóstico con el formato completo que definiste.