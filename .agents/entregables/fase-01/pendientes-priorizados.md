# Fase 01 - Pendientes priorizados

## Resueltos por decision del propietario

| Pendiente | Decision |
| --- | --- |
| Nombre tecnico del modulo | `vx_sendifico`. |
| Author | `Velox`. |
| Namespace/composer | Namespace `Vx\Sendifico\`; composer package `velox/vx-sendifico`. |
| Checkout | Checkout default/clasico de PrestaShop confirmado. |
| Desinstalacion | Borrar todo al desinstalar. |
| `purchaseWith` | Usar `walletAvailable`. |
| Estados adicionales | Solo `Courier no pagado`. |
| Dominancia de `contents` | Categoria que mas se repite; si hay empate, la de mayor peso acumulado. |
| Defaults operativos | Definibles desde Back Office: pais, moneda, medidas y demas defaults. |
| UX territorio checkout | Selectores separados y encadenados provincia -> canton -> ciudad. |

## Bloqueantes antes de fase 03

| Prioridad | Pendiente | Responsable | Criterio de cierre | Fase objetivo |
| --- | --- | --- | --- | --- |
| P0 | Sin bloqueantes abiertos para fase 03. | Tecnico | Continuar con fase 02 o fase 03 segun orden del plan. | Fase 02/Fase 03 |

## Altos antes de cerrar arquitectura

| Prioridad | Pendiente | Responsable | Criterio de cierre | Fase objetivo |
| --- | --- | --- | --- | --- |
| P1 | Definir modelo local minimo de shipment y quotation cache. | Tecnico | Tablas, indices, claves unicas, retencion y campos de auditoria aprobados. | Fase 06 |

## Medios para fases especificas

| Prioridad | Pendiente | Responsable | Criterio de cierre | Fase objetivo |
| --- | --- | --- | --- | --- |
| P2 | Definir fallback lat/lng. | Producto / tecnico | Decidir si se omite, se captura via navegador, o se permite ingresarlo manualmente. | Fase 08/Fase 09 |
| P2 | Definir estrategia de cache de territorios. | Tecnico | TTL, comando/manual sync, tabla y comportamiento si Sendifico falla. | Fase 05 |
| P2 | Definir sincronizacion de sender addresses. | Tecnico / operaciones | Frecuencia, UI de seleccion por tienda y validacion de sender activo. | Fase 05 |
| P2 | Definir tolerancia ante diferencia de precio quotation vs shipment rates. | Negocio / tecnico | Politica: bloquear, aceptar bajo umbral, o marcar revision manual. | Fase 10 |
| P2 | Definir permisos BO. | Propietario tienda / tecnico | Perfiles que pueden configurar, reintentar purchase, generar tracking y label. | Fase 11/Fase 12 |
| P2 | Definir politica de logs y datos personales. | Tecnico / seguridad | Campos permitidos, mascara de credenciales, retencion y nivel de detalle. | Fase 12 |

## Resultado de salida de fase 01

- Alcance v1 queda definido en `.agents/entregables/fase-01/decisiones-base.md`.
- Dependencias tecnicas PrestaShop y Sendifico quedan inventariadas en `.agents/entregables/fase-01/inventario-integraciones.md`.
- Riesgos iniciales y mitigaciones quedan registrados en `.agents/entregables/fase-01/matriz-riesgos-inicial.md`.
- Pendientes bloqueantes tienen responsable y fase de cierre en este documento.
