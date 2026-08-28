<?php

/** @noinspection HtmlDeprecatedAttribute */
// Verificar headers
global $URL;
if (headers_sent()) {
    die("Los headers ya fueron enviados. Verifica que no haya salida de contenido antes de este script.");
}

// Improved mobile detection function
function isMobile(): bool
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $mobileKeywords = [
        'Android',
        'webOS',
        'iPhone',
        'iPad',
        'iPod',
        'BlackBerry',
        'Windows Phone'
    ];

    foreach ($mobileKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

// Función para formatear fechas
function formatearFecha($fecha): string
{
    return date('d/m/Y H:i', strtotime($fecha));
}

try {
    // Include required files con manejo de errores
    if (!file_exists('../../libs/TCPDF-main/tcpdf.php')) {
        die("Error: No se encontró TCPDF en la ruta especificada");
    }
    require_once('../../libs/TCPDF-main/tcpdf.php');

    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../views/layouts/session.php';
    require_once __DIR__ . '/../../services/AuthorizationService.php';
    require_once __DIR__ . '/../../controllers/recepcion/RecepcionController.php';

    // Verificar si existe el archivo literal.php
    if (file_exists(__DIR__ . '/../../services/literal.php')) {
        require_once __DIR__ . '/../../services/literal.php';
    } else {
        // Función de respaldo para convertir números a letras
        function numeroletras($numero)
        {
            return "(" . number_format($numero, 2) . " BOLIVIANOS)";
        }
    }

    // Verificar sesión
    if (!isset($_SESSION['usuario_id'])) {
        die("Error: Sesión no iniciada");
    }

    // Verificar permisos
    $idusuario = $_SESSION['usuario_id'];
    $authService = new AuthorizationService();

    if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'recepcion')) {
        die("Error: No tiene permisos para generar este recibo");
    }

    // Set the current date and time
    $fecha_actual = date('d/m/Y');
    $hora_actual = date('H:i');

    // Get request parameters
    if (!isset($_GET['id'])) {
        die("Error: ID de recepción requerido para generar el recibo");
    }

    $id_recepcion = (int)$_GET['id'];

    if ($id_recepcion <= 0) {
        die("Error: ID de recepción inválido");
    }

    // Instanciar controlador de recepción
    $recepcionController = new RecepcionController();

    // Obtener datos de la recepción
    $recepcion = $recepcionController->mostrar($id_recepcion);

    if (!$recepcion) {
        die("Error: No se encontró la recepción especificada");
    }

    // Validar estado de la recepción
    if (!in_array($recepcion['estado'], ['reservado', 'en_curso', 'finalizado'])) {
        die("Error: No se puede generar recibo para recepciones canceladas");
    }

    // Folio de huésped: líneas de cargo/pago/reverso + saldo real (libro mayor).
    // Fuente de verdad para el desglose financiero del recibo; el cache
    // montototal/montopagado de recepcion queda solo como fallback.
    $folio = $recepcionController->obtenerFolio($id_recepcion);
    $folio_lineas = $folio['lineas'] ?? [];
    $folio_saldo = $folio['saldo'] ?? ['total_cargos' => 0, 'total_pagado' => 0, 'saldo' => 0];

    // Extraer datos principales con validación
    $cliente = ($recepcion['nombre_cliente'] ?? '') . ' ' . ($recepcion['apellido_cliente'] ?? '');
    $cliente = trim($cliente) ?: 'Cliente no disponible';
    $cliente = htmlspecialchars($cliente, ENT_QUOTES, 'UTF-8');

    $numero_habitacion = htmlspecialchars($recepcion['numero_habitacion'] ?? 'Sin habitación', ENT_QUOTES, 'UTF-8');
    $tipo_habitacion = htmlspecialchars($recepcion['descripcion_habitacion'] ?? 'Habitación estándar', ENT_QUOTES, 'UTF-8');
    $fecha_entrada = formatearFecha($recepcion['fechaentrada'] ?? date('Y-m-d H:i:s'));
    $fecha_salida_prevista = formatearFecha($recepcion['fechasalida_prevista'] ?? date('Y-m-d H:i:s'));
    $fecha_salida_real = !empty($recepcion['fechasalida']) ? formatearFecha($recepcion['fechasalida']) : null;

    // Si el folio tiene líneas, es la fuente de verdad; si no (recepciones
    // anteriores al folio), se usa el cache de la tabla recepcion.
    if (!empty($folio_lineas)) {
        $monto_total = (float)$folio_saldo['total_cargos'];
        $monto_pagado = (float)$folio_saldo['total_pagado'];
        $saldo_pendiente = (float)$folio_saldo['saldo'];
    } else {
        $monto_total = (float)($recepcion['montototal'] ?? 0);
        $monto_pagado = (float)($recepcion['montopagado'] ?? 0);
        $saldo_pendiente = $monto_total - $monto_pagado;
    }

    $estado = ucfirst(str_replace('_', ' ', $recepcion['estado'] ?? 'reservado'));
    $metodo_pago = htmlspecialchars($recepcion['metodopago'] ?? 'Sin especificar', ENT_QUOTES, 'UTF-8');
    $tipo_tarifa = htmlspecialchars($recepcion['tipo_tarifa'] ?? 'Tarifa estándar', ENT_QUOTES, 'UTF-8');
    $observaciones = htmlspecialchars($recepcion['observaciones'] ?? '', ENT_QUOTES, 'UTF-8');

    // Datos del cliente
    $documento_cliente = htmlspecialchars(($recepcion['tipodoc_cliente'] ?? '') . ': ' . ($recepcion['numdoc_cliente'] ?? ''), ENT_QUOTES, 'UTF-8');
    $telefono_cliente = htmlspecialchars($recepcion['telefono_cliente'] ?? 'No disponible', ENT_QUOTES, 'UTF-8');

    // Datos de la empresa
    $empresa = [
        'nombre' => htmlspecialchars(strtoupper($APP_NAME), ENT_QUOTES, 'UTF-8'),
        'direccion' => 'Santa Cruz - Bolivia',
        'telefono' => htmlspecialchars($config['empresa']['telefono'] ?? '+591 74691060', ENT_QUOTES, 'UTF-8')
    ];

    // Obtener usuario que procesó
    $usuario_registro = htmlspecialchars($recepcion['nombre_usuario'] ?? $_SESSION['usuario_nombre'] ?? 'Usuario no disponible', ENT_QUOTES, 'UTF-8');

    // Determinar tipo de documento según estado
    $tipo_documento = '';
    switch ($recepcion['estado']) {
        case 'reservado':
            $tipo_documento = 'COMPROBANTE DE RESERVA';
            break;
        case 'en_curso':
            $tipo_documento = 'COMPROBANTE DE CHECK-IN';
            break;
        case 'finalizado':
            $tipo_documento = 'COMPROBANTE DE ESTANCIA';
            break;
    }

    // Create PDF
    $is_mobile = isMobile();
    $font_size = 9;
    $margin = 5;

    // Convertir monto a literal con manejo de errores
    try {
        $monto_total_literal = numeroletras($monto_total);
    } catch (Exception $e) {
        $monto_total_literal = "(" . number_format($monto_total, 2) . " BOLIVIANOS)";
    }

    // Formatear montos
    $monto_total_formatted = number_format($monto_total, 2, ',', '.');
    $monto_pagado_formatted = number_format($monto_pagado, 2, ',', '.');

    // Set header content
    $html_cabecera = <<<EOD
