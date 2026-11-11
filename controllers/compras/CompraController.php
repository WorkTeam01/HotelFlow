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
            'totalcompra' => isset($post_data['totalcompra']) ? (float)$post_data['totalcompra'] : 0,
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
                        'preciocompra' => isset($post_data['precios'][$key]) ? (float)$post_data['precios'][$key] : 0
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
        foreach ($compra['detalles'] as &$detalle) {
            $detalle['subtotal'] = $detalle['cantidad'] * $detalle['preciocompra'];
        }
        unset($detalle);

        return $compra;
    }

    /**
     * Cancela una compra
     * 
     * @param int $id ID de la compra
     * @return array Resultado de la operación
     */
    public function cancelar($id = null)
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
    public function completar($id = null)
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
