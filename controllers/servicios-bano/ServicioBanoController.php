<?php

/**
 * Controlador de Servicios de Baños
 * 
 * Gestiona las operaciones relacionadas con los servicios de baños
 * 
 * @author Sistema de Alojamiento
 * @version 2.0
 */
require_once __DIR__ . '/../../services/AuthorizationService.php';

class ServicioBanoController
{
    /**
     * Modelo de ServicioBano
     * @var ServicioBano
     */
    public $modelo;

    /**
     * Modelo de Producto
     * @var Producto
     */
    public $productoModelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de ServicioBano
        require_once __DIR__ . '/../../models/ServicioBano.php';
        $this->modelo = new ServicioBano();

        // Incluir el modelo de Producto
        require_once __DIR__ . '/../../models/Producto.php';
        $this->productoModelo = new Producto();
    }

    /**
     * Registra en el log un error capturado, con contexto sobre dónde ocurrió
     *
     * @param string $contexto Descripción de la operación que falló
     * @param Exception $e Excepción capturada
     */
    private function logError($contexto, $e)
    {
        error_log($contexto . ': ' . $e->getMessage());
    }

    /**
     * Muestra la lista de servicios de baños
     * 
     * @return array Lista de servicios de baños
     */
    public function index()
    {
        return $this->decorarEstados($this->modelo->getAll());
    }

    /**
     * Prepara los datos para el formulario de creación
     * 
     * @return array Datos para el formulario
     */
    public function crear()
    {
        // Obtener los baños disponibles para el select
        $banos_disponibles = $this->getBanosDisponibles();

        // Huéspedes con estancia en curso: el servicio se liga a la estancia, no a una persona suelta
        require_once __DIR__ . '/../../models/Recepcion.php';
        $estancias = (new Recepcion())->getInHouse();

        return [
            'banos_disponibles' => $banos_disponibles,
            'estancias_en_curso' => $estancias
        ];
    }

    /**
     * Obtiene todos los baños disponibles
     * 
     * @return array Lista de baños disponibles
     */
    public function getBanosDisponibles()
    {
        return $this->modelo->getBanosDisponibles();
    }

    /**
     * Obtiene todos los clientes registrados
     * 
     * @return array Lista de clientes
     */
    public function getClientes()
    {
        return $this->modelo->getClientes();
    }

    /**
     * Prepara los datos del servicio de baño desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosServicio($post_data)
    {
        // El total sale del precio del baño en BD, nunca del cliente
        $idbano = isset($post_data['idbano']) ? (int)$post_data['idbano'] : 0;
        $total = $this->modelo->getPrecioBano($idbano) ?? 0;

        $tipoCliente = ($post_data['tipo_cliente'] ?? 'Publico') === 'Huesped' ? 'Huesped' : 'Publico';
        // Solo un huésped se liga a una estancia; el servidor deriva el cliente de ella
        $idrecepcion = $tipoCliente === 'Huesped' && !empty($post_data['idrecepcion']) ? (int)$post_data['idrecepcion'] : null;
        $modalidad = $this->modalidadSolicitada($post_data, $idrecepcion);

        // Un método fuera de la lista blanca no se corrige en silencio: lo rechaza validarMetodoPago()
        $metodopago = $modalidad === 'folio' ? null : ($post_data['metodopago'] ?? 'Efectivo');
        // Solo en efectivo hay un monto recibido distinto del total
        $pagorecibido = $modalidad === 'folio' ? 0 : ($metodopago === 'Efectivo' && isset($post_data['pagorecibido'])
            ? (float)$post_data['pagorecibido']
            : $total);
        $cambio = max(0, $pagorecibido - $total);

        return [
            'idbano' => $idbano,
            'idusuario' => $_SESSION['usuario_id'], // Usuario actual que registra el servicio
            'idcliente' => null,
            'idrecepcion' => $idrecepcion,
            'tipo_cliente' => $tipoCliente,
            'modalidad_cobro' => $modalidad,
            'total' => $total,
            'metodopago' => $metodopago,
            'pagorecibido' => $pagorecibido,
            'cambio' => $cambio,
            'observaciones' => mb_substr(trim($post_data['observaciones'] ?? ''), 0, 255)
            // Nota: 'estado' se maneja exclusivamente vía cambiarEstadoServicio(),
            // nunca a partir de datos de creación/actualización del formulario.
        ];
    }

    /**
     * Modalidades de cobro que admite un servicio. [D2]
     * Con una estancia en curso se puede cobrar al momento o cargar al folio; sin ella, solo al contado.
     *
     * @param int|null $idrecepcion
     * @return array
     */
    public function modalidadesPermitidas($idrecepcion)
    {
        return $idrecepcion ? ['contado', 'folio'] : ['contado'];
    }

    private function modalidadSolicitada($post_data, $idrecepcion)
    {
        $pedida = ($post_data['modalidad_cobro'] ?? 'contado') === 'folio' ? 'folio' : 'contado';
        return in_array($pedida, $this->modalidadesPermitidas($idrecepcion), true) ? $pedida : 'contado';
    }

    private function validarMetodoPago($metodopago)
    {
        return in_array($metodopago, ['Efectivo', 'QR', 'OTROS'], true) ? null : 'El método de pago no es válido.';
    }

    /**
     * Procesa el formulario para guardar un nuevo servicio de baño
     * y descuenta el insumo configurado
     * 
     * @return array Resultado de la operación
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        try {
            $datos = $this->modelo->sanitizarDatos($this->prepararDatosServicio($_POST));

            $errores = $this->modelo->validarDatos($datos);
            if (empty($errores) && $datos['modalidad_cobro'] === 'contado' && ($errorMetodo = $this->validarMetodoPago($datos['metodopago']))) {
                $errores[] = $errorMetodo;
            }
            if (empty($errores) && $datos['tipo_cliente'] === 'Huesped' && !$datos['idrecepcion']) {
                $errores[] = 'Seleccione la estancia del huésped.';
            }
            if (!empty($errores)) {
                return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
            }

            $resultado = $this->modelo->registrar($datos, $_SESSION['usuario_id']);
            if ($resultado === false) {
                return ['success' => false, 'message' => $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'create.php'];
            }

            $mensaje = 'Servicio de baño registrado correctamente.';
            if ($resultado['alertaStockBajo']) {
                $mensaje .= " ADVERTENCIA: Stock bajo - Quedan {$resultado['stock_restante']} unidades (mínimo recomendado: {$resultado['stock_minimo']}).";
            }

            return [
                'success' => true,
                'message' => $mensaje,
                'icon' => $resultado['alertaStockBajo'] ? 'warning' : 'success',
                'redirect' => 'index.php'
            ];
        } catch (Exception $e) {
            $this->logError('Error inesperado al crear servicio de baño', $e);
            return [
                'success' => false,
                'message' => 'Ocurrió un error inesperado. Intente nuevamente.',
                'icon' => 'error',
                'redirect' => 'create.php'
            ];
        }
    }

    /**
     * Crea un servicio de baño rápido
     * 
     * @param int $idbano ID del baño (opcional)
     * @return array Resultado de la operación
     */
    public function crearServicioRapido($idbano = null)
    {
        try {
            // Si no se proporcionó un baño, obtener el primer baño disponible
            if ($idbano === null) {
                $banos_disponibles = $this->getBanosDisponibles();
                if (empty($banos_disponibles)) {
                    return [
                        'success' => false,
                        'message' => 'No hay baños disponibles en este momento',
                        'icon' => 'error'
                    ];
                }
                $idbano = $banos_disponibles[0]['idbano'];
            }

            $resultado = $this->modelo->registrar([
                'idbano' => $idbano,
                'idcliente' => null,
                'tipo_cliente' => 'Publico',
                'metodopago' => 'Efectivo'
            ], $_SESSION['usuario_id']);

            if ($resultado === false) {
                return ['success' => false, 'message' => $this->modelo->getLastError(), 'icon' => 'error'];
            }

            $mensaje = 'Servicio de baño registrado correctamente.';
            if ($resultado['alertaStockBajo']) {
                $mensaje .= " ADVERTENCIA: Stock bajo de papel higiénico ({$resultado['stock_restante']} unidades restantes).";
            }

            return [
                'success' => true,
                'message' => $mensaje,
                'icon' => $resultado['alertaStockBajo'] ? 'warning' : 'success',
                'idservicio' => $resultado['idservicio'],
                'total' => $resultado['total']
            ];
        } catch (Exception $e) {
            $this->logError('Error inesperado al crear servicio de baño rápido', $e);
            return [
                'success' => false,
                'message' => 'Ocurrió un error inesperado. Intente nuevamente.',
                'icon' => 'error'
            ];
        }
    }

    /**
     * Verifica si se puede crear un servicio de baño
     * 
     * @return array Resultado de la validación
     */
    public function validarDisponibilidadServicio()
    {
        try {
            // Verificar producto y stock
            $config = require __DIR__ . '/../../config/config.php';
            $nombreInsumo = $config['servicios_bano']['producto_consumible'];
            $producto = $this->productoModelo->getByNombre($nombreInsumo);

            if (!$producto) {
                return [
                    'disponible' => false,
                    'message' => 'Producto "' . $nombreInsumo . '" no configurado'
                ];
            }

            if (!isset($producto['estado']) || $producto['estado'] != 1) {
                return [
                    'disponible' => false,
                    'message' => 'Producto "' . $nombreInsumo . '" inactivo'
                ];
            }

            $stockActual = (int)($producto['stock'] ?? 0);
            if ($stockActual <= 0) {
                return [
                    'disponible' => false,
                    'message' => 'Sin stock de papel higiénico'
                ];
            }

            return [
                'disponible' => true,
                'message' => 'Servicio disponible',
                'stock_actual' => $stockActual,
                'stock_minimo' => $producto['stock_minimo'] ?? 0
            ];
        } catch (Exception $e) {
            $this->logError('Error al verificar disponibilidad de servicio de baño', $e);
            return [
                'disponible' => false,
                'message' => 'Error al verificar disponibilidad del servicio.'
            ];
        }
    }

    /**
     * Datos para los formularios de detalle y edición de un servicio de baño.
     * No redirige: la vista decide el destino antes de incluir el layout.
     *
     * @param int|null $id ID del servicio de baño
     * @return array|null Datos del servicio, o null si el ID es inválido o no existe
     */
    public function obtener($id = null)
    {
        if (!$id) {
            return null;
        }

        $servicio = $this->modelo->getById($id);
        if (!$servicio) {
            return null;
        }

        require_once __DIR__ . '/../../models/Pago.php';

        return [
            'servicio' => $this->decorarEstados([$servicio])[0],
            'folio' => (new Pago())->getByServicioBano($id)
        ];
    }

    /**
     * Procesa el formulario para actualizar un servicio de baño
     * 
     * @return array Resultado de la operación
     */
    public function actualizar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Obtener ID del servicio
        $id = isset($_POST['idservicio']) ? (int)$_POST['idservicio'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de servicio de baño no válido', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        if (!$this->modelo->getById($id)) {
            return ['success' => false, 'message' => 'Servicio de baño no encontrado para actualizar', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Solo se editan campos descriptivos; el dinero y el estado no vienen del formulario
        $datos = ['observaciones' => mb_substr(trim($_POST['observaciones'] ?? ''), 0, 255)];

        if ($this->modelo->actualizar($id, $datos)) {
            return ['success' => true, 'message' => 'Servicio de baño actualizado correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        }

        return ['success' => false, 'message' => $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => "update.php?id=$id"];
    }

    /**
     * Cambia el estado de un servicio de baño.
     *
     * El estado actual se lee siempre de la BD (nunca del cliente) y la matriz de
     * transiciones se aplica en el modelo.
     *
     * @param int $id ID del servicio de baño
     * @param string $nuevo_estado Nuevo estado
     * @param string $motivo Obligatorio al cancelar
     * @return array Resultado de la operación
     */
    public function cambiarEstadoServicio($id = null, $nuevo_estado = null, $motivo = '')
    {
        if ($id === null || $nuevo_estado === null) {
            return ['success' => false, 'message' => 'Datos no válidos para cambiar el estado del servicio', 'icon' => 'error'];
        }

        if (!in_array($nuevo_estado, ServicioBano::ESTADOS_VALIDOS, true)) {
            return ['success' => false, 'message' => 'Estado no válido', 'icon' => 'error'];
        }

        if (!in_array($nuevo_estado, ['finalizado', 'cancelado'], true)) {
            return ['success' => false, 'message' => 'Esa acción no está disponible.', 'icon' => 'warning'];
        }

        if (!$this->modelo->getById($id)) {
            return ['success' => false, 'message' => 'Servicio de baño no encontrado', 'icon' => 'error'];
        }

        $auth = new AuthorizationService();
        $esAdmin = $auth->esAdministrador($_SESSION['usuario_id']);

        if ($nuevo_estado === 'finalizado') {
            if ($this->modelo->finalizar($id, $_SESSION['usuario_id'], $esAdmin)) {
                return ['success' => true, 'message' => 'Servicio de baño finalizado correctamente', 'icon' => 'success'];
            }
            return ['success' => false, 'message' => $this->modelo->getLastError(), 'icon' => 'error'];
        }

        if ($this->modelo->cancelar($id, $_SESSION['usuario_id'], $esAdmin, $motivo)) {
            return ['success' => true, 'message' => 'Servicio de baño cancelado correctamente', 'icon' => 'success'];
        }

        return ['success' => false, 'message' => $this->modelo->getLastError(), 'icon' => 'error'];
    }

    /**
     * Acciones de estado que el usuario puede ejecutar sobre un servicio; las vistas solo pintan botones.
     *
     * @param array $servicio Fila de servicio (necesita 'estado')
     * @return array Lista de estados destino permitidos
     */
    public function accionesEstado(array $servicio)
    {
        $auth = new AuthorizationService();
        $esAdmin = $auth->esAdministrador($_SESSION['usuario_id']);

        return array_values(array_filter(
            ['finalizado', 'cancelado'],
            fn($destino) => ServicioBano::transicionPermitida($servicio['estado'], $destino, $esAdmin)
        ));
    }

    /**
     * Presentación de un estado de servicio: etiqueta, color AdminLTE, badge e icono. Fuente única;
     * ninguna vista vuelve a escribir un switch sobre el estado. 'excedido' es derivado (ver estadoDerivado()).
     * Vocabulario: amarillo = en uso (ocupado), rojo = excedido, verde = finalizado, gris = cancelado.
     *
     * @return array{label:string,clase:string,badge:string,icono:string,orden:int}
     */
    public static function estadoServicio(string $estado): array
    {
        switch ($estado) {
            case 'iniciado':
                $ui = ['label' => 'En uso', 'clase' => 'warning', 'icono' => 'clock', 'orden' => 1];
                break;
            case 'excedido':
                $ui = ['label' => 'Excedido', 'clase' => 'danger', 'icono' => 'hourglass-half', 'orden' => 2];
                break;
            case 'finalizado':
                $ui = ['label' => 'Finalizado', 'clase' => 'success', 'icono' => 'flag-checkered', 'orden' => 3];
                break;
            case 'cancelado':
                $ui = ['label' => 'Cancelado', 'clase' => 'secondary', 'icono' => 'ban', 'orden' => 4];
                break;
            default:
                $ui = ['label' => ucfirst($estado), 'clase' => 'secondary', 'icono' => 'question-circle', 'orden' => 99];
                break;
        }

        $ui['badge'] = 'badge-' . $ui['clase'];
        return $ui;
    }

    /**
     * Etiqueta de cobro de un servicio: método de pago o, si va al folio, la habitación.
     */
    public static function etiquetaCobro(array $servicio): string
    {
        if (($servicio['modalidad_cobro'] ?? 'contado') === 'folio') {
            return 'Folio · Hab. ' . ($servicio['numero_habitacion'] ?? 'N/A');
        }
        $metodo = $servicio['metodopago'] ?? null;
        return $metodo === 'OTROS' ? 'Otros' : ($metodo ?? 'N/A');
    }

    /**
     * Etiqueta de cliente: "Público" o el huésped con su habitación.
     */
    public static function etiquetaCliente(array $servicio): string
    {
        if (($servicio['tipo_cliente'] ?? 'Publico') === 'Publico') {
            return 'Público';
        }
        $nombre = $servicio['nombre_cliente'] ?? 'N/A';
        return isset($servicio['numero_habitacion']) ? $nombre . ' · Hab. ' . $servicio['numero_habitacion'] : $nombre;
    }

    /**
     * Un servicio cancelado ya no admite cambios (ni siquiera de campos descriptivos).
     */
    public static function esEditable(array $servicio): bool
    {
        return $servicio['estado'] !== 'cancelado';
    }

    /**
     * Agrega a cada servicio los datos de presentación que las vistas necesitan.
     *
     * @param array $servicios Filas de servicio
     * @return array Las mismas filas con estado_ui, minutos_en_uso, cobro_label y cliente_label
     */
    public function decorarEstados(array $servicios): array
    {
        foreach ($servicios as &$servicio) {
            $servicio['estado_ui'] = self::estadoServicio($this->estadoDerivado($servicio));
            $servicio['minutos_en_uso'] = $servicio['estado'] === 'iniciado' ? $this->minutosEnCurso($servicio['fecha']) : null;
            $servicio['cobro_label'] = self::etiquetaCobro($servicio);
            $servicio['cliente_label'] = self::etiquetaCliente($servicio);
        }
        unset($servicio);

        return $servicios;
    }

    /**
     * Minutos que lleva en curso un servicio iniciado.
     */
    private function minutosEnCurso($inicio)
    {
        return max(0, (int)floor((time() - strtotime($inicio)) / 60));
    }

    /**
     * Estado de presentación de un servicio: 'excedido' es derivado (un iniciado que supera
     * los minutos de alerta configurados; 0 desactiva la alerta), no un valor de BD.
     *
     * @param array $servicio Fila de servicio (estado, fecha)
     * @return string
     */
    public function estadoDerivado(array $servicio)
    {
        $alerta = $this->minutosAlerta();
        if ($alerta > 0 && $servicio['estado'] === 'iniciado' && $this->minutosEnCurso($servicio['fecha']) > $alerta) {
            return 'excedido';
        }
        return $servicio['estado'];
    }

    private function minutosAlerta()
    {
        $config = require __DIR__ . '/../../config/config.php';
        return (int)$config['servicios_bano']['minutos_alerta'];
    }

    /**
     * Baños con su ocupación para el rack del listado: libre, en_uso, excedido, mantenimiento o fuera_servicio.
     *
     * @return array
     */
    public function getBanosConOcupacion()
    {
        $banos = $this->modelo->getBanosConOcupacion();
        $alerta = $this->minutosAlerta();

        foreach ($banos as &$bano) {
            $bano['minutos'] = null;
            if ($bano['idservicio']) {
                $bano['minutos'] = $this->minutosEnCurso($bano['inicio']);
                $bano['ocupacion'] = $alerta > 0 && $bano['minutos'] > $alerta ? 'excedido' : 'en_uso';
            } else {
                $bano['ocupacion'] = $bano['estado'] === 'disponible' ? 'libre' : $bano['estado'];
            }
        }
        unset($bano);

        return $banos;
    }

    /**
     * Obtiene estadísticas de servicios de baños
     * 
     * @return array Estadísticas de servicios de baños
     */
    public function getEstadisticas()
    {
        return $this->modelo->getEstadisticas();
    }
}