<style>
    * { font-family: Arial, sans-serif; margin: 0; padding: 0; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 1px; font-size: {$font_size}pt; }
    .center { text-align: center; }
    .left { text-align: left; }
    .right { text-align: right; }
    .bold { font-weight: bold; }
    h1 { margin: 0 0 3px 0; padding: 0; }
    p { margin: 2px 0; }
    hr { margin: 2px 0; }
</style>
<h1 style="font-size: 12pt; text-align: center; font-weight: bold; margin-bottom: 5px;">$tipo_documento</h1>
<table>
    <tr><td colspan="2" class="center bold">{$empresa['nombre']}</td></tr>
    <tr><td colspan="2" class="center">Servicio de Alojamiento</td></tr>
    <tr><td colspan="2" class="center">{$empresa['direccion']}</td></tr>
    <tr><td colspan="2" class="center">Cel. {$empresa['telefono']}</td></tr>
</table>
<hr style="margin: 5px 0 3px 0;">
<table cellspacing="1">
    <tr><td width="50%">RECEPCIÓN No.:</td><td width="50%" class="bold">{$recepcion['idrecepcion']}</td></tr>
    <tr><td>FECHA EMISIÓN:</td><td>$fecha_actual $hora_actual</td></tr>
    <tr><td>CLIENTE:</td><td>$cliente</td></tr>
    <tr><td>DOCUMENTO:</td><td>$documento_cliente</td></tr>
    <tr><td>TELÉFONO:</td><td>$telefono_cliente</td></tr>
