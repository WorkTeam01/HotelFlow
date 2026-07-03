-- HotelFlow - Datos de ejemplo (seeder)
-- Ejecutar despues de importar database/db_hotel_flow.sql:
--   mysql -u root -p db_hotel_flow < database/seed.sql
--
-- Incluye: permisos base, usuario administrador y catalogos minimos
-- para poder operar el sistema (pisos, tipos de habitacion, habitaciones,
-- tarifas, categoria/productos, precios de equipaje y bano).

START TRANSACTION;

-- --------------------------------------------------------
-- Permisos
-- --------------------------------------------------------
INSERT INTO `permiso` (`nombre`) VALUES
('gestionar_habitaciones'),
('gestionar_recepcion'),
('gestionar_ventas'),
('gestionar_compras'),
('gestionar_productos'),
('gestionar_usuarios'),
('gestionar_limpieza'),
('gestionar_servicios_bano'),
('gestionar_almacenamiento_equipaje'),
('gestionar_tarifas');

-- --------------------------------------------------------
-- Usuario administrador
-- Usuario: admin  |  Contraseña: admin123
--
-- ADVERTENCIA: credenciales de ejemplo para entornos de desarrollo.
-- NO USAR EN PRODUCCION. Cambiar la contraseña inmediatamente
-- despues del primer inicio de sesion.
-- --------------------------------------------------------
INSERT INTO `usuarios`
(`nombre`, `apellidop`, `apellidom`, `tipodocumento`, `numdocumento`, `direccion`, `telefono`, `correo`, `cargo`, `clave`, `estado`)
VALUES
('Admin', 'Sistema', NULL, 'CI', '00000001', NULL, NULL, 'admin@hotelflow.local', 'Administrador',
'$2y$10$oOuyzOz0J896yI8dbnJeYOcO14fYJpX6ockCLMD.EwGWnzp1fYRLu', 1);

INSERT INTO `permiso_usuario` (`idpermiso`, `idusuario`)
SELECT `idpermiso`, 1 FROM `permiso`;

-- --------------------------------------------------------
-- Pisos
-- --------------------------------------------------------
INSERT INTO `pisos` (`nombre`, `descripcion`) VALUES
('Piso 1', 'Planta baja'),
('Piso 2', 'Segundo nivel');

-- --------------------------------------------------------
-- Tipos de habitación
-- --------------------------------------------------------
INSERT INTO `tipo_habitacion` (`nombre`, `descripcion`, `capacidad_maxima`) VALUES
('Simple', 'Habitación individual', 1),
('Doble', 'Habitación para dos personas', 2),
('Matrimonial', 'Habitación con cama matrimonial', 2),
('Suite', 'Habitación amplia con sala de estar', 4);

-- --------------------------------------------------------
-- Habitaciones
-- --------------------------------------------------------
INSERT INTO `habitaciones` (`numero`, `id_tipo`, `idpiso`, `precio_base`, `estado`) VALUES
('101', 1, 1, 80.00, 'disponible'),
('102', 2, 1, 120.00, 'disponible'),
('103', 3, 1, 150.00, 'disponible'),
('201', 4, 2, 250.00, 'disponible');

-- --------------------------------------------------------
-- Tarifas
-- --------------------------------------------------------
INSERT INTO `tarifas` (`id_tipo`, `tipo_estancia`, `duracion`, `precio`, `descripcion`) VALUES
(1, 'horas', 3, 40.00, 'Simple - 3 horas'),
(1, 'dias', 1, 80.00, 'Simple - noche completa'),
(2, 'horas', 3, 60.00, 'Doble - 3 horas'),
(2, 'dias', 1, 120.00, 'Doble - noche completa'),
(3, 'dias', 1, 150.00, 'Matrimonial - noche completa'),
(4, 'dias', 1, 250.00, 'Suite - noche completa');

-- --------------------------------------------------------
-- Categorías y productos
-- --------------------------------------------------------
INSERT INTO `categoria` (`nombre`, `descripcion`) VALUES
('Bebidas', 'Bebidas frías y calientes'),
('Snacks', 'Aperitivos y bocadillos'),
('Higiene', 'Artículos de aseo personal');

INSERT INTO `productos` (`idcategoria`, `codigo`, `nombre`, `descripcion`, `stock`, `stock_minimo`, `precioventa`, `preciocompra`) VALUES
(1, 'BEB-001', 'Agua mineral 500ml', 'Botella de agua mineral', 50, 10, 5.00, 2.50),
(1, 'BEB-002', 'Gaseosa 500ml', 'Bebida gaseosa', 40, 10, 8.00, 4.00),
(2, 'SNK-001', 'Papas fritas', 'Bolsa de papas fritas', 30, 5, 6.00, 3.00),
(3, 'HIG-001', 'Kit de aseo', 'Cepillo, pasta y jabón desechable', 25, 5, 12.00, 6.00);

-- --------------------------------------------------------
-- Precios de equipaje
-- --------------------------------------------------------
INSERT INTO `precio_equipaje` (`tamano`, `descripcion`, `precio`) VALUES
('Pequeño', 'Mochila o bolso pequeño', 5.00),
('Mediano', 'Maleta mediana', 8.00),
('Grande', 'Maleta grande', 12.00),
('Extra_Grande', 'Equipaje voluminoso', 15.00);

-- --------------------------------------------------------
-- Baños
-- --------------------------------------------------------
INSERT INTO `bano` (`nombre`, `ubicacion`) VALUES
('Baño 1', 'Planta baja - pasillo principal'),
('Baño 2', 'Segundo nivel - pasillo principal');

COMMIT;
