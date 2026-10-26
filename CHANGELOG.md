# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto usa [Versionado Semántico](https://semver.org/lang/es/) (sin prefijo `v`, ej. `1.0.0`).

## [1.8.1] - 2026-10-26

Auditoría de frontend de `ventas` (13/20 → 20/20) y refactor de la pantalla de nueva venta. Sin cambios de esquema.

### Added

- La tarjeta «Forma de Pago» cambia de color según el cobro: verde si está completo (total cubierto y efectivo recibido suficiente), rojo si falta algo y azul sin total; vale igual para pago único y mixto.
- `VentaController::estadoVenta()` (etiqueta, badge e icono del estado) y `datosParaCrear()` (productos con stock y clientes activos, solo con los campos que el formulario necesita).

### Changed

- `create.php` se divide en partials (`venta-carrito`, `venta-pago`, `venta-cliente`, `venta-resumen`, `modales-venta`, `plantilla-metodo-pago`) y `show.php` en cuatro (`info-general`, `metodos-pago`, `productos-vendidos`, `informacion-adicional`).
- `create-venta.js` (870 líneas) pasa a un orquestador de 83 líneas sobre cuatro módulos: `venta-util`, `venta-selectores`, `venta-pagos` y `venta-detalle`. Importes en centavos enteros, búsqueda indexada con `debounce`, eventos delegados y bloqueo del doble envío.
- Contraste, áreas táctiles de 44px y colores por tokens en el CSS del módulo; sin colores hexadecimales.
- Globales: breadcrumb, `.btn-tool`, controles y paginación de DataTables y `.btn-primary` cumplen contraste AA y 44px.

### Fixed

- Con QR u Otros como método de pago único, «Pago recibido» seguía visible: Select2 dispara `change` por jQuery y el listener nativo no lo recibía.
- El HTML de la pantalla de nueva venta exponía precios de compra y datos de contacto de clientes.

## [1.8.0] - 2026-10-20

Refactor de `servicios-bano`: el cobro entra al libro de `pagos`, el servicio se registra y cancela de forma atómica, un huésped puede cargarlo a su folio y cada baño sabe si está en uso. Auditoría de frontend del módulo (16/20 → 18/20). Hay cambios de esquema (ver Migración).

### Added

- **Cobro en `pagos`** (`pagos.idservicio_bano`) y **cargo al folio** de una estancia `en_curso`; el Dashboard cuenta solo el baño al contado para no duplicar lo que ya entra por Recepción.
- **Cancelación con reverso** y motivo obligatorio; un cancelado no se reactiva y cancelar uno finalizado es solo del Administrador.
- **Ocupación del baño** (`bano_en_uso` + `UNIQUE`): acción Finalizar, rack de baños en el listado y estado derivado `excedido`.
- Variables `BANO_PRODUCTO_CONSUMIBLE` y `BANO_MINUTOS_ALERTA` (`0` desactiva la alerta).

### Changed

- El servicio se registra en una transacción con bloqueos y el cambio de estado pasa de GET a POST con CSRF; `update.php` solo edita `observaciones`.
- Estado por helper único, partials, KPI en `info-box` y mejoras de contraste, áreas táctiles y foco.

### Fixed

- Los ingresos del baño salían en 0 en el Dashboard y el kardex (solo contaban `finalizado`).
- La fecha y el total del cobro los definía el cliente.
- Dos servicios simultáneos sobre el mismo baño eran posibles y cancelar no revertía el cobro.
- Un usuario sin permiso sobre el módulo (Limpieza) entraba en un bucle de redirecciones.

### Migración

Ejecutar en orden. Antes, comprobar que dan 0 filas: `SELECT COUNT(*) FROM pagos WHERE idequipaje IS NOT NULL AND idrecepcion IS NOT NULL`, `SELECT COUNT(*) FROM servicio_bano WHERE pagorecibido < total OR total < 0` y `SELECT id_pago_reversado FROM pagos WHERE id_pago_reversado IS NOT NULL GROUP BY id_pago_reversado HAVING COUNT(*) > 1`.

```sql
ALTER TABLE servicio_bano
  ADD COLUMN idrecepcion int(11) DEFAULT NULL AFTER idcliente,
  ADD COLUMN modalidad_cobro enum('contado','folio') NOT NULL DEFAULT 'contado' AFTER tipo_cliente,
  ADD COLUMN fecha_fin datetime DEFAULT NULL AFTER fecha,
  ADD COLUMN observaciones varchar(255) DEFAULT NULL AFTER estado,
  ADD COLUMN motivo_cancelacion varchar(255) DEFAULT NULL AFTER observaciones,
  ADD COLUMN idusuario_cancelacion int(11) DEFAULT NULL AFTER motivo_cancelacion,
  ADD COLUMN fecha_cancelacion datetime DEFAULT NULL AFTER idusuario_cancelacion,
  ADD COLUMN stock_repuesto tinyint(1) NOT NULL DEFAULT 0 AFTER fecha_cancelacion,
  MODIFY metodopago enum('Efectivo','QR','OTROS') DEFAULT NULL;
ALTER TABLE pagos ADD COLUMN idservicio_bano int(11) DEFAULT NULL AFTER idequipaje;

-- Los 'iniciado' anteriores significaban "cobrado": cerrarlos antes del UNIQUE
UPDATE servicio_bano SET estado = 'finalizado', fecha_fin = fecha WHERE estado = 'iniciado';
UPDATE servicio_bano SET fecha_fin = fecha WHERE estado = 'finalizado' AND fecha_fin IS NULL;
UPDATE servicio_bano SET fecha_cancelacion = COALESCE(fechaactualizacion, fecha), motivo_cancelacion = 'Cancelado antes de 1.8.0'
 WHERE estado = 'cancelado' AND fecha_cancelacion IS NULL;

-- Ledger de los servicios vigentes
INSERT INTO pagos (idservicio_bano, tipo, concepto, montototal, metodopago, idusuario, fechacreacion)
  SELECT idservicio, 'cargo', CONCAT('Servicio de baño #', idservicio, ' (migración)'), total, 'OTROS', idusuario, fecha
  FROM servicio_bano WHERE estado <> 'cancelado' AND total > 0;
INSERT INTO pagos (idservicio_bano, tipo, concepto, montototal, metodopago, idusuario, fechacreacion)
  SELECT idservicio, 'pago', CONCAT('Pago servicio de baño #', idservicio, ' (migración)'), total, metodopago, idusuario, fecha
  FROM servicio_bano WHERE estado <> 'cancelado' AND total > 0;

ALTER TABLE servicio_bano
  ADD COLUMN bano_en_uso int(11) AS (IF(estado = 'iniciado', idbano, NULL)) PERSISTENT,
  ADD KEY idrecepcion (idrecepcion), ADD KEY idusuario_cancelacion (idusuario_cancelacion),
  ADD KEY idx_sb_fecha_estado (fecha, estado), ADD UNIQUE KEY uq_sb_bano_en_uso (bano_en_uso),
  ADD CONSTRAINT servicio_bano_ibfk_4 FOREIGN KEY (idusuario_cancelacion) REFERENCES usuarios (idusuario),
  ADD CONSTRAINT servicio_bano_ibfk_5 FOREIGN KEY (idrecepcion) REFERENCES recepcion (idrecepcion),
  ADD CONSTRAINT chk_sb_montos CHECK (total >= 0 AND pagorecibido >= 0 AND cambio >= 0),
  ADD CONSTRAINT chk_sb_contado CHECK (modalidad_cobro <> 'contado' OR (metodopago IS NOT NULL AND pagorecibido >= total)),
  ADD CONSTRAINT chk_sb_folio CHECK (modalidad_cobro <> 'folio' OR (idrecepcion IS NOT NULL AND metodopago IS NULL AND pagorecibido = 0 AND cambio = 0)),
  ADD CONSTRAINT chk_sb_fin CHECK (estado <> 'finalizado' OR fecha_fin IS NOT NULL),
  ADD CONSTRAINT chk_sb_cancel CHECK (estado <> 'cancelado' OR fecha_cancelacion IS NOT NULL);
ALTER TABLE pagos
  ADD KEY idservicio_bano (idservicio_bano),
  ADD CONSTRAINT pagos_ibfk_5 FOREIGN KEY (idservicio_bano) REFERENCES servicio_bano (idservicio),
  ADD CONSTRAINT chk_pagos_origen CHECK (idequipaje IS NULL OR (idrecepcion IS NULL AND idservicio_bano IS NULL)),
  DROP KEY id_pago_reversado, ADD UNIQUE KEY uq_pagos_reversado (id_pago_reversado);
```

Debe existir un producto con el nombre de `BANO_PRODUCTO_CONSUMIBLE` (por defecto `Rollo de papel`) o no se podrá registrar ningún servicio. Los servicios existentes quedan como `contado`.

### Docs

- `CLAUDE.md`, `PROMPTS.md` y `README.md` alineados con el módulo.

## [1.7.2] - 2026-10-12

Auditoría de frontend de `almacenamiento-equipaje` (13/20 → 19/20, verificada en navegador a 1280px y 390px) y listado de habitaciones con dropdown de acciones.

### Changed

- **Cambio de estado de equipaje por POST con CSRF en el body** (antes GET con `csrf_token` en la URL): `cambiar_estado.php` es de contrato dual (JSON con `X-Requested-With`, redirect + mensaje de sesión si no) y solo acepta POST. Un solo flujo de JS (`cambiar-estado-equipajes.js`) reemplaza las dos copias de `index-equipajes.js` y `show-equipaje.js`, con confirmación y spinner de carga.
- **Listado de equipaje**: cada fila muestra solo "Ver" y un dropdown con Editar, Imprimir recibo y los cambios de estado; los ítems son `<button>` con nombre accesible. El filtro de estado y las acciones salen de helpers del controlador (`estadosFiltro()`, `accionesEstado()`, `esEditable()`), y `index` ya no llama a `crear()` solo para obtener clientes.
- **Listado de habitaciones**: los cambios de estado pasan a un dropdown en vez de un botón por transición.
- **Historial de `show.php` de equipaje** armado en el controlador (`armarTimeline()`) y pintado por el partial `timeline-equipaje.php`; 463 → ~330 líneas en la vista. Los avisos de estado final salen de `estadoEquipaje()['alerta']`.
- CSS de equipaje depurado: se elimina el CSS sin uso, los ajustes globales de formulario van bajo `.equipaje-pagina` y los `style=` estáticos pasan a clases. Nuevo token `--color-shadow`.
- Accesibilidad del módulo: `aria-label` en los botones de icono, `aria-hidden` en los iconos decorativos y label vacío del filtro oculto a lectores de pantalla.

