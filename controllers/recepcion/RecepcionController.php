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
        // Liberar reservas vencidas (no-show) antes de leer cualquier dato para el
        // panel — fail-open: si falla, se loguea y el panel se carga igual.
        $config = require __DIR__ . '/../../config/config.php';
        $noshowHoras = $config['recepcion']['noshow_horas'] ?? 6;
        $this->modelo->liberarNoShows($noshowHoras);

        // Obtener estadísticas
        $estadisticas = $this->modelo->getEstadisticas();

        // Obtener habitaciones disponibles para mostrar como tarjetas
        $habitaciones_disponibles = $this->modelo->getHabitacionesDisponibles();

        // Obtener habitaciones en mantenimiento
        $habitaciones_mantenimiento = $this->modelo->getHabitacionesEnMantenimiento();

        // Obtener recepciones en curso para mostrar
        $recepciones_en_curso = $this->modelo->getAllByEstado('en_curso');

        // Reservas: llegadas de hoy y próximas (distinción operativa reserva vs. en curso)
        $llegadas_hoy = $this->modelo->getLlegadasHoy();
        $reservas_proximas = $this->modelo->getReservasProximas();

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
            'llegadas_hoy' => $llegadas_hoy,
            'reservas_proximas' => $reservas_proximas,
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

        // La vista es una sola página: el rack de habitaciones disponibles se muestra
        // siempre (agrupado por piso) y ?idhabitacion= solo preselecciona una.
        $habitaciones_disponibles = $this->modelo->getHabitacionesDisponibles();
        $habitaciones_por_piso = self::agruparHabitacionesPorPiso($habitaciones_disponibles);
        $pisos_unicos = array_keys($habitaciones_por_piso);

        $habitacion = null;
        if ($idhabitacion) {
            require_once __DIR__ . '/../../controllers/habitaciones/HabitacionController.php';
            $habController = new HabitacionController();
            $habitacion = $habController->getById($idhabitacion);
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
     * Estado canónico para UI. Fuente única de verdad de etiqueta/color/icono para
     * TODAS las vistas y partials del módulo (elimina los switch de estado duplicados).
     * Cubre los estados de BD (recepcion.estado y habitaciones.estado) y los estados
     * derivados que calcula el controlador (no_show, salida_vencida).
     *
     * @param string $estado 'reservado'|'en_curso'|'ocupada'|'finalizado'|'cancelado'
     *                        |'disponible'|'limpieza'|'mantenimiento'|'no_show'|'salida_vencida'
     * @return array{label:string, clase:string, badge:string, icono:string, orden:int}
     *   clase = sufijo AdminLTE ('success'|'warning'|'info'|'danger'|'secondary')
     *   badge = 'badge-'.clase   icono = nombre FontAwesome sin prefijo 'fa-'
     */
    public static function estadoRecepcion(string $estado): array
    {
        switch ($estado) {
            case 'disponible':
                $ui = ['label' => 'Disponible', 'clase' => 'success', 'icono' => 'door-open', 'orden' => 1];
                break;
            case 'reservado':
                $ui = ['label' => 'Reservada', 'clase' => 'info', 'icono' => 'calendar-check', 'orden' => 2];
                break;
            case 'en_curso':
            case 'ocupada':
                $ui = ['label' => 'Ocupada', 'clase' => 'warning', 'icono' => 'bed', 'orden' => 3];
                break;
            case 'salida_vencida':
                $ui = ['label' => 'Salida vencida', 'clase' => 'danger', 'icono' => 'clock', 'orden' => 4];
                break;
            case 'limpieza':
                $ui = ['label' => 'Por limpiar', 'clase' => 'secondary', 'icono' => 'broom', 'orden' => 4];
                break;
            case 'finalizado':
                $ui = ['label' => 'Finalizada', 'clase' => 'secondary', 'icono' => 'check-circle', 'orden' => 5];
                break;
            case 'no_show':
                $ui = ['label' => 'No-show', 'clase' => 'danger', 'icono' => 'user-slash', 'orden' => 6];
                break;
            case 'cancelado':
                $ui = ['label' => 'Cancelada', 'clase' => 'danger', 'icono' => 'times-circle', 'orden' => 6];
                break;
            case 'mantenimiento':
                $ui = ['label' => 'Mantenimiento', 'clase' => 'danger', 'icono' => 'tools', 'orden' => 7];
                break;
            default:
                $ui = ['label' => 'Desconocido', 'clase' => 'secondary', 'icono' => 'question-circle', 'orden' => 99];
                break;
        }

        $ui['badge'] = 'badge-' . $ui['clase'];
        return $ui;
    }

    /**
     * Estado derivado de una fila de recepción, resuelto contra la hora actual.
     * - 'reservado' con entrada vencida hace más de noshow_horas → 'no_show'
     * - 'en_curso' con salida prevista ya pasada                  → 'salida_vencida'
     * - resto → el estado tal cual está en BD
     *
     * @param array $recepcion Fila con al menos 'estado', 'fechaentrada', 'fechasalida_prevista'
     * @return string
     */
    public static function estadoDerivado(array $recepcion): string
    {
        $estado = $recepcion['estado'] ?? '';
        $ahora = time();

        if ($estado === 'reservado' && !empty($recepcion['fechaentrada'])) {
            $config = require __DIR__ . '/../../config/config.php';
            $horas = $config['recepcion']['noshow_horas'] ?? 6;
            if (strtotime($recepcion['fechaentrada']) < $ahora - ($horas * 3600)) {
                return 'no_show';
            }
        }

        if ($estado === 'en_curso' && !empty($recepcion['fechasalida_prevista'])) {
            if (strtotime($recepcion['fechasalida_prevista']) < $ahora) {
                return 'salida_vencida';
            }
        }

        return $estado;
    }

    /**
     * Etiqueta legible de la tarifa aplicada: duración + unidad de estancia + precio.
     * Fuente única para show.php, tarjeta-registro.php y recibo.php (las vistas no
     * vuelven a componer este texto ni a mostrar `tipo_estancia` crudo como "tarifa").
     *
     * @param array $recepcion Fila de Recepcion::getById() (usa tipo_estancia, duracion, precio_tarifa)
     * @return string Ej. "1 día(s) · Bs 150.00"; cadena vacía si no hay datos de tarifa
     */
    public static function etiquetaTarifa(array $recepcion): string
    {
        $duracion = (int) ($recepcion['duracion'] ?? 0);
        if ($duracion <= 0) {
            return '';
        }

        $unidad = ($recepcion['tipo_estancia'] ?? '') === 'horas' ? 'hora(s)' : 'día(s)';
        $label = $duracion . ' ' . $unidad;

        if (!empty($recepcion['precio_tarifa'])) {
            $label .= ' · Bs ' . number_format((float) $recepcion['precio_tarifa'], 2);
        }

        return $label;
    }

    /**
     * Añade 'estado_ui' (salida de estadoRecepcion() sobre el estado derivado) a cada
     * fila de una lista de recepciones. Ninguna vista vuelve a resolver color/etiqueta.
     *
     * @param array $recepciones
     * @return array
     */
    private static function decorarEstados(array $recepciones): array
    {
        foreach ($recepciones as &$r) {
            $r['estado_derivado'] = self::estadoDerivado($r);
            $r['estado_ui'] = self::estadoRecepcion($r['estado_derivado']);
        }
        unset($r);
        return $recepciones;
    }

    /**
     * Datos del tab "Hoy": llegadas, salidas previstas e in-house con contadores.
     *
     * @return array{llegadas:array, salidas:array, in_house:array, contadores:array}
     */
    public function hoy(): array
    {
        $llegadas = self::decorarEstados($this->modelo->getLlegadasHoy());
        $salidas = self::decorarEstados($this->modelo->getSalidasHoy());
        $inHouse = self::decorarEstados($this->modelo->getInHouse());

        $salidasVencidas = 0;
        foreach ($salidas as $s) {
            if ($s['estado_derivado'] === 'salida_vencida') {
                $salidasVencidas++;
            }
        }

        $kpis = $this->modelo->getKpisDia();

        return [
            'llegadas' => $llegadas,
            'salidas' => $salidas,
            'in_house' => $inHouse,
            'contadores' => [
                'llegadas' => count($llegadas),
                'llegadas_pendientes' => (int) $kpis['llegadas_pendientes'],
                'salidas' => count($salidas),
                'salidas_vencidas' => $salidasVencidas,
                'in_house' => count($inHouse),
                'sucias' => (int) $kpis['habitaciones_sucias'],
            ],
        ];
    }

    /**
     * Datos del tab "Mapa" (room rack). Absorbe el agrupado por piso que hacía la vista.
     * Cada fila lleva doble dimensión: 'estado_ui' (ocupación) y 'housekeeping_ui',
     * más 'mostrar_hk' (bool) y 'accion' (['label','href' relativo,'clase']) para el tile.
     *
     * @return array{habitaciones_por_piso:array<string,array>, pisos:array, contadores:array}
     */
    public function mapa(): array
    {
        $filas = $this->modelo->getMapaHabitaciones();
        $porPiso = [];
        $contadores = [
            'disponible' => 0, 'ocupada' => 0, 'reservada' => 0,
            'limpieza' => 0, 'mantenimiento' => 0, 'total' => 0,
        ];

        foreach ($filas as $f) {
            if (!empty($f['idrecepcion']) && $f['estado_recepcion'] === 'en_curso') {
                $ocupacion = self::estadoDerivado([
                    'estado' => 'en_curso',
                    'fechasalida_prevista' => $f['fechasalida_prevista'] ?? null,
                ]);
                $contadores['ocupada']++;
            } elseif (!empty($f['idrecepcion']) && $f['estado_recepcion'] === 'reservado') {
                $ocupacion = 'reservado';
                $contadores['reservada']++;
            } elseif ($f['estado'] === 'mantenimiento') {
                $ocupacion = 'mantenimiento';
                $contadores['mantenimiento']++;
            } elseif ($f['estado'] === 'limpieza') {
                $ocupacion = 'limpieza';
                $contadores['limpieza']++;
            } else {
                $ocupacion = 'disponible';
                $contadores['disponible']++;
            }

            $esHk = in_array($f['estado'], ['limpieza', 'mantenimiento'], true);
            $housekeeping = $esHk ? $f['estado'] : 'disponible';

            $estadoUi = self::estadoRecepcion($ocupacion);
            $f['estado_ui'] = $estadoUi;
            $f['housekeeping_ui'] = self::estadoRecepcion($housekeeping);
            $f['huesped'] = $f['huesped'] ?? null;
            $f['mostrar_hk'] = $esHk;

            // Acción primaria del tile, ya resuelta (el partial solo pinta HTML):
            // ruta relativa; el partial le antepone $URL.
            if (!empty($f['idrecepcion'])) {
                $f['accion'] = [
                    'label' => 'Ver folio',
                    'href' => 'views/recepcion/show.php?id=' . (int) $f['idrecepcion'],
                    'clase' => $estadoUi['clase'],
                ];
            } elseif ($esHk) {
                $f['accion'] = [
                    'label' => 'Ver habitación',
                    'href' => 'views/habitaciones/show.php?id=' . (int) $f['id_habitacion'],
                    'clase' => 'secondary',
                ];
            } else {
                $f['accion'] = [
                    'label' => 'Check-in',
                    'href' => 'views/recepcion/create.php?idhabitacion=' . (int) $f['id_habitacion'],
                    'clase' => 'success',
                ];
            }

            $piso = $f['piso_nombre'] ?? 'Sin piso';
            $porPiso[$piso][] = $f;
            $contadores['total']++;
        }

        ksort($porPiso);

        return [
            'habitaciones_por_piso' => $porPiso,
            'pisos' => array_keys($porPiso),
            'contadores' => $contadores,
        ];
    }

    /**
     * KPIs del día para partials/kpi-bar.php. El cálculo lo hace el modelo (SQL).
     *
     * @return array
     */
    public function kpis(): array
    {
        return $this->modelo->getKpisDia();
    }

    /**
     * Tab Historial. Filtro opcional por estado; sin liberarNoShows() (ya corrió en panel()).
     *
     * @param string|null $estado
     * @return array{recepciones:array}
     */
    public function historial(?string $estado = null): array
    {
        $recepciones = $estado
            ? $this->modelo->getAllByEstado($estado)
            : $this->modelo->getAll();

        return ['recepciones' => self::decorarEstados($recepciones)];
    }

    /**
     * Búsqueda global para el Select2 remoto (endpoint buscar_ajax.php).
     *
     * @param string $termino
     * @param int    $limite
     * @return array
     */
    public function buscar(string $termino, int $limite = 20): array
    {
        $termino = trim($termino);
        if ($termino === '') {
            return [];
        }
        return $this->modelo->buscarGlobal($termino, $limite);
    }

    /**
     * Reemplaza a index(): orquesta el panel completo. Único punto que llama
     * liberarNoShows() (una vez por carga).
     *
     * @return array{hoy:array, mapa:array, kpis:array, historial:array}
     */
    public function panel(): array
    {
        $config = require __DIR__ . '/../../config/config.php';
        $noshowHoras = $config['recepcion']['noshow_horas'] ?? 6;
        $this->modelo->liberarNoShows($noshowHoras);

        return [
            'hoy' => $this->hoy(),
            'mapa' => $this->mapa(),
            'kpis' => $this->kpis(),
            'historial' => $this->historial(),
        ];
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
     * Actualiza SOLO los datos de estancia de una recepción. NUNCA acepta
     * montototal/montopagado/cambio/metodopago (el dinero se gestiona por el folio,
     * ver models/Pago.php) ni transiciones de estado (van por cambiarEstado()/checkout()).
     * Whitelist estricta contra mass-assignment hacia Recepcion::actualizar().
     *
     * @param int $id
     * @param array $datos $_POST completo; se filtra internamente
     * @return array{success:bool, message:string, icon:string}
     */
    public function actualizarEstancia(int $id, array $datos): array
    {
        $permitidos = ['idcliente', 'idtarifa', 'fechaentrada', 'fechasalida_prevista', 'observaciones'];
        $limpios = [];
        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $limpios[$campo] = $datos[$campo];
            }
        }

        if (isset($limpios['idcliente'])) {
            $limpios['idcliente'] = (int) $limpios['idcliente'];
        }
        if (isset($limpios['idtarifa'])) {
            $limpios['idtarifa'] = (int) $limpios['idtarifa'];
        }

        $limpios = $this->modelo->sanitizarDatos($limpios);

        $errores = [];
        if (empty($limpios['idcliente'])) {
            $errores[] = 'El cliente es obligatorio.';
        }
        if (empty($limpios['idtarifa'])) {
            $errores[] = 'La tarifa es obligatoria.';
        }
        if (empty($limpios['fechaentrada'])) {
            $errores[] = 'La fecha de entrada es obligatoria.';
        }
        if (empty($limpios['fechasalida_prevista'])) {
            $errores[] = 'La fecha de salida prevista es obligatoria.';
        } elseif (!empty($limpios['fechaentrada']) && strtotime($limpios['fechasalida_prevista']) <= strtotime($limpios['fechaentrada'])) {
            $errores[] = 'La fecha de salida prevista debe ser posterior a la fecha de entrada.';
        }

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error'];
        }

        if ($this->modelo->actualizar($id, $limpios)) {
            return ['success' => true, 'message' => 'Datos de estancia actualizados correctamente', 'icon' => 'success'];
        }

        return [
            'success' => false,
            'message' => 'Error al actualizar la estancia: ' . $this->modelo->getLastError(),
            'icon' => 'error',
        ];
    }

    /**
     * Check-out completo: verifica el saldo real del folio (Pago::calcularSaldo) antes
     * de finalizar. Si hay saldo pendiente y no se envía un pago que lo cubra, no finaliza.
     * Reutiliza Pago::registrarPago() + Recepcion::cambiarEstado() (cada uno transaccional,
     * sin SQL de dinero nuevo); si el registro del pago tuvo éxito el folio queda cuadrado
     * aunque el cambio de estado fallara y se pueda reintentar.
     *
     * @param int $id
     * @param int $idusuario
     * @param array|null $pagoFinal ['monto'=>float, 'metodopago'=>'Efectivo'|'QR'|'OTROS']
     * @return array{success:bool, message:string, requiere_pago?:bool, saldo?:float}
     */
    public function checkout(int $id, int $idusuario, ?array $pagoFinal = null): array
    {
        require_once __DIR__ . '/../../models/Pago.php';
        $pagoModelo = new Pago();

        $recepcion = $this->modelo->getById($id);
        if (!$recepcion) {
            return ['success' => false, 'message' => 'Recepción no encontrada.'];
        }
        if ($recepcion['estado'] !== 'en_curso') {
            return ['success' => false, 'message' => 'Solo se puede hacer check-out de una estancia en curso.'];
        }

        $saldo = (float) $pagoModelo->calcularSaldo($id)['saldo'];

        if ($saldo > 0.01) {
            if ($pagoFinal === null) {
                return [
                    'success' => false,
                    'requiere_pago' => true,
                    'saldo' => $saldo,
                    'message' => 'Saldo pendiente Bs ' . number_format($saldo, 2),
                ];
            }

            $monto = (float) ($pagoFinal['monto'] ?? 0);
            $metodo = $pagoFinal['metodopago'] ?? '';

            if (!in_array($metodo, ['Efectivo', 'QR', 'OTROS'], true)) {
                return ['success' => false, 'message' => 'Método de pago no válido.', 'saldo' => $saldo];
            }
            if ($monto + 0.01 < $saldo) {
                return [
                    'success' => false,
                    'message' => 'El pago no cubre el saldo. Saldo pendiente Bs ' . number_format($saldo - $monto, 2),
                    'saldo' => $saldo,
                ];
            }

            $idLinea = $pagoModelo->registrarPago($id, $saldo, $metodo, $idusuario, 'Pago de saldo (check-out)');
            if (!$idLinea) {
                return ['success' => false, 'message' => $pagoModelo->getLastError(), 'saldo' => $saldo];
            }
        }

        if ($this->modelo->cambiarEstado($id, 'finalizado')) {
            return ['success' => true, 'message' => 'Check-out realizado correctamente'];
        }

        return ['success' => false, 'message' => 'Error al finalizar la recepción: ' . $this->modelo->getLastError()];
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
     * Cambia la habitación de una recepción en curso
     *
     * @param int $id ID de la recepción
     * @param int $idHabitacionDestino ID de la habitación destino
     * @param int $idusuario Usuario que realiza el cambio
     * @param string|null $motivo Motivo del cambio
     * @return array Resultado de la operación
     */
    public function cambiarHabitacion($id, $idHabitacionDestino, $idusuario, $motivo = null)
    {
        if ($this->modelo->cambiarHabitacion($id, $idHabitacionDestino, $idusuario, $motivo)) {
            return [
                'success' => true,
                'message' => 'Habitación cambiada correctamente',
                'icon' => 'success'
            ];
        }

        return [
            'success' => false,
            'message' => $this->modelo->getLastError(),
            'icon' => 'error'
        ];
    }

    /**
     * Obtiene el historial de movimientos (cambios de habitación/extensión) de una recepción
     *
     * @param int $id ID de la recepción
     * @return array
     */
    public function obtenerMovimientos($id)
    {
        return $this->modelo->getMovimientos($id);
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
