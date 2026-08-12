# CLAUDE.md

Este archivo proporciona orientación a Claude Code (claude.ai/code) cuando trabaja con código en este repositorio.

## Descripción del Proyecto

HotelFlow es una aplicación PHP para administración hotelera usando arquitectura MVC. Gestiona habitaciones, huéspedes, check-in/out, servicios de baño, almacenamiento de equipaje, ventas, compras y permisos de usuarios.

**Stack Tecnológico:** PHP 8.2+, MariaDB 10.4+, Bootstrap 4, AdminLTE 3, jQuery 3, DataTables, TCPDF para generación de PDF

## Configuración de Desarrollo

```bash
# Importar esquema de base de datos (solo estructura, sin datos)
mysql -u root -p < database/db_hotel_flow.sql

# Cargar datos de ejemplo (seeder opcional)
mysql -u root -p db_hotel_flow < database/seed.sql

# Copiar configuración de entorno
cp .env.example .env

# Editar .env con tus credenciales y URL

# Establecer permisos (Unix)
chmod -R 755 public/uploads/
chmod -R 755 libs/TCPDF-main/

# Login por defecto: admin@hotelflow.local / admin123 (el campo de login pide correo o número de documento, no username)
```

> **Nota:** `chmod 755` solo funciona si el usuario que ejecuta PHP (p. ej. `daemon` en XAMPP, `www-data` en Apache/Debian) es el propietario o pertenece al grupo propietario de `public/uploads/`. Si el directorio pertenece a tu usuario de desarrollo y el servidor web corre como otro usuario/grupo, las subidas fallarán en silencio (`move_uploaded_file` retorna false, `ImagenService::procesarImagen` responde "Error al procesar la imagen" aunque el archivo sea válido). Verifica con `ps aux | grep httpd` qué usuario corre Apache y ajusta el propietario/grupo de `public/uploads/` en consecuencia, o usa `chmod -R 777` solo en entornos de desarrollo local.

**No se usa sistema de build, gestor de paquetes ni framework de testing.** Los archivos PHP usan require/include directamente.

## Arquitectura

### Estructura MVC

- **Modelos** (`/models/`): Operaciones de base de datos via PDO con prepared statements. Todos los modelos usan la conexión Singleton de `config/conexion.php`.
- **Vistas** (`/views/`): Templates PHP. Layouts comunes en `views/layouts/` (header.php, footer.php, session.php).
- **Controladores** (`/controllers/`): Un directorio por módulo. Cada módulo tiene su controlador principal más archivos `ajax_*.php` separados para operaciones AJAX.

### Patrones Clave

**Conexión Singleton a Base de Datos:**

```php
$this->conexion = Conexion::getInstance()->getConnection();
```

**Endpoints AJAX:** Los controladores exponen archivos separados para operaciones asíncronas:

- `crear_[entidad]_ajax.php` - Operaciones de creación
- `actualizar_[entidad]_ajax.php` - Operaciones de actualización
- `cambiar_estado_ajax.php` - Cambios de estado

**Control de Acceso Basado en Roles:** Tres roles (Administrador, Recepcionista, Limpieza). Permisos verificados via `AuthorizationService.php`. El rol Administrador omite todas las verificaciones de permisos.

### Capa de Servicios (`/services/`)

- `AuthorizationService.php` - Verificación de permisos y RBAC
- `ImagenService.php` - Subida de imágenes, validación (límite 5MB), redimensionado con proporción
- `literal.php` - Conversión de números a letras para moneda boliviana

### Punto de Entrada

`index.php` actúa como front controller: carga sesión, verifica auth via `requireLogin()`, instancia controlador de dashboard según rol, incluye header/vista/footer.

## Organización de Módulos

Cada módulo funcional sigue el mismo patrón:

- Controlador: `controllers/[modulo]/[Modulo]Controller.php`
- Modelo: `models/[Entidad].php`
- Vistas: `views/[modulo]/` (index.php, create.php, update.php, show.php)
- JS: `public/js/modules/[modulo]/` (index-[modulo].js, create-[modulo].js, update-[modulo].js)
- CSS: `public/css/modules/[modulo].css`