</table>
<hr style="margin: 3px 0 2px 0;">
<p class="center bold" style="margin: 3px 0;">DETALLE DE LA RESERVA</p>
<hr style="margin: 2px 0 3px 0;">
EOD;

    // Generate details HTML
    $html_cuerpo = <<<EOD
<table cellspacing="1">
    <tr>
        <td width="50%"><strong>Habitación:</strong></td>
        <td width="50%">Nº $numero_habitacion</td>
    </tr>
    <tr>
        <td width="50%"><strong>Tipo:</strong></td>
        <td width="50%">$tipo_habitacion</td>
    </tr>
    <tr>
        <td width="50%"><strong>Tarifa:</strong></td>
        <td width="50%">$tipo_tarifa</td>
    </tr>
EOD;

    // Agregar método de pago en el detalle de la reserva
    if ($metodo_pago !== 'Sin especificar') {
        $html_cuerpo .= <<<EOD
    <tr>
        <td width="50%"><strong>Método de pago:</strong></td>
        <td width="50%">$metodo_pago</td>
    </tr>
EOD;
    }

    $html_cuerpo .= <<<EOD
</table>
<hr style="margin: 3px 0 2px 0;">
<p style="margin: 3px 0; text-align: center; font-weight: bold">FECHAS DE ESTANCIA</p>
<hr style="margin: 2px 0 3px 0;">
<table cellspacing="1">
    <tr>
        <td width="50%"><strong>Check-in:</strong></td>
        <td width="50%">$fecha_entrada</td>
    </tr>
    <tr>
        <td width="50%"><strong>Check-out previsto:</strong></td>
        <td width="50%">$fecha_salida_prevista</td>
    </tr>
EOD;

    // Agregar fecha de salida real si existe
    if ($fecha_salida_real) {
        $html_cuerpo .= <<<EOD
    <tr>
        <td width="50%"><strong>Check-out real:</strong></td>
        <td width="50%">$fecha_salida_real</td>
    </tr>
EOD;
    }

    // Desglose itemizado del folio (cargos, pagos y reversos en orden cronológico)
    $filas_folio = '';
    foreach ($folio_lineas as $linea) {
        $concepto_folio = htmlspecialchars($linea['concepto'] ?? '', ENT_QUOTES, 'UTF-8');
        $monto_linea = number_format((float)$linea['montototal'], 2, ',', '.');
        $tipo_linea = $linea['tipo'] ?? 'pago';
        if ($tipo_linea === 'cargo') {
            $etiqueta_folio = $concepto_folio !== '' ? $concepto_folio : 'Cargo';
            $signo_folio = '';
        } elseif ($tipo_linea === 'pago') {
            $metodo_linea = htmlspecialchars($linea['metodopago'] ?? '', ENT_QUOTES, 'UTF-8');
            $etiqueta_folio = 'Pago' . ($metodo_linea !== '' ? " ($metodo_linea)" : '');
            $signo_folio = '-';
        } else {
            $etiqueta_folio = $concepto_folio !== '' ? $concepto_folio : 'Reverso';
            $signo_folio = '';
        }
        $filas_folio .= "<tr><td width=\"65%\">$etiqueta_folio</td>"
            . "<td width=\"35%\" style=\"text-align: right;\">$signo_folio$monto_linea</td></tr>";
    }
    if ($filas_folio === '') {
        $filas_folio = '<tr><td colspan="2" class="center">Sin movimientos registrados</td></tr>';
    }

    $total_cargos_formatted = number_format($monto_total, 2, ',', '.');
    $saldo_abs_formatted = number_format(abs($saldo_pendiente), 2, ',', '.');
    $etiqueta_saldo = $saldo_pendiente > 0.001 ? 'SALDO PENDIENTE Bs' : 'SALDO Bs';

    $html_cuerpo .= <<<EOD
</table>
<hr style="margin: 5px 0 3px 0;">
<p style="margin: 3px 0; text-align: center; font-weight: bold">DETALLE FINANCIERO</p>
<hr style="margin: 2px 0 3px 0;">
<table cellspacing="1">
    $filas_folio
</table>
<hr style="margin: 3px 0 2px 0;">
<table cellspacing="1">
    <tr>
        <td width="65%">Total cargos Bs</td>
        <td width="35%" style="text-align: right;">$total_cargos_formatted</td>
    </tr>
    <tr>
        <td width="65%">Total pagado Bs</td>
        <td width="35%" style="text-align: right;">$monto_pagado_formatted</td>
    </tr>
    <tr>
        <td width="65%"><strong>$etiqueta_saldo</strong></td>
        <td width="35%" style="text-align: right;"><strong>$saldo_abs_formatted</strong></td>
    </tr>
