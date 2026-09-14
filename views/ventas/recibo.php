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

// Formatea un monto en moneda boliviana
function formatearMoneda($monto): string
{
    return number_format((float)$monto, 2, ',', '.');
}

// Formatea una fecha legible
function formatearFecha($fecha): string
{
    return date('d/m/Y H:i', strtotime($fecha));
}

try {
    // Include required files con manejo de errores
    if (!file_exists(__DIR__ . '/../../libs/TCPDF-main/tcpdf.php')) {
        die("Error: No se encontró TCPDF en la ruta especificada");
    }
    require_once __DIR__ . '/../../libs/TCPDF-main/tcpdf.php';

    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../views/layouts/session.php';
    require_once __DIR__ . '/../../services/AuthorizationService.php';
    require_once __DIR__ . '/../../controllers/ventas/VentaController.php';

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
    requireLogin();

    // Verificar permisos del módulo ventas
    $idusuario = $_SESSION['usuario_id'];
    $authService = new AuthorizationService();

    if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'ventas')) {
        die("Error: No tiene permisos para generar este recibo");
    }

    // Set the current date and time
    $fecha_actual = date('d/m/Y');
    $hora_actual = date('H:i');

    // Get request parameters
    if (!isset($_GET['id'])) {
        die("Error: ID de venta requerido para generar el recibo");
    }

    $id_venta = (int)$_GET['id'];

    if ($id_venta <= 0) {
        die("Error: ID de venta inválido");
    }

    // Obtener datos de la venta (ver() adjunta pagos, totales y desglose de detalles)
    $ventaController = new VentaController();
    $venta = $ventaController->ver($id_venta);

    if (!$venta) {
        die("Error: No se encontró la venta especificada");
    }

    // Los usuarios no administradores solo generan el recibo de sus propias ventas
    if (!$authService->esAdministrador($idusuario) && (int)$venta['idusuario'] !== (int)$idusuario) {
        die("Error: No tiene permisos para generar este recibo");
    }

    // Extraer datos principales con validación
    $cliente = trim($venta['cliente_nombre'] ?? '') ?: 'Consumidor Final';
    $cliente = htmlspecialchars($cliente, ENT_QUOTES, 'UTF-8');

    $usuario_registro = trim($venta['usuario_nombre'] ?? '') ?: ($_SESSION['usuario_nombre'] ?? 'Usuario no disponible');
    $usuario_registro = htmlspecialchars($usuario_registro, ENT_QUOTES, 'UTF-8');

    $observaciones = htmlspecialchars(trim($venta['observacion'] ?? ''), ENT_QUOTES, 'UTF-8');

    $es_anulada = (int)($venta['estado'] ?? 1) !== 1;
    $fecha_anulacion = $es_anulada && !empty($venta['fechaactualizacion']) ? formatearFecha($venta['fechaactualizacion']) : '';
    $codigo_venta = 'VENT-' . str_pad($id_venta, 6, '0', STR_PAD_LEFT);
    $etiqueta_estado = $es_anulada ? 'ANULADA' : 'ACTIVA';

    // Fuente de verdad del cobro: ledger append-only de pagoventa (mismo criterio que recepción).
    $pagos = $venta['pagos'] ?? [];
    $totales = $venta['totales'] ?? ['subtotal' => 0, 'descuento' => 0, 'total' => 0, 'total_pagado' => 0];
    $desglose = $venta['desglose_detalles'] ?? ['lineas' => []];
    $resumen_pagos = $venta['resumen_pagos'] ?? ['total_recibido' => 0, 'total_cambio' => 0];

    $monto_total = (float)$totales['total'];
    $monto_total_formatted = formatearMoneda($monto_total);

    // Datos de la empresa
    $empresa = [
        'nombre' => htmlspecialchars(strtoupper($APP_NAME), ENT_QUOTES, 'UTF-8'),
        'direccion' => 'Santa Cruz - Bolivia',
        'telefono' => '+591 74691060'
    ];

    // Convertir monto a literal con manejo de errores
    try {
        $monto_total_literal = strtoupper(numeroletras($monto_total));
    } catch (Exception $e) {
        $monto_total_literal = strtoupper($monto_total_formatted . " BOLIVIANOS");
    }

    // Create PDF
    $is_mobile = isMobile();
    $font_size = 9;
    $margin = 5;

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
<h1 style="font-size: 12pt; text-align: center; font-weight: bold; margin-bottom: 5px;">RECIBO DE VENTA</h1>
<table>
    <tr><td colspan="2" class="center bold">{$empresa['nombre']}</td></tr>
    <tr><td colspan="2" class="center">{$empresa['direccion']}</td></tr>
    <tr><td colspan="2" class="center">Cel. {$empresa['telefono']}</td></tr>
