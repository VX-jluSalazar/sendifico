# Fase 04 - Configuracion Back Office y parametros por tienda

## Objetivo
Implementar la configuracion moderna del modulo con soporte multitienda desde el diseno.

## Alcance
- Formulario de configuracion Symfony.
- Persistencia de parametros por tienda o grupo de tiendas.
- Validaciones y permisos administrativos.

## Actividades
1. Definir claves de configuracion para API key, pais, moneda, metodo COD, sender por tienda y politicas operativas.
2. Crear `DataConfiguration`, `FormDataProvider`, `FormType` y controlador BO.
3. Diseñar secciones funcionales: credenciales, remitente, checkout, operaciones y logs.
4. Implementar validaciones de formato y consistencia por tienda.
5. Añadir mensajes operativos y UX minima de ayuda para configuraciones incompletas.

## Entregables
- Pantalla de configuracion BO funcional.
- Persistencia multitienda de ajustes.
- Reglas de validacion y permisos administrativas.

## Dependencias
- Fase 03.
- Definicion del modelo de configuracion por tienda.

## Criterios de salida
- La configuracion se guarda y lee por contexto de tienda.
- Credenciales y defaults operativos quedan disponibles para servicios internos.
- Los errores de configuracion son comprensibles y accionables.

## Pendientes para modo plan
- Metodo exacto para detectar COD por metodo de pago configurable.
- Politica de default si una tienda no tiene sender configurado.
