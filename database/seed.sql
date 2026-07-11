-- HotelFlow - Datos de ejemplo (seeder)
-- Ejecutar despues de importar database/db_hotel_flow.sql:
--   mysql -u root -p db_hotel_flow < database/seed.sql
--
-- Incluye: permisos base, usuarios de cada rol, catalogos minimos
-- (pisos, tipos de habitacion, habitaciones, tarifas, categoria/productos,
-- precios de equipaje y bano) y operaciones de ejemplo (check-in, venta,
-- compra, servicio de bano, asignacion de limpieza, equipaje almacenado)
-- con fechas relativas a la fecha de ejecucion (NOW()/CURDATE()), para que
-- el dashboard y los reportes muestren datos del dia al hacer la demo.

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
-- Usuarios (uno por rol, para poder probar los tres flujos)
-- Usuario: admin                          | Contraseña: admin123
-- Usuario: recepcion@hotelflow.local      | Contraseña: recepcion123
-- Usuario: limpieza@hotelflow.local       | Contraseña: limpieza123
--
-- ADVERTENCIA: credenciales de ejemplo para entornos de desarrollo.
-- NO USAR EN PRODUCCION. Cambiar la contraseña inmediatamente
-- despues del primer inicio de sesion.
-- --------------------------------------------------------
INSERT INTO `usuarios`
(`nombre`, `apellidop`, `apellidom`, `tipodocumento`, `numdocumento`, `direccion`, `telefono`, `correo`, `cargo`, `clave`, `estado`)
VALUES
('Admin', 'Sistema', NULL, 'CI', '00000001', NULL, NULL, 'admin@hotelflow.local', 'Administrador',
'$2y$10$oOuyzOz0J896yI8dbnJeYOcO14fYJpX6ockCLMD.EwGWnzp1fYRLu', 1),
('Ana', 'Torrez', 'Vega', 'CI', '00000002', NULL, '70000002', 'recepcion@hotelflow.local', 'Recepcionista',
'$2y$10$Z5t1JIKhCK7WXrlkrh0QaewYSWgOwAWUlREkC6r.C.HIC39ZbWAHe', 1),
('Luis', 'Quispe', 'Mamani', 'CI', '00000003', NULL, '70000003', 'limpieza@hotelflow.local', 'Limpieza',
'$2y$10$b3HiuPqzCqxE5JWvrR7daOD0FvbVnMJWNPWiKyzAhCcScvsSfwvh2', 1);

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
('101', 1, 1, 80.00, 'ocupada'),
('102', 2, 1, 120.00, 'limpieza'),
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
INSERT INTO `bano` (`nombre`, `ubicacion`, `precio`) VALUES
('Baño 1', 'Planta baja - pasillo principal', 10.00),
('Baño 2', 'Segundo nivel - pasillo principal', 10.00);

-- --------------------------------------------------------
-- Clientes (persona)
-- --------------------------------------------------------
INSERT INTO `persona` (`nombre`, `apellidopaterno`, `apellidomaterno`, `tipodocumento`, `numdocumento`, `direccion`, `telefono`, `email`, `estado`) VALUES
('Juan', 'Perez', 'Rojas', 'CI', '10000001', 'Av. Siempre Viva 123', '71111111', 'juan.perez@example.com', 1),
('Maria', 'Gonzales', 'Flores', 'CI', '10000002', 'Calle Sucre 456', '72222222', 'maria.gonzales@example.com', 1);

-- --------------------------------------------------------
-- Operaciones de ejemplo (fechas relativas a la fecha actual,
-- para que el dashboard y los reportes muestren datos del dia)
-- --------------------------------------------------------

-- Check-in en curso: Juan Perez en la habitación 101 (marcada 'ocupada' arriba)
INSERT INTO `recepcion` (`idcliente`, `idhabitacion`, `idtarifa`, `idusuario`, `metodopago`, `fechaentrada`, `fechasalida_prevista`, `montototal`, `montopagado`, `cambio`, `estado`) VALUES
(1, 1, 2, 2, 'Efectivo', NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), 80.00, 80.00, 0.00, 'en_curso');

-- Asignación de limpieza pendiente para la habitación 102 (marcada 'limpieza' arriba)
INSERT INTO `asignaciones_limpieza` (`idusuario`, `idhabitacion`, `fecha`, `hora`, `estado`) VALUES
(3, 2, CURDATE(), CURTIME(), 'pendiente');

-- Venta de productos del día (mostrador)
INSERT INTO `venta` (`idcliente`, `idusuario`, `totalventa`, `fechaventa`, `metodopago`, `pagorecibido`, `cambio`, `estado`) VALUES
(2, 2, 13.00, CURDATE(), 'Efectivo', 15.00, 2.00, 1);

INSERT INTO `detalleventa` (`idventa`, `idproducto`, `cantidad`, `precioventa`) VALUES
(1, 1, 1, 5.00),
(1, 3, 1, 8.00);

-- Compra de reposición de stock del día
INSERT INTO `compra` (`idusuario`, `totalcompra`, `fechacompra`, `estado`) VALUES
(1, 40.00, CURDATE(), 'completada');

INSERT INTO `detallecompra` (`idcompra`, `idproducto`, `cantidad`, `preciocompra`) VALUES
(1, 1, 10, 2.50),
(1, 2, 5, 3.00);

-- Servicio de baño para un huésped del día
INSERT INTO `servicio_bano` (`idbano`, `idusuario`, `idcliente`, `tipo_cliente`, `total`, `metodopago`, `pagorecibido`, `cambio`, `fecha`, `duracion_minutos`, `estado`) VALUES
(1, 2, 1, 'Huesped', 10.00, 'Efectivo', 10.00, 0.00, NOW(), 20, 'finalizado');

-- Equipaje almacenado del día
INSERT INTO `almacenamiento_equipaje` (`idcliente`, `idusuario`, `descripcion`, `cantidad_piezas`, `codigo_ticket`, `fechaentrada`, `idpequipaje`, `monto`, `estado`) VALUES
(2, 2, 'Maleta mediana azul', 1, CONCAT('EQ-', DATE_FORMAT(NOW(), '%Y%m%d'), '-01'), NOW(), 2, 8.00, 'almacenado');

COMMIT;
