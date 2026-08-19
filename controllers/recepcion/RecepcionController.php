<?php

/**
 * Controlador de Recepción
 * 
 * Gestiona las operaciones relacionadas con las recepciones y check-ins
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */
class RecepcionController
{
    /**
     * Modelo de Recepcion
     * @var Recepcion
     */
    public $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Recepcion
        require_once __DIR__ . '/../../models/Recepcion.php';
        $this->modelo = new Recepcion();
    }

    /**
     * Muestra la lista de recepciones o el dashboard con habitaciones disponibles
     * 
     * @param string $filtro Filtro opcional por estado
     * @return array Datos para la vista
     */
    public function index($filtro = null)
    {
        // Obtener estadísticas
        $estadisticas = $this->modelo->getEstadisticas();

        // Obtener habitaciones disponibles para mostrar como tarjetas
        $habitaciones_disponibles = $this->modelo->getHabitacionesDisponibles();

        // Obtener habitaciones en mantenimiento
        $habitaciones_mantenimiento = $this->modelo->getHabitacionesEnMantenimiento();

        // Obtener recepciones en curso para mostrar
        $recepciones_en_curso = $this->modelo->getAllByEstado('en_curso');

        // Si hay un filtro específico, obtener recepciones por ese estado
        $recepciones_filtradas = [];
        if ($filtro) {
            $recepciones_filtradas = $this->modelo->getAllByEstado($filtro);
        } else {
            // Por defecto, mostrar todas las recepciones
            $recepciones_filtradas = $this->modelo->getAll();
        }

        return [
            'estadisticas' => $estadisticas,
            'habitaciones_disponibles' => $habitaciones_disponibles,
            'habitaciones_mantenimiento' => $habitaciones_mantenimiento,
            'recepciones_en_curso' => $recepciones_en_curso,
            'recepciones' => $recepciones_filtradas
        ];
    }

    /**
     * Muestra el formulario para crear una nueva recepción
     * 
     * @param int $idhabitacion ID de la habitación preseleccionada (opcional)
     * @return array Datos para la vista
     */
    public function crear($idhabitacion = null)
    {
        // Obtener datos para los selectores
        $clientes = $this->modelo->getClientes();
        $tarifas = $this->modelo->getTarifas();

        // Si hay una habitación preseleccionada, obtener sus detalles
        $habitacion = null;
        $habitaciones_disponibles = [];
        $habitaciones_por_piso = [];
        $pisos_unicos = [];

        if ($idhabitacion) {
            // Obtener detalles de la habitación específica
            require_once __DIR__ . '/../../controllers/habitaciones/HabitacionController.php';
            $habController = new HabitacionController();
            $habitacion = $habController->getById($idhabitacion);
        } else {
            // Sin habitación preseleccionada: obtener todas las disponibles agrupadas por piso
            $habitaciones_disponibles = $this->modelo->getHabitacionesDisponibles();
            $habitaciones_por_piso = self::agruparHabitacionesPorPiso($habitaciones_disponibles);
            $pisos_unicos = array_keys($habitaciones_por_piso);
        }

        return [
            'clientes' => $clientes,
            'tarifas' => $tarifas,
            'habitacion' => $habitacion,
            'habitaciones_disponibles' => $habitaciones_disponibles,
            'habitaciones_por_piso' => $habitaciones_por_piso,
            'pisos_unicos' => $pisos_unicos
        ];
    }

    /**
     * Agrupa una lista de habitaciones por piso y las ordena
     * (habitaciones privadas/individuales primero, luego por número natural;
     * los pisos se ordenan alfabéticamente)
     *
     * @param array $habitaciones Lista de habitaciones
     * @return array Mapa [nombre_piso => habitaciones[]]
     */
    public static function agruparHabitacionesPorPiso($habitaciones)
    {
        $pisos = [];

        foreach ($habitaciones as $habitacion) {
            $piso = $habitacion['piso_nombre'] ?? 'Sin piso';
            if (!isset($pisos[$piso])) {
                $pisos[$piso] = [];
            }
            $pisos[$piso][] = $habitacion;
        }

        // Ordenar habitaciones dentro de cada piso
        foreach ($pisos as $nombrePiso => &$habitacionesPiso) {
            usort($habitacionesPiso, function ($a, $b) {
                // Priorizar habitaciones privadas (individual) primero
                $tipoA = $a['tipo_nombre'] ?? '';
                $tipoB = $b['tipo_nombre'] ?? '';

                $esPrivadaA = stripos($tipoA, 'individual') !== false || stripos($tipoA, 'privada') !== false;
                $esPrivadaB = stripos($tipoB, 'individual') !== false || stripos($tipoB, 'privada') !== false;

                if ($esPrivadaA && !$esPrivadaB) return -1;
                if (!$esPrivadaA && $esPrivadaB) return 1;

                // Ordenar por número de habitación
                $numeroA = $a['numero'] ?? '0';
                $numeroB = $b['numero'] ?? '0';

                return strnatcmp($numeroA, $numeroB);
            });
        }
        unset($habitacionesPiso);

        // Ordenar pisos alfabéticamente
        ksort($pisos);

        return $pisos;
    }

    /**
     * Procesa los datos del formulario para crear una nueva recepción
     * 
     * @return array Resultado de la operación
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return [
                'success' => false,
                'message' => 'Método no permitido',
                'icon' => 'error',
                'redirect' => 'index.php'
            ];
        }

        // Preparar datos del registro
        $datos = $this->prepararDatos($_POST);
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return [
                'success' => false,
                'message' => $errores[0],
                'icon' => 'error',
                'redirect' => 'create.php' . (isset($_POST['idhabitacion']) ? '?idhabitacion=' . $_POST['idhabitacion'] : '')
            ];
        }

        // Procesar método de pago
        if (isset($_POST['pago_metodo']) && !empty($_POST['pago_metodo'])) {
            $datos['metodopago'] = $_POST['pago_metodo'];

            // Si es efectivo, procesar cambio
            if ($_POST['pago_metodo'] === 'Efectivo' && isset($_POST['pago_recibido'])) {
                $montoRecibido = (float)$_POST['pago_recibido'];
                $cambio = $montoRecibido - $datos['montopagado'];
                if ($cambio >= 0) {
                    $datos['cambio'] = $cambio;
                }
            }
        }

        // Guardar registro usando el modelo
        $idrecepcion = $this->modelo->crear($datos);

        if ($idrecepcion) {
            return [
                'success' => true,
                'message' => 'Check-in registrado correctamente',
                'icon' => 'success',
                'redirect' => 'show.php?id=' . $idrecepcion,
                'id' => $idrecepcion
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al registrar el check-in: ' . $this->modelo->getLastError(),
                'icon' => 'error',
                'redirect' => 'create.php' . (isset($_POST['idhabitacion']) ? '?idhabitacion=' . $_POST['idhabitacion'] : '')
            ];
        }
    }

    /**
     * Muestra los detalles de una recepción
     * 
     * @param int $id ID de la recepción
     * @return array|null Datos de la recepción
     */
    public function mostrar($id)
    {
        return $this->modelo->getById($id);
    }

    /**
     * Obtiene el folio (líneas de cargo/pago/reverso y saldo) de una recepción
     *
     * @param int $id ID de la recepción
     * @return array ['lineas' => array, 'saldo' => array]
     */
    public function obtenerFolio($id)
    {
        require_once __DIR__ . '/../../models/Pago.php';
        $pagoModelo = new Pago();

        return [
            'lineas' => $pagoModelo->getByRecepcion($id),
            'saldo' => $pagoModelo->calcularSaldo($id),
        ];
    }

    /**
     * Muestra el formulario para editar una recepción
     * 
     * @param int $id ID de la recepción
     * @return array Datos para la vista
     */
    public function editar($id)
    {
        // Obtener datos de la recepción
        $recepcion = $this->modelo->getById($id);

        if (!$recepcion) {
            return null;
        }

        // Obtener datos para los selectores
        $clientes = $this->modelo->getClientes();
        $tarifas = $this->modelo->getTarifas();

        // Obtener habitaciones disponibles (para posible cambio)
        $habitaciones_disponibles = $this->modelo->getHabitacionesDisponibles();

        // Agregar la habitación actual a la lista de opciones disponibles
        require_once __DIR__ . '/../../controllers/habitaciones/HabitacionController.php';
        $habController = new HabitacionController();
        $habitacion_actual = $habController->getById($recepcion['idhabitacion']);

        if ($habitacion_actual) {
            // Marcar esta habitación como la actual
            $habitacion_actual['es_actual'] = true;

            // Verificar si ya está en la lista
            $encontrada = false;
            foreach ($habitaciones_disponibles as &$hab) {
                if ($hab['id_habitacion'] == $habitacion_actual['id_habitacion']) {
                    $hab['es_actual'] = true;
                    $encontrada = true;
                    break;
                }
            }

            // Si no está en la lista, agregarla
            if (!$encontrada) {
                $habitaciones_disponibles[] = $habitacion_actual;
            }
        }

        return [
            'recepcion' => $recepcion,
            'clientes' => $clientes,
            'tarifas' => $tarifas,
            'habitaciones_disponibles' => $habitaciones_disponibles
        ];
    }

    /**
     * Actualiza una recepción existente
     * 
     * @param int $id ID de la recepción
     * @param array $datos Datos actualizados
     * @return array Resultado de la operación
     */
    public function actualizar($id, $datos)
    {
        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return [
                'success' => false,
                'message' => $errores[0],
                'icon' => 'error'
            ];
        }

        // Actualizar mediante el modelo
        if ($this->modelo->actualizar($id, $datos)) {
            return [
                'success' => true,
                'message' => 'Recepción actualizada correctamente',
                'icon' => 'success'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al actualizar la recepción: ' . $this->modelo->getLastError(),
                'icon' => 'error'
            ];
        }
    }

    /**
     * Cambia el estado de una recepción
     * 
     * @param int $id ID de la recepción
     * @param string $estado Nuevo estado
     * @return array Resultado de la operación
     */
    public function cambiarEstado($id, $estado)
    {
        if (!in_array($estado, ['reservado', 'en_curso', 'finalizado', 'cancelado'])) {
            return [
                'success' => false,
                'message' => 'Estado no válido',
                'icon' => 'error'
            ];
        }

        if ($this->modelo->cambiarEstado($id, $estado)) {
            $mensaje = '';
            switch ($estado) {
                case 'reservado':
                    $mensaje = 'Recepción marcada como reservada';
                    break;
                case 'en_curso':
                    $mensaje = 'Check-in realizado correctamente';
                    break;
                case 'finalizado':
                    $mensaje = 'Check-out realizado correctamente';
                    break;
                case 'cancelado':
                    $mensaje = 'Recepción cancelada correctamente';
                    break;
                default:
                    $mensaje = 'Estado actualizado correctamente';
                    break;
            }

            return [
                'success' => true,
                'message' => $mensaje,
                'icon' => 'success'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $this->modelo->getLastError(),
                'icon' => 'error'
            ];
        }
    }

    /**
     * Prepara los datos de recepción desde $_POST
     * siguiendo el enfoque tradicional del manejo de pagos
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatos($post_data)
    {
        // Datos básicos de la recepción
        $datos = [
            'idcliente' => isset($post_data['idcliente']) ? (int)$post_data['idcliente'] : 0,
            'idhabitacion' => isset($post_data['idhabitacion']) ? (int)$post_data['idhabitacion'] : 0,
            'idtarifa' => isset($post_data['idtarifa']) ? (int)$post_data['idtarifa'] : 0,
            'idusuario' => $_SESSION['usuario_id'], // Usuario actual
            'fechaentrada' => isset($post_data['fechaentrada']) ? $post_data['fechaentrada'] : date('Y-m-d H:i:s'),
            'fechasalida_prevista' => isset($post_data['fechasalida_prevista']) ? $post_data['fechasalida_prevista'] : '',
            'montototal' => isset($post_data['montototal']) ? (float)$post_data['montototal'] : 0,
            'montopagado' => isset($post_data['montopagado']) ? (float)$post_data['montopagado'] : 0,
            'estado' => isset($post_data['estado']) ? $post_data['estado'] : 'en_curso',
            'observaciones' => isset($post_data['observaciones']) ? trim($post_data['observaciones']) : null,
            'cambio' => null // Valor predeterminado
        ];

        // Método de pago
        if (isset($post_data['pago_metodo']) && !empty($post_data['pago_metodo'])) {
            $datos['metodopago'] = $post_data['pago_metodo'];
        } elseif (isset($post_data['metodopago']) && !empty($post_data['metodopago'])) {
            $datos['metodopago'] = $post_data['metodopago'];
        } else {
            $datos['metodopago'] = null;
        }

        // Para pagos en efectivo, se necesita el dinero físico recibido para que el modelo
        // recalcule el cambio a partir del monto total real (tomado de la tarifa en BD, no del cliente)
        if ($datos['metodopago'] === 'Efectivo') {
            $dineroRecibido = 0;
            if (isset($post_data['pago_recibido']) && !empty($post_data['pago_recibido'])) {
                $dineroRecibido = (float)$post_data['pago_recibido'];
            } elseif (isset($post_data['monto_recibido']) && !empty($post_data['monto_recibido'])) {
                $dineroRecibido = (float)$post_data['monto_recibido'];
            }
            $datos['dinero_recibido'] = $dineroRecibido;
        } else {
            $datos['dinero_recibido'] = null;
        }

        // montopagado, cambio y montototal se recalculan en el modelo a partir de la tarifa
        // real en BD; los valores aquí son solo un fallback si el modelo no los recalculara.
        $datos['montopagado'] = $datos['montototal'];
        $datos['cambio'] = null;

        return $datos;
    }
}
