<?php
/**
 * CONFIGURACIONES DEL SISTEMA - ADMINISTRADOR
 * Configuración general del parqueadero
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger ruta - solo admin
middlewareAdmin();
aplicarHeadersSeguridad();

// Obtener todas las configuraciones
$sql = "SELECT nombre, valor FROM configuraciones";
$configs_raw = obtenerFilas($sql);

$configs = [];
foreach ($configs_raw as $config) {
    $configs[$config['nombre']] = $config['valor'];
}

$nombre_parqueadero = obtenerConfiguracion('nombre_parqueadero');
$mensajes = obtenerMensajes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuraciones - <?php echo htmlspecialchars($nombre_parqueadero); ?></title>
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
                <a href="tarifas.php" class="nav-item">
                    <i class="fa-solid fa-dollar-sign"></i> Tarifas
                </a>
                <a href="reportes.php" class="nav-item">
                    <i class="fa-solid fa-chart-line"></i> Reportes
                </a>
                <a href="configuraciones.php" class="nav-item active">
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
                <h1>Configuraciones del Sistema</h1>
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

                <!-- Configuración General -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-building"></i> Configuración General</h3>
                    </div>
                    <div class="card-body">
                        <form id="formConfigGeneral">
                            <?php echo campoCSRF(); ?>
                            
                            <div class="form-group">
                                <label class="form-label" for="nombre_parqueadero">
                                    <i class="fa-solid fa-square-parking"></i> Nombre del Parqueadero
                                </label>
                                <input 
                                    type="text" 
                                    id="nombre_parqueadero" 
                                    name="nombre_parqueadero" 
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($configs['nombre_parqueadero']); ?>"
                                    required
                                    maxlength="100"
                                >
                                <small class="text-muted">Nombre que aparecerá en todo el sistema</small>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div class="form-group">
                                    <label class="form-label" for="moneda">
                                        <i class="fa-solid fa-dollar-sign"></i> Moneda
                                    </label>
                                    <select id="moneda" name="moneda" class="form-control" required>
                                        <option value="COP" <?php echo $configs['moneda'] === 'COP' ? 'selected' : ''; ?>>COP (Peso Colombiano)</option>
                                        <option value="USD" <?php echo $configs['moneda'] === 'USD' ? 'selected' : ''; ?>>USD (Dólar)</option>
                                        <option value="EUR" <?php echo $configs['moneda'] === 'EUR' ? 'selected' : ''; ?>>EUR (Euro)</option>
                                        <option value="MXN" <?php echo $configs['moneda'] === 'MXN' ? 'selected' : ''; ?>>MXN (Peso Mexicano)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="zona_horaria">
                                        <i class="fa-solid fa-clock"></i> Zona Horaria
                                    </label>
                                    <select id="zona_horaria" name="zona_horaria" class="form-control" required>
                                        <option value="America/Bogota" <?php echo $configs['zona_horaria'] === 'America/Bogota' ? 'selected' : ''; ?>>Bogotá (Colombia)</option>
                                        <option value="America/Mexico_City" <?php echo $configs['zona_horaria'] === 'America/Mexico_City' ? 'selected' : ''; ?>>Ciudad de México</option>
                                        <option value="America/Lima" <?php echo $configs['zona_horaria'] === 'America/Lima' ? 'selected' : ''; ?>>Lima (Perú)</option>
                                        <option value="America/Santiago" <?php echo $configs['zona_horaria'] === 'America/Santiago' ? 'selected' : ''; ?>>Santiago (Chile)</option>
                                        <option value="America/Buenos_Aires" <?php echo $configs['zona_horaria'] === 'America/Buenos_Aires' ? 'selected' : ''; ?>>Buenos Aires (Argentina)</option>
                                    </select>
                                </div>
                            </div>

                            <div style="margin-top: 20px; text-align: right;">
                                <button type="submit" class="btn btn-primary" id="btnGuardarGeneral">
                                    <i class="fa-solid fa-save"></i> Guardar Configuración General
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Configuración de Cobro -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-calculator"></i> Configuración de Cobro</h3>
                    </div>
                    <div class="card-body">
                        <form id="formConfigCobro">
                            <?php echo campoCSRF(); ?>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div class="form-group">
                                    <label class="form-label" for="redondeo_minutos">
                                        <i class="fa-solid fa-clock"></i> Redondeo de Tiempo (minutos)
                                    </label>
                                    <select id="redondeo_minutos" name="redondeo_minutos" class="form-control" required>
                                        <option value="5" <?php echo $configs['redondeo_minutos'] == 5 ? 'selected' : ''; ?>>5 minutos</option>
                                        <option value="10" <?php echo $configs['redondeo_minutos'] == 10 ? 'selected' : ''; ?>>10 minutos</option>
                                        <option value="15" <?php echo $configs['redondeo_minutos'] == 15 ? 'selected' : ''; ?>>15 minutos</option>
                                        <option value="30" <?php echo $configs['redondeo_minutos'] == 30 ? 'selected' : ''; ?>>30 minutos</option>
                                        <option value="60" <?php echo $configs['redondeo_minutos'] == 60 ? 'selected' : ''; ?>>60 minutos (1 hora)</option>
                                    </select>
                                    <small class="text-muted">El tiempo se redondea hacia arriba en este intervalo</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="tarifa_minima_horas">
                                        <i class="fa-solid fa-hourglass-start"></i> Tarifa Mínima (horas)
                                    </label>
                                    <select id="tarifa_minima_horas" name="tarifa_minima_horas" class="form-control" required>
                                        <option value="0.25" <?php echo $configs['tarifa_minima_horas'] == 0.25 ? 'selected' : ''; ?>>15 minutos (0.25h)</option>
                                        <option value="0.5" <?php echo $configs['tarifa_minima_horas'] == 0.5 ? 'selected' : ''; ?>>30 minutos (0.5h)</option>
                                        <option value="1" <?php echo $configs['tarifa_minima_horas'] == 1 ? 'selected' : ''; ?>>1 hora</option>
                                        <option value="2" <?php echo $configs['tarifa_minima_horas'] == 2 ? 'selected' : ''; ?>>2 horas</option>
                                    </select>
                                    <small class="text-muted">Mínimo de horas a cobrar por servicio</small>
                                </div>
                            </div>

                            <div class="alert alert-info" style="margin-top: 20px;">
                                <i class="fa-solid fa-circle-info"></i>
                                <div>
                                    <strong>Ejemplo de cálculo actual:</strong><br>
                                    Un vehículo que permanece 1 hora y 20 minutos se redondea a 
                                    <strong>
                                    <?php 
                                    $ejemplo_min = 80; // 1h 20min
                                    $redondeo = (int)$configs['redondeo_minutos'];
                                    $redondeado = ceil($ejemplo_min / $redondeo) * $redondeo;
                                    $horas = $redondeado / 60;
                                    echo number_format($horas, 2) . ' horas';
                                    ?>
                                    </strong>
                                    (redondeado cada <?php echo $configs['redondeo_minutos']; ?> minutos).
                                    Se cobrará como mínimo <?php echo $configs['tarifa_minima_horas']; ?> hora(s).
                                </div>
                            </div>

                            <div style="margin-top: 20px; text-align: right;">
                                <button type="submit" class="btn btn-primary" id="btnGuardarCobro">
                                    <i class="fa-solid fa-save"></i> Guardar Configuración de Cobro
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Configuración de Capacidad -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-border-all"></i> Configuración de Capacidad</h3>
                    </div>
                    <div class="card-body">
                        <form id="formConfigCapacidad">
                            <?php echo campoCSRF(); ?>
                            
                            <div class="form-group">
                                <label class="form-label" for="limite_puestos">
                                    <i class="fa-solid fa-chart-simple"></i> Límite Máximo de Puestos
                                </label>
                                <input 
                                    type="number" 
                                    id="limite_puestos" 
                                    name="limite_puestos" 
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($configs['limite_puestos']); ?>"
                                    min="1"
                                    max="1000"
                                    required
                                >
                                <small class="text-muted">
                                    Número máximo de puestos permitidos en el parqueadero. 
                                    <strong>Actual:</strong> <?php echo contarPuestos(); ?> puestos creados.
                                </small>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>
                                    <strong>Advertencia:</strong> Si reduces el límite por debajo de la cantidad actual de puestos, 
                                    no se eliminarán los existentes, pero no podrás crear nuevos hasta que estés dentro del límite.
                                </span>
                            </div>

                            <div style="margin-top: 20px; text-align: right;">
                                <button type="submit" class="btn btn-primary" id="btnGuardarCapacidad">
                                    <i class="fa-solid fa-save"></i> Guardar Configuración de Capacidad
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Información del Sistema -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-circle-info"></i> Información del Sistema</h3>
                    </div>
                    <div class="card-body">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div style="padding: 16px; background: var(--surface-color, #f8fafc); border-radius: 8px; border-left: 4px solid var(--color-primario, #3b82f6);">
                                <div style="font-size: 13px; color: var(--text-muted, #64748b); margin-bottom: 4px;">Versión del Sistema</div>
                                <div style="font-size: 18px; font-weight: 600; color: var(--text-color, #1e293b);">v1.0.0</div>
                            </div>

                            <div style="padding: 16px; background: var(--surface-color, #f8fafc); border-radius: 8px; border-left: 4px solid var(--color-exito, #10b981);">
                                <div style="font-size: 13px; color: var(--text-muted, #64748b); margin-bottom: 4px;">Base de Datos</div>
                                <div style="font-size: 18px; font-weight: 600; color: var(--text-color, #1e293b);">MySQL / MariaDB</div>
                            </div>

                            <div style="padding: 16px; background: var(--surface-color, #f8fafc); border-radius: 8px; border-left: 4px solid var(--color-advertencia, #f59e0b);">
                                <div style="font-size: 13px; color: var(--text-muted, #64748b); margin-bottom: 4px;">Versión PHP</div>
                                <div style="font-size: 18px; font-weight: 600; color: var(--text-color, #1e293b);"><?php echo phpversion(); ?></div>
                            </div>

                            <div style="padding: 16px; background: var(--surface-color, #f8fafc); border-radius: 8px; border-left: 4px solid var(--color-peligro, #ef4444);">
                                <div style="font-size: 13px; color: var(--text-muted, #64748b); margin-bottom: 4px;">Servidor</div>
                                <div style="font-size: 18px; font-weight: 600; color: var(--text-color, #1e293b);"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></div>
                            </div>
                        </div>

                        <div style="margin-top: 24px; padding: 16px; background: var(--surface-color, #eff6ff); border-radius: 8px; border: 1px solid var(--border-color, #bfdbfe); color: var(--text-color);">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <i class="fa-solid fa-circle-check text-primary" style="font-size: 24px;"></i>
                                <div>
                                    <strong>Sistema de Parqueadero Inteligente</strong><br>
                                    <small class="text-muted">
                                        Desarrollado con HTML, CSS, JavaScript, PHP y MySQL. 
                                        Sin frameworks externos.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones de Mantenimiento -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-tools"></i> Mantenimiento del Sistema</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span>
                                <strong>Advertencia:</strong> Las acciones de mantenimiento son irreversibles. 
                                Asegúrate de tener respaldos antes de proceder.
                            </span>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-top: 20px;">
                            <button class="btn btn-secondary" onclick="verAuditoria()">
                                <i class="fa-solid fa-clock-rotate-left"></i> Ver Auditoría Completa
                            </button>

                            <button class="btn btn-secondary" onclick="verLogMapa()">
                                <i class="fa-solid fa-map"></i> Ver Log del Mapa
                            </button>

                            <button class="btn btn-danger" onclick="limpiarAuditoriaAntigua()">
                                <i class="fa-solid fa-broom"></i> Limpiar Auditoría Antigua
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/configuraciones.js"></script>
</body>
</html>