**Módulos:** auth, habitaciones, recepcion, servicios-bano, almacenamiento-equipaje, ventas, compras, productos, usuarios, limpieza, pisos, tipohabitaciones, tarifas, precios-equipaje, categorias, banos, dashboard

## Base de Datos

22 tablas usando InnoDB con codificación utf8mb4. Esquema en `database/db_hotel_flow.sql` (solo estructura y relaciones). Datos de ejemplo opcionales en `database/seed.sql`.

**Tablas principales:** usuarios, habitaciones, tipo_habitacion, pisos, persona, recepcion, productos, categoria, venta, detalleventa, compra, detallecompra, servicio_bano, almacenamiento_equipaje, asignaciones_limpieza, tarifas, permiso, permiso_usuario, intentos_login

## Assets del Frontend

Todos los assets en `/public/`:

- `css/lib/` - Bootstrap, AdminLTE, FontAwesome
- `css/core/` - Estilos personalizados de la aplicación
- `js/lib/` - jQuery, Bootstrap JS, DataTables, SweetAlert2, Select2
- `js/core/common-utils.js` - Utilidades compartidas
- `uploads/` - Archivos subidos por usuarios (subdirectorios: habitaciones, productos, personas, usuarios)

**Assets propios del módulo (`$module_styles`/`$module_scripts`), nunca `<link>`/`<script>` inline:** toda vista debe declarar sus CSS/JS de módulo como arrays **antes de** `include_once '../layouts/header.php'` (o, si la vista se renderiza vía `index.php` como front controller —ver dashboards—, antes del `include 'views/layouts/header.php'` en `index.php`), nunca como `<link>`/`<script>` embebido en el cuerpo de la vista:

```php
$module_styles = ['modulo/archivo'];   // → public/css/modules/modulo/archivo.css, en el <head>
$module_scripts = ['modulo/archivo'];  // → public/js/modules/modulo/archivo.js, antes de </body>
```

`header.php` itera `$module_styles` para el `<head>` y `footer.php` itera `$module_scripts` al final del documento — ambos son opcionales e independientes (una vista puede tener solo uno de los dos). Soportan múltiples entradas (`['servicios-bano/index-servicios-bano', 'servicios-bano/servicio-bano-rapido']`). Antes de esta convención, ~26 vistas declaraban su CSS vía `$module_styles` pero su JS con un `<script src="...">` suelto al final del archivo (o viceversa) — inconsistencia corregida en 2026-07-27 estandarizando ambas a array.

**No dejar `<style>` embebido en las vistas:** igual que con el JS inline, un `<style>` con reglas CSS dentro de un `.php` de `views/` debe extraerse a su propio archivo en `public/css/modules/[modulo]/[archivo].css` y registrarse en `$module_styles` (creando el archivo si aún no existe para ese módulo/vista). Si el módulo ya tiene un CSS compartido entre varias vistas (create/update/show), añadir ahí en vez de crear uno nuevo por vista — seguir el mismo criterio "un archivo por módulo o por vista" que ya tenga ese módulo. Excepción: páginas standalone que no usan `views/layouts/header.php`/`footer.php` (ej. `login.php`, `recepcion/recibo.php`, `almacenamiento-equipaje/recibo.php` — recibos imprimibles), donde el `<style>` embebido es aceptable porque no hay compartición de layout que aplique la convención de módulos. En 2026-07-27 se extrajeron 6 bloques `<style>` de `compras/create.php`, `ventas/create.php`, `almacenamiento-equipaje/show.php`, `recepcion/update.php`, `productos/show.php` y `habitaciones/show.php`; de paso se detectó que `recepcion/update.php` ya declaraba `$module_styles = ['recepciones/update-recepcion']` apuntando a un archivo CSS que nunca se había creado (link roto silencioso).

