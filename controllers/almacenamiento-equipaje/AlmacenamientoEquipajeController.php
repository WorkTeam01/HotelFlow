<?php

/**
 * Controlador de Almacenamiento de Equipaje
 * 
 * Gestiona las operaciones relacionadas con el almacenamiento de equipaje
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */
class AlmacenamientoEquipajeController
{
    /**
     * Modelo de AlmacenamientoEquipaje
     * @var AlmacenamientoEquipaje
     */
    public $modelo;

    /**
     * Array para almacenar errores
     * @var array
     */
    private $errores = [];

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de AlmacenamientoEquipaje
        require_once __DIR__ . '/../../models/AlmacenamientoEquipaje.php';
        $this->modelo = new AlmacenamientoEquipaje();
    }

    /**
     * Obtiene los errores acumulados
     * 
     * @return array Lista de errores
     */
    public function getErrores()
    {
        return $this->errores;
    }

    /**
     * Limpia la lista de errores
     */
    public function limpiarErrores()
    {
        $this->errores = [];
    }

    /**
     * Agrega un error a la lista
     * 
     * @param string $error Mensaje de error
     */
    private function agregarError($error)
    {
        $this->errores[] = $error;
    }

    /**
     * Muestra la lista de registros de almacenamiento de equipaje
     * 
     * @param array $filtros Filtros opcionales para la consulta
     * @return array Lista de registros de almacenamiento de equipaje
     */
    public function index($filtros = [])
    {
        // Obtener todos los registros de almacenamiento de equipaje
        return $this->modelo->getAll($filtros);
    }

    /**
     * Prepara los datos necesarios para la vista de creación
     * 
     * @return array Datos para la vista de creación
     */
    public function crear()
    {
        // Obtener clientes y precios de equipaje para los selectores
        $clientes = $this->modelo->getClientes();
        $precios_equipaje = $this->modelo->getPreciosEquipaje();

        return [
            'clientes' => $clientes,
            'precios_equipaje' => $precios_equipaje
        ];
    }

    /**
     * Prepara los datos del registro de almacenamiento de equipaje desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatos($post_data)
    {
        $datos = [
            'idcliente' => isset($post_data['idcliente']) ? (int)$post_data['idcliente'] : 0,
            'idusuario' => $_SESSION['usuario_id'], // Usuario actual que registra el servicio
            'descripcion' => isset($post_data['descripcion']) ? trim($post_data['descripcion']) : null,
            'cantidad_piezas' => isset($post_data['cantidad_piezas']) ? (int)$post_data['cantidad_piezas'] : 1,
            'codigo_ticket' => isset($post_data['codigo_ticket']) ? trim($post_data['codigo_ticket']) : '',
            'idpequipaje' => isset($post_data['idpequipaje']) ? (int)$post_data['idpequipaje'] : 0,
            'monto' => isset($post_data['monto']) ? (float)$post_data['monto'] : 0,
            'fechaentrada' => isset($post_data['fechaentrada']) ? $post_data['fechaentrada'] : date('Y-m-d H:i:s'),
            'estado' => isset($post_data['estado']) ? $post_data['estado'] : 'almacenado'
        ];

        return $datos;
    }

    /**
     * Procesa el formulario para guardar un nuevo registro de almacenamiento de equipaje
     * 
     * @return array Resultado de la operación
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Preparar datos del registro
        $datos = $this->modelo->sanitizarDatos($this->prepararDatos($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Guardar registro usando el modelo
        $id_guardado = $this->modelo->crear($datos);

        if ($id_guardado) {
            return [
                'success' => true,
                'message' => 'Equipaje registrado correctamente',
                'icon' => 'success',
                'redirect' => 'index.php',
                'id' => $id_guardado
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al registrar el equipaje: ' . $this->modelo->getLastError(),
                'icon' => 'error',
                'redirect' => 'create.php'
            ];
        }
    }

    /**
     * Muestra el formulario para editar un registro de almacenamiento de equipaje
     * 
     * @param int $id ID del registro de almacenamiento de equipaje
     * @return array|null Datos del registro o redirige en caso de error
     */
    public function editar($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de equipaje no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/almacenamiento-equipaje');
            exit;
        }

        // Obtener datos del registro
        $equipaje = $this->modelo->getById($id);

        if (!$equipaje) {
            global $URL;
            $_SESSION['mensaje'] = 'Registro de equipaje no encontrado';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/almacenamiento-equipaje');
            exit;
        }

        // Obtener clientes y precios de equipaje para los selectores
        $clientes = $this->modelo->getClientes();
        $precios_equipaje = $this->modelo->getPreciosEquipaje();

        // Devolver los datos para la vista
        return [
            'equipaje' => $equipaje,
            'clientes' => $clientes,
            'precios_equipaje' => $precios_equipaje
        ];
    }

    /**
     * Procesa el formulario para actualizar un registro de almacenamiento de equipaje
     * 
     * @return array Resultado de la operación
     */
    public function actualizar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Obtener ID del registro
        $id = isset($_POST['idalmacen']) ? (int)$_POST['idalmacen'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de equipaje no válido', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Obtener datos actuales del registro
        $equipaje_actual = $this->modelo->getById($id);
        if (!$equipaje_actual) {
            return ['success' => false, 'message' => 'Registro de equipaje no encontrado para actualizar', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Si el equipaje ya está retirado, no permitir su actualización
        if ($equipaje_actual['estado'] === 'retirado') {
            return ['success' => false, 'message' => 'No se puede modificar un equipaje ya retirado', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Preparar datos del registro
        $datos = $this->prepararDatos($_POST);

        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Actualizar registro
        if ($this->modelo->actualizar($id, $datos)) {
            return ['success' => true, 'message' => 'Registro de equipaje actualizado correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            $error_message = 'Error al actualizar el registro de equipaje: ' . $this->modelo->getLastError();
            return ['success' => false, 'message' => $error_message, 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }
    }

    /**
     * Muestra los detalles de un registro de almacenamiento de equipaje
     * 
     * @param int $id ID del registro de almacenamiento de equipaje
     * @return array|null Datos del registro o redirige en caso de error
     */
    public function mostrar($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de equipaje no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/almacenamiento-equipaje');
            exit;
        }

        // Obtener datos del registro
        $equipaje = $this->modelo->getById($id);

        if (!$equipaje) {
            global $URL;
            $_SESSION['mensaje'] = 'Registro de equipaje no encontrado';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/almacenamiento-equipaje');
            exit;
        }

        // Devolver los datos para la vista
        return $equipaje;
    }

    /**
     * Cambia el estado de un registro de almacenamiento de equipaje
     * 
     * @param int $id ID del registro de almacenamiento de equipaje
     * @param string $nuevo_estado Nuevo estado ('almacenado', 'retirado', 'perdido', 'dañado')
     * @return array Resultado de la operación
     */
    public function cambiarEstado($id = null, $nuevo_estado = null)
    {
        if ($id === null || $nuevo_estado === null) {
            return ['success' => false, 'message' => 'Datos no válidos para cambiar el estado del equipaje', 'icon' => 'error'];
        }

        if (!in_array($nuevo_estado, ['almacenado', 'retirado', 'perdido', 'dañado'])) {
            return ['success' => false, 'message' => 'Estado no válido', 'icon' => 'error'];
        }

        // Verificar que el registro existe
        $equipaje = $this->modelo->getById($id);
        if (!$equipaje) {
            return ['success' => false, 'message' => 'Registro de equipaje no encontrado', 'icon' => 'error'];
        }

        // Verificar que el equipaje no esté ya retirado al intentar cambiar a otro estado
        if ($equipaje['estado'] === 'retirado' && $nuevo_estado !== 'retirado') {
            return ['success' => false, 'message' => 'No se puede cambiar el estado de un equipaje ya retirado', 'icon' => 'warning'];
        }

        if ($this->modelo->cambiarEstado($id, $nuevo_estado)) {
            $mensajes = [
                'almacenado' => 'almacenado',
                'retirado' => 'marcado como retirado',
                'perdido' => 'marcado como perdido',
                'dañado' => 'marcado como dañado'
            ];

            return [
                'success' => true,
                'message' => "Equipaje " . $mensajes[$nuevo_estado] . " correctamente",
                'icon' => 'success'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado del equipaje: ' . $this->modelo->getLastError(),
                'icon' => 'error'
            ];
        }
    }

    /**
     * Procesa un formulario de filtrado para la lista de equipajes
     * 
     * @return array Filtros procesados
     */
    public function procesarFiltros()
    {
        $filtros = [];

        if (isset($_GET['estado']) && !empty($_GET['estado'])) {
            $filtros['estado'] = $_GET['estado'];
        }

        if (isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio'])) {
            $filtros['fecha_inicio'] = $_GET['fecha_inicio'];
        }

        if (isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])) {
            $filtros['fecha_fin'] = $_GET['fecha_fin'];
        }

        if (isset($_GET['idcliente']) && !empty($_GET['idcliente'])) {
            $filtros['idcliente'] = (int)$_GET['idcliente'];
        }

        return $filtros;
    }

    /**
     * Obtiene estadísticas de almacenamiento de equipaje
     * 
     * @return array Estadísticas de almacenamiento de equipaje
     */
    public function getEstadisticas()
    {
        return $this->modelo->getEstadisticas();
    }

    /**
     * Obtiene los datos completos de un equipaje para generar el recibo
     * 
     * @param int $id ID del equipaje
     * @return array|false Datos del equipaje para el recibo o false si hay error
     */
    public function getDatosParaRecibo($id)
    {
        try {
            // Limpiar errores previos
            $this->limpiarErrores();

            // Validar el ID
            if (!is_numeric($id) || $id <= 0) {
                $this->agregarError('ID de equipaje inválido.');
                return false;
            }

            // Obtener datos del modelo
            $datos = $this->modelo->getDatosParaRecibo($id);

            if (!$datos) {
                $this->agregarError('No se encontró el equipaje especificado.');
                return false;
            }

            // Validar que el equipaje existe y tiene los datos mínimos necesarios
            if (empty($datos['codigo_ticket']) || empty($datos['cliente']['nombre_completo'])) {
                $this->agregarError('Datos incompletos para generar el recibo.');
                return false;
            }

            return $datos;
        } catch (Exception $e) {
            error_log('Error en getDatosParaRecibo: ' . $e->getMessage());
            $this->agregarError('Ocurrió un error inesperado. Intente nuevamente.');
            return false;
        }
    }

    /**
     * Método específico para el PDF del recibo
     * 
     * @param int $id ID del equipaje
     * @return array|false Datos optimizados para el PDF o false si hay error
     */
    public function generarDatosReciboPDF($id)
    {
        $datos = $this->getDatosParaRecibo($id);

        if (!$datos) {
            return false;
        }

        // Agregar datos adicionales específicos para el PDF
        $app_name = $GLOBALS['APP_NAME'] ?? 'HotelFlow';
        $datos['empresa'] = [
            'nombre' => strtoupper($app_name),
            'direccion' => 'Dirección de la empresa',
            'telefono' => '+1234567890',
            'email' => 'contacto@empresa.com'
        ];

        $datos['fecha_actual'] = date('d/m/Y');
        $datos['hora_actual'] = date('H:i');

        // Información adicional para el QR
        $datos['qr_info'] = $this->generarTextoQR($datos);

        return $datos;
    }

    /**
     * Genera el texto para el código QR
     * 
     * @param array $datos Datos del equipaje
     * @return string Texto para el QR
     */
    private function generarTextoQR($datos)
    {
        $qr_texto = "RECIBO DE EQUIPAJE\n";
        $qr_texto .= "Ticket: " . $datos['codigo_ticket'] . "\n";
        $qr_texto .= "Fecha: " . $datos['fecha_entrada_formateada'] . "\n";
        $qr_texto .= "Cliente: " . $datos['cliente']['nombre_completo'] . "\n";
        $qr_texto .= "Descripción: " . $datos['descripcion'] . "\n";
        $qr_texto .= "Piezas: " . $datos['cantidad_piezas'] . "\n";
        $qr_texto .= "Tamaño: " . $datos['equipaje']['tamano'] . "\n";
        $qr_texto .= "Monto: " . $datos['monto_formateado'] . " Bs\n";
        $qr_texto .= "Tiempo almacenado: " . $datos['tiempo_almacenado']['texto'] . "\n";
        $app_name = $GLOBALS['APP_NAME'] ?? 'HotelFlow';
        $qr_texto .= $app_name;

        return $qr_texto;
    }
}
