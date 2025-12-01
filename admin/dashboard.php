<?php
/**
 * DASHBOARD ADMINISTRADOR
 * Vista principal con estadísticas y acceso rápido
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger ruta - solo admin
middlewareAdmin();
aplicarHeadersSeguridad();

// Obtener estadísticas
$sql_puestos = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'libre' THEN 1 ELSE 0 END) as libres,
    SUM(CASE WHEN estado = 'ocupado' THEN 1 ELSE 0 END) as ocupados,
    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
    FROM puestos";
$stats_puestos = obtenerFila($sql_puestos);

// Estadísticas por tipo de vehículo
$sql_por_tipo = "SELECT 
    tp.nombre as tipo,
    COUNT(p.id) as total,
    SUM(CASE WHEN p.estado = 'libre' THEN 1 ELSE 0 END) as libres,
    SUM(CASE WHEN p.estado = 'ocupado' THEN 1 ELSE 0 END) as ocupados
    FROM tipos_puesto tp
    LEFT JOIN puestos p ON tp.id = p.tipo_id
    GROUP BY tp.id, tp.nombre
    ORDER BY tp.id";
$stats_por_tipo = obtenerFilas($sql_por_tipo);

// Movimientos de hoy
$sql_hoy = "SELECT COUNT(*) as total FROM movimientos 
            WHERE DATE(hora_entrada) = CURDATE()";
$movimientos_hoy = obtenerFila($sql_hoy);

// Ingresos de hoy
$sql_ingresos_hoy = "SELECT COALESCE(SUM(total_pagar), 0) as total 
                      FROM movimientos 
                      WHERE DATE(hora_salida) = CURDATE() AND hora_salida IS NOT NULL";
$ingresos_hoy = obtenerFila($sql_ingresos_hoy);

// Ingresos del mes
$sql_ingresos_mes = "SELECT COALESCE(SUM(total_pagar), 0) as total 
                      FROM movimientos 
                      WHERE MONTH(hora_salida) = MONTH(CURDATE()) 
                      AND YEAR(hora_salida) = YEAR(CURDATE())
                      AND hora_salida IS NOT NULL";
$ingresos_mes = obtenerFila($sql_ingresos_mes);

// Últimos movimientos (10 más recientes)
$sql_ultimos = "SELECT m.*, p.codigo as puesto_codigo, u.nombre as empleado_nombre
                FROM movimientos m
                INNER JOIN puestos p ON m.puesto_id = p.id
                INNER JOIN usuarios u ON m.creado_por = u.id
                ORDER BY m.creado_en DESC
                LIMIT 10";
$ultimos_movimientos = obtenerFilas($sql_ultimos);

// Obtener configuración
$nombre_parqueadero = obtenerConfiguracion('nombre_parqueadero');
$limite_puestos = obtenerConfiguracion('limite_puestos');

// Obtener mensajes
$mensajes = obtenerMensajes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($nombre_parqueadero); ?></title>
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
                <a href="dashboard.php" class="nav-item active">
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
                </</a>
                <a href="reportes.php" class="nav-item">
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
                <h1>Dashboard</h1>
                <div class="topbar-actions">
                    <span class="text-muted">
                        <i class="fa-solid fa-calendar"></i> 
                        <?php echo date('d/m/Y H:i'); ?>
                    </span>
                </div>
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

                <!-- Estadísticas Principales -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Total Puestos</span>
                            <div class="stat-card-icon primary">
                                <i class="fa-solid fa-border-all"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $stats_puestos['total']; ?></div>
                        <div class="text-muted" style="font-size: 12px;">
                            Límite: <?php echo $limite_puestos; ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Puestos Libres</span>
                            <div class="stat-card-icon success">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $stats_puestos['libres']; ?></div>
                        <div class="text-muted" style="font-size: 12px;">
                            <?php 
                            $porcentaje = $stats_puestos['total'] > 0 
                                ? round(($stats_puestos['libres'] / $stats_puestos['total']) * 100, 1)
                                : 0;
                            echo $porcentaje . '% disponible';
                            ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Puestos Ocupados</span>
                            <div class="stat-card-icon danger">
                                <i class="fa-solid fa-car"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $stats_puestos['ocupados']; ?></div>
                        <div class="text-muted" style="font-size: 12px;">
                            <?php 
                            $porcentaje_ocupado = $stats_puestos['total'] > 0 
                                ? round(($stats_puestos['ocupados'] / $stats_puestos['total']) * 100, 1)
                                : 0;
                            echo $porcentaje_ocupado . '% ocupación';
                            ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Movimientos Hoy</span>
                            <div class="stat-card-icon primary">
                                <i class="fa-solid fa-right-left"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $movimientos_hoy['total']; ?></div>
                        <div class="text-muted" style="font-size: 12px;">
                            Entradas registradas
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Financieras -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Ingresos de Hoy</span>
                            <div class="stat-card-icon success">
                                <i class="fa-solid fa-money-bill-wave"></i>
                            </div>
                        </div>
                        <div class="stat-card-value">
                            <?php echo formatearMoneda($ingresos_hoy['total']); ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Ingresos del Mes</span>
                            <div class="stat-card-icon success">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                        </div>
                        <div class="stat-card-value">
                            <?php echo formatearMoneda($ingresos_mes['total']); ?>
                        </div>
                    </div>
                </div>

                <!-- Distribución por Tipo de Vehículo -->
                <div class="card">
                    <div class="card-header">
                        <h3>Distribución por Tipo de Vehículo</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Total Puestos</th>
                                        <th>Libres</th>
                                        <th>Ocupados</th>
                                        <th>Ocupación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats_por_tipo as $tipo): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $icono = $tipo['tipo'] == 'moto' ? 'motorcycle' : 
                                                    ($tipo['tipo'] == 'carro' ? 'car' : 'truck');
                                            ?>
                                            <i class="fa-solid fa-<?php echo $icono; ?>"></i>
                                            <?php echo ucfirst($tipo['tipo']); ?>
                                        </td>
                                        <td><?php echo $tipo['total']; ?></td>
                                        <td>
                                            <span class="badge badge-success">
                                                <?php echo $tipo['libres']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-danger">
                                                <?php echo $tipo['ocupados']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $ocupacion = $tipo['total'] > 0 
                                                ? round(($tipo['ocupados'] / $tipo['total']) * 100, 1)
                                                : 0;
                                            ?>
                                            <strong><?php echo $ocupacion; ?>%</strong>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Últimos Movimientos -->
                <div class="card">
                    <div class="card-header">
                        <h3>Últimos Movimientos</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ultimos_movimientos)): ?>
                        <p class="text-muted text-center">No hay movimientos registrados aún.</p>
                        <?php else: ?>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Puesto</th>
                                        <th>Tipo</th>
                                        <th>Placa</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Total</th>
                                        <th>Empleado</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimos_movimientos as $mov): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($mov['puesto_codigo']); ?></strong></td>
                                        <td>
                                            <?php 
                                            $icono = $mov['tipo_vehiculo'] == 'moto' ? 'motorcycle' : 
                                                    ($mov['tipo_vehiculo'] == 'carro' ? 'car' : 'truck');
                                            ?>
                                            <i class="fa-solid fa-<?php echo $icono; ?>"></i>
                                            <?php echo ucfirst($mov['tipo_vehiculo']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($mov['placa']); ?></td>
                                        <td><?php echo formatearFecha($mov['hora_entrada']); ?></td>
                                        <td>
                                            <?php 
                                            echo $mov['hora_salida'] 
                                                ? formatearFecha($mov['hora_salida']) 
                                                : '<span class="badge badge-primary">Activo</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            echo $mov['total_pagar'] 
                                                ? formatearMoneda($mov['total_pagar']) 
                                                : '-';
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($mov['empleado_nombre']); ?></td>
                                        <td>
                                            <?php if ($mov['hora_salida']): ?>
                                                <span class="badge badge-secondary">Finalizado</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">En curso</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Accesos Rápidos -->
                <div class="card">
                    <div class="card-header">
                        <h3>Accesos Rápidos</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <a href="mapa.php" class="btn btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
                                <i class="fa-solid fa-map"></i> Ver Mapa
                            </a>
                            <a href="puestos.php" class="btn btn-success" style="display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
                                <i class="fa-solid fa-plus"></i> Gestionar Puestos
                            </a>
                            <a href="reportes.php" class="btn btn-secondary" style="display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
                                <i class="fa-solid fa-file-pdf"></i> Ver Reportes
                            </a>
                            <a href="configuraciones.php" class="btn btn-secondary" style="display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
                                <i class="fa-solid fa-gear"></i> Configuraciones
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/global.js"></script>
</body>
</html>