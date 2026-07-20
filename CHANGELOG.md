# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto usa [Versionado Semántico](https://semver.org/lang/es/) (sin prefijo `v`, ej. `1.0.0`).

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

[1.0.3]: https://github.com/WorkTeam01/HotelFlow/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/WorkTeam01/HotelFlow/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/WorkTeam01/HotelFlow/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/WorkTeam01/HotelFlow/releases/tag/1.0.0