</table>
<hr style="margin: 5px 0 3px 0;">
<table cellspacing="1">
    <tr><td width="50%">VENTA No.:</td><td width="50%" class="bold">$codigo_venta</td></tr>
    <tr><td>FECHA EMISIÓN:</td><td>$fecha_actual $hora_actual</td></tr>
    <tr><td>CLIENTE:</td><td>$cliente</td></tr>
    <tr><td>ATENDIDO POR:</td><td>$usuario_registro</td></tr>
    <tr><td>ESTADO:</td><td class="bold">$etiqueta_estado</td></tr>
EOD;

    // Fecha de anulación si la venta fue anulada
    if ($es_anulada && $fecha_anulacion !== '') {
        $html_cabecera .= <<<EOD
    <tr><td>FECHA ANULACIÓN:</td><td>$fecha_anulacion</td></tr>
EOD;
    }

    $html_cabecera .= <<<EOD
</table>
<hr style="margin: 3px 0 2px 0;">
<p class="center bold" style="margin: 3px 0;">DETALLE DE PRODUCTOS</p>
<hr style="margin: 2px 0 3px 0;">
EOD;

    // Desglose itemizado de los productos vendidos (subtotal/descuento/neto por línea)
    $html_cuerpo = '';
    foreach ($desglose['lineas'] as $index => $linea) {
        $num = $index + 1;
        $producto = htmlspecialchars($linea['producto_nombre'] ?? 'Producto no disponible', ENT_QUOTES, 'UTF-8');
        $cantidad = (int)($linea['cantidad'] ?? 0);
        $precio_formatted = formatearMoneda($linea['precioventa'] ?? 0);
        $calculo = $linea['calculo'] ?? ['subtotal' => 0, 'descuento_total' => 0, 'neto' => 0];
        $subtotal_linea = formatearMoneda($calculo['subtotal'] ?? 0);
        $descuento_linea = formatearMoneda($calculo['descuento_total'] ?? 0);
        $neto_linea = formatearMoneda($calculo['neto'] ?? 0);

        $html_cuerpo .= <<<EOD
<table cellspacing="1">
    <tr><td class="bold">{$num}. {$producto}</td></tr>
    <tr><td>{$cantidad} x {$precio_formatted} = {$subtotal_linea} Bs.</td></tr>
EOD;

        if ((float)($calculo['descuento_total'] ?? 0) > 0) {
            $html_cuerpo .= <<<EOD
    <tr><td>Descuento: -{$descuento_linea} Bs.</td></tr>
EOD;
        }

        $html_cuerpo .= <<<EOD
    <tr><td>Subtotal: {$neto_linea} Bs.</td></tr>
</table>
EOD;
    }

    // Totales de la venta (Subtotal/Descuento/Total recalculados desde los detalles)
    $subtotal_general = formatearMoneda($totales['subtotal'] ?? 0);
    $descuento_general = formatearMoneda($totales['descuento'] ?? 0);

    $html_cuerpo .= <<<EOD
<table cellspacing="1">
    <tr>
        <td width="65%">Subtotal Bs.</td>
        <td width="35%" style="text-align: right;">$subtotal_general</td>
    </tr>
EOD;

    if ((float)($totales['descuento'] ?? 0) > 0) {
        $html_cuerpo .= <<<EOD
    <tr>
        <td width="65%">Descuento Bs.</td>
        <td width="35%" style="text-align: right;">-$descuento_general</td>
    </tr>
EOD;
    }

    $html_cuerpo .= <<<EOD
    <tr>
        <td width="65%"><strong>TOTAL VENTA Bs.</strong></td>
        <td width="35%" style="text-align: right;"><strong>$monto_total_formatted</strong></td>
    </tr>
</table>
<p style="margin: 3px 0;"><strong>SON:</strong> $monto_total_literal</p>
EOD;

    // Bloque FORMA DE PAGO desglosando cada línea del ledger de pagoventa
    $html_cuerpo .= <<<EOD
