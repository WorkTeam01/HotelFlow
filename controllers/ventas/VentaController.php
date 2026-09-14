<?php

/**
 * Controlador de Ventas
 * 
 * Gestiona las operaciones relacionadas con las ventas
 */

class VentaController
{
    /**
     * Modelo de Venta
     * @var Venta
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Venta
        require_once __DIR__ . '/../../models/Venta.php';
        $this->modelo = new Venta();
    }

    /**
     * Muestra la lista de ventas con información de métodos de pago
     *
     * @param int|null $idusuario Si se indica, filtra por usuario (scoping no-admin)
     * @return array Lista de ventas con 'pagos' e 'info_metodo_pago' adjuntos
     */
    public function index($idusuario = null)
    {
        $ventas = $idusuario !== null
            ? $this->modelo->getPorUsuario($idusuario)
            : $this->modelo->getAll();

        return $this->adjuntarInfoMetodoPago($ventas);
    }

    /**
     * Adjunta a cada venta sus pagos y la información de presentación del método
     * de pago (texto, clase de badge, tooltip), evitando consultas N+1 al listar.
     *
     * @param array $ventas Lista de ventas (de getAll()/getPorUsuario())
     * @return array Lista de ventas con 'pagos' e 'info_metodo_pago'
     */
    private function adjuntarInfoMetodoPago(array $ventas)
    {
        $idsVenta = array_column($ventas, 'idventa');
        $pagosPorVenta = $this->modelo->getMetodosPagoPorVentas($idsVenta);

        foreach ($ventas as &$venta) {
            $pagos = $pagosPorVenta[$venta['idventa']] ?? [];
            $venta['pagos'] = $pagos;
            $venta['info_metodo_pago'] = $this->calcularInfoMetodoPago($pagos);
        }
        unset($venta);

        return $ventas;
    }

    /**
     * Información de presentación del método de pago (texto, clase de badge,
     * tooltip del desglose si es mixto). Único helper para listado y detalle.
     *
     * @param array $pagos Lista de pagos de la venta
     * @return array ['texto','clase','tooltip','es_mixto']
     */
    private function calcularInfoMetodoPago(array $pagos)
    {
        if ($this->esPagoMixto($pagos)) {
            $tooltip = '';
            foreach ($pagos as $pago) {
                $tooltip .= htmlspecialchars($pago['metodopago']) . ': ' . number_format((float)$pago['monto'], 2) . '<br>';
            }
            return ['texto' => 'Pago Mixto', 'clase' => 'badge-purple', 'tooltip' => $tooltip, 'es_mixto' => true];
        }

        $metodoPago = strtolower(trim($pagos[0]['metodopago'] ?? 'efectivo'));
        $mapaMetodos = [
            'efectivo' => ['texto' => 'Efectivo', 'clase' => 'badge-success'],
            'qr' => ['texto' => 'QR', 'clase' => 'badge-info'],
            'otros' => ['texto' => 'Otros', 'clase' => 'badge-secondary'],
        ];
        $info = $mapaMetodos[$metodoPago] ?? ['texto' => ucfirst($metodoPago), 'clase' => 'badge-secondary'];
        $info['tooltip'] = '';
        $info['es_mixto'] = false;

        return $info;
    }

    /**
     * Determina si una lista de pagos corresponde a un pago mixto
     * (más de un método de pago distinto en la misma venta).
     *
     * @param array $pagos Lista de pagos
     * @return bool
     */
    public function esPagoMixto(array $pagos)
    {
        $metodosDistintos = array_unique(array_map(
            fn($pago) => strtolower(trim($pago['metodopago'] ?? '')),
            $pagos
        ));

        return count($metodosDistintos) > 1;
    }

    /**
     * Ícono FontAwesome y clase de badge asociados a un método de pago.
     *
     * @param string $metodoPago Método de pago
     * @return array [claseIcono, claseBadge]
     */
    public function obtenerIconoMetodoPago($metodoPago)
    {
        $mapa = [
            'efectivo' => ['fas fa-money-bill-wave', 'badge-success'],
            'qr' => ['fas fa-qrcode', 'badge-info'],
            'otros' => ['fas fa-hand-holding-usd', 'badge-secondary'],
        ];

        return $mapa[strtolower(trim($metodoPago ?? ''))] ?? ['fas fa-coins', 'badge-secondary'];
    }

