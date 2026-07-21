# Diagrama lógico de la base de datos

Sistema de almacén Confipetrol — modelo lógico actualizado al 20/07/2026.

Este documento representa las entidades del negocio y sus relaciones. Omite caché, sesiones, colas, migraciones y otros detalles internos de Laravel. Los nombres mostrados están en español; al final se incluye la correspondencia con las tablas físicas actuales.

## Vista general del sistema

```mermaid
flowchart LR
    CAT[Categorías y atributos] --> PROD[Productos y variantes]
    PROD --> SER[Unidades seriadas]
    PROD --> LOT[Lotes y vencimientos]

    REM[Remitos de ingreso y salida] --> KAR[Kardex]
    LOT --> KAR
    SER --> KAR

    TRA[Trabajadores] --> ENT[Entregas]
    ENT --> KAR

    KAR --> STO[Stock actual]
    KAR --> REP[Reportes y alertas]

    USU[Usuarios, roles y permisos] --> REM
    USU --> ENT
    USU --> AUD[Auditoría]
    REM --> AUD
    ENT --> AUD
    PROD --> AUD
```

El stock no es un dato editable: se obtiene sumando los movimientos del Kardex por variante, lote o número de serie.

## 1. Catálogo de productos

```mermaid
erDiagram
    CATEGORIA ||--o{ PRODUCTO : clasifica
    CATEGORIA ||--o{ CONFIGURACION_ATRIBUTO : configura
    ATRIBUTO ||--o{ CONFIGURACION_ATRIBUTO : se_asigna

    PRODUCTO ||--o{ VALOR_ATRIBUTO_PRODUCTO : posee
    ATRIBUTO ||--o{ VALOR_ATRIBUTO_PRODUCTO : define

    PRODUCTO ||--|{ VARIANTE : contiene
    VARIANTE ||--o{ VALOR_ATRIBUTO_VARIANTE : posee
    ATRIBUTO ||--o{ VALOR_ATRIBUTO_VARIANTE : define

    VARIANTE ||--o{ UNIDAD_SERIADA : individualiza
    UNIDAD_SERIADA ||--o{ VALOR_ATRIBUTO_UNIDAD : posee
    ATRIBUTO ||--o{ VALOR_ATRIBUTO_UNIDAD : define

    CATEGORIA {
        id identificador
        codigo codigo_unico
        nombre nombre
        estado activo_inactivo
    }

    ATRIBUTO {
        id identificador
        codigo codigo_unico
        nombre nombre
        tipo texto_numero_lista_fecha_booleano
        alcance producto_variante_unidad
        estado activo_inactivo
    }

    CONFIGURACION_ATRIBUTO {
        id_categoria categoria
        id_atributo atributo
        obligatorio requerido
        posicion orden
    }

    PRODUCTO {
        id identificador
        id_categoria categoria
        codigo codigo_unico
        nombre nombre
        unidad unidad_medida
        tipo_seguimiento cantidad_seriado
        estado activo_inactivo
    }

    VARIANTE {
        id identificador
        id_producto producto
        sku codigo_unico
        nombre presentacion_talla
        stock_minimo alerta
        estado activo_inactivo
    }

    UNIDAD_SERIADA {
        id identificador
        id_variante variante
        numero_serie identificador_unico
        estado situacion_actual
    }

    VALOR_ATRIBUTO_PRODUCTO {
        id_producto producto
        id_atributo atributo
        valor contenido
    }

    VALOR_ATRIBUTO_VARIANTE {
        id_variante variante
        id_atributo atributo
        valor contenido
    }

    VALOR_ATRIBUTO_UNIDAD {
        id_unidad unidad_seriada
        id_atributo atributo
        valor contenido
    }
```

Una variante es la unidad lógica que participa en el inventario. Un producto sin tallas también tiene una variante única interna. Cuando el producto es seriado, cada equipo físico se representa mediante una unidad seriada.

## 2. Operación, lotes e inventario