### Fixed

- **Persistencia de pestaña de `show.php` de equipaje no funcionaba**: escuchaba `shown.bs.tab` con `addEventListener`, y Bootstrap 4 lo dispara por jQuery.
- **Listener global `.btn-secondary` de `index-equipajes.js`** borraba las fechas del filtro al pulsar cualquier botón secundario de la página.
- **Acciones de equipaje ocultas en móvil**: la DataTable responsive las colapsaba; ahora Acciones, Código y Estado tienen prioridad de visibilidad.
- **Eventos perdidos en la timeline**: un equipaje perdido o dañado no mostraba su evento "Cambio de estado" (exigía `fechasalida`, que solo tiene el retirado), y un cambio a estado final aparecía también duplicado como "Actualización".
- Contraste de las etiquetas de la timeline (success/primary con texto blanco quedaban bajo 4.5:1): ahora usan tonos `tl-*` desde `tokens.css` (4.69 a 11.51:1, medidos en navegador).

### Docs

- `CLAUDE.md`: flujo único de estado de equipaje, helpers nuevos del controlador, `responsivePriority` en la columna Acciones de DataTables Responsive, regla de especificidad al sobrescribir componentes de AdminLTE y eventos de Bootstrap 4 por jQuery.

## [1.7.1] - 2026-10-10

Segunda pasada de la auditoría de bugs: se probaron con acciones reales los flujos de ventas, compras, baño, equipaje, productos y usuarios (apareció que en 1.7.0 no se podían crear ni editar productos), se cerró la deuda técnica conocida y se unificaron los reportes de DataTables. Dos correcciones que cambian el comportamiento: el stock de una compra entra al completarla y la gestión de usuarios queda solo para el Administrador (ver Migración).

### Fixed

- **Dashboard de administrador mostraba datos inventados** en los gráficos de actividad (`[5, 7, 3, 8, …]`) cuando no había actividad real: `Dashboard::getDatosGraficos()` devolvía series de ejemplo. Ahora devuelve las series reales (ceros).
- **Gráfico de actividad ignoraba su configuración de ejes**: estaba escrita con la sintaxis de Chart.js 3 (`scales.x`/`scales.y`) y el proyecto carga Chart.js 2, así que el eje Y mostraba valores negativos con datos en cero. Pasa a `xAxes`/`yAxes` con `beginAtZero` y `precision: 0`.
- **Gráficos del dashboard con opciones de Chart.js 3 ignoradas** (Chart.js 2 instalado): leyenda nativa duplicada junto a la HTML, tooltips sin fecha completa ni unidades (`Bs.`, `habitaciones`) ni porcentaje en la dona, dona al 50 % en vez de 70 %. Pasan a `legend`/`tooltips`/`hover`/`cutoutPercentage`/`lineTension` de la v2.
- **Exportaciones de `productos`** incluían la columna Imagen (vacía) y omitían Estado; las columnas exportadas pasan a Nro, Código, Nombre, Categoría, Precio, Stock y Estado.
- **Acciones de `productos` ocultas en móvil**: la DataTable responsive las colapsaba tras el control de expansión; ahora Nombre, Acciones, Stock y Estado tienen prioridad de visibilidad.
- **Crear y editar productos fallaba siempre** (`SQLSTATE[HY093]`): el bind de `:stock` se había quitado del método equivocado; ahora `crear()` guarda el stock inicial y `actualizar()` no lo toca.
- **Método de pago inválido en baño y equipaje** se convertía en silencio a Efectivo; ahora se rechaza.
- **El servicio de baño normal registraba baños en mantenimiento**; ahora exige un baño existente y `disponible`, igual que el servicio rápido.
- **Anular una venta o cancelar una compra por segunda vez** mostraba "error inesperado"; ahora indica que ya estaba anulada/cancelada.
- **Exportaciones PDF salían siempre en vertical**: la orientación estaba puesta en la collection de botones, que pdfmake ignora. Ahora va en el botón PDF (horizontal).
- El mensaje al intentar modificar a un Administrador sin serlo hablaba de "asignar el cargo".

### Changed

- **El stock de una compra entra al completarla**, no al crearla: una compra `pendiente` ya no suma stock, `completar()` lo suma y `cancelar()` solo lo revierte si estaba `completada`.
- **Gestión de usuarios (vistas y altas/ediciones/desactivaciones) solo para el Administrador**; el permiso `usuarios` ya no basta, porque permitía cambiar claves y permisos de otros usuarios.
- El historial de recepciones gana el menú Reportes (copiar, PDF horizontal, Excel, CSV, imprimir): era el único listado sin exportación.
- Los reportes de DataTables de 15 módulos pasan a un helper común (`public/js/core/datatable-utils.js`): cada `index-*.js` queda en una llamada de ~15 líneas en vez de ~150. La cabecera del PDF usa el color `dark` del tema; la impresión de usuarios y tipos de habitación incluye todas las columnas exportadas (antes solo las dos primeras).
- `Recepcion::actualizar()` solo acepta `idcliente`, `idtarifa`, `fechaentrada`, `fechasalida_prevista` y `observaciones`; ya no puede escribir dinero, método de pago, estado ni habitación (eso va por el folio, `cambiarEstado()`, `checkout()` y `cambiarHabitacion()`).

### Removed

- `RecepcionController::actualizar()` (sin llamadas) e `ImagenService::redimensionarImagen()` (sin uso; sobrescribía el original).

### Migración

- Si hay compras `pendiente` creadas antes de este cambio, su stock ya se sumó al crearlas: restar esas cantidades a `productos.stock` (o completar/cancelar con cuidado) para no duplicarlo al completarlas.

### Docs

- Capturas del README regeneradas (login, dashboard, habitaciones, recepción y ventas): la de ventas salía con el sidebar tapando el contenido por usar captura de página completa, y todas mostraban versiones antiguas en el pie.

## [1.7.0] - 2026-10-07

Auditoría de bugs de todo el proyecto (PHP MVC completo) con correcciones en seguridad, integridad de dinero/estados y escape de salida. Un cambio de esquema menor y un paso de datos al desplegar (ver Migración).

### Security

- **`.htaccess` nuevo**: `Options -Indexes`, 404 para `.git`/`.env`/`.claude`, bloqueo de `sql|md|log|lock|json|sh|yml|yaml|composer.*|LICENSE` y de los directorios `config/`, `models/`, `services/`, `database/`.
- **Sesión**: `session.use_strict_mode`; solo `session.php` inicia sesiones (cookie `HttpOnly` + `SameSite=Lax` también en login/logout); `requireLogin()` destruye la sesión si el usuario fue desactivado (`usuarioSigueActivo()`); `AuthorizationService::tienePermiso()` exige usuario activo.
- **Logout con CSRF** (`?csrf_token=`) y errores de login que redirigen a la vista de login en vez de `HTTP_REFERER`.
- **Usuarios**: `UsuarioController::validarPrivilegios()` (cargo válido; solo un Administrador asigna el cargo Administrador o modifica administradores; nadie cambia su propio cargo, estado ni permisos); `clave` ya no se sanitiza; imagen anterior se borra solo tras un UPDATE exitoso.
- **Compras**: `idusuario` desde la sesión; solo un administrador crea `completada`; completar/cancelar bloquean filas y validan estado y stock (`Compra::completar()`, `cancelar()`); eliminado `actualizar_compra.php`.
- **Ventas, baño y equipaje**: totales de baño y equipaje desde precio real en BD, método de pago en lista blanca; el estado inicial de equipaje siempre es `almacenado`; descuentos y pagos inválidos se rechazan con mensaje de negocio; estadísticas de ventas filtradas por usuario para no administradores.
- `requireLogin()` en 16 vistas y en los recibos de recepción y equipaje; permiso `tipohabitaciones` correcto en tipos de habitación; `cambiar_estado.php` valida que el referer empiece por `$URL`.

### Fixed

- **`Recepcion::existeSolape()` fallaba en silencio** (`HY093` por parámetro nombrado repetido): ahora usa `:excluir_nulo`/`:excluir_id` y el overbooking se rechaza de verdad.
- **Recepción**: tabla de transiciones de estado (`reservado→en_curso|cancelado`, `en_curso→finalizado|cancelado`); check-in bloquea la habitación y exige `disponible`; cancelar una reserva no toca la habitación; `Recepcion::checkout()` atómico (lock → saldo → pago → finalizar); la tarifa debe ser del tipo de la habitación y no se edita en una estancia; `montopagado` inicial según método de pago; `cambiarHabitacion` comprueba solape en destino; reservas futuras sobre habitaciones ocupadas/por limpiar permitidas con control de fechas.
- **Folio**: `Pago::registrarLinea()` participa de una transacción abierta; recepciones cerradas aceptan solo reversos; `getKpisDia` ya no cuenta pagos de equipaje.
- **Habitaciones y limpieza**: una habitación con estancia `en_curso` solo puede estar `ocupada`; completar/verificar una limpieza libera la habitación (`limpieza→disponible`); una asignación verificada no se reabre; un usuario no administrador opera solo sus asignaciones y no verifica.
- **Equipaje**: número de ticket protegido con `GET_LOCK` contra duplicados concurrentes.
- **Servicio de baño**: descuento de stock y alta del servicio en una sola transacción; el baño debe estar `disponible`.
- **Productos**: el stock ya no se edita en el formulario (solo por movimientos).
- **Ventas**: mensajes de stock insuficiente con nombre y disponible; listener de descuento y toggle Único/Mixto corregidos.
- **Clientes, compras, categorías**: eventos delegados para que los botones funcionen en páginas siguientes del DataTable; `show.php` de clientes sin `onclick` inline; redirecciones de personas a `views/clientes`.
- `config/conexion.php` con `charset=utf8mb4`; `config/env.php` ignora líneas sin `=`; `session.php` reutiliza `loadEnv()`.