**Pasar datos complejos de PHP a JS vía `data-*`, no `<script>` con múltiples variables:** bloques que arman un objeto grande o declaran varias variables (`dashboardData`, `productosDisponibles` + `clientesDisponibles` + `baseUrl`) deben serializarse como JSON en uno o más atributos `data-*` de un elemento del DOM (`htmlspecialchars(json_encode(...), ENT_QUOTES)`), y el módulo JS los lee con `JSON.parse(elemento.dataset.x)` — igual que el caso de una sola constante (ver "No dejar JS inline..."), no hay excepción por tamaño del bloque. Se corrigió en `views/dashboard/administrador_dashboard.php` (objeto `dashboardData` completo → `data-dashboard` en un `<div id="dashboard-admin-root">`, leído por `dashboard-admin.js`) y en `views/ventas/create.php` (`data-productos`/`data-clientes` en el `<form id="form-venta">`, leídos por `create-venta.js`; la variable `baseUrl` no se usaba en el JS y se eliminó).

**Opt-out de librerías pesadas por vista (`views/layouts/header.php` + `footer.php`):** DataTables, Select2 y ChartJS se cargan por defecto en toda vista (para no romper nada existente), pero cada vista puede declarar, **antes de incluir `header.php`**, una variable booleana para omitir la librería que no usa:

```php
$skip_datatables = true; // vista sin <table>/DataTable()
$skip_select2 = true;    // vista sin class="select2" ni .select2()
$skip_chartjs = true;    // vista sin gráficos Chart.js
```

`header.php` calcula `$cargar_datatables`/`$cargar_select2`/`$cargar_chartjs` (CSS + JS del `<head>`) y `footer.php` reutiliza esas mismas variables para el JS del final del documento — el CSS y el JS de una librería siempre se cargan/omiten juntos, nunca por separado. Antes de agregar un `$skip_*` nuevo a una vista, verificar (grep) que ningún JS de módulo que esa vista incluya use la librería, incluyendo JS reusado entre create/update. Actualmente `$skip_chartjs` solo se omite en `index.php` (dashboard) cuando el rol no es Administrador — es el único lugar que usa Chart.js (`dashboard-admin.js`).

**Grupos del sidebar (`views/layouts/sidebar.php`, incluido desde `header.php`):** cada `<li>` de primer nivel con submenú (Recepción, Housekeeping/Limpieza, Servicios, Inventario y Compras, Ventas, Configuración, Administración) debe agrupar módulos afines por dominio funcional del PMS (no por "cuántos enlaces tiene"), y mantenerse en un puñado de enlaces (idealmente ≤4-5); si un grupo crece más allá de eso, dividirlo en grupos temáticos nuevos en vez de seguir apilando enlaces — pero un grupo con 1 solo enlace fuerte (rol dedicado, ej. Limpieza) también puede vivir como `<li>` de primer nivel sin submenú (link directo, sin `<ul class="nav nav-treeview">`) en vez de forzarlo dentro de otro grupo solo por tener pocos enlaces. El grupo original "Inventario y Ventas" llegó a acumular 8 enlaces (productos, categorías, ventas, clientes, compras) y se dividió en **Productos**, **Ventas** y **Compras**; en una reorganización posterior (2026-08-12) se alineó a la convención estándar de un PMS (triada Front Desk/Housekeeping/POS): **Limpieza** salió de "Servicios" a su propio grupo de primer nivel (rol dedicado), y **Productos**+**Compras** se fusionaron en **Inventario y Compras** (Compras alimenta el stock de Productos, no tenía sentido como grupo aparte de solo 2 enlaces). Cada grupo mantiene su propio `<?php if (...) : ?>` de visibilidad basado en los permisos reales de sus enlaces internos (no reutilizar la condición combinada de un grupo anterior).