    /**
     * Totales de una venta a partir de sus detalles y pagos.
     *
     * @param array $venta Venta (con 'detalles' y opcionalmente 'pagos')
     * @return array ['subtotal','descuento','total','total_pagado']
     */
    public function calcularTotales(array $venta)
    {
        $subtotal = 0;
        $descuento = 0;

        foreach ($venta['detalles'] ?? [] as $detalle) {
            $subtotal += $detalle['precioventa'] * $detalle['cantidad'];
            $descuento += $detalle['descuento'];
        }

        return [
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => $subtotal - $descuento,
            'total_pagado' => array_sum(array_column($venta['pagos'] ?? [], 'monto')),
        ];
    }

    /**
     * Desglosa una línea de detalle de venta en subtotal bruto, descuento
     * total y neto, para uso en el recibo/detalle.
     * En HotelFlow el descuento de la línea ya es el total aplicado a esa línea.
     *
     * @param array $detalle Línea de detalle (cantidad, precioventa, descuento)
     * @return array ['subtotal','descuento_total','neto']
     */
    public function calcularDetalleLinea(array $detalle)
    {
        $cantidad = $detalle['cantidad'] ?? 0;
        $precio = $detalle['precioventa'] ?? 0;
        $descuentoTotal = (float)($detalle['descuento'] ?? 0);

        $subtotal = $cantidad * $precio;

        return [
            'subtotal' => $subtotal,
            'descuento_total' => $descuentoTotal,
            'neto' => $subtotal - $descuentoTotal,
        ];
    }

    /**
     * Desglose completo de los detalles de una venta (líneas + subtotales generales).
     *
     * @param array $detalles Detalles de la venta
     * @return array ['lineas','subtotal_general','descuento_general']
     */
    public function calcularDesgloseDetalles(array $detalles)
    {
        $subtotalGeneral = 0;
        $descuentoGeneral = 0;
        $lineas = [];

        foreach ($detalles as $detalle) {
            $calculo = $this->calcularDetalleLinea($detalle);
            $subtotalGeneral += $calculo['subtotal'];
            $descuentoGeneral += $calculo['descuento_total'];
            $lineas[] = $detalle + ['calculo' => $calculo];
        }

        return [
            'lineas' => $lineas,
            'subtotal_general' => $subtotalGeneral,
            'descuento_general' => $descuentoGeneral,
        ];
    }

    /**
     * Total recibido y cambio a partir de la lista de pagos de una venta.
     *
     * @param array $pagos Lista de pagos
     * @return array ['total_recibido','total_cambio']
     */
    public function calcularResumenPagos(array $pagos)
    {
        return [
            'total_recibido' => array_sum(array_column($pagos, 'pagorecibido')),
            'total_cambio' => array_sum(array_column($pagos, 'cambio')),
        ];
    }

    /**
     * Muestra el formulario para crear una nueva venta
     */
    public function crear()
    {
        // Incluir modelos necesarios
        require_once __DIR__ . '/../../models/Producto.php';
        require_once __DIR__ . '/../../models/Persona.php';
        require_once __DIR__ . '/../../models/Usuario.php';

        // Obtener productos, clientes y usuarios para los selects
        $productoModel = new Producto();
        $personaModel = new Persona();
        $usuarioModel = new Usuario();

        $datos = [
            'productos' => $productoModel->getAll(),
            'clientes' => $personaModel->getAll(),
            'usuarios' => $usuarioModel->getAll()
        ];

        return $datos;
    }