```mermaid
erDiagram
    REMITO ||--|{ DETALLE_REMITO : contiene
    VARIANTE ||--o{ DETALLE_REMITO : se_registra
    DETALLE_REMITO ||--o{ SERIE_REMITO : selecciona
    UNIDAD_SERIADA ||--o{ SERIE_REMITO : participa

    TRABAJADOR ||--o{ ENTREGA : recibe
    ENTREGA ||--|{ DETALLE_ENTREGA : contiene
    VARIANTE ||--o{ DETALLE_ENTREGA : se_entrega
    DETALLE_ENTREGA ||--o{ SERIE_ENTREGA : selecciona
    UNIDAD_SERIADA ||--o{ SERIE_ENTREGA : participa

    VARIANTE ||--o{ LOTE : organiza
    LOTE ||--o{ ASIGNACION_LOTE : distribuye
    DETALLE_REMITO o|--o{ ASIGNACION_LOTE : utiliza
    DETALLE_ENTREGA o|--o{ ASIGNACION_LOTE : utiliza

    VARIANTE ||--o{ MOVIMIENTO_INVENTARIO : afecta
    LOTE o|--o{ MOVIMIENTO_INVENTARIO : identifica
    UNIDAD_SERIADA o|--o{ MOVIMIENTO_INVENTARIO : identifica
    REMITO o|--o{ MOVIMIENTO_INVENTARIO : origina
    ENTREGA o|--o{ MOVIMIENTO_INVENTARIO : origina
    MOVIMIENTO_INVENTARIO o|--o{ MOVIMIENTO_INVENTARIO : revierte

    REMITO o|--o| REMITO : corrige
    ENTREGA o|--o| ENTREGA : corrige

    REMITO {
        id identificador
        numero codigo_documento
        tipo ingreso_salida
        fecha fecha_documento
        contraparte origen_destino
        estado borrador_confirmado_anulado
        id_original trazabilidad_correccion
    }

    DETALLE_REMITO {
        id identificador
        id_remito remito
        id_variante variante
        cantidad cantidad
        lote lote_ingresado
        vencimiento fecha_vencimiento
    }

    TRABAJADOR {
        id identificador
        codigo codigo_interno
        documento identificacion
        nombre nombre_completo
        cargo cargo
        area area
        estado activo_inactivo
    }

    ENTREGA {
        id identificador
        numero codigo_documento
        id_trabajador receptor
        fecha fecha_entrega
        estado borrador_confirmado_anulado
        id_original trazabilidad_correccion
    }

    DETALLE_ENTREGA {
        id identificador
        id_entrega entrega
        id_variante variante
        cantidad cantidad
    }

    LOTE {
        id identificador
        id_variante variante
        numero_lote lote
        fecha_vencimiento vencimiento
        fecha_recepcion recepcion
        estado activo_inactivo
    }

    ASIGNACION_LOTE {
        id identificador
        id_lote lote
        id_detalle_remito remito_opcional
        id_detalle_entrega entrega_opcional
        cantidad cantidad_asignada
    }

    MOVIMIENTO_INVENTARIO {
        id identificador
        id_variante variante
        id_lote lote_opcional
        id_unidad_seriada serie_opcional
        id_remito remito_opcional
        id_entrega entrega_opcional
        id_movimiento_revertido reversion_opcional
        tipo tipo_movimiento
        cantidad positiva_negativa
        fecha_hora momento_registro
    }

    SERIE_REMITO {
        id_detalle_remito detalle
        id_unidad_seriada serie
    }

    SERIE_ENTREGA {
        id_detalle_entrega detalle
        id_unidad_seriada serie
    }
```

### Reglas lógicas principales

- Un remito representa un ingreso o una salida del almacén.
- Una entrega representa la asignación de productos a un trabajador.
- Una asignación de lote pertenece a un detalle de remito o a un detalle de entrega, pero nunca a ambos.
- Los productos con vencimiento se entregan mediante FEFO: primero sale el lote vigente que vence antes.
- Una anulación no elimina movimientos; crea movimientos inversos relacionados con los originales.
- Una corrección conserva el documento original y registra una nueva versión.
- Una unidad seriada solamente puede tener saldo cero o uno.

## 3. Seguridad y trazabilidad

```mermaid
erDiagram
    USUARIO ||--o{ USUARIO_ROL : recibe
    ROL ||--o{ USUARIO_ROL : se_asigna
    ROL ||--o{ ROL_PERMISO : contiene
    PERMISO ||--o{ ROL_PERMISO : autoriza

    USUARIO o|--o{ AUDITORIA : ejecuta
    USUARIO ||--o{ REMITO : registra
    USUARIO ||--o{ ENTREGA : registra
    USUARIO ||--o{ MOVIMIENTO_INVENTARIO : registra

    USUARIO {
        id identificador
        usuario credencial
        nombre nombre_completo
        correo correo
        estado activo_inactivo
        maximo_sesiones limite
    }

    ROL {
        id identificador
        nombre nombre_rol
        estado activo_inactivo
    }

    PERMISO {
        id identificador
        nombre accion_permitida
        grupo modulo
    }

    USUARIO_ROL {
        id_usuario usuario
        id_rol rol
    }

    ROL_PERMISO {
        id_rol rol
        id_permiso permiso
    }

    AUDITORIA {
        id identificador
        id_usuario actor_opcional
        usuario_actor identidad_historica
        modulo modulo
        accion accion
        registro_afectado identificador
        valores_anteriores antes
        valores_nuevos despues
        ip origen
        fecha_hora momento
    }
```

La identidad textual del actor permanece en auditoría aunque posteriormente se elimine el usuario. Los valores anteriores y nuevos permiten reconstruir qué campos cambiaron.

## Correspondencia con las tablas físicas

| Entidad lógica | Tabla física actual |
|---|---|
| Categoría | `categories` |
| Atributo | `product_attributes` |
| Configuración de atributo | `category_product_attribute` |
| Producto | `products` |
| Variante | `product_variants` |
| Unidad seriada | `serialized_items` |
| Valores de atributos | `product_attribute_values`, `variant_attribute_values`, `serialized_item_attribute_values` |
| Remito y detalle | `dispatch_notes`, `dispatch_note_items` |
| Series de remito | `dispatch_note_serialized_items` |
| Trabajador | `workers` |
| Entrega y detalle | `deliveries`, `delivery_items` |
| Series de entrega | `delivery_serialized_items` |
| Lote | `inventory_lots` |
| Asignación de lote | `inventory_lot_allocations` |
| Movimiento / Kardex | `inventory_movements` |
| Usuario | `users` |
| Rol y permiso | `roles`, `permissions` |
| Relaciones de seguridad | `model_has_roles`, `model_has_permissions`, `role_has_permissions` |
| Auditoría | `logs` |
| Secuencia documental | `document_sequences` |

Las tablas técnicas `migrations`, `cache`, `cache_locks`, `sessions`, `password_reset_tokens`, `jobs`, `job_batches` y `failed_jobs` no forman parte del diagrama lógico porque no representan procesos propios del almacén.

## Nota de simplificación

`serialized_item_attribute_values` se muestra porque existe en el esquema actual. En el uso presente duplica el número almacenado en `serialized_items.serial_number` y es candidata a eliminación si se decide que cada unidad solo necesitará número de serie. Las demás relaciones del diagrama representan funciones activas o estructuras necesarias para conservar la trazabilidad.