### Changed

- **Los datos de texto se guardan crudos y se escapan al mostrarlos**: los 16 `sanitizarDatos()` solo hacen `trim()` (antes `htmlspecialchars()` escapaba en cada edición: `A &amp;amp; B`, y rompía contraseñas con caracteres especiales). Nuevo `escapeHtml()` en `common-utils.js` aplicado a los diálogos SweetAlert y `.html()` de ~15 módulos; el navbar y los PDF de recepción, equipaje y ventas escapan cliente, usuario y descripción; los toasts usan `titleText`.
- `AsignacionLimpieza::validarDatos()` captura excepciones de BD en vez de propagarlas.
- Selector de cliente de ventas solo lista clientes activos; el precio unitario es de solo lectura.

### Docs

- `CLAUDE.md`: convenciones de datos crudos con escape en salida, sesión viva vs. usuario activo, privilegios de usuarios, transiciones y check-out de recepción, y reutilización de parámetros nombrados en PDO; correcciones sobre `ImagenService` (sin redimensionado automático).
- `README.md`, `PROMPTS.md`, `.env.example`: versión 1.7.0 y notas de seguridad/migración.

### Migración

1. Restricción única de permisos (en BD existentes, comprobar antes que no haya duplicados):

   ```sql
   ALTER TABLE `permiso_usuario` ADD UNIQUE KEY `uq_permiso_usuario` (`idusuario`,`idpermiso`);
   ```

2. Si la BD tiene texto guardado con el esquema anterior (`&amp;`, `&#039;`, `&quot;`, `&lt;`, `&gt;`), des-escaparlo **una sola vez** con `html_entity_decode(valor, ENT_QUOTES)` en las columnas de texto (nombres, descripciones, observaciones). No repetir el paso.
3. Apache debe tener `AllowOverride All` para que el nuevo `.htaccess` se aplique.

## [1.6.1] - 2026-09-29

Refactor del frontend del módulo Productos para alinearlo con los patrones ya establecidos en Recepción y Habitaciones (helpers únicos de estado, partials, flujo único de cambio de estado y CSS propio), y extensión a todo el proyecto de la clase de área táctil `.btn-touch` y del helper de loading `mostrarCarga()`. Sin cambios de esquema.

### Added

- **`public/css/modules/productos/productos.css`**: clases de imagen (`prod-img-perfil/miniatura/sm/preview/ejemplo`) y badge de estado (`prod-badge-estado`). Cero hex: todo sale de `tokens.css`.
- **Partials de productos** (`views/productos/partials/`): `imagen-producto.php`, `tab-general.php`, `tab-inventario.php`, `tab-precios.php`. `show.php` pasa de 515 a 223 líneas.
- **Helpers únicos de `ProductoController`**: `estadoProducto()` / `estadosProducto()` (etiqueta, `badge`, icono y claves de acción, incl. `accion_texto`), `estadoMovimiento()` / `decorarMovimientos()` (presentación de `tipo_movimiento`), `calcularInventario()`, `calcularRentabilidad()` y `umbralesMargen()`.
- **Config `productos`** (`PRODUCTOS_MARGEN_ALERTA`, `PRODUCTOS_MARGEN_BUENO`) en `config/config.php` + `.env.example`: los umbrales de margen dejan de estar hardcodeados tanto en PHP como en JS (el form los recibe por `data-margen-*`).
- **`cambiar-estado-productos.js`**: flujo único de activación/desactivación (confirmación → `POST` → `reload()`), compartido por listado, detalle y formulario.
- **`producto-form.js`**: Select2, preview de imagen, validación en vivo de precios/stock y guardas de submit compartidos por `create.php` y `update.php`.
- **`.btn-touch`** en `public/css/core/styles.css`: clase global de área táctil ≥44px (`::before` absoluto con `max(100%,44px)`; en `.btn-group > .btn-touch` añade `min-width:44px`, AAA 2.5.5) sin alterar la caja visible. Aplicada a los botones de acción de 14 listados (usuarios, categorías, tipos de habitación, servicios de baño, pisos, limpieza, ventas, tarifas, compras, baños, clientes, precios de equipaje, equipaje y dashboard del recepcionista) además de productos, habitaciones y recepción.
- **`mostrarCarga(titulo, texto)`** en `public/js/core/common-utils.js`: modal de loading con spinner (sin botón, no se cierra por clic fuera/Esc) y listener `pageshow` que lo cierra al volver con «Atrás». Añadido a los guardados de clientes y usuarios, registro de compra/venta, anulación de venta, cambios de estado (usuarios, clientes, tarifas, tipos de habitación, servicios de baño, compras, limpieza) y los modales + AJAX de baños, categorías, pisos, precios de equipaje y limpieza (en `beforeSend`).

### Changed

- **`.rec-touch`, `.hab-touch` y `.prod-touch` unificadas en `.btn-touch`**; se eliminan las copias por módulo de `recepcion.css` y `habitaciones.css`. Los 10 loaders manuales con `Swal.showLoading()` pasan a `mostrarCarga()`.
- **`desactivar_producto.php` pasa de GET + redirect a POST + contrato dual** (JSON con `X-Requested-With`, redirect + mensaje en sesión si no), con `csrf_token` en el body; el nuevo estado se recalcula siempre en el servidor.
- **Vocabulario de estado de producto**: `inactivo` pasa de `badge-danger` a `badge-secondary` (gris), alineado con recepción/habitaciones/equipaje.
- **Tres copias del bloque de cambio de estado** (en `index-productos.js`, `show-producto.js` y `update-productos.js`) → un solo flujo delegado; los tres disparadores pasan a la clase común `.cambiar-estado-producto` y `update.php` usa `$estado_ui['accion_*']` en lugar de su propio `if (estado == 1)`.
- **JS de formularios deduplicado**: `create-producto.js` (140 → 11 líneas) y `update-productos.js` (184 → 11) delegan en `producto-form.js`.
- **Estilos inline estáticos de las vistas de productos** → clases de `productos.css`; quedan solo los dinámicos (ancho de barras de progreso y `display:none` de previews).
- **`$skip_datatables = true`** en `create.php`, `show.php` y `update.php` (ninguna de las tres usa DataTables).

### Fixed

- **`clientes/update-persona.js`** llamaba a `mostrarCargando()`, función inexistente: cada submit válido lanzaba `ReferenceError`. Ahora usa `mostrarCarga()`.
- **`.hab-touch` sin definir**: las vistas de habitaciones la seguían usando tras borrarse del CSS, y `.prod-touch` nunca llegó a definirse, por lo que esos botones quedaban sin área táctil de 44px.

### Removed

- **`views/productos/buscar_codigo.php`** y **`public/js/modules/productos/buscar-codigo.js`**: no tenían ningún enlace ni referencia desde el resto de la aplicación.
- Variables muertas en `views/productos/index.php`.

### Docs

- **`CLAUDE.md`**: convenciones de productos añadidas (helper único de estado, flujo único de cambio de estado, lógica de formulario compartida), partials de `productos` sumados al patrón de partials, `.btn-touch` reemplaza a `.rec-touch`/`.hab-touch` y nueva regla de `mostrarCarga()`.
- **`README.md`**: nueva sección **Productos** bajo _Módulos_; badge de versión a 1.6.1.
- **`PROMPTS.md`**: restricciones de productos, `.btn-touch` y `mostrarCarga()` en la plantilla base; fecha actualizada.
- **`.env.example`**: `APP_VERSION` sincronizado a `1.6.1`; documentadas `PRODUCTOS_MARGEN_ALERTA` y `PRODUCTOS_MARGEN_BUENO`.

### Migración

Ninguna: sin cambios de esquema. Las variables `PRODUCTOS_MARGEN_*` son opcionales (defaults `10` / `20`).

## [1.6.0] - 2026-09-27

### Added

- **`public/css/core/tokens.css`**: paleta de la app como variables CSS (`--color-*`), enlazada en `header.php` (con `?v=filemtime`) y en `login.php`. Helpers **`themeColor()` / `themeColorEstado()`** en `public/js/core/common-utils.js` como única fuente de colores para JS (SweetAlert2, Chart.js, estados).
- **Documentación de la convención de colores** en `CLAUDE.md` y `PROMPTS.md` (tokens, reglas de tono, contraste ≥4.5:1).

### Changed

- **155 hex → `var(--color-*)`** en 15 archivos CSS propios. Sin cambio visual: verificado con hash de estilos computados A/B (antes/después, `git stash`, 8 vistas) idéntico byte a byte.
- **144 diálogos SweetAlert con color explícito** (antes 88 heredaban el morado default `#7066e0` de SweetAlert2): cancels rojos → gris `secondary`, activar → verde `success`, estados → `themeColorEstado()`; colores de los gráficos del dashboard → tokens. Los colores de estado (`pendiente`, acción sobre amarillo) usan `warning-dark` por contraste AA (blanco sobre `#ffc107` = 1.63:1).
- **Capturas y secciones del README sincronizadas** con la UI actual (habitaciones y ventas regeneradas; se elimina la sección de Reportes, módulo eliminado en 1.4.0).

### Fixed

- **`common-utils.js` se servía sin `?v=filemtime`** en `footer.php` (tag hardcodeado fuera del loop de `$module_scripts`): el navegador ejecutaba una copia cacheada y los módulos fallaban con `ReferenceError: themeColor is not defined` aunque el archivo en disco estaba correcto.

## [1.5.0] - 2026-09-21

Refactor del módulo Almacenamiento de equipaje (partials, helpers de estado, folio de pagos y estado "vencido" derivado), mejora de accesibilidad y semántica en Usuarios, y coloreo global de pestañas inactivas por clase de card. Sin cambios de esquema.

### Added