**`views/layouts/sidebar.php` (extraído de `header.php` el 2026-08-12):** el sidebar vive en su propio archivo, incluido vía `<?php require __DIR__ . '/sidebar.php'; ?>` dentro de `header.php` — depende de `$URL`, `$authService`, `$idusuariosesion` y `$APP_NAME`, ya definidos por `header.php` antes del require (documentado en el docblock del archivo; los "undefined variable" que marca el linter estático ahí son falsos positivos, no un bug). Resalta el link/grupo activo automáticamente comparando la ruta actual (`$_SERVER['REQUEST_URI']`, normalizada con `sidebarNormalizarRuta()`: sin query string, sin `/` final, sin sufijo `/index.php`) contra la ruta de cada `href` — no contra el rol o el nombre del módulo. Cada enlace hoja usa `sidebarClaseActiva($url, $sidebarRutaActual)` para su clase `active`; cada grupo con submenú precalcula un booleano `$sidebarGrupo*Activo` (OR de `sidebarEsActivo()` sobre las rutas de sus hijos) para añadir `menu-open`/`active` al `<li>`/`<a>` del grupo, de forma que el submenú correcto aparezca ya expandido al cargar la página (no depende del clic para abrirse). Al añadir un enlace nuevo a un grupo existente, hay que sumarlo también al booleano `$sidebarGrupo*Activo` de ese grupo — si se omite, el link resalta bien pero el grupo no se auto-expande al entrar directo por URL.

**No dejar JS inline en las vistas (`<script>` con lógica de negocio o datos):** toda vista debe delegar su JavaScript a un archivo dedicado en `public/js/modules/[modulo]/[vista]-[modulo].js` (ver "Organización de Módulos"), no a un `<script>` embebido con jQuery/validaciones/lógica de UI. Sin excepción: **ni siquiera un `<script>` corto que solo declara una constante** (`const x = <?= json_encode(...) ?>`) es aceptable — siempre existe un elemento del DOM natural (el `<form>`, el botón que dispara la acción, un `<section data-module="...">`) donde exponer el dato como atributo `data-*` y leerlo desde el módulo JS con `dataset`/`JSON.parse`. En una migración de 2026-07-27 se sacó el JS inline de 25+ vistas (`habitaciones`, `productos`, `compras`, `ventas`, `tarifas`, `tipohabitacion`, `clientes`, `recepcion`, dashboards de limpieza/recepcionista, `login`, etc.) a módulos dedicados; en una segunda pasada el mismo día se eliminaron también los últimos 5 `<script>` de una sola constante que se habían dejado como "excepción aceptable" (`compras/create.php`, `servicios-bano/create.php`, `productos/update.php`, `productos/show.php`, `productos/buscar_codigo.php`) — el dato se movió a `data-*` en el `<form>` o en el elemento disparador (ej. `.cambiar-estado-link`/`#btnCambiarEstado` con `data-id`/`data-estado`/`data-nombre`) y el JS lo lee de `this.dataset`/`elemento.dataset` en vez de una constante global. No queda ninguna excepción vigente para este patrón.

**No poner lógica de negocio/consultas en las vistas:** una vista solo debe llamar al controlador y pintar el resultado, nunca definir funciones PHP propias (`function agruparHabitacionesPorPiso() {...}` dentro de un `.php` de `views/`) ni instanciar modelos directamente para hacer cálculos. Se encontró y corrigió este patrón en `views/recepcion/create.php`: agrupaba habitaciones por piso con una función local y consultaba `Recepcion::getHabitacionesDisponibles()` directamente. Se trasladó a `RecepcionController::crear()` (que ahora devuelve `habitaciones_disponibles`/`habitaciones_por_piso`/`pisos_unicos`) y al método estático `RecepcionController::agruparHabitacionesPorPiso()`.

## Variables de Entorno

El sistema de env personalizado en `config/env.php` soporta conversión de tipos:

- `true`/`false` se convierten a booleano
- `null` se convierte a null
- `empty` se convierte a cadena vacía
- Usar `env('KEY', 'default')` para obtener valores

## Seguridad — Patrones Obligatorios

Tras una pasada de endurecimiento de seguridad en todo el código, todo endpoint o modelo nuevo debe seguir estos patrones:

