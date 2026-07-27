# Repository Guidelines

## Project Structure & Module Organization
`vx_sendifico` is a PrestaShop 8.2.1 module for integrating checkout and order operations with Sendifico.

- `vx_sendifico.php`: module entrypoint and bootstrap.
- `composer.json`: PSR-4 autoload for `Vx\Sendifico\`.
- `src/`: module code grouped by bounded area such as `Configuration/`, `Install/`, `Controller/`, `Checkout/`, `Carrier/`, `Order/`, `Package/`, `Repository/`, `Sendifico/`, and `Sync/`.
- `config/`: service wiring and routes split into `common.yml`, `admin/services.yml`, `front/services.yml`, and `components/`.
- `views/`: Twig templates and UI assets.
- `tests/`: unit or integration tests for mapping, persistence, and operational flows.
- `upgrade/`: upgrade scripts for future versions.
- `.agents/`: delivery plan, phase-by-phase execution notes, API contract, and supporting documentation.

Treat `.agents/SOT_Sendifico_API.yml` as the source of truth for Sendifico fields, enums, headers, and endpoint behavior.

## Current Status
The module is no longer a planning-only repository. There is already a baseline implementation for phase 03:

- installable module skeleton,
- Composer autoload and namespace,
- installer and configuration bootstrap,
- admin route and configuration controller wiring,
- initial configuration keys.

Before changing architecture, validate whether the intended work belongs to a later phase under `.agents/fases/`.

## Phases
Execution is organized in `.agents/fases/` and should be followed in order unless a bugfix explicitly targets existing code.

1. `fase-01-preparacion-y-diagnostico.md`
2. `fase-02-diseno-funcional-y-arquitectura.md`
3. `fase-03-estructura-base-del-modulo.md`
4. `fase-04-configuracion-back-office-y-parametros-por-tienda.md`
5. `fase-05-sincronizacion-y-cache-de-territorios-y-remitentes.md`
6. `fase-06-modelo-de-datos-y-persistencia-de-trazabilidad.md`
7. `fase-07-estrategia-de-carriers-persistentes-mapeados-a-sendifico.md`
8. `fase-08-integracion-con-checkout-clasico-y-cotizacion.md`
9. `fase-09-resolucion-de-paquete-contents-y-validaciones-previas.md`
10. `fase-10-creacion-de-shipment-y-purchase-al-confirmar-pago.md`
11. `fase-11-operacion-back-office-reintentos-tracking-y-label.md`
12. `fase-12-seguridad-permisos-logs-y-manejo-de-errores.md`
13. `fase-13-instalacion-actualizaciones-desinstalacion-y-compatibilidad-multitienda.md`
14. `fase-14-estrategia-de-pruebas-validacion-y-despliegue.md`

## Deliverables
Use `.agents/entregables/` as the concrete output register for each phase.

- `fase-01/`: decisiones base, inventario de integraciones, matriz de riesgos, pendientes priorizados.
- `fase-02/`: arquitectura lógica, mapa de servicios, flujos, errores e idempotencia.
- `fase-03/`: resumen de implementación del esqueleto instalable.

If a new phase produces artifacts, add them under `.agents/entregables/fase-XX/` and reference them from the corresponding phase document.

## Skills
The implementation baseline explicitly relies on the local skill:

- `.agents/skills/prestashop-module-development/SKILL.md`

Use this skill as the primary development convention for:

- modern module structure,
- installer delegation,
- Symfony configuration pages,
- services and DI,
- security, hooks, translations, and validation.

When a change conflicts with the skill, document the reason in the relevant phase or deliverable before implementing it.

## Build, Test, and Development Commands
Run commands from `modules/vx_sendifico`.

- `git status`: inspect local changes before editing.
- `composer validate --strict`: validate package metadata.
- `composer dump-autoload`: refresh PSR-4 autoloading.
- `php -l vx_sendifico.php`: lint the main module entrypoint.
- `find src -name '*.php' -print0 | xargs -0 -n1 php -l`: lint PHP source files.
- `rg --files .agents`: inspect planning, phases, and deliverables quickly.
- `sed -n '1,160p' .agents/SOT_Sendifico_API.yml`: review the API contract in chunks.

If the local PrestaShop runtime is available, validate installation with the project-standard module install flow already recorded in `.agents/entregables/fase-03/resumen-implementacion.md`.

## Uso obligatorio de DDEV para comandos de consola
When a command depends on the real PrestaShop application context, database, modules, Symfony container, or `bin/console`, run it through DDEV and not directly from the host shell.

This is the root path: `~/Sites/prestashop/`.

- Use `ddev exec` for PrestaShop console commands, module install or uninstall flows, cache operations, and runtime validations.
- Prefer running from the project root mounted in the container, for example `ddev exec sh -lc 'cd /var/www/html && php bin/console pr:mo install vx_sendifico'`.
- Do not assume the host PHP binary reflects the same extensions, paths, or runtime configuration as the containerized application.
- Host-side commands are acceptable only for repo-local static tasks such as `rg`, `git status`, `sed`, `composer validate`, or `php -l` when they do not require the live shop context.
- If a validation result depends on database state, shop configuration, multistore context, or installed modules, treat DDEV as mandatory.

## Coding Style & Naming Conventions
Follow PrestaShop and modern PHP conventions:

- Use 4-space indentation and UTF-8 text files.
- Use `StudlyCase` for classes and `camelCase` for methods and properties.
- Use `snake_case` only where PrestaShop configuration keys, database fields, or legacy integration points require it.
- Keep one responsibility per class and prefer explicit names such as `SendificoShipmentService`, `TerritorySyncService`, or `CarrierQuoteMapper`.
- Keep business logic out of `vx_sendifico.php`; delegate installation, configuration, API, repository, and BO actions to `src/`.
- Avoid overrides unless a documented phase decision explicitly justifies them.

## Testing Guidelines
Automated coverage is still incomplete, so every new executable feature should expand validation.

- Add tests under `tests/` mirroring the production namespace or feature area.
- Prioritize coverage for payload mapping, quotation filtering, shipment creation, purchase retries, and error handling.
- Add fixtures or sample payloads near the tests when Sendifico request or response shapes matter.
- Record any manual validation needed for checkout, BO actions, or multistore behavior in the related deliverable.

## Commit & Pull Request Guidelines
Use short, imperative, specific commits such as:

- `Add territory sync service skeleton`
- `Persist selected Sendifico rate on order confirmation`
- `Wire BO action for label generation`

Pull requests should include:

- summary of functional or operational impact,
- affected phase or deliverable,
- impacted Sendifico endpoints or order states,
- test evidence or explicit pending validation,
- screenshots only when BO or checkout UI changed.

## Security & Configuration Tips
Never commit API keys, wallet credentials, sender secrets, or production customer data.

- Keep Sendifico credentials in configuration per shop context.
- Respect mandatory headers and country/version constraints from `.agents/SOT_Sendifico_API.yml`.
- Log operational traces with enough detail for retries, but avoid leaking sensitive payload data.
- Design all new configuration, cache, and persistence layers with multistore in mind.
- Do not create shipments when required checkout or package data is missing; fail internally with traceability.

## Functional Summary
The agreed v1 behavior of the module is:

- quote shipping options in classic checkout using Sendifico,
- expose only mapped persistent carriers returned by the current quotation,
- create the shipment only after the order exists,
- attempt purchase when the order becomes paid or accepted,
- support BO retries for purchase,
- support manual BO tracking generation and label generation,
- persist technical and operational traceability in module-owned storage.
