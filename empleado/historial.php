<?php
/**
 * MÓDULO EMPLEADO - HISTORIAL DE MOVIMIENTOS
 * Visualización de entradas y salidas registradas
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger ruta - solo empleado
middlewareEmpleado();
aplicarHeadersSeguridad();

$usuario_id = obtenerIdUsuario();

// Paginación
$registros_por_pagina = 20;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Filtros
$filtro_placa = isset($_GET['placa']) ? trim($_GET['placa']) : '';
$filtro_tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$filtro_fecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : '';
$filtro_estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

// Construir consulta con filtros
$where = ["m.creado_por = ?"];
$params = [$usuario_id];

if (!empty($filtro_placa)) {
    $where[] = "m.placa LIKE ?";
    $params[] = "%$filtro_placa%";
}

if (!empty($filtro_tipo)) {
    $where[] = "m.tipo_vehiculo = ?";
    $params[] = $filtro_tipo;
}

if (!empty($filtro_fecha)) {
    $where[] = "DATE(m.hora_entrada) = ?";
    $params[] = $filtro_fecha;
}

if (!empty($filtro_estado)) {
    if ($filtro_estado === 'activo') {
        $where[] = "m.hora_salida IS NULL";
    } else {
        $where[] = "m.hora_salida IS NOT NULL";
    }
}

$where_clause = implode(' AND ', $where);

// Contar total de registros
$sql_count = "SELECT COUNT(*) as total FROM movimientos m WHERE $where_clause";
$total_registros = obtenerFila($sql_count, $params)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener movimientos
$sql = "SELECT m.*, p.codigo as puesto_codigo
        FROM movimientos m
        INNER JOIN puestos p ON m.puesto_id = p.id
        WHERE $where_clause
        ORDER BY m.hora_entrada DESC
        LIMIT $registros_por_pagina OFFSET $offset";
$movimientos = obtenerFilas($sql, $params);

// Estadísticas del empleado
$sql_stats = "SELECT 
    COUNT(*) as total_registros,
    SUM(CASE WHEN hora_salida IS NULL THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN hora_salida IS NOT NULL THEN 1 ELSE 0 END) as finalizados,
    COALESCE(SUM(total_pagar), 0) as total_cobrado
    FROM movimientos
    WHERE creado_por = ?";
$stats = obtenerFila($sql_stats, [$usuario_id]);

$nombre_parqueadero = obtenerConfiguracion('nombre_parqueadero');
$mensajes = obtenerMensajes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial - <?php echo htmlspecialchars($nombre_parqueadero); ?></title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../assets/js/tutorial.js"></script>
    <script src="../assets/js/tutorials.js"></script>
</head>
<body>
    <div class="layout-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fa-solid fa-square-parking"></i> <?php echo htmlspecialchars($nombre_parqueadero); ?></h2>
                <div class="user-info">
                    <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars(obtenerNombreUsuario()); ?>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="entrada.php" class="nav-item">
                    <i class="fa-solid fa-right-to-bracket"></i> Registrar Entrada
                </a>
                <a href="salida.php" class="nav-item">
                    <i class="fa-solid fa-right-from-bracket"></i> Registrar Salida
                </a>
                <a href="historial.php" class="nav-item active">
                    <i class="fa-solid fa-clock-rotate-left"></i> Historial
                </a>
            </nav>

            <div class="sidebar-footer">
                <button onclick="startTutorial()" class="nav-item" style="width: 100%; text-align: left; background: none; border: none; cursor: pointer;">
                    <i class="fa-solid fa-graduation-cap"></i> Tutorial
                </button>
                <a href="../public/logout.php" class="nav-item">
                    <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
                </a>
            </div>
        </aside>

        <!-- Contenido Principal -->
        <main class="main-content">
            <div class="topbar">
                <h1>Historial de Movimientos</h1>
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

                <!-- Estadísticas del Empleado -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Total Registros</span>
                            <div class="stat-card-icon primary">
                                <i class="fa-solid fa-list"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $stats['total_registros']; ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Vehículos Activos</span>
                            <div class="stat-card-icon success">
                                <i class="fa-solid fa-car"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $stats['activos']; ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Registros Finalizados</span>
                            <div class="stat-card-icon">
                                <i class="fa-solid fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $stats['finalizados']; ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Total Cobrado</span>
                            <div class="stat-card-icon success">
                                <i class="fa-solid fa-money-bill-wave"></i>
                            </div>
                        </div>
                        <div class="stat-card-value" style="font-size: 22px;">
                            <?php echo formatearMoneda($stats['total_cobrado']); ?>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-filter"></i> Filtros de Búsqueda</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" id="formFiltros">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                                <div class="form-group">
                                    <label class="form-label" for="placa">Placa</label>
                                    <input 
                                        type="text" 
                                        id="placa" 
                                        name="placa" 
                                        class="form-control" 
                                        placeholder="Buscar por placa"
                                        value="<?php echo htmlspecialchars($filtro_placa); ?>"
                                        style="text-transform: uppercase;"
                                    >
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="tipo">Tipo de Vehículo</label>
                                    <select id="tipo" name="tipo" class="form-control">
                                        <option value="">Todos</option>
                                        <option value="moto" <?php echo $filtro_tipo === 'moto' ? 'selected' : ''; ?>>Moto</option>
                                        <option value="carro" <?php echo $filtro_tipo === 'carro' ? 'selected' : ''; ?>>Carro</option>
                                        <option value="camion" <?php echo $filtro_tipo === 'camion' ? 'selected' : ''; ?>>Camión</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="fecha">Fecha</label>
                                    <input 
                                        type="date" 
                                        id="fecha" 
                                        name="fecha" 
                                        class="form-control"
                                        value="<?php echo htmlspecialchars($filtro_fecha); ?>"
                                    >
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="estado">Estado</label>
                                    <select id="estado" name="estado" class="form-control">
                                        <option value="">Todos</option>
                                        <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="finalizado" <?php echo $filtro_estado === 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                                    </select>
                                </div>
                            </div>

                            <div style="margin-top: 16px; display: flex; gap: 12px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-magnifying-glass"></i> Buscar
                                </button>
                                <a href="historial.php" class="btn btn-secondary">
                                    <i class="fa-solid fa-rotate-left"></i> Limpiar Filtros
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de Movimientos -->
                <div class="card">
                    <div class="card-header">
                        <h3>Registros (<?php echo $total_registros; ?> total)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($movimientos)): ?>
                        <p class="text-muted text-center">No se encontraron registros con los filtros aplicados.</p>
                        <?php else: ?>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Puesto</th>
                                        <th>Tipo</th>
                                        <th>Placa</th>
                                        <th>Color</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Tiempo</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movimientos as $mov): ?>
                                    <tr>
                                        <td><?php echo $mov['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($mov['puesto_codigo']); ?></strong></td>
                                        <td>
                                            <?php 
                                            $icono = $mov['tipo_vehiculo'] == 'moto' ? 'motorcycle' : 
                                                    ($mov['tipo_vehiculo'] == 'carro' ? 'car' : 'truck');
                                            ?>
                                            <i class="fa-solid fa-<?php echo $icono; ?>"></i>
                                            <?php echo ucfirst($mov['tipo_vehiculo']); ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($mov['placa']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($mov['color']) ?: '-'; ?></td>
                                        <td><?php echo formatearFecha($mov['hora_entrada']); ?></td>
                                        <td>
                                            <?php 
                                            echo $mov['hora_salida'] 
                                                ? formatearFecha($mov['hora_salida']) 
                                                : '<span class="badge badge-primary">En curso</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($mov['hora_salida']) {
                                                $entrada = new DateTime($mov['hora_entrada']);
                                                $salida = new DateTime($mov['hora_salida']);
                                                $diff = $entrada->diff($salida);
                                                echo sprintf('%dh %dm', 
                                                    ($diff->days * 24 + $diff->h), 
                                                    $diff->i
                                                );
                                            } else {
                                                $entrada = new DateTime($mov['hora_entrada']);
                                                $ahora = new DateTime();
                                                $diff = $entrada->diff($ahora);
                                                echo sprintf('%dh %dm', 
                                                    ($diff->days * 24 + $diff->h), 
                                                    $diff->i
                                                );
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            echo $mov['total_pagar'] 
                                                ? formatearMoneda($mov['total_pagar']) 
                                                : '-';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($mov['hora_salida']): ?>
                                                <span class="badge badge-secondary">Finalizado</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Activo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <?php if ($total_paginas > 1): ?>
                        <div style="margin-top: 24px; display: flex; justify-content: center; align-items: center; gap: 8px;">
                            <?php if ($pagina_actual > 1): ?>
                            <a href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo !empty($filtro_placa) ? '&placa=' . urlencode($filtro_placa) : ''; ?><?php echo !empty($filtro_tipo) ? '&tipo=' . urlencode($filtro_tipo) : ''; ?><?php echo !empty($filtro_fecha) ? '&fecha=' . urlencode($filtro_fecha) : ''; ?><?php echo !empty($filtro_estado) ? '&estado=' . urlencode($filtro_estado) : ''; ?>" 
               class="btn btn-sm btn-secondary">
                                <i class="fa-solid fa-chevron-left"></i> Anterior
                            </a>
                            <?php endif; ?>

                            <span class="text-muted">
                                Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>
                            </span>

                            <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo !empty($filtro_placa) ? '&placa=' . urlencode($filtro_placa) : ''; ?><?php echo !empty($filtro_tipo) ? '&tipo=' . urlencode($filtro_tipo) : ''; ?><?php echo !empty($filtro_fecha) ? '&fecha=' . urlencode($filtro_fecha) : ''; ?><?php echo !empty($filtro_estado) ? '&estado=' . urlencode($filtro_estado) : ''; ?>" 
               class="btn btn-sm btn-secondary">
                                Siguiente <i class="fa-solid fa-chevron-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/global.js"></script>
    <script>
        // Convertir placa a mayúsculas automáticamente
        document.getElementById('placa').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>