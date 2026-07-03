# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto usa [Versionado Semántico](https://semver.org/lang/es/) (sin prefijo `v`, ej. `1.0.0`).

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
