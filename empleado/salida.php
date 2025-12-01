<?php
/**
 * MÓDULO EMPLEADO - REGISTRO DE SALIDAS
 * Registro de salida con cálculo automático de cobro
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger ruta - solo empleado
middlewareEmpleado();
aplicarHeadersSeguridad();

// Obtener tarifas actuales
$sql_tarifas = "SELECT tipo_vehiculo, precio_hora FROM tarifas ORDER BY id";
$tarifas = obtenerFilas($sql_tarifas);

// Obtener configuraciones
$redondeo = obtenerConfiguracion('redondeo_minutos');
$tarifa_minima = obtenerConfiguracion('tarifa_minima_horas');

$nombre_parqueadero = obtenerConfiguracion('nombre_parqueadero');
$mensajes = obtenerMensajes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Salida - <?php echo htmlspecialchars($nombre_parqueadero); ?></title>
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
                    <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars(obtenerNombreUsuario()); ?>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="entrada.php" class="nav-item">
                    <i class="fa-solid fa-right-to-bracket"></i> Registrar Entrada
                </a>
                <a href="salida.php" class="nav-item active">
                    <i class="fa-solid fa-right-from-bracket"></i> Registrar Salida
                </a>
                <a href="historial.php" class="nav-item">
                    <i class="fa-solid fa-clock-rotate-left"></i> Historial
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
                <h1>Registrar Salida de Vehículo</h1>
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

                <!-- Tarifas Actuales -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-dollar-sign"></i> Tarifas Actuales</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <?php foreach ($tarifas as $tarifa): ?>
                            <div style="padding: 16px; border: 2px solid #e2e8f0; border-radius: 8px; text-align: center;">
                                <?php 
                                $icono = $tarifa['tipo_vehiculo'] == 'moto' ? 'motorcycle' : 
                                        ($tarifa['tipo_vehiculo'] == 'carro' ? 'car' : 'truck');
                                ?>
                                <i class="fa-solid fa-<?php echo $icono; ?>" style="font-size: 32px; color: #3b82f6; margin-bottom: 8px;"></i>
                                <div style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">
                                    <?php echo ucfirst($tarifa['tipo_vehiculo']); ?>
                                </div>
                                <div style="font-size: 20px; color: #10b981; font-weight: 700;">
                                    <?php echo formatearMoneda($tarifa['precio_hora']); ?>/hora
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="alert alert-info" style="margin-top: 16px;">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>
                                <strong>Nota:</strong> 
                                Redondeo cada <?php echo $redondeo; ?> minutos. 
                                Tarifa mínima: <?php echo $tarifa_minima; ?> hora(s).
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Búsqueda de Vehículo -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-magnifying-glass"></i> Buscar Vehículo</h3>
                    </div>
                    <div class="card-body">
                        <form id="formBuscarVehiculo">
                            <div class="form-group">
                                <label class="form-label" for="buscarPlaca">
                                    Buscar por Placa o Puesto
                                </label>
                                <div style="display: flex; gap: 12px;">
                                    <input 
                                        type="text" 
                                        id="buscarPlaca" 
                                        name="buscar" 
                                        class="form-control" 
                                        placeholder="Ingresa la placa (Ej: ABC123) o puesto (Ej: M1, C5)"
                                        style="flex: 1; text-transform: uppercase;"
                                    >
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-magnifying-glass"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Resultado de búsqueda -->
                        <div id="resultadoBusqueda" style="margin-top: 20px;"></div>
                    </div>
                </div>

                <!-- Vehículos Activos -->
                <div class="card">
                    <div class="card-header">
                        <h3>Vehículos Actualmente en el Parqueadero</h3>
                    </div>
                    <div class="card-body">
                        <div id="vehiculosActivos">
                            <p class="text-muted text-center">Cargando...</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/salida.js"></script>
</body>
</html>