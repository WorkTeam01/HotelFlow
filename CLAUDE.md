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

# Login por defecto: admin / admin123
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

21 tablas usando InnoDB con codificación utf8mb4. Esquema en `database/db_hotel_flow.sql` (solo estructura y relaciones). Datos de ejemplo opcionales en `database/seed.sql`.

**Tablas principales:** usuarios, habitaciones, tipo_habitacion, pisos, persona, recepcion, productos, categoria, venta, detalleventa, compra, detallecompra, servicio_bano, almacenamiento_equipaje, asignaciones_limpieza, tarifas, permiso, permiso_usuario

## Assets del Frontend

Todos los assets en `/public/`:
- `css/lib/` - Bootstrap, AdminLTE, FontAwesome
- `css/core/` - Estilos personalizados de la aplicación
- `js/lib/` - jQuery, Bootstrap JS, DataTables, SweetAlert2, Select2
- `js/core/common-utils.js` - Utilidades compartidas
- `uploads/` - Archivos subidos por usuarios (subdirectorios: habitaciones, productos, personas, usuarios)

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

- **CSRF obligatorio, no opcional:** no usar `if (isset($_POST['csrf_token']))` como condición para verificar — el token debe verificarse siempre que el POST/GET provenga de una acción de escritura, tratando ausencia de token como inválido. Ver `verificarCSRFToken()` en `AuthController` y `verifyCSRFToken()`/`generateCSRFToken()` global. Acciones de anulación por GET (ej. `anular_venta.php`) también deben llevar `csrf_token` en la URL.

- **Nunca confiar en valores del cliente para estado o dinero:**
  - Estados (activo/inactivo, etc.) se recalculan a partir del valor actual en BD (`$this->modelo->getById($id)`), nunca a partir de un `estado_actual` recibido del formulario/JS.
  - Totales de venta/compra (`totalventa`, `totalcompra`) se recalculan en el modelo a partir de precios y cantidades reales de la BD (`SELECT ... FOR UPDATE`), y se persisten con un `UPDATE` tras insertar los detalles — no se confía en el total enviado por el cliente.
  - Al vender, se bloquea la fila del producto (`FOR UPDATE`) para verificar stock y tomar el precio real antes de insertar el detalle.
  - Al anular una venta, se bloquea la fila de la venta (`FOR UPDATE`) antes de leer su estado, para evitar anulaciones concurrentes duplicadas.

- **Validación de archivos subidos por contenido real, no por metadata del cliente:** `ImagenService` verifica el tipo real con `mime_content_type()` + `getimagesize()` (no el `type` MIME enviado por el navegador), y la extensión final se deriva del tipo de imagen detectado (`IMAGETYPE_*`), no del nombre de archivo del cliente.

- **No filtrar mensajes de excepción al usuario:** en catches de controladores, loguear el detalle con `error_log()`/un helper `logError()` interno y devolver al usuario un mensaje genérico ("Ocurrió un error inesperado. Intente nuevamente."), nunca `$e->getMessage()` directamente en la respuesta.

- **Escapar salida HTML dinámica:** usar `htmlspecialchars()` al imprimir valores que vienen de BD/usuario en vistas (ej. `$venta['metodopago']`).

- **`date_default_timezone_set` y zona horaria:** `config/config.php` es la única fuente de verdad para `TIMEZONE` (con default `America/La_Paz` vía `env('TIMEZONE', 'America/La_Paz')`); `config/conexion.php` reutiliza `$config['app']['timezone']` sin duplicar el default.

- **`switch` exhaustivos:** añadir siempre `default` en los `switch` sobre estados/tipos conocidos, aunque sea un no-op, para que el comportamiento sea explícito ante valores inesperados.

## Notas Importantes

- Toda la interfaz está en Español
- Zona horaria: America/La_Paz (Bolivia)
- Generación de PDF usa TCPDF en `libs/TCPDF-main/`
- Subida de imágenes soporta jpg, png, gif, webp con redimensionado automático
- Manejo de sesión y verificación de auth están en `views/layouts/session.php`
- Variable global `$URL` contiene la ruta base de la aplicación