- **Autorización en cada endpoint AJAX/acción:** todo archivo `crear_*`, `actualizar_*`, `cambiar_estado_*`, `desactivar_*` y similares debe llamar a `requireLogin()` y verificar permisos explícitamente:

  ```php
  $idusuario = $_SESSION['usuario_id'];
  $auth = new AuthorizationService();
  if (!$auth->esAdministrador($idusuario) && !$auth->puedeAccederModulo($idusuario, 'modulo')) {
      // responder con error (JSON si es AJAX, redirect+mensaje si es vista)
  }
  ```

  No asumir que `requireLogin()` sola es suficiente — la verificación de permisos por módulo es obligatoria.

- **Permisos por cargo definidos por nombre, no por ID:** `AuthorizationService::permisosPorCargo()` es la única fuente de verdad (usada por `cargoTienePermiso`, `obtenerPermisosPorCargo` y `obtenerPermisosAgrupados`). Los cargos válidos son `Administrador`, `Recepcionista`, `Limpieza` (no `admin`/`recepcionista`/`vendedor` — esos nombres no existen en la BD). Los permisos se listan por su `nombre` en la tabla `permiso`, no por `idpermiso` hardcodeado.

- **CSRF obligatorio, no opcional:** no usar `if (isset($_POST['csrf_token']))` como condición para verificar — el token debe verificarse siempre que el POST/GET provenga de una acción de escritura, tratando ausencia de token como inválido. Usar siempre las funciones globales `verifyCSRFToken()`/`generateCSRFToken()`/`regenerateCSRFToken()` de `views/layouts/session.php` (comparación con `hash_equals()`) — no duplicar esta lógica en controladores. Acciones de anulación por GET (ej. `anular_venta.php`) también deben llevar `csrf_token` en la URL.

- **Rate-limiting de login:** `AuthController::login()` usa `models/IntentoLogin.php` (tabla `intentos_login`, append-only, sin FK a `usuarios`) para bloquear por `identificador` (correo/documento, normalizado a lower/trim) e IP dentro de una ventana deslizante. El conteo es solo `INSERT` + `SELECT COUNT`, nunca un contador mutado in-place, por lo que no requiere `SELECT ... FOR UPDATE`. La verificación de bloqueo es **fail-open**: si la consulta de conteo falla por error de BD, no se bloquea el login (evita DoS por fallo de infraestructura), pero el error se loguea igual. Cada intento (éxito o fallo) se registra vía `registrar()`; `limpiarAntiguos()` purga filas antiguas y se invoca probabilísticamente (1/100) en cada request de login para evitar depender de un cron.
  - Umbrales configurables vía `.env`, expuestos en `config/config.php` bajo la clave `login_rate_limit` (mismo patrón que `app.timezone` en `Conexion`: `config.php` es la única fuente de verdad, los modelos no llaman a `env()` directamente) y leídos en el constructor de `IntentoLogin` (no como `const`, para poder variar por entorno):
    - `LOGIN_RATE_LIMIT_VENTANA_MINUTOS` (default `15`)
    - `LOGIN_RATE_LIMIT_MAX_FALLOS_IDENTIFICADOR` (default `5`)
    - `LOGIN_RATE_LIMIT_MAX_FALLOS_IP` (default `20`)
    - `LOGIN_RATE_LIMIT_PURGA_HORAS` (default `24`)

- **Nunca confiar en valores del cliente para estado o dinero:**
  - Estados (activo/inactivo, etc.) se recalculan a partir del valor actual en BD (`$this->modelo->getById($id)`), nunca a partir de un `estado_actual` recibido del formulario/JS.
  - Totales de venta/compra (`totalventa`, `totalcompra`) se recalculan en el modelo a partir de precios y cantidades reales de la BD (`SELECT ... FOR UPDATE`), y se persisten con un `UPDATE` tras insertar los detalles — no se confía en el total enviado por el cliente.
  - Al vender, se bloquea la fila del producto (`FOR UPDATE`) para verificar stock y tomar el precio real antes de insertar el detalle.
  - Al anular una venta, se bloquea la fila de la venta (`FOR UPDATE`) antes de leer su estado, para evitar anulaciones concurrentes duplicadas.