- **Estado derivado "vencido"** en almacenamiento de equipaje: un equipaje `almacenado` con más de `EQUIPAJE_DIAS_ALERTA` días (default `7`, en `.env` + `config/config.php`) se muestra como Vencido — filtro en el listado, KPI "Vencidos" y badge rojo, sin mutar la columna `estado` en BD.
- **Helpers únicos de estado** `AlmacenamientoEquipajeController::estadoEquipaje()` / `estadosEquipaje()` / `estadoDerivado()` / `decorarEstados()` / `tiempoAlmacenado()` — mismo patrón que `estadoRecepcion()` y `estadoHabitacion()`; eliminan los `switch` de estado de las vistas.
- **Folio de pagos del equipaje**: `Pago::getByEquipaje()` y `Pago::registrarLineaEquipaje()` (cargo + pago simultáneos al crear, sobre `pagos.idequipaje` ya existente en el esquema); pestaña "Folio" de solo lectura en `show.php` (`partials/folio-equipaje.php`).
- **Partials de las vistas de equipaje**: `form-registro-equipaje.php`, `resumen-registro-equipaje.php`, `form-actualizar-equipaje.php`, `resumen-actualizar-equipaje.php`, `folio-equipaje.php` — `create.php`/`update.php`/`show.php` pasan a dispatchers delgados.
- **KPIs "En almacén" y "Vencidos"** en el index de equipaje (reemplazan "Equipajes hoy"/"Almacenados hoy").
- **Método de pago en el recibo de equipaje** (fila del PDF, tomado de la línea `pago` del folio).
- **Reglas globales de tabs inactivos** en `public/css/core/styles.css`: `.card-{color} .nav-tabs .nav-link:not(.active)` con variantes oscurecidas de la paleta AdminLTE (≥4.5:1 sobre header blanco); `core/styles.css` gana cache-busting `?v=filemtime` en `header.php`.

### Fixed

- **Autorización del recibo de equipaje**: patrón explícito `esAdministrador() || puedeAccederModulo()` (antes solo el segundo); los errores del PDF ya no filtran `getMessage()`/archivo/línea al usuario (van a `error_log()` + mensaje genérico).
- **Equipaje retirado** ya no ofrece acciones de cambio de estado (aviso único, sin botones).
- **`htmlspecialchars(null)`** en las vistas de equipaje (`?? ''` / fallbacks de negocio) — deprecation PHP 8.1+ y posible ruptura de HTML con `display_errors` (select de clientes con `telefono` NULL).
- **Montos del equipaje fuera del cliente**: el monto al crear se recalcula en el servidor desde el precio real en BD (`getPrecioEquipaje()`); al editar, monto/cantidad/tipo/código salen del formulario (solo descriptivos) — antes eran editables tras el cobro.
- **Exports de la DataTable de equipaje**: índices de columnas desalineados (`[0..7]` → `[0..6]`) y extracción de badge solo en la columna de estado.
- **Exports de la DataTable de usuarios**: índices desalineados (`[0..5]` → `[0,1,2,3,4,6,7]`).
- **Usuarios — accesibilidad y semántica**: `aria-hidden` en 114 iconos decorativos, `role="tablist"/"tab"/"tabpanel"` y `aria-live` en feedback de contraseña, `<a>` sin `href` → `<span>` (correo/teléfono/documento), `src="#"` → `""` en la preview de imagen, enlace WhatsApp que normaliza el número (`preg_replace` quitando no-dígitos y el 0 inicial).
- **Tabs inactivos con contraste <4.5:1** hardcodeados por módulo (`show-habitaciones.css`, `show-producto.css`, `usuarios.css` con `#detail-tabs`/`#habitacionTabs`) → reglas globales por clase de card.

### Changed

- `views/almacenamiento-equipaje/create.php`/`update.php`/`show.php` reescritos sobre partials (−1438 / +540 líneas en el módulo); `update-equipaje.js` simplificado (sin recálculo de monto en el cliente); `show-equipaje.js` persiste la pestaña activa en `sessionStorage`.
- `perfil-usuario.js` reestructurado (un solo `DOMContentLoaded`, guards de `null`, validación de contraseña con feedback `aria-live`).
- Listado de equipaje con `estado_ui` del helper (sin `switch` en la vista).

### Removed

- Overrides de tabs por módulo/ID: archivos completos `show-habitaciones.css` y `show-producto.css`; reglas `#detail-tabs` y variables `--usuarios-info*` de `usuarios.css`.

### Docs

- `CLAUDE.md`: convención de tabs globales por clase de card; cache-busting ampliado a `core/styles.css`; regla de `htmlspecialchars` null-safe en PHP 8.1+; helpers de estado y folio de equipaje.
- `PROMPTS.md`: restricciones de estado/folio de equipaje en la plantilla base; fecha actualizada.
- `README.md`: badge a 1.5.0; descripción del equipaje con folio y estados.
- `.env.example`: `APP_VERSION` sincronizado a `1.5.0`.

### Migración

Ninguna: `pagos.idequipaje` (columna, índice y FK) ya existen en `database/db_hotel_flow.sql`; no hay DDL nuevo. El umbral nuevo es opcional (`EQUIPAJE_DIAS_ALERTA`, default `7`).

## [1.4.1] - 2026-09-19

Refactor del módulo Ventas al estándar POS (patrón FlowPOS adaptado): folio de cobro con pago único/mixto, creación y detalle en dos columnas con modales, recibo térmico PDF, listado con pago mixto y exports corregidos. El reporte por fechas se eliminó — lo cubren los exports de la DataTable.

### Added

- **Tabla `pagoventa` (ledger del cobro, append-only)** con CHECK `metodopago IN ('Efectivo','QR','Otros','Mixto')`; `venta.metodopago` pasa de `enum` a `varchar(20)` y `venta.observacion` (text). `metodopago`/`pagorecibido`/`cambio` de `venta` pasan a cache derivado recalculado en la misma transacción; backfill idempotente para ventas existentes (1 fila por venta, `WHERE NOT EXISTS`).
- **`create.php` en dos columnas POS** (`col-lg-8` carrito con scroll + `col-lg-4` sidebar sticky): agregar producto y cliente por **modales** con búsqueda en vivo, pago **único o mixto** (tarjetas `metodo-pago-item` re-numerables), total pagado con "Falta por pagar / Sobrepago / Pago completo" (`aria-live`), empty-state de carrito, observaciones, `removeAttribute('id')` al clonar filas, `aria-invalid`/`aria-describedby` por fila, moneda `Bs.`, `$skip_datatables`.
- **Helpers únicos** `calcularTotales()`, `calcularInfoMetodoPago()`, `obtenerIconoMetodoPago()`, `esPagoMixto()`, `calcularDesgloseDetalles()`, `calcularResumenPagos()` en `VentaController`; el listado adjunta `info_metodo_pago` en batch (`Venta::getMetodosPagoPorVentas()`, sin N+1 por fila).
- **`views/ventas/recibo.php`**: ticket térmico TCPDF 80mm con doble pasada de altura (patrón `recepcion/recibo.php`) — detalle itemizado, bloque **PAGO** desglosado del ledger, total, moneda `Bs.`, "SON:" literal, QR, y estado visible (incl. anulada con aviso). Auth + permiso `ventas` + propiedad (admin OR dueño).
- **Cliente opcional en ventas**: `venta.idcliente` ahora es nullable; ventas sin cliente se muestran como "Consumidor Final" en listado, detalle y recibo.
- **`index.php`**: columna "Nro" eliminada, badge/tooltip de pago mixto (`badge-purple` + desglose), botón Imprimir (solo `estado==1`), `aria-label`/tooltips en acciones.
- **`show.php` en dos columnas**: tarjeta **Métodos de Pago** con desglose por pago y "Total Pagado" si mixto, tabla con Subtotal/Descuento/Total en el tfoot, observaciones y aviso de anulación. Anulación unificada en `public/js/modules/ventas/anular-venta.js` (compartida con el listado, sin `accion=anular`).

### Fixed

- **Exports de la DataTable tras quitar la columna "Nro"**: índices de `columns`/`customize`/`format.body` desalineados y `replace('S/ ','')` muerto — exportaban filas con los totales corridos. El formato del cuerpo también exportaba el método de pago y el estado como texto visible (antes vacíos según la columna).
- **Pago mixto que no creaba el primer método**: el guard que decidía si ya había `metodo-pago-item` contaba la plantilla oculta y dejaba la sección vacía sin error (el conteo pasa a escoparse al contenedor real).
- **Tooltips del listado muertos al paginar/buscar**: DataTable recrea las filas en cada redraw; los `data-toggle="tooltip"` se re-vinculan en `drawCallback`.
- **IDs de fila duplicados** al clonar en el carrito y moneda `$` → `Bs.` en confirmaciones y recibo.
- **Auditoría accesibilidad del módulo Ventas**: score 16/20 → 19/20. Labels con `for` en todos los campos de pago, `aria-label` en inputs del carrito clonados, `role="status"` en badges de estado, label accesible para `idcliente`, tooltips del paginador DataTables inicializados correctamente.
- **"Cliente Top" en el index crasheaba** cuando había ventas sin cliente (`htmlspecialchars(NULL)`); ahora muestra "Sin cliente".

### Changed

- `Venta::getById()` adjunta `pagos`; `crear()` inserta una fila de `pagoventa` por pago y recalcula el cache de `venta` en la misma transacción; `anular()` marca `estado=0` en sus `pagoventa`.
- Endpoint de creación seguro: `idusuario` sale de `$_SESSION['usuario_id']`, nunca del formulario (hidden eliminado).
- Columna "Nro" del listado eliminada (contador redundante frente al id de venta); el método del badge se resuelve con `esPagoMixto()` (métodos distintos > 1), no con el cache.
- Sidebar Ventas: eliminado "Reporte de ventas" (los exports de la DataTable cubren el reporte por fechas).

### Removed

- `views/ventas/reporte.php`, `controllers/ventas/reporte_ventas.php` y su enlace del sidebar.
- Métodos muertos: `VentaController::generarTicket()` (reemplazado por `recibo.php` vía `ver()`), `obtenerPorRangoFechas`/`obtenerPorEstado`/`obtenerPorCliente`, y `Venta::getPorRangoFechas`/`getPorEstado`/`getPorCliente`.

### Migración

En bases existentes, aplicar el DDL de creación del ledger (crear `pagoventa` con PK/FK/CHECK, `ALTER venta` para `varchar(20)` + `observacion`, y el backfill idempotente) — lenguaje exacto en el commit `6a3186d`.

