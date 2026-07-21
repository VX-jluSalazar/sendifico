# Fase 01 - Preparacion y diagnostico

## Objetivo
Validar el marco tecnico y operativo del modulo `sendifico` antes de iniciar implementacion ejecutable.

## Alcance
- Confirmar version objetivo de PrestaShop, PHP y dependencias base.
- Revisar restricciones del checkout clasico, multitienda y flujo de estados del pedido.
- Traducir el resumen tecnico en decisiones implementables y riesgos controlados.

## Actividades
1. Consolidar como baseline `.agents/00-discovery.md` y `.agents/SOT_Sendifico_API.yml`.
2. Definir namespace real, estrategia de composer y estructura de carpetas del modulo.
3. Identificar dependencias con core de PrestaShop: carriers, checkout, order states, hooks, BO y configuracion.
4. Registrar riesgos tecnicos iniciales y mitigaciones por area.
5. Preparar una matriz de decisiones abiertas con responsable y criterio de cierre.

## Entregables
- Documento de decisiones base del proyecto.
- Matriz de riesgos inicial.
- Inventario de puntos de integracion con PrestaShop y Sendifico.
- Lista priorizada de pendientes para resolver antes de la fase 03.

## Dependencias
- Resumen tecnico del modulo en `AGENTS.md`.
- Discovery funcional existente.
- Contrato OpenAPI de Sendifico.

## Criterios de salida
- Existe una definicion unica del alcance v1.
- Las dependencias tecnicas de PrestaShop quedaron identificadas.
- Los pendientes bloqueantes tienen dueño y fase de resolucion.

## Pendientes para modo plan
- Version exacta de PHP objetivo.
- Namespace vendorizado final.
- Confirmacion de que el checkout activo no rompe la integracion con checkout clasico.
