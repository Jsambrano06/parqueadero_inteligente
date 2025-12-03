<?php
/**
 * MÓDULO EMPLEADO - REGISTRO DE ENTRADAS
 * Registro de ingreso de vehículos con asignación automática
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger ruta - solo empleado
middlewareEmpleado();
aplicarHeadersSeguridad();

// Obtener estadísticas rápidas
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'libre' THEN 1 ELSE 0 END) as libres,
    SUM(CASE WHEN estado = 'ocupado' THEN 1 ELSE 0 END) as ocupados
    FROM puestos";
$stats = obtenerFila($sql_stats);

// Disponibilidad por tipo
$sql_tipos = "SELECT 
    tp.id, tp.nombre,
    COUNT(p.id) as total,
    SUM(CASE WHEN p.estado = 'libre' THEN 1 ELSE 0 END) as libres
    FROM tipos_puesto tp
    LEFT JOIN puestos p ON tp.id = p.tipo_id
    GROUP BY tp.id, tp.nombre
    ORDER BY tp.id";
$disponibilidad = obtenerFilas($sql_tipos);

$nombre_parqueadero = obtenerConfiguracion('nombre_parqueadero');
$mensajes = obtenerMensajes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Entrada - <?php echo htmlspecialchars($nombre_parqueadero); ?></title>
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
                <a href="entrada.php" class="nav-item active">
                    <i class="fa-solid fa-right-to-bracket"></i> Registrar Entrada
                </a>
                <a href="salida.php" class="nav-item">
                    <i class="fa-solid fa-right-from-bracket"></i> Registrar Salida
                </a>
                <a href="historial.php" class="nav-item">
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
                <h1>Registrar Entrada de Vehículo</h1>
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

                <!-- Estadísticas Rápidas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Puestos Disponibles</span>
                            <div class="stat-card-icon success">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $stats['libres']; ?></div>
                        <div class="text-muted" style="font-size: 12px;">
                            de <?php echo $stats['total']; ?> totales
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Puestos Ocupados</span>
                            <div class="stat-card-icon danger">
                                <i class="fa-solid fa-car"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $stats['ocupados']; ?></div>
                        <div class="text-muted" style="font-size: 12px;">
                            <?php 
                            $porcentaje = $stats['total'] > 0 
                                ? round(($stats['ocupados'] / $stats['total']) * 100, 1)
                                : 0;
                            echo $porcentaje . '% de ocupación';
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Disponibilidad por Tipo -->
                <div class="card">
                    <div class="card-header">
                        <h3>Disponibilidad por Tipo de Vehículo</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <?php foreach ($disponibilidad as $tipo): ?>
                            <div style="padding: 16px; border: 2px solid #e2e8f0; border-radius: 8px; text-align: center;">
                                <?php 
                                $icono = $tipo['nombre'] == 'moto' ? 'motorcycle' : 
                                        ($tipo['nombre'] == 'carro' ? 'car' : 'truck');
                                $color = $tipo['libres'] > 0 ? '#10b981' : '#ef4444';
                                ?>
                                <i class="fa-solid fa-<?php echo $icono; ?>" style="font-size: 32px; color: <?php echo $color; ?>; margin-bottom: 8px;"></i>
                                <div style="font-size: 18px; font-weight: 600; margin-bottom: 4px;">
                                    <?php echo ucfirst($tipo['nombre']); ?>
                                </div>
                                <div style="font-size: 14px; color: <?php echo $color; ?>; font-weight: 600;">
                                    <?php echo $tipo['libres']; ?> disponibles
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Registro -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-right-to-bracket"></i> Datos del Vehículo</h3>
                    </div>
                    <div class="card-body">
                        <form id="formEntrada" method="POST">
                            <?php echo campoCSRF(); ?>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                                <div class="form-group">
                                    <label class="form-label" for="tipo_vehiculo">
                                        <i class="fa-solid fa-car"></i> Tipo de Vehículo *
                                    </label>
                                    <select id="tipo_vehiculo" name="tipo_vehiculo" class="form-control" required>
                                        <option value="">Seleccione el tipo</option>
                                        <option value="moto">Moto</option>
                                        <option value="carro">Carro</option>
                                        <option value="camion">Camión</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="placa">
                                        <i class="fa-solid fa-hashtag"></i> Placa del Vehículo *
                                    </label>
                                    <input 
                                        type="text" 
                                        id="placa" 
                                        name="placa" 
                                        class="form-control" 
                                        placeholder="Ej: ABC123"
                                        required
                                        maxlength="8"
                                        style="text-transform: uppercase;"
                                    >
                                    <small class="text-muted">Alfanumérico, 4-8 caracteres</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="color">
                                        <i class="fa-solid fa-palette"></i> Color (Opcional)
                                    </label>
                                    <input 
                                        type="text" 
                                        id="color" 
                                        name="color" 
                                        class="form-control" 
                                        placeholder="Ej: Azul, Rojo, Negro"
                                        maxlength="40"
                                    >
                                </div>
                            </div>

                            <div class="alert alert-info" style="margin-top: 20px;">
                                <i class="fa-solid fa-circle-info"></i>
                                <span><strong>Nota:</strong> El sistema asignará automáticamente un puesto disponible según el tipo de vehículo.</span>
                            </div>

                            <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fa-solid fa-rotate-left"></i> Limpiar
                                </button>
                                <button type="submit" class="btn btn-primary" id="btnRegistrar">
                                    <i class="fa-solid fa-check"></i> Registrar Entrada
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Últimas Entradas -->
                <div class="card">
                    <div class="card-header">
                        <h3>Últimas Entradas Registradas</h3>
                    </div>
                    <div class="card-body">
                        <div id="ultimasEntradas">
                            <p class="text-muted text-center">Cargando...</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/entrada.js"></script>
</body>
</html>