- **Validación de archivos subidos por contenido real, no por metadata del cliente:** `ImagenService` verifica el tipo real con `mime_content_type()` + `getimagesize()` (no el `type` MIME enviado por el navegador), y la extensión final se deriva del tipo de imagen detectado (`IMAGETYPE_*`), no del nombre de archivo del cliente.

- **No filtrar mensajes de excepción al usuario:** en catches de controladores, loguear el detalle con `error_log()`/un helper `logError()` interno y devolver al usuario un mensaje genérico ("Ocurrió un error inesperado. Intente nuevamente."), nunca `$e->getMessage()` directamente en la respuesta. Esta regla también aplica a los modelos: en todo bloque `catch (PDOException $e)` de `/models/*.php`, `$this->lastError` **nunca** debe asignarse directamente desde `$e->getMessage()` ni desde `$stmt->errorInfo()` — hacerlo filtra nombres de tablas/columnas/constraints al cliente vía JSON, ya que los controladores concatenan `getLastError()` en la respuesta (`'message' => 'Error al crear... ' . $this->modelo->getLastError()`). Patrón correcto en cada catch:

  ```php
  } catch (PDOException $e) {
      error_log('[' . static::class . '] ' . $e->getMessage());
      $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
      return false; // o [] / null según el método
  }
  ```

  Los mensajes de negocio ya genéricos y escritos a mano (ej. "Stock insuficiente...", "No se puede eliminar la habitación porque tiene registros relacionados.") sí pueden asignarse directamente a `$this->lastError` — no son fuga de información, describen una regla de negocio.

- **Escapar salida HTML dinámica:** usar `htmlspecialchars()` al imprimir valores que vienen de BD/usuario en vistas (ej. `$venta['metodopago']`).

- **`date_default_timezone_set` y zona horaria:** `config/config.php` es la única fuente de verdad para `TIMEZONE` (con default `America/La_Paz` vía `env('TIMEZONE', 'America/La_Paz')`); `config/conexion.php` reutiliza `$config['app']['timezone']` sin duplicar el default.

- **`switch` exhaustivos:** añadir siempre `default` en los `switch` sobre estados/tipos conocidos, aunque sea un no-op, para que el comportamiento sea explícito ante valores inesperados. (Se encontró y corrigió un caso sin `default` en `RecepcionController::cambiarEstado()` — protegido por un `in_array` previo, pero incumplía la regla igual.)

- **La regla de "nunca filtrar `getMessage()`" también aplica a arreglos de errores acumulados, no solo a la última excepción:** un controlador puede agregar un error genérico a un arreglo (`agregarError()`/`$this->errores[]`) y aun así filtrar información si en algún punto ese arreglo se llena con `$e->getMessage()` crudo y luego una vista lo concatena y lo muestra (p. ej. con `die()`). Se encontró este patrón en `AlmacenamientoEquipajeController::getDatosParaRecibo()` → `views/almacenamiento-equipaje/recibo.php`. Al añadir un mensaje a un arreglo de errores que después se muestra al usuario, tratarlo con el mismo cuidado que un `$this->lastError`: nunca crudo desde `$e->getMessage()`.