</table>
<p style="margin: 5px 0 3px 0;"><strong>SON:</strong> $monto_total_literal</p>
EOD;

    $html_cuerpo .= <<<EOD
<hr style="margin: 3px 0;">
<table style="margin: 2px 0;">
    <tr>
        <td width="30%">Hora: $hora_actual</td>
        <td width="70%">Atendido por: $usuario_registro</td>
    </tr>
</table>
<hr style="margin: 3px 0 2px 0;">
<p style="text-align: center; font-size: 7pt; margin: 2px 0; font-weight: bold;">POLÍTICAS IMPORTANTES:</p>
<p style="text-align: center; font-size: 7pt; margin: 1px 0;">
- Check-in: 14:00 hrs / Check-out: 12:00 hrs<br>
- Presente documento de identidad al check-in<br>
- Horario de recepción: 24 horas<br>
- Conserve este comprobante
</p>
<hr style="margin: 2px 0;">
<p style="text-align: center; font-weight: bold; font-size: 8pt; margin: 2px 0;">ESTE RECIBO NO TIENE VALOR FISCAL</p>
EOD;

    $html_recibo = $html_cabecera . $html_cuerpo;

    $qr_style = array(
        'border' => 0,
        'vpadding' => '1',
        'hpadding' => '1',
        'fgcolor' => array(0, 0, 0),
        'bgcolor' => array(255, 255, 255),
        'module_width' => 1,
        'module_height' => 1
    );
    $qr_texto = "Recepción: {$recepcion['idrecepcion']} | Cliente: $cliente | Habitación: $numero_habitacion | Total: Bs $monto_total_formatted";
    $qr_lado = 35; // mm

    /**
     * Construye el recibo completo sobre un TCPDF de la altura de página indicada.
     * Se invoca dos veces: primero sobre una página muy alta solo para medir cuánto
     * ocupa el contenido, y luego sobre una página del tamaño exacto para el output
     * final — así el rollo térmico se corta justo al terminar el recibo, sin dejar
     * papel en blanco ni empujar el QR a otra página.
     */
    $construirRecibo = function (float $altoPagina) use (
        $font_size,
        $margin,
        $tipo_documento,
        $APP_NAME,
        $html_recibo,
        $qr_texto,
        $qr_style,
        $qr_lado
    ): TCPDF {
        $pdf = new TCPDF('P', 'mm', array(80, $altoPagina), true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($APP_NAME);
        $pdf->SetTitle($tipo_documento);
        $pdf->SetSubject('Recibo de Recepción');
        $pdf->SetKeywords('recibo, recepcion, alojamiento, check-in, reserva');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins($margin, $margin, $margin);
        $pdf->SetAutoPageBreak(false, $margin);
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', $font_size);

        $pdf->writeHTML($html_recibo, true, false, true, '');

        try {
            $pdf->write2DBarcode($qr_texto, 'QRCODE,L', (80 - $qr_lado) / 2, $pdf->GetY() + 2, $qr_lado, $qr_lado, $qr_style);
            $pdf->SetY($pdf->GetY() + $qr_lado + 3);
        } catch (Exception $e) {
            $pdf->writeHTML('<p style="text-align: center; margin: 2px 0;">Código QR no disponible</p>', true, false, true, '');
        }

        $pdf->writeHTML('<p style="text-align: center; font-size: ' . $font_size . 'pt; margin: 2px 0;">GRACIAS POR ELEGIRNOS</p>', true, false, true, '');

        return $pdf;
    };

    // Primera pasada: medir el alto real del contenido sobre una página holgada.
    $medidor = $construirRecibo(1000.0);
    $alto_contenido = $medidor->GetY() + $margin;

    // Segunda pasada: recibo final a la altura exacta.
    $pdf = $construirRecibo($alto_contenido);

    // Set headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . ($is_mobile ? 'attachment' : 'inline') . '; filename="Recepcion_' . $recepcion['idrecepcion'] . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Output PDF
    $pdf->Output('Recepcion_' . $recepcion['idrecepcion'] . '.pdf', $is_mobile ? 'D' : 'I');
} catch (Exception $e) {
    error_log('[recepcion/recibo] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    die("Ocurrió un error al generar el PDF. Intente nuevamente.");
} catch (Error $e) {
    error_log('[recepcion/recibo] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    die("Ocurrió un error al generar el PDF. Intente nuevamente.");
}