    /**
     * Prepara los datos de la venta desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosVenta($post_data)
    {
        $datos = [
            'idcliente' => isset($post_data['idcliente']) ? (int)$post_data['idcliente'] : null,
            'idusuario' => (int)($_SESSION['usuario_id'] ?? 0),
            'totalventa' => isset($post_data['totalventa']) ? (float)$post_data['totalventa'] : 0,
            'fechaventa' => isset($post_data['fechaventa']) ? trim($post_data['fechaventa']) : date('Y-m-d'),
            'metodopago' => 'Efectivo', // cache derivado, lo recalcula el modelo
            'pagorecibido' => 0,
            'cambio' => 0,
            'observacion' => isset($post_data['observacion']) ? trim($post_data['observacion']) : null,
            'estado' => 1, // Por defecto activa
            'detalles' => [],
            'pagos' => []
        ];

        // Procesar detalles de la venta
        if (isset($post_data['productos']) && is_array($post_data['productos'])) {
            foreach ($post_data['productos'] as $key => $idProducto) {
                if (!empty($idProducto)) {
                    $datos['detalles'][] = [
                        'idproducto' => (int)$idProducto,
                        'cantidad' => isset($post_data['cantidades'][$key]) ? (int)$post_data['cantidades'][$key] : 1,
                        'precioventa' => isset($post_data['precios'][$key]) ? (float)$post_data['precios'][$key] : 0,
                        'descuento' => isset($post_data['descuentos'][$key]) ? (float)$post_data['descuentos'][$key] : 0.00
                    ];
                }
            }
        }

        // Procesar los métodos de pago según el tipo (único/mixto) o el formulario legacy de pago único
        $tipoPago = isset($post_data['tipo_pago']) ? $post_data['tipo_pago'] : 'unico';

        if ($tipoPago === 'unico' && isset($post_data['metodopago_unico']) && !empty($post_data['metodopago_unico'])) {
            // Pago único (nuevo formulario)
            $metodoPago = trim($post_data['metodopago_unico']);
            $monto = isset($post_data['monto_unico']) ? (float)$post_data['monto_unico'] : 0;
            $pagoRecibido = isset($post_data['pago_recibido_unico']) ? (float)$post_data['pago_recibido_unico'] : $monto;
            $cambio = isset($post_data['cambio_unico']) ? (float)$post_data['cambio_unico'] : 0;

            $datos['pagos'][] = [
                'metodopago' => $metodoPago,
                'monto' => $monto,
                'pagorecibido' => $pagoRecibido,
                'cambio' => $cambio
            ];
        } elseif (isset($post_data['metodopago']) && is_array($post_data['metodopago'])) {
            // Pago mixto: arrays paralelos de métodos, montos, recibidos y cambios
            foreach ($post_data['metodopago'] as $key => $metodoPago) {
                if (!empty($metodoPago)) {
                    $datos['pagos'][] = [
                        'metodopago' => trim($metodoPago),
                        'monto' => isset($post_data['montopago'][$key]) ? (float)$post_data['montopago'][$key] : 0,
                        'pagorecibido' => isset($post_data['pagorecibido'][$key]) ? (float)$post_data['pagorecibido'][$key] : 0,
                        'cambio' => isset($post_data['cambio'][$key]) ? (float)$post_data['cambio'][$key] : 0
                    ];
                }
            }
        } elseif (isset($post_data['metodopago']) && is_string($post_data['metodopago']) && $post_data['metodopago'] !== '') {
            // Pago único legacy (formulario actual): el modelo deriva la línea desde el total calculado
            $datos['metodopago'] = trim($post_data['metodopago']);
            $datos['pagorecibido'] = isset($post_data['pagorecibido']) ? (float)$post_data['pagorecibido'] : 0;
            $datos['cambio'] = isset($post_data['cambio']) ? (float)$post_data['cambio'] : 0;
            unset($datos['pagos']);
        }

        return $datos;
    }

    /**
     * Procesa el formulario para guardar una nueva venta
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Preparar datos de la venta
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosVenta($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Guardar venta usando el modelo
        $idVenta = $this->modelo->crear($datos);

        if ($idVenta) {
            return ['success' => true, 'message' => 'Venta registrada correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            return ['success' => false, 'message' => 'Error al registrar la venta: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'create.php'];
        }
    }

    /**
     * Muestra los detalles de una venta con toda la información derivada
     * que las vistas necesitan (totales, pagos, desglose, info de badge).
     * La vista no recalcula nada — todo sale de estos helpers.
     *
     * @param int $id ID de la venta
     * @return array|null Datos de la venta o null si no existe (la vista redirige)
     */
    public function ver($id = null)
    {
        if (!$id) {
            return null;
        }

        $venta = $this->modelo->getById($id);

        if (!$venta) {
            return null;
        }

        // Helpers F1: la vista consume estos arrays sin calcular nada
        $venta['info_metodo_pago'] = $this->calcularInfoMetodoPago($venta['pagos']);
        $venta['totales'] = $this->calcularTotales($venta);
        $venta['resumen_pagos'] = $this->calcularResumenPagos($venta['pagos']);
        $venta['desglose_detalles'] = $this->calcularDesgloseDetalles($venta['detalles']);

        return $venta;
    }