## [1.3.2] - 2026-09-09

Auditoría `impeccable` del frontend de Habitaciones: score 12/20 → 18/20. Unificación del vocabulario de estado, del flujo de cambio de estado y de la responsividad del listado; sin cambios de esquema.

### Added

- **Helper `HabitacionController::estadoHabitacion(string): array`** (`['label','clase','badge','icono','orden']`) y `estadosHabitacion()` — fuente única del vocabulario visual de estado del módulo, mismo patrón que `RecepcionController::estadoRecepcion()`.
- **Helpers `HabitacionController::badgeEstadoOcupacion()` / `badgeEstadoLimpieza()`** (`['badge','label']`) — estados del historial de `show.php`, antes resueltos con `switch` en la vista.
- `public/js/modules/habitaciones/cambiar-estado-habitaciones.js` — flujo único de cambio de estado (confirmación SweetAlert + AJAX + `location.reload()`), enlace delegado a `.cambiar-estado`, compartido por listado y detalle.
- `public/css/modules/habitaciones/habitaciones.css` — `.filtro-estado` (tarjetas de resumen que actúan como filtro) y `.hab-touch` (área táctil ≥44px, patrón `::before` de `.rec-touch`).
- **`sidebarEnSeccion($hrefBase, $rutaActual)`** en `views/layouts/sidebar.php` — match por prefijo para resaltar/auto-expandir un grupo también en sus subpáginas (`create`/`update`/`show`).
- **Cache-busting de assets de módulo**: `header.php` y `footer.php` sirven cada `$module_styles`/`$module_scripts` con `?v=<filemtime>` (fallback `$APP_VERSION`).

### Fixed

- **`show.php` enlazaba a `edit.php`** (archivo inexistente) → `update.php`.
- **`update.php`**: `$iconos[$estado]` / `$clases_estado[$estado]` sin fallback ante un estado inesperado → helper único con rama `default`.
- **Sidebar**: el grupo Recepción y "Estado de habitaciones" no se resaltaban ni auto-expandían al entrar directo a `create`/`update`/`show` (match exacto de ruta) → `sidebarEnSeccion()`.
- **Assets de módulo cacheados**: sin versionado, el navegador servía JS/CSS viejo tras cada edición.
- **Áreas táctiles <44px** en los botones-ícono del listado → `.hab-touch` con `min-width:44px` (ancho AAA, WCAG 2.5.5).
- **Tarjetas-filtro** (`<a role="button">`) no se activaban con la tecla Espacio → handler `keydown` (Enter ya era nativo).

### Changed

- **4 `switch` divergentes de estado** (index, show, update ×2, ambos JS) → helper único `estadoHabitacion()`. Vocabulario de color alineado con `recepcion`: limpieza = gris "Por limpiar" (antes azul).
- **`index.php` / `index-habitaciones.js` reescritos**: eliminado el 2º sistema responsive (`ajustarVistaMovil()`) y las ~60 líneas de reconstrucción manual de fila; `columnDefs` con `responsivePriority`; las tarjetas info-box de `#resumen-estados` son el filtro de estado (se quitó la card "Vista Rápida por Estado" redundante); `#filtro-estado` de `<select>` a `<input type="hidden">`; columna "Nro" (contador redundante frente a "Habitación") eliminada.
- **`show.php`**: eliminado el modal `#modalCambiarEstado`; "Detalles del Tipo" movido a la columna derecha; `<a class="float-right">` sin `href` → `<span>`.
- Cards `card-primary`/`card-warning` (filled) → `card-outline` en `create`/`update`/`show`. Moneda `$` → `Bs`. Pluralización "1 persona" / "N personas". `aria-label` en los botones-ícono. "Nueva Habitación" salió del card colapsado al header del listado. `<option>` de estado: "Limpieza" → "Por limpiar". `create.php`: callouts de ayuda redundantes eliminados.

### Removed

- `public/js/modules/habitaciones/show-habitaciones.js` — su flujo vive ahora en `cambiar-estado-habitaciones.js`.

## [1.3.1] - 2026-08-30

Auditoría `impeccable` del frontend de Recepción (post 1.3.0): score 14/20 → 20/20.

### Added

- **Helper `RecepcionController::etiquetaTarifa(array $r): string`**: fuente única de la etiqueta de tarifa de una estancia ("1 día(s) · Bs 150.00"), mismo patrón que `estadoRecepcion()`. Usado por `show.php`, `tarjeta-registro.php` y `recibo.php`.
- `Recepcion::getById()` ahora selecciona `th.nombre as tipo_nombre` y `t.precio as precio_tarifa`.
- Panel derecho sticky `.rec-update-acciones` en `update.php` (Guardar/Cancelar + nota del folio, solo ≥992px).
- Tokens de color del módulo en `:root` de `recepcion.css` (`--rec-muted`, `--rec-focus-ring`, `--rec-tint-*`, `--rec-ink`, `--rec-surface`, etc.); `create-recepcion.css` y `update-recepcion.css` los consumen. Cero cambio visual.

### Fixed

