-- HotelFlow - Esquema de base de datos
-- Solo estructura de tablas, índices y relaciones (sin datos)
-- Para datos de ejemplo ejecutar seeders/seed.sql

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `db_hotel_flow`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `almacenamiento_equipaje`
--

CREATE TABLE `almacenamiento_equipaje` (
  `idalmacen` int(11) NOT NULL,
  `idcliente` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad_piezas` int(11) DEFAULT 1,
  `codigo_ticket` varchar(20) DEFAULT NULL,
  `fechaentrada` datetime NOT NULL DEFAULT current_timestamp(),
  `fechasalida` datetime DEFAULT NULL,
  `idpequipaje` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `estado` enum('almacenado','retirado','perdido','dañado') DEFAULT 'almacenado',
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones_limpieza`
--

CREATE TABLE `asignaciones_limpieza` (
  `idasignacion` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `idhabitacion` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `estado` enum('pendiente','enprogreso','completada','verificada') DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `asignaciones_limpieza`
--
DELIMITER $$
CREATE TRIGGER `tr_verificar_limpieza_completada` AFTER UPDATE ON `asignaciones_limpieza` FOR EACH ROW BEGIN
    -- Verificar si el estado cambió a 'completada' o 'verificada'
    IF (NEW.estado = 'verificada') AND OLD.estado != NEW.estado THEN
        -- Cambiar el estado de la habitación a 'disponible'
        UPDATE habitaciones 
        SET estado = 'disponible', 
            fechaactualizacion = NOW()
        WHERE id_habitacion = NEW.idhabitacion;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bano`
--

CREATE TABLE `bano` (
  `idbano` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `ubicacion` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('disponible','mantenimiento','fuera_servicio') DEFAULT 'disponible',
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `idcategoria` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(250) DEFAULT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `estado` tinyint(1) DEFAULT 1 COMMENT '1: Activo, 0: Inactivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compra`
--

CREATE TABLE `compra` (
  `idcompra` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `totalcompra` decimal(11,2) NOT NULL,
  `fechacompra` date NOT NULL,
  `estado` enum('pendiente','completada','cancelada') DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallecompra`
--

CREATE TABLE `detallecompra` (
  `iddetallecompra` int(11) NOT NULL,
  `idcompra` int(11) NOT NULL,
  `idproducto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `preciocompra` decimal(10,2) NOT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalleventa`
--

CREATE TABLE `detalleventa` (
  `iddetalleventa` int(11) NOT NULL,
  `idventa` int(11) NOT NULL,
  `idproducto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precioventa` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `fechacreacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitaciones`
--

CREATE TABLE `habitaciones` (
  `id_habitacion` int(11) NOT NULL,
  `numero` varchar(10) NOT NULL,
  `id_tipo` int(11) NOT NULL,
  `idpiso` int(11) NOT NULL,
  `capacidad_actual` int(11) DEFAULT 0,
  `precio_base` decimal(10,2) DEFAULT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `estado` enum('disponible','ocupada','mantenimiento','limpieza') DEFAULT 'disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `intentos_login`
--

CREATE TABLE `intentos_login` (
  `id` bigint(20) NOT NULL,
  `identificador` varchar(100) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `exitoso` tinyint(1) NOT NULL DEFAULT 0,
  `intento_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `idrecepcion` int(11) DEFAULT NULL,
  `idequipaje` int(11) DEFAULT NULL,
  `tipo` enum('cargo','pago','reverso') NOT NULL DEFAULT 'pago',
  `concepto` varchar(120) NOT NULL DEFAULT '',
  `idusuario` int(11) DEFAULT NULL,
  `id_pago_reversado` int(11) DEFAULT NULL,
  `montototal` decimal(10,2) NOT NULL,
  `metodopago` enum('Efectivo','QR','OTROS') NOT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permiso`
--

CREATE TABLE `permiso` (
  `idpermiso` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `estado` tinyint(1) DEFAULT 1 COMMENT '1: Activo, 0: Inactivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permiso_usuario`
--

CREATE TABLE `permiso_usuario` (
  `idpermisousuario` int(11) NOT NULL,
  `idpermiso` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `estado` tinyint(1) DEFAULT 1 COMMENT '1: Activo, 0: Inactivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `persona`
--

CREATE TABLE `persona` (
  `idpersona` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidopaterno` varchar(100) NOT NULL,
  `apellidomaterno` varchar(100) DEFAULT NULL,
  `tipodocumento` enum('DNI','PASAPORTE','CI','RUC','OTROS') NOT NULL,
  `numdocumento` varchar(25) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `fechanacimiento` date DEFAULT NULL,
  `genero` enum('Masculino','Femenino','Otros') DEFAULT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `estado` tinyint(1) DEFAULT 1 COMMENT '1: Activo, 0: Inactivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pisos`
--

CREATE TABLE `pisos` (
  `idpiso` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `estado` tinyint(1) DEFAULT 1 COMMENT '1: Activo, 0: Inactivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `precio_equipaje`
--

CREATE TABLE `precio_equipaje` (
  `idprecioe` int(11) NOT NULL,
  `tamano` enum('Pequeño','Mediano','Grande','Extra_Grande') NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `estado` tinyint(1) DEFAULT 1 COMMENT '1: Activo, 0: Inactivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `idproducto` int(11) NOT NULL,
  `idcategoria` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 0,
  `stock_maximo` int(11) DEFAULT NULL,
  `precioventa` decimal(10,2) NOT NULL,
  `preciocompra` decimal(10,2) NOT NULL,
  `imagen` text DEFAULT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `estado` tinyint(1) DEFAULT 1 COMMENT '1: Activo, 0: Inactivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recepcion`
--

CREATE TABLE `recepcion` (
  `idrecepcion` int(11) NOT NULL,
  `idcliente` int(11) NOT NULL,
  `idhabitacion` int(11) NOT NULL,
  `idtarifa` int(11) DEFAULT NULL,
  `idusuario` int(11) NOT NULL,
  `metodopago` enum('Efectivo','QR','OTROS') NOT NULL,
  `fechaentrada` datetime NOT NULL,
  `fechasalida` datetime DEFAULT NULL,
  `fechasalida_prevista` datetime DEFAULT NULL,
  `montototal` decimal(10,2) NOT NULL,
  `montopagado` decimal(10,2) DEFAULT 0.00,
  `cambio` decimal(10,2) DEFAULT NULL,
  `estado` enum('reservado','en_curso','finalizado','cancelado') DEFAULT 'reservado',
  `observaciones` text DEFAULT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Nota: el trigger `tr_exact_time_check` (BEFORE UPDATE ON `recepcion`) se eliminó
-- durante el refactor frontend de recepción. Forzaba estado='finalizado' + habitación a 'limpieza' desde la BD cuando
-- fechasalida_prevista coincidía al minuto con NOW(), saltándose el folio y el orden
-- canónico de bloqueo. El check-out ahora es explícito (RecepcionController::checkout()).
-- En bases existentes ejecutar manualmente: DROP TRIGGER IF EXISTS `tr_exact_time_check`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recepcion_movimientos`
--

CREATE TABLE `recepcion_movimientos` (
  `idmovimiento` int(11) NOT NULL,
  `idrecepcion` int(11) NOT NULL,
  `tipo` enum('cambio_habitacion','extension_estancia') NOT NULL,
  `valor_anterior` varchar(64) DEFAULT NULL,
  `valor_nuevo` varchar(64) DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `idusuario` int(11) NOT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio_bano`
--

CREATE TABLE `servicio_bano` (
  `idservicio` int(11) NOT NULL,
  `idbano` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `idcliente` int(11) DEFAULT NULL,
  `tipo_cliente` enum('Huesped','Publico') DEFAULT 'Publico',
  `total` decimal(10,2) NOT NULL,
  `metodopago` enum('Efectivo','QR','OTROS') NOT NULL,
  `pagorecibido` decimal(10,2) NOT NULL,
  `cambio` decimal(10,2) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `duracion_minutos` int(11) DEFAULT NULL,
  `estado` enum('iniciado','finalizado','cancelado') DEFAULT 'iniciado',
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tarifas`
--

CREATE TABLE `tarifas` (
  `idtarifa` int(11) NOT NULL,
  `id_tipo` int(11) NOT NULL,
  `tipo_estancia` enum('horas','dias') NOT NULL,
  `duracion` int(11) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `estado` tinyint(1) DEFAULT 1 COMMENT '1: Activo, 0: Inactivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_habitacion`
--

CREATE TABLE `tipo_habitacion` (
  `id_tipo` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `capacidad_maxima` int(11) NOT NULL DEFAULT 1,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `estado` tinyint(1) DEFAULT 1 COMMENT '1: Activo, 0: Inactivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `idusuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidop` varchar(100) NOT NULL,
  `apellidom` varchar(100) DEFAULT NULL,
  `tipodocumento` enum('DNI','PASAPORTE','CI','RUC','OTROS') NOT NULL,
  `numdocumento` varchar(25) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) NOT NULL,
  `cargo` varchar(50) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `estado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

CREATE TABLE `venta` (
  `idventa` int(11) NOT NULL,
  `idcliente` int(11) DEFAULT NULL,
  `idusuario` int(11) DEFAULT NULL,
  `totalventa` decimal(11,2) NOT NULL,
  `fechaventa` date NOT NULL,
  `metodopago` enum('Efectivo','QR','Otros') NOT NULL,
  `pagorecibido` decimal(10,2) NOT NULL,
  `cambio` decimal(10,2) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fechacreacion` datetime DEFAULT current_timestamp(),
  `fechaactualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `idsucursal` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `almacenamiento_equipaje`
--
ALTER TABLE `almacenamiento_equipaje`
  ADD PRIMARY KEY (`idalmacen`),
  ADD UNIQUE KEY `codigo_ticket` (`codigo_ticket`),
  ADD KEY `idcliente` (`idcliente`),
  ADD KEY `idusuario` (`idusuario`),
  ADD KEY `idpequipaje` (`idpequipaje`);

--
-- Indices de la tabla `asignaciones_limpieza`
--
ALTER TABLE `asignaciones_limpieza`
  ADD PRIMARY KEY (`idasignacion`),
  ADD KEY `idusuario` (`idusuario`),
  ADD KEY `idhabitacion` (`idhabitacion`);

--
-- Indices de la tabla `bano`
--
ALTER TABLE `bano`
  ADD PRIMARY KEY (`idbano`);

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`idcategoria`);

--
-- Indices de la tabla `compra`
--
ALTER TABLE `compra`
  ADD PRIMARY KEY (`idcompra`),
  ADD KEY `idusuario` (`idusuario`);

--
-- Indices de la tabla `detallecompra`
--
ALTER TABLE `detallecompra`
  ADD PRIMARY KEY (`iddetallecompra`),
  ADD KEY `idcompra` (`idcompra`),
  ADD KEY `idproducto` (`idproducto`);

--
-- Indices de la tabla `detalleventa`
--
ALTER TABLE `detalleventa`
  ADD PRIMARY KEY (`iddetalleventa`),
  ADD KEY `idventa` (`idventa`),
  ADD KEY `idproducto` (`idproducto`);

--
-- Indices de la tabla `habitaciones`
--
ALTER TABLE `habitaciones`
  ADD PRIMARY KEY (`id_habitacion`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD KEY `idpiso` (`idpiso`),
  ADD KEY `id_tipo` (`id_tipo`);

--
-- Indices de la tabla `intentos_login`
--
ALTER TABLE `intentos_login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_identificador_tiempo` (`identificador`, `intento_en`),
  ADD KEY `idx_ip_tiempo` (`ip`, `intento_en`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `idrecepcion` (`idrecepcion`),
  ADD KEY `idequipaje` (`idequipaje`),
  ADD KEY `idx_pagos_recepcion_fecha` (`idrecepcion`,`fechacreacion`),
  ADD KEY `idusuario` (`idusuario`),
  ADD KEY `id_pago_reversado` (`id_pago_reversado`);

--
-- Indices de la tabla `permiso`
--
ALTER TABLE `permiso`
  ADD PRIMARY KEY (`idpermiso`);

--
-- Indices de la tabla `permiso_usuario`
--
ALTER TABLE `permiso_usuario`
  ADD PRIMARY KEY (`idpermisousuario`),
  ADD KEY `idpermiso` (`idpermiso`),
  ADD KEY `idusuario` (`idusuario`);

--
-- Indices de la tabla `persona`
--
ALTER TABLE `persona`
  ADD PRIMARY KEY (`idpersona`),
  ADD UNIQUE KEY `numdocumento` (`numdocumento`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `pisos`
--
ALTER TABLE `pisos`
  ADD PRIMARY KEY (`idpiso`);

--
-- Indices de la tabla `precio_equipaje`
--
ALTER TABLE `precio_equipaje`
  ADD PRIMARY KEY (`idprecioe`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`idproducto`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idcategoria` (`idcategoria`);

--
-- Indices de la tabla `recepcion`
--
ALTER TABLE `recepcion`
  ADD PRIMARY KEY (`idrecepcion`),
  ADD KEY `idcliente` (`idcliente`),
  ADD KEY `idhabitacion` (`idhabitacion`),
  ADD KEY `idusuario` (`idusuario`),
  ADD KEY `idtarifa` (`idtarifa`);

--
-- Indices de la tabla `recepcion_movimientos`
--
ALTER TABLE `recepcion_movimientos`
  ADD PRIMARY KEY (`idmovimiento`),
  ADD KEY `idx_mov_recepcion` (`idrecepcion`),
  ADD KEY `idusuario` (`idusuario`);

--
-- Indices de la tabla `servicio_bano`
--
ALTER TABLE `servicio_bano`
  ADD PRIMARY KEY (`idservicio`),
  ADD KEY `idbano` (`idbano`),
  ADD KEY `idusuario` (`idusuario`),
  ADD KEY `idcliente` (`idcliente`);

--
-- Indices de la tabla `tarifas`
--
ALTER TABLE `tarifas`
  ADD PRIMARY KEY (`idtarifa`),
  ADD KEY `id_tipo` (`id_tipo`);

--
-- Indices de la tabla `tipo_habitacion`
--
ALTER TABLE `tipo_habitacion`
  ADD PRIMARY KEY (`id_tipo`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`idusuario`),
  ADD UNIQUE KEY `numdocumento` (`numdocumento`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `venta`
--
ALTER TABLE `venta`
  ADD PRIMARY KEY (`idventa`),
  ADD KEY `idcliente` (`idcliente`),
  ADD KEY `idusuario` (`idusuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `almacenamiento_equipaje`
--
ALTER TABLE `almacenamiento_equipaje`
  MODIFY `idalmacen` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asignaciones_limpieza`
--
ALTER TABLE `asignaciones_limpieza`
  MODIFY `idasignacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `bano`
--
ALTER TABLE `bano`
  MODIFY `idbano` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `idcategoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `compra`
--
ALTER TABLE `compra`
  MODIFY `idcompra` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detallecompra`
--
ALTER TABLE `detallecompra`
  MODIFY `iddetallecompra` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalleventa`
--
ALTER TABLE `detalleventa`
  MODIFY `iddetalleventa` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `habitaciones`
--
ALTER TABLE `habitaciones`
  MODIFY `id_habitacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `intentos_login`
--
ALTER TABLE `intentos_login`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permiso`
--
ALTER TABLE `permiso`
  MODIFY `idpermiso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permiso_usuario`
--
ALTER TABLE `permiso_usuario`
  MODIFY `idpermisousuario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `persona`
--
ALTER TABLE `persona`
  MODIFY `idpersona` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pisos`
--
ALTER TABLE `pisos`
  MODIFY `idpiso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `precio_equipaje`
--
ALTER TABLE `precio_equipaje`
  MODIFY `idprecioe` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `idproducto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recepcion`
--
ALTER TABLE `recepcion`
  MODIFY `idrecepcion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recepcion_movimientos`
--
ALTER TABLE `recepcion_movimientos`
  MODIFY `idmovimiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `servicio_bano`
--
ALTER TABLE `servicio_bano`
  MODIFY `idservicio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tarifas`
--
ALTER TABLE `tarifas`
  MODIFY `idtarifa` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipo_habitacion`
--
ALTER TABLE `tipo_habitacion`
  MODIFY `id_tipo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idusuario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `venta`
--
ALTER TABLE `venta`
  MODIFY `idventa` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `almacenamiento_equipaje`
--
ALTER TABLE `almacenamiento_equipaje`
  ADD CONSTRAINT `almacenamiento_equipaje_ibfk_1` FOREIGN KEY (`idcliente`) REFERENCES `persona` (`idpersona`),
  ADD CONSTRAINT `almacenamiento_equipaje_ibfk_2` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`),
  ADD CONSTRAINT `almacenamiento_equipaje_ibfk_3` FOREIGN KEY (`idpequipaje`) REFERENCES `precio_equipaje` (`idprecioe`);

--
-- Filtros para la tabla `asignaciones_limpieza`
--
ALTER TABLE `asignaciones_limpieza`
  ADD CONSTRAINT `asignaciones_limpieza_ibfk_1` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`),
  ADD CONSTRAINT `asignaciones_limpieza_ibfk_2` FOREIGN KEY (`idhabitacion`) REFERENCES `habitaciones` (`id_habitacion`);

--
-- Filtros para la tabla `compra`
--
ALTER TABLE `compra`
  ADD CONSTRAINT `compra_ibfk_1` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`);

--
-- Filtros para la tabla `detallecompra`
--
ALTER TABLE `detallecompra`
  ADD CONSTRAINT `detallecompra_ibfk_1` FOREIGN KEY (`idcompra`) REFERENCES `compra` (`idcompra`),
  ADD CONSTRAINT `detallecompra_ibfk_2` FOREIGN KEY (`idproducto`) REFERENCES `productos` (`idproducto`);

--
-- Filtros para la tabla `detalleventa`
--
ALTER TABLE `detalleventa`
  ADD CONSTRAINT `detalleventa_ibfk_1` FOREIGN KEY (`idventa`) REFERENCES `venta` (`idventa`),
  ADD CONSTRAINT `detalleventa_ibfk_2` FOREIGN KEY (`idproducto`) REFERENCES `productos` (`idproducto`);

--
-- Filtros para la tabla `habitaciones`
--
ALTER TABLE `habitaciones`
  ADD CONSTRAINT `habitaciones_ibfk_1` FOREIGN KEY (`idpiso`) REFERENCES `pisos` (`idpiso`),
  ADD CONSTRAINT `habitaciones_ibfk_2` FOREIGN KEY (`id_tipo`) REFERENCES `tipo_habitacion` (`id_tipo`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`idrecepcion`) REFERENCES `recepcion` (`idrecepcion`),
  ADD CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`idequipaje`) REFERENCES `almacenamiento_equipaje` (`idalmacen`),
  ADD CONSTRAINT `pagos_ibfk_3` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`),
  ADD CONSTRAINT `pagos_ibfk_4` FOREIGN KEY (`id_pago_reversado`) REFERENCES `pagos` (`id_pago`);

--
-- Filtros para la tabla `permiso_usuario`
--
ALTER TABLE `permiso_usuario`
  ADD CONSTRAINT `permiso_usuario_ibfk_1` FOREIGN KEY (`idpermiso`) REFERENCES `permiso` (`idpermiso`),
  ADD CONSTRAINT `permiso_usuario_ibfk_2` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`idcategoria`) REFERENCES `categoria` (`idcategoria`);

--
-- Filtros para la tabla `recepcion`
--
ALTER TABLE `recepcion`
  ADD CONSTRAINT `recepcion_ibfk_1` FOREIGN KEY (`idcliente`) REFERENCES `persona` (`idpersona`),
  ADD CONSTRAINT `recepcion_ibfk_2` FOREIGN KEY (`idhabitacion`) REFERENCES `habitaciones` (`id_habitacion`),
  ADD CONSTRAINT `recepcion_ibfk_3` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`),
  ADD CONSTRAINT `recepcion_ibfk_4` FOREIGN KEY (`idtarifa`) REFERENCES `tarifas` (`idtarifa`);

--
-- Filtros para la tabla `recepcion_movimientos`
--
ALTER TABLE `recepcion_movimientos`
  ADD CONSTRAINT `recmov_ibfk_1` FOREIGN KEY (`idrecepcion`) REFERENCES `recepcion` (`idrecepcion`),
  ADD CONSTRAINT `recmov_ibfk_2` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`);

--
-- Filtros para la tabla `servicio_bano`
--
ALTER TABLE `servicio_bano`
  ADD CONSTRAINT `servicio_bano_ibfk_1` FOREIGN KEY (`idbano`) REFERENCES `bano` (`idbano`),
  ADD CONSTRAINT `servicio_bano_ibfk_2` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`),
  ADD CONSTRAINT `servicio_bano_ibfk_3` FOREIGN KEY (`idcliente`) REFERENCES `persona` (`idpersona`) ON DELETE SET NULL;

--
-- Filtros para la tabla `tarifas`
--
ALTER TABLE `tarifas`
  ADD CONSTRAINT `tarifas_ibfk_1` FOREIGN KEY (`id_tipo`) REFERENCES `tipo_habitacion` (`id_tipo`);

--
-- Filtros para la tabla `venta`
--
ALTER TABLE `venta`
  ADD CONSTRAINT `venta_ibfk_1` FOREIGN KEY (`idcliente`) REFERENCES `persona` (`idpersona`) ON DELETE SET NULL,
  ADD CONSTRAINT `venta_ibfk_2` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
