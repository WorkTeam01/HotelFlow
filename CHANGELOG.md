# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto usa [Versionado Semántico](https://semver.org/lang/es/) (sin prefijo `v`, ej. `1.0.0`).

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

[1.2.0]: https://github.com/WorkTeam01/HotelFlow/compare/1.1.4...1.2.0
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
