<?php
/**
 * API - GENERAR REPORTE PDF
 * Generar reporte mensual en formato HTML imprimible
 * Sistema de Parqueadero Inteligente
 * 
 * NOTA: Para generar PDF real se requeriría una librería como TCPDF o FPDF
 * Esta versión genera un HTML optimizado para impresión
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger API - solo admin
middlewareAPIAdmin();
verificarMetodo('GET');

// Obtener mes y año
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

$mes = max(1, min(12, $mes));
$anio = max(2020, min(date('Y'), $anio));

$fecha_inicio = "$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";
$fecha_fin = date('Y-m-t 23:59:59', strtotime($fecha_inicio));

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Obtener datos para el reporte
$sql_stats = "SELECT 
    COUNT(*) as total_movimientos,
    SUM(CASE WHEN hora_salida IS NULL THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN hora_salida IS NOT NULL THEN 1 ELSE 0 END) as finalizados,
    COALESCE(SUM(total_pagar), 0) as ingresos_totales,
    COALESCE(AVG(total_pagar), 0) as ticket_promedio
    FROM movimientos
    WHERE hora_entrada >= ? AND hora_entrada <= ?";
$stats = obtenerFila($sql_stats, [$fecha_inicio, $fecha_fin]);

$sql_por_tipo = "SELECT 
    tipo_vehiculo,
    COUNT(*) as cantidad,
    COALESCE(SUM(total_pagar), 0) as ingresos
    FROM movimientos
    WHERE hora_entrada >= ? AND hora_entrada <= ?
      AND hora_salida IS NOT NULL
    GROUP BY tipo_vehiculo";
$por_tipo = obtenerFilas($sql_por_tipo, [$fecha_inicio, $fecha_fin]);

$nombre_parqueadero = obtenerConfiguracion('nombre_parqueadero');
$moneda = obtenerConfiguracion('moneda');

// Generar HTML para impresión
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte <?php echo $meses[$mes] . ' ' . $anio; ?> - <?php echo htmlspecialchars($nombre_parqueadero); ?></title>
    <style>
        @media print {
            .no-print { display: none; }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 14px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2563eb;
        }
        
        .header h1 {
            margin: 0;
            color: #1e293b;
        }
        
        .header h2 {
            margin: 10px 0 0 0;
            color: #64748b;
            font-weight: normal;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .info-box {
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 8px;
        }
        
        .info-box .label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .info-box .value {
            font-size: 24px;
            font-weight: bold;
            color: #1e293b;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        table th {
            background-color: #f1f5f9;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
        }
        
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }
        
        .btn-print {
            background-color: #2563eb;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .btn-print:hover {
            background-color: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">
            🖨️ Imprimir Reporte
        </button>
        <button class="btn-print" onclick="window.history.back()" style="background-color: #64748b;">
            ✖ Cerrar
        </button>
    </div>

    <div class="header">
        <h1><?php echo htmlspecialchars($nombre_parqueadero); ?></h1>
        <h2>Reporte Mensual - <?php echo $meses[$mes] . ' ' . $anio; ?></h2>
        <p style="margin-top: 10px; color: #64748b;">
            Período: <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> - <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
        </p>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="label">Total Movimientos</div>
            <div class="value"><?php echo $stats['total_movimientos']; ?></div>
        </div>
        
        <div class="info-box">
            <div class="label">Ingresos Totales</div>
            <div class="value"><?php echo formatearMoneda($stats['ingresos_totales']); ?></div>
        </div>
        
        <div class="info-box">
            <div class="label">Movimientos Finalizados</div>
            <div class="value"><?php echo $stats['finalizados']; ?></div>
        </div>
        
        <div class="info-box">
            <div class="label">Ticket Promedio</div>
            <div class="value"><?php echo formatearMoneda($stats['ticket_promedio']); ?></div>
        </div>
    </div>

    <h3 style="margin-top: 30px; margin-bottom: 15px; color: #1e293b;">Ingresos por Tipo de Vehículo</h3>
    
    <?php if (empty($por_tipo)): ?>
    <p style="text-align: center; color: #64748b;">No hay datos para este período</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Tipo de Vehículo</th>
                <th style="text-align: right;">Cantidad</th>
                <th style="text-align: right;">Ingresos</th>
                <th style="text-align: right;">% del Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($por_tipo as $tipo): ?>
            <tr>
                <td><strong><?php echo ucfirst($tipo['tipo_vehiculo']); ?></strong></td>
                <td style="text-align: right;"><?php echo $tipo['cantidad']; ?></td>
                <td style="text-align: right;"><strong><?php echo formatearMoneda($tipo['ingresos']); ?></strong></td>
                <td style="text-align: right;">
                    <?php 
                    $porcentaje = $stats['ingresos_totales'] > 0 
                        ? round(($tipo['ingresos'] / $stats['ingresos_totales']) * 100, 1)
                        : 0;
                    echo $porcentaje . '%';
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td>TOTAL</td>
                <td style="text-align: right;"><?php echo $stats['finalizados']; ?></td>
                <td style="text-align: right;"><?php echo formatearMoneda($stats['ingresos_totales']); ?></td>
                <td style="text-align: right;">100%</td>
            </tr>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="footer">
        <p>Reporte generado el <?php echo date('d/m/Y H:i:s'); ?></p>
        <p><?php echo htmlspecialchars($nombre_parqueadero); ?> - Sistema de Parqueadero Inteligente</p>
    </div>
</body>
</html>