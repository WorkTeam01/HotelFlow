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
    require_once __DIR__ . '/../../controllers/almacenamiento-equipaje/AlmacenamientoEquipajeController.php';

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

    if (!($authService->puedeAccederModulo($idusuario, 'equipajes'))) {
        die("Error: No tiene permisos para generar este recibo");
    }

    // Set the current date and time
    $fecha_actual = date('d/m/Y');
    $hora_actual = date('H:i');

    // Get request parameters
    if (!isset($_GET['id'])) {
        die("Error: ID de equipaje requerido para generar el recibo");
    }

    $id_equipaje = (int)$_GET['id'];

    if ($id_equipaje <= 0) {
        die("Error: ID de equipaje inválido");
    }

    // Instanciar controlador de equipajes
    $equipajeController = new AlmacenamientoEquipajeController();

    // Obtener datos del equipaje para el recibo
    $equipaje = $equipajeController->generarDatosReciboPDF($id_equipaje);

    if (!$equipaje) {
        $errores = $equipajeController->getErrores();
        $mensaje_error = "Error: No se encontró el equipaje especificado";
        if (!empty($errores)) {
            $mensaje_error .= "\nDetalles: " . implode(", ", $errores);
        }
        die($mensaje_error);
    }

    // Verificar datos principales
    if (empty($equipaje['codigo_ticket'])) {
        die("Error: Código de ticket vacío");
    }

    // Extraer datos principales con validación
    // FUENTE: Base de datos tabla almacenamiento_equipaje + persona + usuarios + precio_equipaje
    $cliente = $equipaje['cliente']['nombre_completo'] ?? 'Cliente no disponible';
    $codigo_ticket = $equipaje['codigo_ticket'] ?? 'Sin código';
    $descripcion = $equipaje['descripcion'] ?? 'Equipaje personal';
    $cantidad_piezas = $equipaje['cantidad_piezas'] ?? 1;
    $tamano_equipaje = $equipaje['equipaje']['tamano'] ?? 'No especificado';
    $monto = $equipaje['monto'] ?? 0;
    $fecha_entrada = $equipaje['fecha_entrada_formateada'] ?? $fecha_actual;
    $estado = $equipaje['estado_formateado'] ?? 'Almacenado';
    $tiempo_almacenado = $equipaje['tiempo_almacenado']['texto'] ?? 'No calculado';

    // Datos de la empresa - FUENTE: Configurado en el controlador
    $empresa = $equipaje['empresa'] ?? [
        'nombre' => strtoupper($APP_NAME),
        'direccion' => 'Santa Cruz - Bolivia',
        'telefono' => 'Teléfono no disponible'
    ];

    // Obtener usuario que registró - FUENTE: Base de datos tabla usuarios
    $usuario_registro = $equipaje['usuario']['nombre_completo'] ?? $_SESSION['usuario_nombre'] ?? 'Usuario no disponible';

    // Create PDF
    $is_mobile = isMobile();
    $pdf = new TCPDF('P', 'mm', array(80, 200), true, 'UTF-8', false);
    $font_size = 9;
    $margin = 5;

    // Set document properties
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($APP_NAME);
    $pdf->SetTitle('Recibo de Almacenamiento de Equipaje');
    $pdf->SetSubject('Recibo de Equipaje');
    $pdf->SetKeywords('recibo, equipaje, almacenamiento, alojamiento');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins($margin, $margin, $margin);
    $pdf->SetAutoPageBreak(TRUE, $margin);
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', '', $font_size);

    // Set header content
    $html = <<<EOD
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
<h1 style="font-size: 12pt; text-align: center; font-weight: bold; margin-bottom: 5px;">RECIBO DE EQUIPAJE</h1>
<table>
    <tr><td colspan="2" class="center bold">{$empresa['nombre']}</td></tr>
    <tr><td colspan="2" class="center">Servicio de Almacenamiento de Equipaje</td></tr>
    <tr><td colspan="2" class="center">Santa Cruz - Bolivia</td></tr>
    <tr><td colspan="2" class="center">Cel. {$empresa['telefono']}</td></tr>
</table>
<hr style="margin: 5px 0 3px 0;">
<table cellspacing="1">
    <tr><td width="50%">TICKET No.:</td><td width="50%" class="bold">$codigo_ticket</td></tr>
    <tr><td>FECHA ENTRADA:</td><td>$fecha_entrada</td></tr>
    <tr><td>CLIENTE:</td><td>$cliente</td></tr>
