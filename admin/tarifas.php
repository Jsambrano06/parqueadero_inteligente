<?php
/**
 * GESTIÓN DE TARIFAS - ADMINISTRADOR
 * Actualización de precios por tipo de vehículo
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger ruta - solo admin
middlewareAdmin();
aplicarHeadersSeguridad();

// Obtener tarifas actuales
$sql = "SELECT id, tipo_vehiculo, precio_hora FROM tarifas ORDER BY id";
$tarifas = obtenerFilas($sql);

// Obtener configuraciones de cobro
$redondeo = obtenerConfiguracion('redondeo_minutos');
$tarifa_minima = obtenerConfiguracion('tarifa_minima_horas');
$moneda = obtenerConfiguracion('moneda');

$nombre_parqueadero = obtenerConfiguracion('nombre_parqueadero');
$mensajes = obtenerMensajes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tarifas - <?php echo htmlspecialchars($nombre_parqueadero); ?></title>
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
                <a href="tarifas.php" class="nav-item active">
                    <i class="fa-solid fa-dollar-sign"></i> Tarifas
                </a>
                <a href="reportes.php" class="nav-item">
                    <i class="fa-solid fa-chart-line"></i> Reportes
                </a>
                <a href="configuraciones.php" class="nav-item">
                    <i class="fa-solid fa-gear"></i> Configuraciones
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
                <h1>Gestión de Tarifas</h1>
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

                <!-- Información de Configuración -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-circle-info"></i> Configuración de Cobro</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #3b82f6;">
                                <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">Redondeo de Tiempo</div>
                                <div style="font-size: 20px; font-weight: 600; color: #1e293b;">
                                    Cada <?php echo $redondeo; ?> minutos
                                </div>
                            </div>
                            
                            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #10b981;">
                                <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">Tarifa Mínima</div>
                                <div style="font-size: 20px; font-weight: 600; color: #1e293b;">
                                    <?php echo $tarifa_minima; ?> hora(s)
                                </div>
                            </div>
                            
                            <div style="padding: 16px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #f59e0b;">
                                <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">Moneda</div>
                                <div style="font-size: 20px; font-weight: 600; color: #1e293b;">
                                    <?php echo $moneda; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info" style="margin-top: 20px;">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>
                                <strong>Ejemplo de cálculo:</strong> Un vehículo que permanece 1 hora y 20 minutos 
                                se redondea a 1 hora y 30 minutos (<?php echo $redondeo; ?> min), y se cobra como 1.5 horas.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tarifas Actuales -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-dollar-sign"></i> Tarifas por Tipo de Vehículo</h3>
                    </div>
                    <div class="card-body">
                        <form id="formTarifas">
                            <?php echo campoCSRF(); ?>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                                <?php foreach ($tarifas as $tarifa): ?>
                                <div style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 24px; background: white; transition: all 0.2s;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#e2e8f0'">
                                    <div style="text-align: center; margin-bottom: 20px;">
                                        <?php 
                                        $icono = $tarifa['tipo_vehiculo'] == 'moto' ? 'motorcycle' : 
                                                ($tarifa['tipo_vehiculo'] == 'carro' ? 'car' : 'truck');
                                        $color = $tarifa['tipo_vehiculo'] == 'moto' ? '#3b82f6' : 
                                                ($tarifa['tipo_vehiculo'] == 'carro' ? '#10b981' : '#f59e0b');
                                        ?>
                                        <div style="width: 80px; height: 80px; margin: 0 auto 12px; background: <?php echo $color; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="fa-solid fa-<?php echo $icono; ?>" style="font-size: 40px; color: white;"></i>
                                        </div>
                                        <h4 style="font-size: 20px; font-weight: 600; margin-bottom: 4px; text-transform: capitalize;">
                                            <?php echo $tarifa['tipo_vehiculo']; ?>
                                        </h4>
                                    </div>
                                    
                                    <input type="hidden" name="tarifa_id[]" value="<?php echo $tarifa['id']; ?>">
                                    <input type="hidden" name="tipo_vehiculo[]" value="<?php echo $tarifa['tipo_vehiculo']; ?>">
                                    
                                    <div class="form-group">
                                        <label class="form-label" for="precio_<?php echo $tarifa['id']; ?>">
                                            Precio por Hora (<?php echo $moneda; ?>)
                                        </label>
                                        <div style="position: relative;">
                                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-weight: 600; color: #64748b;">$</span>
                                            <input 
                                                type="number" 
                                                id="precio_<?php echo $tarifa['id']; ?>" 
                                                name="precio_hora[]" 
                                                class="form-control" 
                                                value="<?php echo number_format($tarifa['precio_hora'], 0, '', ''); ?>"
                                                min="0"
                                                step="100"
                                                required
                                                style="padding-left: 32px; font-size: 18px; font-weight: 600;"
                                            >
                                        </div>
                                    </div>
                                    
                                    <div style="background: #f8fafc; padding: 12px; border-radius: 6px; margin-top: 12px;">
                                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Tarifa Actual:</div>
                                        <div style="font-size: 24px; font-weight: 700; color: <?php echo $color; ?>;">
                                            <?php echo formatearMoneda($tarifa['precio_hora']); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="margin-top: 32px; text-align: center;">
                                <button type="submit" class="btn btn-primary btn-lg" id="btnActualizarTarifas">
                                    <i class="fa-solid fa-save"></i> Guardar Cambios en Tarifas
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Historial de Cambios -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-clock-rotate-left"></i> Historial Reciente de Cambios</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $sql_historial = "SELECT a.*, u.nombre as usuario_nombre 
                                         FROM auditoria a
                                         LEFT JOIN usuarios u ON a.usuario_id = u.id
                                         WHERE a.accion LIKE '%tarifa%'
                                         ORDER BY a.fecha DESC
                                         LIMIT 10";
                        $historial = obtenerFilas($sql_historial);
                        ?>
                        
                        <?php if (empty($historial)): ?>
                        <p class="text-muted text-center">No hay cambios registrados aún.</p>
                        <?php else: ?>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Usuario</th>
                                        <th>Acción</th>
                                        <th>Detalles</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $item): ?>
                                    <tr>
                                        <td><?php echo formatearFecha($item['fecha']); ?></td>
                                        <td><?php echo $item['usuario_nombre'] ?: 'Sistema'; ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['accion']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['detalles']); ?></td>
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
    <script src="../assets/js/tarifas.js"></script>
</body>
</html>