    /**
     * Anula una venta
     * 
     * @param int $id ID de la venta
     * @return array Resultado de la operación
     */
    public function anular($id = null)
    {
        if (!$id) {
            return ['success' => false, 'message' => 'ID de venta no válido', 'icon' => 'error'];
        }

        if ($this->modelo->anular($id)) {
            return ['success' => true, 'message' => 'Venta anulada correctamente', 'icon' => 'success'];
        } else {
            return ['success' => false, 'message' => 'Error al anular la venta: ' . $this->modelo->getLastError(), 'icon' => 'error'];
        }
    }

    /**
     * Obtiene ventas por rango de fechas
     * 
     * @param string $fechaInicio Fecha de inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha de fin (YYYY-MM-DD)
     * @return array Lista de ventas en el rango
     */
    public function obtenerPorRangoFechas($fechaInicio, $fechaFin)
    {
        return $this->modelo->getPorRangoFechas($fechaInicio, $fechaFin);
    }

    /**
     * Obtiene ventas por estado
     * 
     * @param int $estado Estado de las ventas (1: Activo, 0: Inactivo)
     * @return array Lista de ventas con el estado especificado
     */
    public function obtenerPorEstado($estado)
    {
        return $this->modelo->getPorEstado($estado);
    }

    /**
     * Obtiene ventas por usuario
     * 
     * @param int $idUsuario ID del usuario
     * @return array Lista de ventas del usuario
     */
    public function obtenerPorUsuario($idUsuario)
    {
        return $this->modelo->getPorUsuario($idUsuario);
    }

    /**
     * Obtiene ventas por cliente
     * 
     * @param int $idCliente ID del cliente
     * @return array Lista de ventas del cliente
     */
    public function obtenerPorCliente($idCliente)
    {
        return $this->modelo->getPorCliente($idCliente);
    }

    /**
     * Obtiene estadísticas de ventas
     * 
     * @return array Estadísticas de ventas
     */
    public function getEstadisticas()
    {
        return $this->modelo->getEstadisticas();
    }

    /**
     * Genera un ticket de venta (para impresión)
     * 
     * @param int $idVenta ID de la venta
     * @return array Datos para el ticket
     */
    public function generarTicket($idVenta)
    {
        $venta = $this->modelo->getById($idVenta);

        if (!$venta) {
            return ['success' => false, 'message' => 'Venta no encontrada'];
        }

        // Formatear datos para el ticket
        $ticket = [
            'id' => $venta['idventa'],
            'fecha' => date('d/m/Y H:i', strtotime($venta['fechacreacion'])),
            'cliente' => $venta['cliente_nombre'] ?? 'Consumidor Final',
            'usuario' => $venta['usuario_nombre'],
            'metodo_pago' => $venta['metodopago'],
            'productos' => [],
            'total' => $venta['totalventa'],
            'pago' => $venta['pagorecibido'],
            'cambio' => $venta['cambio']
        ];

        foreach ($venta['detalles'] as $detalle) {
            $ticket['productos'][] = [
                'nombre' => $detalle['producto_nombre'],
                'cantidad' => $detalle['cantidad'],
                'precio' => $detalle['precioventa'],
                'subtotal' => $detalle['cantidad'] * $detalle['precioventa']
            ];
        }

        return ['success' => true, 'data' => $ticket];
    }
}