</table>
<hr style="margin: 3px 0 2px 0;">
<p class="center bold" style="margin: 3px 0;">DETALLE DEL EQUIPAJE</p>
<hr style="margin: 2px 0 3px 0;">
EOD;

    $pdf->writeHTML($html, true, false, true, '');

    // Convertir monto a literal con manejo de errores
    try {
        $monto_literal = numeroletras($monto);
    } catch (Exception $e) {
        $monto_literal = "(" . number_format($monto, 2) . " BOLIVIANOS)";
    }

    // Formatear monto
    $monto_formatted = number_format($monto, 2, ',', '.');

    // Generate details HTML
    $html = <<<EOD
<table cellspacing="1">
    <tr>
        <td colspan="2"><strong>Descripción:</strong></td>
    </tr>
    <tr>
        <td colspan="2">$descripcion</td>
    </tr>
    <tr>
        <td width="50%"><strong>Cantidad de piezas:</strong></td>
        <td width="50%">$cantidad_piezas</td>
    </tr>
    <tr>
        <td width="50%"><strong>Tamaño:</strong></td>
        <td width="50%">$tamano_equipaje</td>
    </tr>
    <tr>
        <td width="50%"><strong>Tiempo almacenado:</strong></td>
        <td width="50%">$tiempo_almacenado</td>
    </tr>
</table>
<hr style="margin: 5px 0 3px 0;">
<table style="margin: 3px 0;">
    <tr>
        <td width="70%"><strong>TOTAL A PAGAR Bs</strong></td>
        <td width="30%" style="text-align: right;"><strong>$monto_formatted</strong></td>
    </tr>
</table>
<p style="margin: 5px 0 3px 0;"><strong>SON:</strong> $monto_literal</p>
<hr style="margin: 3px 0;">
<table style="margin: 2px 0;">
    <tr><br>
        <td width="30%">Hora: $hora_actual</td>
        <td width="70%">Recibió: $usuario_registro</td>
    </tr>
</table>
<br>
<hr style="margin: 3px 0 2px 0;">
<p style="text-align: center; font-size: 7pt; margin: 2px 0; font-weight: bold;">IMPORTANTE:</p>
<p style="text-align: center; font-size: 7pt; margin: 1px 0;">
- Presente este recibo para retirar su equipaje<br>
- Horario de atención: Lunes a Viernes 8:00-18:00<br>
- Conserve este comprobante
</p>
<hr style="margin: 2px 0;">
<p style="text-align: center; font-weight: bold; font-size: 8pt; margin: 2px 0;">ESTE RECIBO NO TIENE VALOR FISCAL</p>
EOD;

    $pdf->writeHTML($html, true, false, true, '');

    // Generate QR code con manejo de errores
    $style = array(
        'border' => 0,
        'vpadding' => '1',
        'hpadding' => '1',
        'fgcolor' => array(0, 0, 0),
        'bgcolor' => array(255, 255, 255),
        'module_width' => 1,
        'module_height' => 1
    );

    $QR = $equipaje['qr_info'] ?? "Recibo de Equipaje: $codigo_ticket";

    try {
        $pdf->write2DBarcode($QR, 'QRCODE,L', 24, $pdf->GetY() + 2, 35, 35, $style);
    } catch (Exception $e) {
        // Si falla el QR, continuar sin él
        $pdf->writeHTML('<p style="text-align: center; margin: 2px 0;">Código QR no disponible</p>', true, false, true, '');
    }

    $pdf->Ln(38);

    // Add a final message
    $html = '<p style="text-align: center; font-size: ' . $font_size . 'pt; margin: 2px 0;">GRACIAS POR CONFIAR EN NOSOTROS</p>';
    $pdf->writeHTML($html, true, false, true, '');

    // Set headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . ($is_mobile ? 'attachment' : 'inline') . '; filename="Equipaje_' . $codigo_ticket . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Output PDF
    $pdf->Output('Equipaje_' . $codigo_ticket . '.pdf', $is_mobile ? 'D' : 'I');
} catch (Exception $e) {
    // Capturar cualquier error y mostrarlo
    die("Error al generar el PDF: " . $e->getMessage() . "\nArchivo: " . $e->getFile() . "\nLínea: " . $e->getLine());
} catch (Error $e) {
    // Capturar errores fatales
    die("Error fatal al generar el PDF: " . $e->getMessage() . "\nArchivo: " . $e->getFile() . "\nLínea: " . $e->getLine());
}
