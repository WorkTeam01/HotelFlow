# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto usa [Versionado Semántico](https://semver.org/lang/es/) (sin prefijo `v`, ej. `1.0.0`).

## [1.0.1] - 2026-07-06

### Added

- Variable `APP_VERSION` en `.env`/`.env.example`, expuesta como `$APP_VERSION` (ver `views/layouts/session.php`) y usada en `views/layouts/footer.php` en vez de un número de versión hardcodeado.

### Security

- Verificación de permisos por módulo (`AuthorizationService::puedeAccederModulo`) añadida a endpoints que solo validaban sesión (categorías, pisos, habitaciones, tarifas, tipos de habitación, usuarios, servicios de baño, baños, ventas, personas).
- CSRF ahora es obligatorio (antes se verificaba solo si el token estaba presente en el POST); se agregó `csrf_token` a las acciones de anulación de venta por GET.
- `AuthorizationService`: los permisos por cargo se centralizaron en `permisosPorCargo()` y se corrigieron los nombres de cargo (`Administrador`, `Recepcionista`, `Limpieza`, antes `admin`/`recepcionista`/`vendedor`, que no existían en la BD); los permisos se resuelven por nombre en vez de IDs hardcodeados.
- `Venta::crear()` y `Compra::crear()` recalculan el total real a partir de los precios y cantidades en BD (`SELECT ... FOR UPDATE`) en lugar de confiar en el total enviado por el cliente; la venta también bloquea la fila del producto para validar stock antes de descontar.
- `Venta::anular()` bloquea la fila de la venta (`FOR UPDATE`) antes de leer su estado, evitando anulaciones concurrentes duplicadas.
- `UsuarioController::cambiarEstadoUsuario()` calcula el nuevo estado a partir del valor real en BD, no del `estado_actual` recibido del cliente.
- `ImagenService` valida el tipo de archivo inspeccionando el contenido real (`mime_content_type()` + `getimagesize()`) en vez de confiar en el MIME/extensión enviados por el cliente.
- `views/ventas/show.php` escapa `metodopago` con `htmlspecialchars()` antes de imprimirlo.
- Mensajes de excepción ya no se exponen al usuario en `ServicioBanoController`; se loguean internamente y se devuelve un mensaje genérico.

### Changed

- `config/config.php` es ahora la única fuente de verdad para `TIMEZONE` (default `America/La_Paz`); `config/conexion.php` reutiliza ese valor en vez de duplicar el default.
- Eliminados logs de depuración (`error_log`) usados durante desarrollo en `Recepcion::actualizar()`, `RecepcionController` y `Dashboard`.
- `Recepcion::validar()` valida que la fecha de salida prevista sea posterior a la fecha de entrada.

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
