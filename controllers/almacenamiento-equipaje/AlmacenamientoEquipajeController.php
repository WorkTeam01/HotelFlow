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

    // ─── Helpers únicos de estado (fuente única, ninguna vista escribe switch) ───

    /**
     * Presentación de un estado de equipaje (bd o derivado).
     * Misma interfaz que estadoRecepcion() / estadoHabitacion().
     *
     * @return array{label:string,clase:string,badge:string,icono:string,orden:int}
     */
    public static function estadoEquipaje(string $estado): array
    {
        switch ($estado) {
            case 'almacenado':
                $ui = ['label' => 'Almacenado', 'clase' => 'warning', 'icono' => 'clock', 'orden' => 1];
                break;
            case 'retirado':
                $ui = ['label' => 'Retirado', 'clase' => 'success', 'icono' => 'check-circle', 'orden' => 2];
                break;
            case 'perdido':
                $ui = ['label' => 'Perdido', 'clase' => 'danger', 'icono' => 'exclamation-triangle', 'orden' => 3];
                break;
            case 'dañado':
                $ui = ['label' => 'Dañado', 'clase' => 'dark', 'icono' => 'times-circle', 'orden' => 4];
                break;
            case 'vencido':
                $ui = ['label' => 'Vencido', 'clase' => 'danger', 'icono' => 'hourglass-half', 'orden' => 5];
                break;
            default:
                $ui = ['label' => ucfirst($estado), 'clase' => 'secondary', 'icono' => 'question-circle', 'orden' => 99];
                break;
        }

        $ui['badge'] = 'badge-' . $ui['clase'];
        $ui['alerta'] = self::alertaEstado($estado);
        return $ui;
    }

    /**
     * Aviso que acompaña a un estado final en el historial del detalle.
     *
     * @return array{clase:string,icono:string,texto:string}|null
     */
    private static function alertaEstado(string $estado): ?array
    {
        switch ($estado) {
            case 'retirado':
                return ['clase' => 'success', 'icono' => 'info-circle', 'texto' => 'El equipaje ha sido entregado al cliente.'];
            case 'perdido':
                return ['clase' => 'danger', 'icono' => 'exclamation-triangle', 'texto' => 'El equipaje ha sido marcado como perdido.'];
            case 'dañado':
                return ['clase' => 'dark', 'icono' => 'exclamation-circle', 'texto' => 'El equipaje ha sido marcado como dañado.'];
            default:
                return null;
        }
    }

    /**
     * Lista de estados operables (no incluye 'vencido' ni 'retirado' — son derivados o finales).
     * Para filtros y dropdowns.
     *
     * @return array<string,array{label:string,clase:string,badge:string,icono:string,orden:int}>
     */
    public static function estadosEquipaje(): array
    {
        $estados = [];
        foreach (['almacenado', 'perdido', 'dañado'] as $estado) {
            $estados[$estado] = self::estadoEquipaje($estado);
        }
        return $estados;
    }

    /**
     * Estados que ofrece el filtro del listado (incluye los finales y el derivado 'vencido').
     *
     * @return array<string,array{label:string,clase:string,badge:string,icono:string,orden:int}>
     */
    public static function estadosFiltro(): array
    {
        $estados = [];
        foreach (['almacenado', 'vencido', 'retirado', 'perdido', 'dañado'] as $estado) {
            $estados[$estado] = self::estadoEquipaje($estado);
        }
        return $estados;
    }

    /**
     * Un equipaje retirado es final: no se edita ni cambia de estado.
     */
    public static function esEditable(string $estado): bool
    {
        return $estado !== 'retirado';
    }

    /**
     * Cambios de estado que se pueden ofrecer sobre un equipaje: un retirado es final.
     *
     * @return array<string,array{label:string,clase:string,badge:string,icono:string,orden:int,accion_label:string}>
     */
    public static function accionesEstado(string $estadoActual): array
    {
        if (!self::esEditable($estadoActual)) {
            return [];
        }

        $acciones = [];
        foreach (['retirado', 'perdido', 'dañado'] as $destino) {
            if ($destino === $estadoActual) {
                continue;
            }
            $ui = self::estadoEquipaje($destino);
            $ui['accion_label'] = 'Marcar como ' . $ui['label'];
            $acciones[$destino] = $ui;
        }
        return $acciones;
    }

    /**
     * Estado derivado calculado: 'almacenado' supera dias_alerta → 'vencido'.
     * Lee la config directamente (mismo patrón que estadoDerivado de RecepcionController).
     *
     * @param array $equipaje Fila con al menos 'estado' y 'fechaentrada'
     * @return string Estado real o derivado
     */
    public static function estadoDerivado(array $equipaje): string
    {
        $estado = $equipaje['estado'] ?? '';

        if ($estado !== 'almacenado') {
            return $estado;
        }

        $fechaEntrada = $equipaje['fechaentrada'] ?? null;
        if (empty($fechaEntrada)) {
            return $estado;
        }

        $config = require __DIR__ . '/../../config/config.php';
        $diasAlerta = $config['equipaje']['dias_alerta'] ?? 7;

        $timestampEntrada = strtotime($fechaEntrada);
        if ($timestampEntrada === false) {
            return $estado;
        }

        if (time() > $timestampEntrada + ($diasAlerta * 86400)) {
            return 'vencido';
        }

        return $estado;
    }

    /**
     * Calcula el tiempo de almacenamiento de un equipaje.
     *
     * @param string $fechaEntrada Fecha de entrada (formato datetime de BD)
     * @param string|null $fechaSalida Fecha de salida (null si aún está almacenado)
     * @return array{texto:string,horas:float,porcentaje:float}
     */
    public static function tiempoAlmacenado(string $fechaEntrada, ?string $fechaSalida = null): array
    {
        $inicio = new DateTime($fechaEntrada);
        $fin = !empty($fechaSalida) ? new DateTime($fechaSalida) : new DateTime();
        $intervalo = $inicio->diff($fin);

        // Texto legible
        $texto = '';
        if ($intervalo->days > 0) {
            $texto .= $intervalo->days . ' día(s) ';
        }
        if ($intervalo->h > 0) {
            $texto .= $intervalo->h . ' hora(s) ';
        }
        if ($intervalo->i > 0) {
            $texto .= $intervalo->i . ' minuto(s)';
        }
        if (empty($texto)) {
            $texto = 'Menos de un minuto';
        }

        $horas = $intervalo->days * 24 + $intervalo->h + ($intervalo->i / 60);
        // 7 días = 100% (configurable vía dias_alerta, pero aquí usamos un máximo fijo razonable)
        $porcentaje = min(100, ($horas / 168) * 100);

        return [
            'texto' => trim($texto),
            'horas' => round($horas, 1),
            'porcentaje' => round($porcentaje, 1),
        ];
    }

    /**
     * Agrega estado_ui (con vencido derivado) y tiempo_almacenado a cada fila.
     *
     * @param array $filas Filas del modelo (getAll)
     * @return array Mismas filas con campo extra 'estado_ui'
     */
    public static function decorarEstados(array $filas): array
    {
        return array_map(function ($fila) {
            $estadoReal = $fila['estado'] ?? '';
            $estadoCalculado = self::estadoDerivado($fila);
            $ui = self::estadoEquipaje($estadoCalculado);

            $fila['estado_derivado'] = $estadoCalculado;
            $fila['estado_ui'] = $ui;
            $fila['tiempo_almacenado'] = self::tiempoAlmacenado(
                $fila['fechaentrada'] ?? date('Y-m-d H:i:s'),
                $fila['fechasalida'] ?? null
            );
            return $fila;
        }, $filas);
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
        $filas = $this->modelo->getAll($filtros);
        return self::decorarEstados($filas);
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
     * Clientes para el selector del filtro del listado.
     */
    public function clientesParaFiltro()
    {
        return $this->modelo->getClientes();
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
            'idusuario' => $_SESSION['usuario_id'],
            'descripcion' => isset($post_data['descripcion']) ? trim($post_data['descripcion']) : null,
            'cantidad_piezas' => isset($post_data['cantidad_piezas']) ? (int)$post_data['cantidad_piezas'] : 1,
            'codigo_ticket' => isset($post_data['codigo_ticket']) ? trim($post_data['codigo_ticket']) : '',
            'idpequipaje' => isset($post_data['idpequipaje']) ? (int)$post_data['idpequipaje'] : 0,
            'monto' => 0, // Ignorado: se calcula server-side desde precio_equipaje
            'fechaentrada' => isset($post_data['fechaentrada']) ? $post_data['fechaentrada'] : date('Y-m-d H:i:s'),
            'estado' => 'almacenado', // el retiro y demás estados van por cambiarEstado()
            'metodopago' => isset($post_data['metodopago']) ? $post_data['metodopago'] : 'Efectivo'
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
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        $datos = $this->modelo->sanitizarDatos($this->prepararDatos($_POST));

        // Validar método de pago
        $metodosValidos = ['Efectivo', 'QR', 'OTROS'];
        if (!in_array($datos['metodopago'], $metodosValidos, true)) {
            return ['success' => false, 'message' => 'El método de pago no es válido.', 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Validar idpequipaje antes de que el modelo lo lea de BD
        if (empty($datos['idpequipaje']) || $datos['idpequipaje'] <= 0) {
            return ['success' => false, 'message' => 'El tipo de equipaje es obligatorio.', 'icon' => 'error', 'redirect' => 'create.php'];
        }

        $precio = $this->modelo->getPrecioEquipaje($datos['idpequipaje']);
        if (!$precio) {
            return ['success' => false, 'message' => 'El tipo de equipaje seleccionado no es válido.', 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Quitar monto de la validación del modelo (ya no viene del POST)
        unset($datos['monto']);
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

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
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de equipaje no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/almacenamiento-equipaje');
            exit;
        }

        $equipaje = $this->modelo->getById($id);

        if (!$equipaje) {
            global $URL;
            $_SESSION['mensaje'] = 'Registro de equipaje no encontrado';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/almacenamiento-equipaje');
            exit;
        }

        $estadoCalculado = self::estadoDerivado($equipaje);
        $equipaje['estado_ui'] = self::estadoEquipaje($estadoCalculado);
        $equipaje['estado_derivado'] = $estadoCalculado;
        $equipaje['tiempo_almacenado'] = self::tiempoAlmacenado(
            $equipaje['fechaentrada'] ?? date('Y-m-d H:i:s'),
            $equipaje['fechasalida'] ?? null
        );

        // Método de pago del folio
        require_once __DIR__ . '/../../models/Pago.php';
        $pagoModel = new Pago();
        $pagos = $pagoModel->getByEquipaje($id);
        $equipaje['metodopago'] = $this->obtenerMetodoPago($pagos);

        $clientes = $this->modelo->getClientes();
        $precios_equipaje = $this->modelo->getPreciosEquipaje();

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
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        $id = isset($_POST['idalmacen']) ? (int)$_POST['idalmacen'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de equipaje no válido', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        $equipaje_actual = $this->modelo->getById($id);
        if (!$equipaje_actual) {
            return ['success' => false, 'message' => 'Registro de equipaje no encontrado para actualizar', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        if ($equipaje_actual['estado'] === 'retirado') {
            return ['success' => false, 'message' => 'No se puede modificar un equipaje ya retirado', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Solo pasar campos editables (monto/cantidad/tipo/ticket quedan fijos)
        $datos = [
            'idcliente' => isset($_POST['idcliente']) ? (int)$_POST['idcliente'] : $equipaje_actual['idcliente'],
            'descripcion' => isset($_POST['descripcion']) ? trim($_POST['descripcion']) : null,
            // 'retirado' solo por cambiarEstado(), que registra la fecha de salida
            'estado' => (isset($_POST['estado']) && in_array($_POST['estado'], ['almacenado', 'perdido', 'dañado'], true))
                ? $_POST['estado']
                : $equipaje_actual['estado']
        ];

        $datos = $this->modelo->sanitizarDatos($datos);

        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

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
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de equipaje no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/almacenamiento-equipaje');
            exit;
        }

        $equipaje = $this->modelo->getById($id);

        if (!$equipaje) {
            global $URL;
            $_SESSION['mensaje'] = 'Registro de equipaje no encontrado';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/almacenamiento-equipaje');
            exit;
        }

        $estadoCalculado = self::estadoDerivado($equipaje);
        $equipaje['estado_ui'] = self::estadoEquipaje($estadoCalculado);
        $equipaje['estado_derivado'] = $estadoCalculado;
        $equipaje['tiempo_almacenado'] = self::tiempoAlmacenado(
            $equipaje['fechaentrada'] ?? date('Y-m-d H:i:s'),
            $equipaje['fechasalida'] ?? null
        );

        // Folio de pagos del equipaje
        require_once __DIR__ . '/../../models/Pago.php';
        $pagoModel = new Pago();
        $equipaje['pagos'] = $pagoModel->getByEquipaje($id);
        $equipaje['metodopago'] = $this->obtenerMetodoPago($equipaje['pagos']);
        $equipaje['timeline'] = self::armarTimeline($equipaje);

        return $equipaje;
    }

    /**
     * Historial del detalle como lista plana de eventos ya resueltos para pintar.
     * 'dia' viene solo en el primer evento de cada fecha. Un perdido/dañado no tiene
     * fecha de salida: su evento usa la última actualización.
     *
     * @param array $equipaje Fila decorada por mostrar() (estado_ui, tiempo_almacenado)
     * @return array<int,array{dia:?string,tono:string,icono:string,hora:string,titulo:string,mensaje:string,estado_ui:?array,detalles:array,alerta:?array,pie:string}>
     */
    public static function armarTimeline(array $equipaje): array
    {
        $fmtDia = static fn($f) => date('d/m/Y', strtotime($f));
        $fmtHora = static fn($f) => date('H:i', strtotime($f));

        $entrada = $equipaje['fechaentrada'];
        $actualizacion = $equipaje['fechaactualizacion'] ?? null;
        $ultimoDia = null;
        $eventos = [];

        $diaDe = static function (string $fecha) use (&$ultimoDia, $fmtDia) {
            $dia = $fmtDia($fecha);
            if ($dia === $ultimoDia) {
                return null;
            }
            $ultimoDia = $dia;
            return $dia;
        };

        $eventos[] = [
            'dia' => $diaDe($entrada),
            'tono' => 'primary',
            'icono' => 'calendar-plus',
            'hora' => $fmtHora($entrada),
            'titulo' => 'Registro inicial',
            'mensaje' => 'Equipaje registrado en el sistema con estado',
            'estado_ui' => self::estadoEquipaje('almacenado'),
            'detalles' => [
                'Cliente' => $equipaje['nombre_cliente'] ?? 'N/A',
                'Tipo' => $equipaje['tamano_equipaje'] ?? 'N/A',
                'Código' => $equipaje['codigo_ticket'],
            ],
            'alerta' => null,
            'pie' => 'Registrado por: ' . ($equipaje['nombre_usuario'] ?? 'Sistema'),
        ];

        $estadoFinal = in_array($equipaje['estado'], ['retirado', 'perdido', 'dañado'], true);
        $fechaFinal = $equipaje['estado'] === 'retirado'
            ? ($equipaje['fechasalida'] ?? $actualizacion)
            : $actualizacion;

        // El cambio a un estado final ya registra esa misma marca de tiempo: no se duplica como 'Actualización'
        if ($actualizacion && $actualizacion != $entrada && !($estadoFinal && $actualizacion == $fechaFinal)) {
            $eventos[] = [
                'dia' => $diaDe($actualizacion),
                'tono' => 'warning',
                'icono' => 'edit',
                'hora' => $fmtHora($actualizacion),
                'titulo' => 'Actualización',
                'mensaje' => 'Se actualizó la información del equipaje.',
                'estado_ui' => null,
                'detalles' => [],
                'alerta' => null,
                'pie' => '',
            ];
        }

        if ($estadoFinal && $fechaFinal) {
            $ui = self::estadoEquipaje($equipaje['estado']);
            $eventos[] = [
                'dia' => $diaDe($fechaFinal),
                'tono' => $ui['clase'],
                'icono' => $ui['icono'],
                'hora' => $fmtHora($fechaFinal),
                'titulo' => 'Cambio de estado',
                'mensaje' => 'Estado cambiado a',
                'estado_ui' => $ui,
                'detalles' => [],
                'alerta' => $ui['alerta'],
                'pie' => 'Tiempo total almacenado: ' . $equipaje['tiempo_almacenado']['texto'],
            ];
        }

        return $eventos;
    }

    /**
     * Obtiene el método de pago de las líneas del folio de un equipaje.
     *
     * @param array $pagos Líneas del folio
     * @return string Método de pago principal ('Efectivo', 'QR', 'OTROS')
     */
    private function obtenerMetodoPago($pagos)
    {
        foreach ($pagos as $pago) {
            if ($pago['tipo'] === 'pago' && !empty($pago['metodopago'])) {
                return $pago['metodopago'];
            }
        }
        return 'Efectivo';
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

        if ($equipaje['estado'] === $nuevo_estado) {
            return ['success' => false, 'message' => 'El equipaje ya se encuentra en ese estado', 'icon' => 'info'];
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
            $filtros['fecha_inicio'] = static::normalizarFechaFiltro($_GET['fecha_inicio']);
        }

        if (isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])) {
            $filtros['fecha_fin'] = static::normalizarFechaFiltro($_GET['fecha_fin']);
        }

        if (isset($_GET['idcliente']) && !empty($_GET['idcliente'])) {
            $filtros['idcliente'] = (int)$_GET['idcliente'];
        }

        return $filtros;
    }

    /**
     * Normaliza un valor de fecha recibido del cliente.
     *
     * Solo se acepta el formato AAAA-MM-DD que envía el input[type=date]; cualquier
     * otro valor se descarta. Así el valor que llega a la vista y a la query es
     * siempre una fecha, nunca texto arbitrario del usuario.
     */
    private static function normalizarFechaFiltro(string $valor): string
    {
        $valor = trim($valor);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) !== 1 || !DateTime::createFromFormat('!Y-m-d', $valor)) {
            return '';
        }

        return $valor;
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