- **Tipo de habitación siempre "Estándar"**: `show.php` y `tarjeta-registro.php` leían `$recepcion['tipo_habitacion']`, clave que `getById()` nunca seleccionó → todas las habitaciones caían al fallback. Ahora usan `tipo_nombre`.
- **"Tarifa: dias"**: las 3 vistas imprimían `tipo_estancia` crudo en vez de una etiqueta legible. Resuelto con `etiquetaTarifa()`.
- **Campo readonly de habitación en `update.php`** mostraba `201 - dias`; ahora `201 — Suite`.
- Contraste AA: `text-muted` en filas zebra del historial (≈4.0:1) → clase `.rec-hist-sub` (#58606a, ≥5:1).
- Áreas táctiles <44px en los botones de acción de `tab-historial.php` y `folio.php` → clase `.rec-touch`.
- `show.php`: `onclick="return confirm()"` en Check-in/Cancelar → SweetAlert (`.rec-confirm-nav` + handler delegado en `show-recepcion.js`).
- `tile-habitacion.php`: quitado `badge-sm` (no existe en Bootstrap 4).
- `show-recepcion.js`: borrada línea muerta `$('.card').addClass('card-animation')`.

### Changed

- **`update.php` −187 líneas**: eliminados los bloques `#cliente-info`/`#habitacion-info`/`#tarifa-info` (repetían el `<option>` seleccionado), el panel "Resumen de Recepción" (espejo del form), la card "Información de Ayuda" y el banner `fa-3x`. `update-recepcion.js` −69 líneas (handlers de elementos borrados); `update-recepcion.css` reescrito lean.
- `folio.php`: tabla en `.table-responsive`; columna "Registrado por" oculta en móvil (`d-none d-sm-table-cell`); monto y fecha sin wrap en desktop.
- `index.php`: `card-primary` en `card-outline-tabs` (el tab activo no tenía acento de color).
- `form-reserva.php`: `style="display:none"` → `.d-none` (con `!important`, `create-recepcion.js` usa `.toggleClass('d-none', …)`).
- Títulos a formato oración: "Folio de huésped", "Cambio de habitación".
- `buscador-global.php`: `style="min-width:260px"` → clase `.rec-buscador`.

## [1.3.0] - 2026-08-27

Refactor del frontend del módulo Recepción al estándar de un PMS real (Cloudbeds / Mews / Little Hotelier), sin salir de AdminLTE 3 + Bootstrap 4.

### Added

- **Vista unificada de Recepción**: `views/recepcion/index.php` pasa a una sola vista con tabs `Hoy` / `Mapa` / `Historial` (hash persistido en `sessionStorage`). Nuevos partials `tab-hoy.php` (llegadas / salidas previstas / en casa con contadores y acción inline), `tab-mapa.php`, `tab-historial.php`, `kpi-bar.php` (6 KPIs del día), `fila-movimiento.php` y `buscador-global.php` (Select2 remoto de huésped/reserva presente en index, show y create).
- **Room rack denso**: `partials/tile-habitacion.php` — un tile parametrizado (`col-6 col-sm-4 col-md-3 col-lg-2`, alto ≤96px) con doble dimensión ocupación + housekeeping; un hotel de 36 habitaciones entra en un viewport 1366×768. Reemplaza las 5 ramas de `card-habitacion.php`.
- **Helper de estado único**: `RecepcionController::estadoRecepcion()` (label/clase/badge/icono/orden) y `estadoDerivado()` (`no_show`, `salida_vencida` resueltos contra la hora actual). Fuente única de vocabulario y color de estado para todas las vistas del módulo.
- **Consultas de front desk** en `models/Recepcion.php`: `getSalidasHoy()`, `getInHouse()` (saldo real del folio por subconsulta), `getMapaHabitaciones()` (una fila por habitación), `getKpisDia()` (ocupación %, ADR estándar hotelero, ingresos cobrados hoy, pendientes, sucias), `existeSolape()` y `buscarGlobal()`.
- **Orquestación en `RecepcionController`**: `hoy()`, `mapa()`, `kpis()`, `historial()`, `buscar()`, `panel()` (único que dispara `liberarNoShows()`, llamado una sola vez por carga), `checkout()` y `actualizarEstancia()` (whitelist estricta).
- **`create.php` como página única** con panel resumen sticky en vivo (patrón Little Hotelier): nuevos partials `form-reserva.php` y `resumen-reserva.php`; desaparece el wizard de pasos y el "paso 3 Confirmación" fantasma.
- **`show.php`**: cabecera compacta (identidad + saldo en una línea) + action bar `sticky-top` con los botones filtrados por estado; nueva `views/recepcion/tarjeta-registro.php` imprimible.
- **Endpoints** `controllers/recepcion/checkout_ajax.php`, `buscar_ajax.php` y `disponibilidad_ajax.php` (todos con `requireLogin` + autorización explícita + CSRF).
- Índices `idx_recepcion_estado_entrada (estado, fechaentrada)` e `idx_recepcion_estado_salida (estado, fechasalida_prevista)` en `recepcion`.
- Accesibilidad: `aria-label` en botones solo-icono del tab Hoy y del rack, `aria-live="polite"` en los contadores, foco visible por teclado en tiles y filas, áreas táctiles ≥44px con el patrón `::before`.

### Changed

- `views/recepcion/show.php` (563 → ~230 líneas): se eliminan las 4 representaciones redundantes del dinero (trío `small-box`, `info-box` de método/tarifa, bloque de efectivo, progress bar); el saldo vive solo en la cabecera y el detalle solo en `partials/folio.php` (con columna Usuario + hora).
- `views/recepcion/update.php`: reducida a datos de estancia; **cero campos de dinero**. El ajuste financiero va por el folio, el cambio de habitación por su flujo auditado.
- El check-out del rack y de la lista usan el mismo flujo POST que `show.php` (antes eran `<a href>` GET con reglas distintas y sin comprobar saldo).
- `validarDatos()` unificado al valor de enum real `OTROS` para `metodopago` (antes enviaba `Otros`, funcionaba solo por colación `_ci`).
- `cambiar_estado.php`: redirección validada con `str_starts_with($referer, $URL)` (antes `strpos(...) !== false`, open redirect de baja severidad); `guardar_checkin.php` verifica CSRF incondicionalmente; `actualizar_recepcion.php` castea `idrecepcion` a `int` y valida `> 0`.

### Fixed

- **Check-out roto**: `show.php` enlazaba a `views/recepcion/checkout.php` (archivo inexistente → 404) siempre que hubiera saldo pendiente. Reemplazado por `checkout_ajax.php` con cobro del saldo en la misma transacción.
- **Folio-as-ledger**: `update.php` escribía `montototal`/`montopagado`/`cambio` directo sobre `recepcion` vía `actualizar()`, desincronizando el cache respecto de la tabla `pagos`. Eliminado.
- **Overbooking**: `Recepcion::crear()` y `Recepcion::actualizar()` rechazan reservas/estancias cuyo rango de fechas se solapa con otra de la misma habitación (comprobado dentro de la transacción, tras el `FOR UPDATE` sobre `habitaciones`).
- Housekeeping invisible: una habitación en `limpieza` ("por limpiar") ya se distingue visualmente de una en `mantenimiento` ("fuera de servicio").

### Removed

- `views/recepcion/lista-recepciones.php` y sus assets (`lista-recepciones.js`, `lista-recepciones.css`) — el panel y el historial estaban solapados. `partials/card-habitacion.php`, `paso-seleccion-habitacion.php`, `paso-datos-checkin.php`, `index-recepciones.css`. Redirección legacy `?redirect=historial` → `index.php#historial` durante esta release.
- Trigger `tr_exact_time_check` (BEFORE UPDATE ON `recepcion`): forzaba check-out fantasma desde la BD saltándose el folio y el orden de bloqueo. **Paso manual en bases existentes:** `DROP TRIGGER IF EXISTS \`tr_exact_time_check\`;`

### Migración

En bases existentes, además del `DROP TRIGGER` de arriba:

```sql
ALTER TABLE `recepcion`
  ADD KEY `idx_recepcion_estado_entrada` (`estado`,`fechaentrada`),
  ADD KEY `idx_recepcion_estado_salida` (`estado`,`fechasalida_prevista`);
```

## [1.2.0] - 2026-08-19

Refactorización del módulo Recepción al estándar funcional/arquitectónico de un PMS real (folio de huésped, wizard de 2 pasos, robustez de concurrencia, cambio de habitación auditable, llegadas/reservas y liberación automática de no-show). Plan completo documentado y ejecutado en 6 fases atómicas.

### Added

- **Folio de huésped real**: tabla `pagos` activada como libro mayor append-only (`tipo` `cargo|pago|reverso`, `concepto`, `idusuario`, `id_pago_reversado`); `recepcion.montototal`/`montopagado` pasan a ser un cache recalculado en la misma transacción. Nuevo modelo `models/Pago.php`, endpoints `registrar_pago_ajax.php`/`registrar_cargo_ajax.php`, partial `views/recepcion/partials/folio.php`. Backfill idempotente para recepciones previas a la migración.
- **Wizard de check-in de 2 pasos**: `views/recepcion/create.php` (647 → ~116 líneas) pasa a ser un despachador delgado sobre `partials/paso-seleccion-habitacion.php` y `partials/paso-datos-checkin.php`, mismo contrato `?idhabitacion=`.
- **Partial de tarjeta de habitación reutilizable** (`views/recepcion/partials/card-habitacion.php`, modos `disponible|ocupada|mantenimiento|seleccion|reserva`), elimina 4 copias casi idénticas del markup entre `index.php` y `create.php`.
- **Cambio de habitación auditable**: `Recepcion::cambiarHabitacion()` mueve una estancia `en_curso` a otra habitación, libera la anterior, registra el movimiento en la nueva tabla `recepcion_movimientos` y carga al folio la diferencia de tarifa si la habitación destino es más cara. Endpoint `cambiar_habitacion_ajax.php`, partial `views/recepcion/partials/cambio-habitacion.php` con historial.
- **Llegadas de hoy y reservas próximas**: nueva sección en `views/recepcion/index.php` que distingue reservas (`estado='reservado'`) de estancias en curso, con `Recepcion::getLlegadasHoy()`/`getReservasProximas()`.
- **Liberación automática de no-show**: `Recepcion::liberarNoShows()` cancela, fail-open y de forma determinista al cargar el panel de recepción, las reservas sin check-in vencidas por más de `RECEPCION_NOSHOW_HORAS` horas (nuevo, default 6, configurable vía `.env`/`config/config.php`).

### Fixed

- Condición de carrera cerrada en `Recepcion::cambiarEstado()`/`actualizar()`: ambos métodos ahora bloquean la fila de `recepcion` (`FOR UPDATE`) y la de `habitaciones` antes de decidir una transición de estado, con orden canónico de bloqueo `recepcion` → `habitaciones` (mismo criterio que ya usaba `crear()`).
- Regresión visual de 1.1.4: el modal de confirmación de `create.php` mostraba el precio con `$` en vez del badge "Bs" usado en el resto del módulo.
- `?idhabitacion=` inexistente/ocupado en `create.php` causaba warnings de PHP en vez de redirigir; ahora valida y redirige antes de `include header.php`.

### Docs

- `CLAUDE.md`: documentadas las reglas de folio-como-cache, orden canónico de bloqueo `recepcion` → `habitaciones`, y la convención de partials con docblock de dependencias (`views/[modulo]/partials/*.php`).

## [1.1.4] - 2026-08-12

Extracción del sidebar a su propio archivo con reorganización de grupos, extracción de CSS embebido restante en `recepcion`, y barrido de seguridad/accesibilidad en todas las vistas.

### Changed

- `views/layouts/sidebar.php` extraído de `header.php` (ya no vive inline dentro del layout); resalta automáticamente el link/grupo activo comparando la ruta normalizada de `$_SERVER['REQUEST_URI']` contra cada `href`, y auto-expande el grupo correcto al entrar directo por URL.
- Grupos del sidebar realineados a la convención estándar de un PMS (triada Front Desk/Housekeeping/POS): **Limpieza** sale de "Servicios" a su propio `<li>` de primer nivel (rol dedicado, sin submenú); **Productos** y **Compras** se fusionan en **Inventario y Compras**.
- `views/recepcion/lista-recepciones.php` y `views/recepcion/show-recepcion` ganan hoja de estilos dedicada (`public/css/modules/recepciones/lista-recepciones.css`, `show-recepcion.css`) declarada vía `$module_styles`.
- Símbolo de moneda corregido de `$` a `Bs` (boliviano) en `views/recepcion/create.php` y `views/recepcion/lista-recepciones.php`.
- Accesibilidad: `aria-label` añadido a botones de icono sin texto (`data-card-widget="collapse"`, acciones de tabla en `lista-recepciones.php`, botón de cerrar modal) y `loading="lazy"` a la imagen de perfil de cliente en `recepcion/show.php`.

### Security

- Barrido del patrón de autorización explícito (`esAdministrador() || puedeAccederModulo()`) extendido a las vistas de `habitaciones`, `almacenamiento-equipaje`, `clientes`, `productos`, `servicios-bano`, `recepcion`, `limpieza`, `banos`, `categorias` y `precios-equipaje`.
- Reemplazado el patrón manual `if (session_status() == PHP_SESSION_NONE) { session_start(); }` por `requireLogin()` en las vistas que aún lo usaban — `requireLogin()` ya inicia sesión además de verificar autenticación, así que el check manual dejaba una ventana sin verificar auth.
- `views/recepcion/recibo.php`: valores dinámicos (nombre de cliente, habitación, método de pago, observaciones, datos de la empresa) ahora pasan por `htmlspecialchars()` antes de insertarse en el PDF; los `catch` de generación de PDF ya no filtran `$e->getMessage()`/archivo/línea al usuario — se loguean con `error_log()` y se devuelve un mensaje genérico.

### Docs

- `CLAUDE.md` actualizado: sección de sidebar reescrita para reflejar la extracción a `sidebar.php` y la reorganización de grupos; nota añadida a la regla de "verificación de autorización explícita" documentando el alcance de este barrido y el reemplazo de `session_start()` manual por `requireLogin()`.

## [1.1.3] - 2026-08-10

Auditoría de UX/accesibilidad del módulo Dashboard (administrador, recepcionista, limpieza) vía `/impeccable audit`, 13/20 → 19/20.

### Fixed

- **Integridad de datos**: los gráficos del dashboard de administrador (servicios de baño, equipajes, ocupación, ingresos y estado de baños) sustituían silenciosamente datos inventados (ej. `[5,7,3,8,6,9,5]`) cuando las estadísticas reales venían vacías, indistinguibles para un administrador de datos reales. `administrador_dashboard.php` y `dashboard-admin.js` ahora usan ceros honestos como fallback, y el gráfico de estado de baños muestra un mensaje explícito ("No hay datos de baños disponibles.") en vez de una dona con datos ficticios.
- `RecepcionistaController`/`limpieza_dashboard.php`/`recepcionista_dashboard.php`: agregado `default:` a 4 bloques `switch($estado)` que incumplían la convención de "switch exhaustivo" ya documentada en CLAUDE.md.
- Accesibilidad: agregado `<label class="sr-only">`/`aria-label` a los 4 buscadores sin etiqueta de los dashboards de recepcionista y limpieza; agregado `aria-pressed` (sincronizado por JS) a todos los grupos de botones de filtro/métrica de los 3 dashboards por rol.

### Changed

- Eliminada la función `ajustarVistaResponsiva()` (~115 líneas) de `dashboard-admin.js`, que reescribía estilos inline vía jQuery `.css()` en competencia con los media queries CSS existentes. El único comportamiento no duplicado (tamaño de las celdas del mapa de habitaciones por breakpoint) se trasladó a `dashboard-admin.css`; de paso se corrigió un selector `.d-flex.justify-content-between.align-items-center` sin ámbito que afectaba encabezados no relacionados en móvil — ahora escapado a `.habitaciones-mapa`.
- Eliminado el último `<script>` inline del proyecto, en `index.php` (llamaba a `initializeTooltips()`/`initializeSelect2()`, ambas redundantes: ningún dashboard usa Select2 y el de recepcionista ya se auto-inicializa los tooltips).
- Eliminadas 3 barras de progreso decorativas ancladas en `width: 100%` (Limpiezas Pendientes, Clientes Registrados, Ingresos Totales del dashboard de administrador) que sugerían una métrica real inexistente; se conservaron las 2 legítimas (ocupación %, stock bajo %).

### Docs

- Memoria de proyecto: auditoría de dashboard cerrada (19/20); el hueco de Theming (3/4) se confirma intencional — el proyecto entero usa AdminLTE en modo claro sin sistema de dark mode, no es un defecto propio del dashboard.

## [1.1.2] - 2026-07-27

Segunda pasada de limpieza de JS inline (sin excepción para constantes únicas), rediseño del login, y mapa de habitaciones por piso en el dashboard de administrador.

### Changed

- Eliminados los últimos 5 `<script>` inline que se habían dejado como "excepción aceptable" por declarar una sola constante (`compras/create.php`, `servicios-bano/create.php`, `productos/update.php`, `productos/show.php`, `productos/buscar_codigo.php`). El dato ahora se expone vía atributo `data-*` en el `<form>` o en el elemento disparador de la acción (`.cambiar-estado-link`, `#btnCambiarEstado`) y el JS lo lee con `dataset`/`JSON.parse` en vez de una constante global. CLAUDE.md actualizado: ya no existe ninguna excepción para JS inline en vistas con el layout compartido.
- Rediseño visual y de accesibilidad de `views/login/login.php`: nuevo logo SVG (`public/img/hotel-logo.svg`, reemplaza `hotel.png` como favicon en `views/layouts/header.php`), fondo claro acorde a AdminLTE, paleta de colores centralizada en variables CSS (`--login-color-*`), validación de formulario con `aria-invalid`/`aria-describedby` en vez de solo la clase `is-invalid`, y eliminada la validación de longitud mínima de contraseña en el cliente (la valida el servidor).
- Dashboard de administrador: nuevo "Mapa de Habitaciones" agrupado por piso (`DashboardController::ordenarHabitacionesPorPisoYNumero()` y `agruparHabitacionesPorPiso()`, expuestos como `habitaciones_por_piso` en `$stats`); el bloque `dashboardData` que antes se inyectaba vía `<script>` ahora se sirve como `data-dashboard` en `<div id="dashboard-admin-root">`.
- Botones `data-card-widget="collapse"` de AdminLTE ganan `aria-label="Contraer/expandir panel"` en las vistas de dashboard para lectores de pantalla.

### Fixed

- `views/layouts/mensajes.php`: el toast de `$_SESSION['mensaje']`/`icono` interpolaba el mensaje directamente dentro de un string JS entre comillas dobles (`title: "<?php echo $respuesta; ?>"`); un mensaje de negocio con comillas (ej. `Producto "Rollo de papel" no configurado`, devuelto por `ServicioBanoController::validarDisponibilidadServicio()`) rompía el `<script>` con `SyntaxError: missing } after property list`, dejando la página sin JS funcional. Se cambió a `json_encode()` para ambos valores (`icon`/`title`), consistente con el resto del proyecto. Encontrado navegando `servicios-bano/create.php` con el producto "Rollo de papel" no configurado.
- `public/js/modules/servicios-bano/create-servicios-bano.js`: accedía a `formServicioBano.dataset` sin comprobar que el formulario existiera; en la rama "Servicio No Disponible" de `servicios-bano/create.php` (sin `<form>`) esto lanzaba `TypeError: formServicioBano is null` y abortaba el script. Se agregó una verificación temprana (`if (!formServicioBano) return;`) — regresión introducida en el cambio de `data-*` de esta misma versión.

### Docs

- CLAUDE.md: reescrita la sección "No dejar JS inline en las vistas" para eliminar la excepción de "constante única" documentada en 1.1.1 y dejar constancia de que no queda ninguna excepción vigente.

## [1.1.1] - 2026-07-27

Refactorización de JavaScript inline hacia módulos externos (`public/js/modules/`) en todas las vistas restantes, junto con una corrección de arquitectura MVC en el flujo de recepción (check-in).

### Changed

- Eliminado el JS inline embebido en `<script>` de 25+ vistas (`habitaciones`, `productos`, `compras`, `ventas`, `tarifas`, `tipohabitacion`, `clientes`, `recepcion`, `dashboard` de limpieza/recepcionista, `login`, `buscar_codigo`, etc.) y trasladado a archivos dedicados en `public/js/modules/[modulo]/`, siguiendo la convención ya usada en el resto del proyecto (`js/modules/[modulo]/[vista]-[modulo].js`).
- Las vistas ahora exponen los datos que el JS necesita vía atributos `data-*` en el HTML (ej. `data-module="recepcion-create"`, `data-step="select-room"`) en vez de `<script>` con lógica de negocio y interpolación PHP mezclada; los módulos JS leen esos atributos con `dataset`.
- Nuevos módulos JS creados: `clientes/show-persona.js`, `compras/create-compras.js`, `compras/show-compras.js`, `dashboard/dashboard-limpieza.js`, `dashboard/dashboard-recepcionista.js`, `habitaciones/create-habitaciones.js`, `habitaciones/show-habitaciones.js`, `habitaciones/update-habitaciones.js`, `login/*`, `productos/buscar-codigo.js`, `productos/show-producto.js`, `recepciones/lista-recepciones.js`, `recepciones/show-recepcion.js`, `recepciones/update-recepcion.js`, `tarifas/show-tarifas.js`, `tipohabitacion/show-tipo-habitacion.js`, `ventas/show-venta.js`.
- `index.php` ahora declara `$skip_datatables = true` (el dashboard no usa `<table>`/`DataTable()`); ya declaraba `$skip_select2`.

### Fixed

- **Bug de arquitectura MVC**: `views/recepcion/create.php` definía y ejecutaba una función PHP (`agruparHabitacionesPorPiso()`) y la consulta de habitaciones disponibles directamente en la vista, en vez de en el controlador/modelo — violación del patrón MVC del proyecto. Se trasladó la lógica a `RecepcionController::crear()` (que ahora también devuelve `habitaciones_disponibles`, `habitaciones_por_piso` y `pisos_unicos`) y al nuevo método estático `RecepcionController::agruparHabitacionesPorPiso()`, que además prioriza habitaciones privadas/individuales al ordenar.
- `models/Piso.php` gana `contarHabitacionesPorPiso()` (conteo de habitaciones agrupado por `idpiso`, con manejo de error estándar sin filtrar `getMessage()`), expuesto vía `PisoController::obtenerConteoHabitacionesPorPiso()`.

### Docs

- Documentada en CLAUDE.md la convención de exponer datos a JS externo vía `data-*` en el HTML en vez de `<script>` inline con PHP embebido.

## [1.1.0] - 2026-07-25

Auditoría de seguridad y calidad de todo el proyecto (OWASP Top 10 + patrones documentados en CLAUDE.md), en tres fases (P1/P2/P3) más una re-auditoría final y pruebas manuales end-to-end.

### Security

- Patrón de autorización explícito (`esAdministrador() || puedeAccederModulo()`) extendido a las vistas de tarifas, tipohabitacion, ventas y compras (antes solo se aplicaba en controladores AJAX; las vistas usaban el método `tieneAccesoCritico()`, que exige admin AND permiso en vez de admin OR permiso), y a `controllers/limpieza/obtener_habitaciones_ajax.php`.
- Scoping por usuario: un no-administrador en ventas/compras ahora solo ve sus propios registros en `index.php`, y `show.php` rechaza el acceso a registros ajenos (protección IDOR) en vez de mostrarlos por ID.
- Eliminados los flujos de creación duplicados `views/ventas/nueva.php`/`controllers/ventas/nueva_venta.php` y `views/compras/ingresar.php`/`controllers/compras/ingresar_compra.php` (casi idénticos a `create.php`); el aislamiento por rol que buscaban ahora lo da el scoping de índice/detalle en vez de una vista de creación separada.
- Condición de carrera cerrada en `Producto::reducirStock()` (ahora bloquea la fila con `SELECT ... FOR UPDATE`) y en `Compra::cancelar()` (mismo patrón que `Venta::anular()`).
- `Recepcion::crear()` recalcula `montototal` en el servidor a partir de la tarifa real en BD (con `FOR UPDATE`) en vez de confiar en el total enviado por el cliente; el estado inicial se restringe a `reservado`/`en_curso`.
- `Venta::crear()` recalcula `cambio` a partir del pago recibido y el total real, en vez de confiar en el valor enviado por el cliente; `Venta::validarDatos()` ya no deja que un `cambio` manipulado enmascare un pago insuficiente.
- `Compra::validarDatos()` rechaza `preciocompra` negativo.
- Eliminada la fuga de `PDOException::getMessage()` al cliente en `Venta::actualizarStockProducto()`, `Producto::verificarStock()` y `config/conexion.php` (ahora loguean con `error_log()` y devuelven/mueren con mensaje genérico).
- `Usuario::getAll()`/`getById()` ya no exponen el hash de contraseña (`SELECT *` reemplazado por columnas explícitas).
- Cerrado el side-channel de timing en el login: cuando el identificador no existe, ahora se ejecuta igual `password_verify()` contra un hash dummy, evitando enumeración de usuarios por tiempo de respuesta.
- Cookies de sesión endurecidas (`HttpOnly`, `SameSite=Lax`, `Secure` cuando hay HTTPS) vía `session_set_cookie_params()` antes de `session_start()`.
- CSRF cambiado de "aceptar si el token es válido" a "rechazar si es inválido o falta" en `desactivar_tarifa.php`, `desactivar_tipo_habitacion.php` y `cambiar_estado_servicio.php`, con mensaje de error explícito.
- `DEBUG` de `.env` ahora controla `display_errors`/`error_reporting` vía `config/config.php`.
- Eliminados `prueba_lector.php` y `prueba_producto_db.php`: archivos de desarrollo en la raíz del proyecto sin `requireLogin()` ni CSRF, que exponían precios/stock de productos y filtraban `getMessage()` crudo en JSON.

### Fixed

- `switch` sin `default` en el listado de ventas (`views/ventas/index.php`).
- Regresión introducida durante la corrección de IDOR: `views/compras/show.php` incluía `header.php` (que ya emite HTML) antes de la validación de propiedad del registro, causando "headers already sent" en vez de redirigir cuando un no-administrador intentaba ver una compra ajena.
- `views/compras/create.php` definía localmente `.d-none { display: none !important; }`, clase sin uso real en esa vista que chocaba con la utilidad homónima de Bootstrap y ocultaba permanentemente el texto "Sistema de Gestión" del navbar en esa página.

### Changed

- Sidebar reorganizado: el grupo "Inventario y Ventas" (8 enlaces) se dividió en tres grupos más pequeños y temáticos — **Productos**, **Ventas** y **Compras**.
- Comentarios boilerplate `@author`/`@version` eliminados de `VentaController.php`, `Venta.php`, `UsuarioController.php` y `DashboardController.php`.

### Docs

- `CLAUDE.md`: documentado el patrón de scoping por usuario + protección IDOR para módulos con dueño; la regla de ordenar validaciones (incluyendo `header('Location: ...')`) antes de `include header.php`; la advertencia de no redefinir clases utilitarias de Bootstrap en `<style>` de página; y el criterio de tamaño para los grupos del sidebar.

## [1.0.4] - 2026-07-21

### Security

- Endpoints de asignaciones de limpieza y creación rápida de servicios de baño ahora verifican explícitamente `esAdministrador() || puedeAccederModulo()` en vez de depender solo de `puedeAccederModulo()` (no era explotable, pero rompía la convención documentada de autorización).
- Mensajes de error de login unificados a "Credenciales incorrectas" para correo/documento no registrado, cuenta desactivada y contraseña incorrecta — evita que un atacante pueda enumerar cuentas existentes por el mensaje de error devuelto.
- `AlmacenamientoEquipajeController::getDatosParaRecibo()` ya no filtra `PDOException::getMessage()` al usuario a través del arreglo de errores que consume `views/almacenamiento-equipaje/recibo.php` — el detalle sigue yendo a `error_log()`.

### Fixed

- `switch` sin `default` en `RecepcionController::cambiarEstado()`.
- Anchors rotos del menú de navegación en el README.

### Added

- Módulo de usuarios: toggle de estado (activo/inactivo) por AJAX, con CSRF y verificación de permisos.
- Componente de mostrar/ocultar contraseña centralizado y reutilizable en formularios (login, crear/actualizar usuario).
- Convención `$skip_datatables` / `$skip_select2` / `$skip_chartjs`: cada vista puede optar por no cargar DataTables, Select2 o ChartJS cuando no los usa, evitando bajar librerías innecesarias (antes se cargaban siempre en `header.php`/`footer.php` para toda vista). Extendida del módulo de usuarios a todas las vistas del sistema.
- Globals `BASE_URL`/`CSRF_TOKEN` expuestos a JS de módulo sin duplicar lógica por vista.

### Docs

- `CLAUDE.md` documenta la convención `$skip_datatables`/`$skip_select2`/`$skip_chartjs`, y las reglas de autorización explícita y de no filtrar errores acumulados en arreglos.
- Credenciales de acceso por defecto corregidas en README/CLAUDE.md: el campo de login pide correo o número de documento (`admin@hotelflow.local`), no un nombre de usuario (`admin`).

## [1.0.3] - 2026-07-20

### Security

- Rate-limiting de login por identificador (correo/documento) e IP, vía nueva tabla `intentos_login` y modelo `IntentoLogin` (ventana deslizante, fail-open ante error de BD). Umbrales configurables por `.env` (`LOGIN_RATE_LIMIT_VENTANA_MINUTOS`, `LOGIN_RATE_LIMIT_MAX_FALLOS_IDENTIFICADOR`, `LOGIN_RATE_LIMIT_MAX_FALLOS_IP`, `LOGIN_RATE_LIMIT_PURGA_HORAS`; defaults 15 min / 5 / 20 / 24h).
- Verificación de token CSRF de login unificada: se eliminó la copia local `verificarCSRFToken()`/`generarCSRFToken()` de `AuthController` (comparaba con `!==`) en favor de las funciones globales `verifyCSRFToken()`/`generateCSRFToken()` de `views/layouts/session.php`, que comparan con `hash_equals()`.
- `DEBUG` por defecto cambiado a `false` en `.env.example`.

### Docs

- `CLAUDE.md` documenta el patrón obligatorio de no filtrar `PDOException::getMessage()` a `$this->lastError` en modelos, y el nuevo flujo de rate-limiting de login.

## [1.0.2] - 2026-07-11

### Security

- Verificación de permisos por módulo y token CSRF obligatorio extendidos al resto de módulos (equipaje, baños, categorías, compras, habitaciones, limpieza, personas, pisos, precios de equipaje, productos, recepción, servicios de baño, tarifas, tipos de habitación, usuarios y ventas).
- Estados de servicio de baño migrados de un booleano sin sentido de dominio a un enum validado, recalculado siempre desde la base de datos.

### Fixed

- Columna `precio` faltante en la tabla de baños.
- Registro de equipaje permitía quedar bloqueado por una descripción obligatoria que no debía serlo.

### Docs

- Notas de configuración de permisos de subida de archivos en entornos de desarrollo.

## [1.0.1] - 2026-07-06

### Added

- Número de versión de la aplicación configurable por variable de entorno.

### Security

- Verificación de permisos por módulo y CSRF obligatorio añadidos a los endpoints principales (categorías, pisos, habitaciones, tarifas, tipos de habitación, usuarios, servicios de baño, baños, ventas, personas).
- Permisos por rol centralizados y corregidos para coincidir con los cargos reales de la base de datos.
- Totales de venta y compra recalculados en el servidor en vez de confiar en el valor enviado por el cliente; validaciones de stock y de anulación reforzadas contra condiciones de carrera.
- Validación de archivos subidos por contenido real en vez de metadata del cliente.
- Mensajes de excepción ya no se exponen al usuario final.

### Changed

- Configuración de zona horaria centralizada.
- Eliminados logs de depuración usados durante desarrollo.
- Validación de fechas en el proceso de recepción.

## [1.0.0] - 2026-07-03

Primera versión pública de HotelFlow.

### Added

- Estructura de base de datos separada en `database/db_hotel_flow.sql` (solo esquema, tablas y relaciones).
- Seeder `database/seed.sql` con datos de ejemplo: permisos base, usuario administrador (`admin` / `admin123`), pisos, tipos de habitación, habitaciones, tarifas, categorías/productos, precios de equipaje y baños.
- Licencia MIT (`LICENSE`).
- `PROMPTS.md` con plantillas de prompts para trabajar el repositorio con asistencia de IA.

### Changed

- Renombrado del proyecto y del repositorio remoto a **HotelFlow** (antes `SistemaGestionAlojamiento`).
- Nombre de la aplicación y URLs parametrizados vía variables de entorno (`APP_NAME`, `APP_URL`) en vez de estar hardcodeados.
- README, CLAUDE.md y PROMPTS.md actualizados para reflejar la nueva estructura de `database/` y el nombre del proyecto.

### Removed

- Referencias al nombre del cliente/negocio original en vistas, JS, CSS y plantillas de recibos.
- Dump SQL con datos reales de producción (`bdalojamiento.sql`).

### Security

- Verificado que no existan credenciales, datos personales ni información de negocio real en el código versionado.
- `.env` excluido de control de versiones; `.env.example` documentado con valores de ejemplo.

[1.8.1]: https://github.com/WorkTeam01/HotelFlow/compare/1.8.0...1.8.1
[1.8.0]: https://github.com/WorkTeam01/HotelFlow/compare/1.7.2...1.8.0
[1.7.2]: https://github.com/WorkTeam01/HotelFlow/compare/1.7.1...1.7.2
[1.7.1]: https://github.com/WorkTeam01/HotelFlow/compare/1.7.0...1.7.1
[1.7.0]: https://github.com/WorkTeam01/HotelFlow/compare/1.6.1...1.7.0
[1.6.1]: https://github.com/WorkTeam01/HotelFlow/compare/1.6.0...1.6.1
[1.6.0]: https://github.com/WorkTeam01/HotelFlow/compare/1.5.0...1.6.0
[1.5.0]: https://github.com/WorkTeam01/HotelFlow/compare/1.4.1...1.5.0
[1.2.0]: https://github.com/WorkTeam01/HotelFlow/compare/1.1.4...1.2.0
[1.4.1]: https://github.com/WorkTeam01/HotelFlow/compare/1.4.0...1.4.1
[1.1.4]: https://github.com/WorkTeam01/HotelFlow/compare/1.1.3...1.1.4
[1.1.3]: https://github.com/WorkTeam01/HotelFlow/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/WorkTeam01/HotelFlow/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/WorkTeam01/HotelFlow/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/WorkTeam01/HotelFlow/compare/1.0.4...1.1.0
[1.0.4]: https://github.com/WorkTeam01/HotelFlow/compare/1.0.3...1.0.4
[1.0.3]: https://github.com/WorkTeam01/HotelFlow/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/WorkTeam01/HotelFlow/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/WorkTeam01/HotelFlow/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/WorkTeam01/HotelFlow/releases/tag/1.0.0
