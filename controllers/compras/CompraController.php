<?php

/**
 * Controlador de Compras
 * 
 * Gestiona las operaciones relacionadas con las compras
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

class CompraController
{
    /**
     * Modelo de Compra
     * @var Compra
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Compra
        require_once __DIR__ . '/../../models/Compra.php';
        $this->modelo = new Compra();
    }

    /**
     * Lista de compras con su presentación de estado ya resuelta. Un no-administrador
     * solo ve las suyas.
     *
     * @param int|null $idUsuario Dueño de las compras, o null para todas
     * @return array
     */
    public function index(?int $idUsuario = null)
    {
        $compras = $idUsuario === null
            ? $this->modelo->getAll()
            : $this->modelo->getPorUsuario($idUsuario);

        return $this->decorarEstados($compras);
    }

    /**
     * Indicadores del listado (pendientes, invertido y compras del mes, producto más repuesto).
     *
     * @param int|null $idUsuario Dueño de las compras, o null para todas
     */
    public function getEstadisticas(?int $idUsuario = null): array
    {
        return $this->modelo->getEstadisticas($idUsuario);
    }

    /**
     * Código visible de una compra (COMP-000001).
     */
    public static function codigoCompra($id): string
    {
        return 'COMP-' . str_pad((string) (int) $id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Presentación de un estado de compra: etiqueta, clase de color (sufijo AdminLTE),
     * badge, icono, orden y aviso del efecto sobre el inventario. Única fuente: ninguna
     * vista escribe un switch ni un badge-* de estado.
     *
     * @param string $estado pendiente | completada | cancelada
     * @return array ['label','clase','badge','icono','orden','alerta']
     */
    public static function estadoCompra($estado): array
    {
        switch ((string) $estado) {
            case 'pendiente':
                return [
                    'label' => 'Pendiente',
                    'clase' => 'warning',
                    'badge' => 'badge-warning',
                    'icono' => 'fa-clock',
                    'orden' => 1,
                    'alerta' => 'El stock se sumará al inventario cuando un administrador complete la compra.',
                ];
            case 'completada':
                return [
                    'label' => 'Completada',
                    'clase' => 'success',
                    'badge' => 'badge-success',
                    'icono' => 'fa-check-circle',
                    'orden' => 2,
                    'alerta' => 'La mercadería ingresó al inventario.',
                ];
            case 'cancelada':
                return [
                    'label' => 'Cancelada',
                    'clase' => 'danger',
                    'badge' => 'badge-danger',
                    'icono' => 'fa-ban',
                    'orden' => 3,
                    'alerta' => 'Compra cancelada: no suma stock.',
                ];
            default:
                return [
                    'label' => 'Desconocido',
                    'clase' => 'secondary',
                    'badge' => 'badge-secondary',
                    'icono' => 'fa-question-circle',
                    'orden' => 9,
                    'alerta' => '',
                ];
        }
    }

    /**
     * Mapa completo de estados, en orden, para filtros e indicadores.
     */
    public static function estadosCompra(): array
    {
        $mapa = [];
        foreach (['pendiente', 'completada', 'cancelada'] as $estado) {
            $mapa[$estado] = self::estadoCompra($estado);
        }
        return $mapa;
    }

    /**
     * Estados a los que puede pasar una compra y que el usuario puede ejecutar: solo un
     * Administrador completa o cancela.
     *
     * @return string[] Subconjunto de ['completada','cancelada']
     */
    public static function accionesEstado($estado, bool $esAdmin): array
    {
        if (!$esAdmin) {
            return [];
        }

        require_once __DIR__ . '/../../models/Compra.php';
        return Compra::TRANSICIONES[(string) $estado] ?? [];
    }

    /**
     * Agrega a cada fila su estado de presentación (estado_ui) y su código visible.
     */
    public function decorarEstados(array $compras): array
    {
        foreach ($compras as &$compra) {
            $compra['estado_ui'] = self::estadoCompra($compra['estado'] ?? '');
            $compra['codigo'] = self::codigoCompra($compra['idcompra']);
        }
        unset($compra);

        return $compras;
    }

    /**
     * Datos que consume el formulario de nueva compra. El catálogo se reduce a lo que usa
     * el JS (solo productos activos, sin precio de venta, descripción ni imagen) y NO se
     * filtra por stock: un producto agotado es justo el que hay que reponer.
     *
     * @return array ['productos' => [...], 'responsable' => string, 'esAdmin' => bool]
     */
    public function datosParaCrear(): array
    {
        require_once __DIR__ . '/../../models/Producto.php';
        require_once __DIR__ . '/../../models/Usuario.php';
        require_once __DIR__ . '/../../services/AuthorizationService.php';

        $productos = [];
        foreach ((new Producto())->getAll() as $p) {
            if ((int) $p['estado'] === 1) {
                $productos[] = [
                    'idproducto' => (int) $p['idproducto'],
                    'nombre' => $p['nombre'],
                    'codigo' => $p['codigo'],
                    'preciocompra' => $p['preciocompra'],
                    'stock' => (int) $p['stock'],
                    'stock_minimo' => (int) ($p['stock_minimo'] ?? 0),
                ];
            }
        }

        $idusuario = (int) ($_SESSION['usuario_id'] ?? 0);
        $usuario = (new Usuario())->getById($idusuario);

        return [
            'productos' => $productos,
            'responsable' => $usuario['nombre'] ?? '',
            'esAdmin' => (new AuthorizationService())->esAdministrador($idusuario),
        ];
    }

    /**
     * Solo un administrador puede registrar la compra ya completada; cualquier otro
     * valor (incluida 'cancelada') se reduce a 'pendiente'.
     */
    private function estadoInicialPermitido($estado)
    {
        require_once __DIR__ . '/../../services/AuthorizationService.php';
        $esAdmin = (new AuthorizationService())->esAdministrador($_SESSION['usuario_id'] ?? 0);
        return ($esAdmin && trim((string)$estado) === 'completada') ? 'completada' : 'pendiente';
    }

    /**
     * Prepara los datos de la compra desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosCompra($post_data)
    {
        $datos = [
            'idusuario' => (int)($_SESSION['usuario_id'] ?? 0),
            // El total lo calcula el modelo a partir de los detalles; nunca se lee del cliente
            'totalcompra' => 0,
            'fechacompra' => isset($post_data['fechacompra']) ? trim($post_data['fechacompra']) : date('Y-m-d'),
            'estado' => $this->estadoInicialPermitido($post_data['estado'] ?? 'pendiente'),
            'observaciones' => isset($post_data['observaciones']) && !empty($post_data['observaciones']) ? trim($post_data['observaciones']) : null,
            'detalles' => []
        ];

        // Procesar detalles de la compra
        if (isset($post_data['productos']) && is_array($post_data['productos'])) {
            foreach ($post_data['productos'] as $key => $idProducto) {
                if (!empty($idProducto)) {
                    $datos['detalles'][] = [
                        'idproducto' => (int)$idProducto,
                        'cantidad' => isset($post_data['cantidades'][$key]) ? (int)$post_data['cantidades'][$key] : 1,
                        'preciocompra' => isset($post_data['precios'][$key]) ? round((float)$post_data['precios'][$key], 2) : 0
                    ];
                }
            }
        }

        return $datos;
    }

    /**
     * Procesa el formulario para guardar una nueva compra
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Preparar datos de la compra
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosCompra($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Guardar compra usando el modelo
        $idCompra = $this->modelo->crear($datos);

        if ($idCompra) {
            return ['success' => true, 'message' => 'Compra registrada correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            return ['success' => false, 'message' => 'Error al registrar la compra: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'create.php'];
        }
    }
    /**
     * Detalle de una compra con su estado de presentación, código y el subtotal de
     * cada línea calculado aquí (la vista solo pinta).
     *
     * @param int|null $id ID de la compra
     * @return array|null null si el ID no es válido o la compra no existe
     */
    public function ver($id = null)
    {
        if (!$id) {
            return null;
        }

        $compra = $this->modelo->getById($id);
        if (!$compra) {
            return null;
        }

        $compra['estado_ui'] = self::estadoCompra($compra['estado']);
        $compra['codigo'] = self::codigoCompra($compra['idcompra']);
        $compra['desglose'] = $this->calcularDesglose($compra['detalles']);

        return $compra;
    }

    /**
     * Subtotal de cada línea y total, en centavos enteros para que la suma coincida con
     * el totalcompra que el modelo persistió (sin deriva de coma flotante).
     *
     * @param array $detalles Líneas de la compra (cantidad, preciocompra, producto_*)
     * @return array ['lineas' => [... + 'subtotal'], 'total' => float]
     */
    public function calcularDesglose(array $detalles): array
    {
        $totalCentavos = 0;
        $lineas = [];
        foreach ($detalles as $detalle) {
            $centavos = (int) round((float) $detalle['preciocompra'] * 100) * (int) $detalle['cantidad'];
            $totalCentavos += $centavos;
            $detalle['subtotal'] = $centavos / 100;
            $lineas[] = $detalle;
        }

        return ['lineas' => $lineas, 'total' => $totalCentavos / 100];
    }

    /**
     * Cambia el estado de una compra. El destino se valida contra una lista blanca y la
     * transición contra el estado bloqueado en BD (dentro del modelo), nunca contra un
     * estado enviado por el cliente.
     *
     * @param int    $id          ID de la compra
     * @param string $nuevoEstado completada | cancelada
     * @return array ['success','message','icon']
     */
    public function cambiarEstado(int $id, string $nuevoEstado): array
    {
        switch ($nuevoEstado) {
            case 'completada':
                return $this->completar($id);
            case 'cancelada':
                return $this->cancelar($id);
            default:
                return ['success' => false, 'message' => 'Estado de destino no válido.', 'icon' => 'error'];
        }
    }

    /**
     * Cancela una compra
     * 
     * @param int $id ID de la compra
     * @return array Resultado de la operación
     */
    private function cancelar($id = null)
    {
        if (!$id) {
            return ['success' => false, 'message' => 'ID de compra no válido', 'icon' => 'error'];
        }

        if ($this->modelo->cancelar($id)) {
            return ['success' => true, 'message' => 'Compra cancelada correctamente', 'icon' => 'success'];
        } else {
            return ['success' => false, 'message' => 'Error al cancelar la compra: ' . $this->modelo->getLastError(), 'icon' => 'error'];
        }
    }

    /**
     * Completa una compra (cambia estado a completada)
     * 
     * @param int $id ID de la compra
     * @return array Resultado de la operación
     */
    private function completar($id = null)
    {
        if (!$id) {
            return ['success' => false, 'message' => 'ID de compra no válido', 'icon' => 'error'];
        }

        if ($this->modelo->completar($id)) {
            return ['success' => true, 'message' => 'Compra marcada como completada', 'icon' => 'success'];
        } else {
            return ['success' => false, 'message' => 'Error al completar la compra: ' . $this->modelo->getLastError(), 'icon' => 'error'];
        }
    }
}
