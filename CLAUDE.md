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

## Notas Importantes

- Toda la interfaz está en Español
- Zona horaria: America/La_Paz (Bolivia)
- Generación de PDF usa TCPDF en `libs/TCPDF-main/`
- Subida de imágenes soporta jpg, png, gif, webp con redimensionado automático
- Manejo de sesión y verificación de auth están en `views/layouts/session.php`
- Variable global `$URL` contiene la ruta base de la aplicación