- **Verificación de autorización explícita, no solo por composición interna:** todo endpoint debe escribir literalmente `!$auth->esAdministrador($idusuario) && !$auth->puedeAccederModulo($idusuario, 'modulo')`, incluso si `puedeAccederModulo()` ya contempla administrador internamente (vía `tienePermisoNombre`). Omitir el `esAdministrador()` explícito no es un agujero de seguridad hoy, pero rompe la convención documentada y dificulta auditar el código a futuro sin releer la implementación interna de `AuthorizationService`. Se corrigieron tres endpoints que solo verificaban `puedeAccederModulo()` (`controllers/limpieza/crear_asignacion_ajax.php`, `controllers/limpieza/actualizar_asignacion_ajax.php`, `controllers/servicios-bano/crear_servicio_rapido.php`). En una auditoría posterior de todo el proyecto se encontró el mismo patrón (uso de `tieneAccesoCritico()`/`tieneAccesoModulo()` en vez del patrón documentado) en las vistas de tarifas, tipohabitacion, ventas y compras — no solo en controladores AJAX, también en vistas (`index.php`/`create.php`/`show.php`/`update.php`); el patrón explícito aplica igual en vistas que en endpoints AJAX. En una pasada posterior (2026-08-12) se extendió el mismo barrido a `habitaciones`, `almacenamiento-equipaje`, `clientes`, `productos`, `servicios-bano`, `recepcion`, `limpieza`, `banos`, `categorias` y `precios-equipaje`; de paso se reemplazó el patrón manual `if (session_status() == PHP_SESSION_NONE) { session_start(); }` (presente al inicio de varias vistas) por `requireLogin()` — `requireLogin()` ya inicia la sesión si hace falta además de verificar autenticación, así que el check manual era redundante y, al no verificar auth, dejaba una ventana donde `$_SESSION['usuario_id']` podía no existir.

- **Scoping por usuario + IDOR en módulos con dueño (ventas, compras):** un Recepcionista/Vendedor no-administrador solo debe ver y operar sobre sus propios registros. Patrón: (1) `index.php` filtra por `WHERE idusuario = :idUsuario` cuando el usuario no es administrador (los modelos `Venta`/`Compra` ya exponían este query, solo faltaba usarlo desde la vista); (2) `show.php` debe verificar `$registro['idusuario'] != $idusuario` para un no-administrador y redirigir con error — sin este chequeo, cambiar el `id` en la URL permite ver registros ajenos (IDOR); (3) acciones críticas (anular venta, cancelar/completar compra) restringidas a `esAdministrador()`. Los flujos duplicados `views/ventas/nueva.php` y `views/compras/ingresar.php` (creados originalmente para que un vendedor/comprador cree y quede en su propia vista) se eliminaron por ser casi idénticos a `create.php` — el aislamiento por rol ahora lo da el scoping de `index.php`/`show.php`, no una vista de creación separada.

- **Orden de validación vs. `include header.php` en vistas con `header('Location: ...')`:** toda vista que redirija condicionalmente (ID inválido, registro no encontrado, IDOR, falta de permiso) debe completar **todas** esas validaciones con sus `header('Location: ...'); exit;` **antes** de `include_once '../layouts/header.php'`, no después. `header.php` ya emite HTML (el `<head>`/navbar), así que cualquier `header()` posterior falla en runtime con "headers already sent" y deja al usuario con una página rota en vez de redirigirlo. Se encontró y corrigió este bug en `views/compras/show.php` (el chequeo IDOR de propiedad del registro estaba después del `include`); `views/ventas/show.php` ya seguía el orden correcto y sirvió de referencia.

- **No redefinir clases utilitarias de Bootstrap (`.d-none`, `.d-flex`, etc.) en un `<style>` de página:** un `<style>` inline en una vista tiene la misma especificidad CSS que la hoja de Bootstrap, pero al cargar después en el DOM gana el empate — si una vista define su propia `.d-none { display: none !important; }` para uso local, rompe silenciosamente cualquier otro elemento de la página (incluyendo el layout/navbar) que dependa de la clase real de Bootstrap, incluidas sus variantes responsive (`.d-sm-inline-block`, etc.). Se encontró en `views/compras/create.php`: una regla `.d-none` local (sin uso real en esa vista) ocultaba permanentemente el texto "Sistema de Gestión" del navbar. Si una vista necesita mostrar/ocultar algo con clases propias, usar un nombre de clase que no choque con las utilidades de Bootstrap (ej. `.producto-oculto`, prefijado por módulo).

## Notas Importantes

- Toda la interfaz está en Español
- Zona horaria: America/La_Paz (Bolivia)
- Generación de PDF usa TCPDF en `libs/TCPDF-main/`
- Subida de imágenes soporta jpg, png, gif, webp con redimensionado automático
- Manejo de sesión y verificación de auth están en `views/layouts/session.php`
- Variable global `$URL` contiene la ruta base de la aplicación
