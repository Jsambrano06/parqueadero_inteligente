<?php
/**
 * REPORTES - ADMINISTRADOR
 * Reportes mensuales y estadísticas del sistema
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger ruta - solo admin
middlewareAdmin();
aplicarHeadersSeguridad();

// Obtener mes y año para el reporte (por defecto, mes actual)
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

// Validar mes y año
$mes = max(1, min(12, $mes));
$anio = max(2020, min(date('Y'), $anio));

$fecha_inicio = "$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";
$fecha_fin = date('Y-m-t 23:59:59', strtotime($fecha_inicio));

// Estadísticas del mes
$sql_stats = "SELECT 
    COUNT(*) as total_movimientos,
    SUM(CASE WHEN hora_salida IS NULL THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN hora_salida IS NOT NULL THEN 1 ELSE 0 END) as finalizados,
    COALESCE(SUM(total_pagar), 0) as ingresos_totales,
    COALESCE(AVG(total_pagar), 0) as ticket_promedio
    FROM movimientos
    WHERE hora_entrada >= ? AND hora_entrada <= ?";
$stats_mes = obtenerFila($sql_stats, [$fecha_inicio, $fecha_fin]);

// Ingresos por tipo de vehículo
$sql_por_tipo = "SELECT 
    tipo_vehiculo,
    COUNT(*) as cantidad,
    COALESCE(SUM(total_pagar), 0) as ingresos
    FROM movimientos
    WHERE hora_entrada >= ? AND hora_entrada <= ?
      AND hora_salida IS NOT NULL
    GROUP BY tipo_vehiculo
    ORDER BY ingresos DESC";
$ingresos_por_tipo = obtenerFilas($sql_por_tipo, [$fecha_inicio, $fecha_fin]);

// Ingresos por día del mes
$sql_por_dia = "SELECT 
    DATE(hora_entrada) as fecha,
    COUNT(*) as cantidad,
    COALESCE(SUM(total_pagar), 0) as ingresos
    FROM movimientos
    WHERE hora_entrada >= ? AND hora_entrada <= ?
      AND hora_salida IS NOT NULL
    GROUP BY DATE(hora_entrada)
    ORDER BY fecha ASC";
$ingresos_por_dia = obtenerFilas($sql_por_dia, [$fecha_inicio, $fecha_fin]);

// Movimientos por empleado
$sql_por_empleado = "SELECT 
    u.nombre as empleado,
    COUNT(*) as movimientos,
    COALESCE(SUM(m.total_pagar), 0) as ingresos_generados
    FROM movimientos m
    INNER JOIN usuarios u ON m.creado_por = u.id
    WHERE m.hora_entrada >= ? AND m.hora_entrada <= ?
    GROUP BY u.id, u.nombre
    ORDER BY movimientos DESC";
$stats_empleados = obtenerFilas($sql_por_empleado, [$fecha_inicio, $fecha_fin]);

// Top 10 vehículos que más tiempo estuvieron
$sql_top_tiempo = "SELECT 
    placa,
    tipo_vehiculo,
    hora_entrada,
    hora_salida,
    TIMESTAMPDIFF(MINUTE, hora_entrada, hora_salida) as minutos,
    total_pagar
    FROM movimientos
    WHERE hora_entrada >= ? AND hora_entrada <= ?
      AND hora_salida IS NOT NULL
    ORDER BY minutos DESC
    LIMIT 10";
$top_tiempo = obtenerFilas($sql_top_tiempo, [$fecha_inicio, $fecha_fin]);

$nombre_parqueadero = obtenerConfiguracion('nombre_parqueadero');
$mensajes = obtenerMensajes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - <?php echo htmlspecialchars($nombre_parqueadero); ?></title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="layout-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fa-solid fa-square-parking"></i> <?php echo htmlspecialchars($nombre_parqueadero); ?></h2>
                <div class="user-info">
                    <i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars(obtenerNombreUsuario()); ?>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fa-solid fa-gauge"></i> Dashboard
                </a>
                <a href="mapa.php" class="nav-item">
                    <i class="fa-solid fa-map"></i> Mapa del Parqueadero
                </a>
                <a href="puestos.php" class="nav-item">
                    <i class="fa-solid fa-border-all"></i> Gestión de Puestos
                </a>
                <a href="empleados.php" class="nav-item">
                    <i class="fa-solid fa-users"></i> Empleados
                </a>
                <a href="tarifas.php" class="nav-item">
                    <i class="fa-solid fa-dollar-sign"></i> Tarifas
                </a>
                <a href="reportes.php" class="nav-item active">
                    <i class="fa-solid fa-chart-line"></i> Reportes
                </a>
                <a href="configuraciones.php" class="nav-item">
                    <i class="fa-solid fa-gear"></i> Configuraciones
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="../public/logout.php" class="nav-item">
                    <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
                </a>
            </div>
        </aside>

        <!-- Contenido Principal -->
        <main class="main-content">
            <div class="topbar">
                <h1>Reportes y Estadísticas</h1>
            </div>

            <div class="content-wrapper">
                <!-- Mensajes -->
                <?php if ($mensajes['exito']): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?php echo htmlspecialchars($mensajes['exito']); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($mensajes['error']): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($mensajes['error']); ?></span>
                </div>
                <?php endif; ?>

                <!-- Selector de Mes/Año -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-calendar"></i> Seleccionar Período</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" style="display: flex; gap: 12px; align-items: end; flex-wrap: wrap;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" for="mes">Mes</label>
                                <select id="mes" name="mes" class="form-control">
                                    <?php
                                    $meses = [
                                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                    ];
                                    foreach ($meses as $num => $nombre):
                                    ?>
                                    <option value="<?php echo $num; ?>" <?php echo $mes == $num ? 'selected' : ''; ?>>
                                        <?php echo $nombre; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" for="anio">Año</label>
                                <select id="anio" name="anio" class="form-control">
                                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $anio == $y ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-magnifying-glass"></i> Generar Reporte
                            </button>

                            <button type="button" class="btn btn-success" onclick="exportarPDF()">
                                <i class="fa-solid fa-file-pdf"></i> Exportar PDF
                            </button>
                        </form>

                        <div style="margin-top: 16px; padding: 12px; background: #f8fafc; border-radius: 6px;">
                            <strong>Período seleccionado:</strong> 
                            <?php echo $meses[$mes] . ' ' . $anio; ?>
                            <span class="text-muted" style="margin-left: 12px;">
                                (<?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> - <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Generales -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Total Movimientos</span>
                            <div class="stat-card-icon primary">
                                <i class="fa-solid fa-right-left"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $stats_mes['total_movimientos']; ?></div>
                        <div class="text-muted" style="font-size: 12px;">
                            Finalizados: <?php echo $stats_mes['finalizados']; ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Ingresos Totales</span>
                            <div class="stat-card-icon success">
                                <i class="fa-solid fa-money-bill-wave"></i>
                            </div>
                        </div>
                        <div class="stat-card-value" style="font-size: 24px;">
                            <?php echo formatearMoneda($stats_mes['ingresos_totales']); ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Ticket Promedio</span>
                            <div class="stat-card-icon primary">
                                <i class="fa-solid fa-receipt"></i>
                            </div>
                        </div>
                        <div class="stat-card-value" style="font-size: 24px;">
                            <?php echo formatearMoneda($stats_mes['ticket_promedio']); ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Vehículos Activos</span>
                            <div class="stat-card-icon danger">
                                <i class="fa-solid fa-car"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $stats_mes['activos']; ?></div>
                        <div class="text-muted" style="font-size: 12px;">
                            Aún en el parqueadero
                        </div>
                    </div>
                </div>

                <!-- Ingresos por Tipo de Vehículo -->
                <div class="card">
                    <div class="card-header">
                        <h3>Ingresos por Tipo de Vehículo</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ingresos_por_tipo)): ?>
                        <p class="text-muted text-center">No hay datos para este período</p>
                        <?php else: ?>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Cantidad</th>
                                        <th>Ingresos</th>
                                        <th>% del Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ingresos_por_tipo as $tipo): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $icono = $tipo['tipo_vehiculo'] == 'moto' ? 'motorcycle' : 
                                                    ($tipo['tipo_vehiculo'] == 'carro' ? 'car' : 'truck');
                                            ?>
                                            <i class="fa-solid fa-<?php echo $icono; ?>"></i>
                                            <strong><?php echo ucfirst($tipo['tipo_vehiculo']); ?></strong>
                                        </td>
                                        <td><?php echo $tipo['cantidad']; ?></td>
                                        <td><strong><?php echo formatearMoneda($tipo['ingresos']); ?></strong></td>
                                        <td>
                                            <?php 
                                            $porcentaje = $stats_mes['ingresos_totales'] > 0 
                                                ? round(($tipo['ingresos'] / $stats_mes['ingresos_totales']) * 100, 1)
                                                : 0;
                                            ?>
                                            <?php echo $porcentaje; ?>%
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ingresos por Día -->
                <div class="card">
                    <div class="card-header">
                        <h3>Ingresos Diarios del Mes</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ingresos_por_dia)): ?>
                        <p class="text-muted text-center">No hay datos para este período</p>
                        <?php else: ?>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Movimientos</th>
                                        <th>Ingresos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ingresos_por_dia as $dia): ?>
                                    <tr>
                                        <td><?php echo formatearFecha($dia['fecha'], 'd/m/Y'); ?></td>
                                        <td><?php echo $dia['cantidad']; ?></td>
                                        <td><strong><?php echo formatearMoneda($dia['ingresos']); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Rendimiento por Empleado -->
                <div class="card">
                    <div class="card-header">
                        <h3>Rendimiento por Empleado</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($stats_empleados)): ?>
                        <p class="text-muted text-center">No hay datos para este período</p>
                        <?php else: ?>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Movimientos Registrados</th>
                                        <th>Ingresos Generados</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats_empleados as $emp): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($emp['empleado']); ?></strong></td>
                                        <td><?php echo $emp['movimientos']; ?></td>
                                        <td><strong><?php echo formatearMoneda($emp['ingresos_generados']); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top 10 Tiempos Más Largos -->
                <div class="card">
                    <div class="card-header">
                        <h3>Top 10 Vehículos con Mayor Tiempo de Permanencia</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_tiempo)): ?>
                        <p class="text-muted text-center">No hay datos para este período</p>
                        <?php else: ?>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Placa</th>
                                        <th>Tipo</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Tiempo</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $pos = 1; foreach ($top_tiempo as $item): ?>
                                    <tr>
                                        <td><?php echo $pos++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['placa']); ?></strong></td>
                                        <td><?php echo ucfirst($item['tipo_vehiculo']); ?></td>
                                        <td><?php echo formatearFecha($item['hora_entrada']); ?></td>
                                        <td><?php echo formatearFecha($item['hora_salida']); ?></td>
                                        <td>
                                            <strong>
                                            <?php 
                                            $horas = floor($item['minutos'] / 60);
                                            $mins = $item['minutos'] % 60;
                                            echo "{$horas}h {$mins}m";
                                            ?>
                                            </strong>
                                        </td>
                                        <td><?php echo formatearMoneda($item['total_pagar']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/global.js"></script>
    <script>
        function exportarPDF() {
            const mes = document.getElementById('mes').value;
            const anio = document.getElementById('anio').value;
            window.location.href = `../api/generar_reporte_pdf.php?mes=${mes}&anio=${anio}`;
        }
    </script>
</body>
</html>