<hr style="margin: 3px 0 2px 0;">
<p class="center bold" style="margin: 3px 0;">FORMA DE PAGO</p>
<hr style="margin: 2px 0 3px 0;">
EOD;

    if (empty($pagos)) {
        $html_cuerpo .= '<table cellspacing="1"><tr><td class="center">Sin pagos registrados</td></tr></table>';
    } else {
        $html_cuerpo .= '<table cellspacing="1">';
        foreach ($pagos as $pago) {
            $metodo_pago = ucfirst(trim($pago['metodopago'] ?? 'No especificado'));
            $monto_pago = formatearMoneda($pago['monto'] ?? 0);

            $html_cuerpo .= <<<EOD
    <tr>
        <td width="65%">- $metodo_pago:</td>
        <td width="35%" style="text-align: right;">$monto_pago Bs.</td>
    </tr>
EOD;
        }

        // Recibido y cambio consolidado solo cuando el efectivo recibido supera el total
        if ((float)($resumen_pagos['total_recibido'] ?? 0) > $monto_total) {
            $recibido_formatted = formatearMoneda($resumen_pagos['total_recibido'] ?? 0);
            $cambio_formatted = formatearMoneda($resumen_pagos['total_cambio'] ?? 0);

            $html_cuerpo .= <<<EOD
    <tr>
        <td width="65%">Recibido:</td>
        <td width="35%" style="text-align: right;">$recibido_formatted Bs.</td>
    </tr>
    <tr>
        <td width="65%">Cambio:</td>
        <td width="35%" style="text-align: right;">$cambio_formatted Bs.</td>
    </tr>
EOD;
        }
        $html_cuerpo .= '</table>';
    }

    // Observaciones de la venta si existen
    if ($observaciones !== '') {
        $html_cuerpo .= <<<EOD
<hr style="margin: 3px 0 2px 0;">
<p class="center bold" style="margin: 3px 0;">OBSERVACIONES</p>
<hr style="margin: 2px 0 3px 0;">
<table cellspacing="1"><tr><td>$observaciones</td></tr></table>
EOD;
    }

    // Aviso de anulación: el recibo se genera pero queda marcado
    if ($es_anulada) {
        $html_cuerpo .= <<<EOD
<hr style="margin: 3px 0 2px 0;">
<p style="text-align: center; font-weight: bold; margin: 3px 0;">*** VENTA ANULADA ***</p>
<p style="text-align: center; margin: 2px 0;">Esta venta fue anulada el $fecha_anulacion. El stock de los productos fue restaurado.</p>
EOD;
    }

    $html_cuerpo .= <<<EOD
<hr style="margin: 3px 0;">
<p style="text-align: center; font-size: 7pt; margin: 2px 0;">ESTE RECIBO NO TIENE VALOR FISCAL</p>
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
    $qr_texto = "Venta: {$id_venta}\nCliente: $cliente\nTotal: Bs. $monto_total_formatted";
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
        $APP_NAME,
        $html_recibo,
        $qr_texto,
        $qr_style,
        $qr_lado
    ): TCPDF {
        $pdf = new TCPDF('P', 'mm', array(80, $altoPagina), true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($APP_NAME);
        $pdf->SetTitle('Recibo de Venta');
        $pdf->SetSubject('Recibo de Venta');
        $pdf->SetKeywords('recibo, venta');
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

        $pdf->writeHTML('<p style="text-align: center; font-size: ' . $font_size . 'pt; margin: 2px 0;">GRACIAS POR SU COMPRA</p>', true, false, true, '');

        return $pdf;
    };

    // Primera pasada: medir el alto real del contenido sobre una página holgada.
    $medidor = $construirRecibo(1000.0);
    $alto_contenido = $medidor->GetY() + $margin;

    // Segunda pasada: recibo final a la altura exacta.
    $pdf = $construirRecibo($alto_contenido);

    // Set headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . ($is_mobile ? 'attachment' : 'inline') . '; filename="Venta_' . $id_venta . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Output PDF
    $pdf->Output('Venta_' . $id_venta . '.pdf', $is_mobile ? 'D' : 'I');
} catch (Exception $e) {
    error_log('[ventas/recibo] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    die("Ocurrió un error al generar el PDF. Intente nuevamente.");
} catch (Error $e) {
    error_log('[ventas/recibo] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    die("Ocurrió un error al generar el PDF. Intente nuevamente.");
}