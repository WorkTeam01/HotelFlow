<?php

/**
 * Tarjeta de registro de huésped — página standalone imprimible.
 * No usa views/layouts/header.php/footer.php: el <style> embebido es la excepción
 * documentada en CLAUDE.md (igual que recibo.php).
 */

require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../../controllers/recepcion/RecepcionController.php';

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'recepcion')) {
    die('No tiene permisos para generar esta tarjeta.');
}

if (!isset($_GET['id']) || (int) $_GET['id'] <= 0) {
    die('ID de recepción inválido.');
}

$controller = new RecepcionController();
$recepcion = $controller->mostrar((int) $_GET['id']);

if (!$recepcion) {
    die('Recepción no encontrada.');
}

$nombreCompleto = trim($recepcion['nombre_cliente'] . ' ' . $recepcion['apellido_cliente']);
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarjeta de registro — Folio #<?= (int) $recepcion['idrecepcion']; ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            color: #222;
            background: #f4f4f4;
            padding: 24px;
            font-size: 14px;
        }

        .tarjeta {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ccc;
            padding: 32px;
        }

        .tarjeta__head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #222;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .tarjeta__head h1 {
            font-size: 20px;
        }

        .tarjeta__head p {
            color: #666;
            font-size: 12px;
        }

        .folio-num {
            font-size: 16px;
            font-weight: bold;
        }

        h2 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #555;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
            margin: 20px 0 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 6px 4px;
            vertical-align: top;
        }

        td.label {
            width: 40%;
            color: #666;
        }

        .firma {
            margin-top: 56px;
            display: flex;
            justify-content: space-between;
            gap: 40px;
        }

        .firma div {
            flex: 1;
            border-top: 1px solid #222;
            padding-top: 6px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        .aviso {
            margin-top: 24px;
            font-size: 11px;
            color: #888;
            line-height: 1.5;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .tarjeta {
                border: none;
            }

            .no-print {
                display: none;
            }
        }

        .no-print {
            max-width: 720px;
            margin: 0 auto 16px;
            text-align: right;
        }

        .no-print button {
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>

    <div class="tarjeta">
        <div class="tarjeta__head">
            <div>
                <h1>Tarjeta de registro de huésped</h1>
                <p><?= $e($APP_NAME ?? 'HotelFlow'); ?> · Emitida el <?= date('d/m/Y H:i'); ?></p>
            </div>
            <div class="folio-num">Folio #<?= (int) $recepcion['idrecepcion']; ?></div>
        </div>

        <h2>Datos del huésped</h2>
        <table>
            <tr>
                <td class="label">Nombre completo</td>
                <td><?= $e($nombreCompleto); ?></td>
            </tr>
            <tr>
                <td class="label">Documento</td>
                <td><?= $e(($recepcion['tipodoc_cliente'] ?? 'Doc') . ': ' . ($recepcion['numdoc_cliente'] ?? '—')); ?></td>
            </tr>
            <tr>
                <td class="label">Teléfono</td>
                <td><?= $e($recepcion['telefono_cliente'] ?? '—'); ?></td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td><?= $e($recepcion['email_cliente'] ?? '—'); ?></td>
            </tr>
            <tr>
                <td class="label">Dirección</td>
                <td><?= $e($recepcion['direccion_cliente'] ?? '—'); ?></td>
            </tr>
        </table>

        <h2>Datos de la estancia</h2>
        <table>
            <tr>
                <td class="label">Habitación</td>
                <td><?= $e($recepcion['numero_habitacion']); ?> — <?= $e($recepcion['tipo_habitacion'] ?? 'Estándar'); ?></td>
            </tr>
            <tr>
                <td class="label">Fecha de entrada</td>
                <td><?= date('d/m/Y H:i', strtotime($recepcion['fechaentrada'])); ?></td>
            </tr>
            <tr>
                <td class="label">Fecha de salida prevista</td>
                <td><?= date('d/m/Y H:i', strtotime($recepcion['fechasalida_prevista'])); ?></td>
            </tr>
            <tr>
                <td class="label">Tarifa aplicada</td>
                <td><?= $e($recepcion['tipo_tarifa'] ?? '—'); ?></td>
            </tr>
        </table>

        <p class="aviso">
            El huésped declara que los datos consignados son correctos y acepta las condiciones
            de hospedaje del establecimiento, incluyendo el horario de check-out y la política
            de daños a las instalaciones.
        </p>

        <div class="firma">
            <div>Firma del huésped</div>
            <div>Firma de recepción</div>
        </div>
    </div>
</body>

</html>