<?php
/**
 * MAPA VISUAL DEL PARQUEADERO
 * Vista interactiva con drag & drop para admin
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger ruta - solo admin
middlewareAdmin();
aplicarHeadersSeguridad();

// Obtener todos los puestos con sus tipos
$sql = "SELECT p.*, tp.nombre as tipo_nombre, tp.ancho, tp.alto
        FROM puestos p
        INNER JOIN tipos_puesto tp ON p.tipo_id = tp.id
        ORDER BY p.id ASC";
$puestos = obtenerFilas($sql);

// Obtener estadísticas rápidas
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'libre' THEN 1 ELSE 0 END) as libres,
    SUM(CASE WHEN estado = 'ocupado' THEN 1 ELSE 0 END) as ocupados,
    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
    FROM puestos";
$stats = obtenerFila($sql_stats);

// Obtener tipos de puesto para el modal de agregar
$sql_tipos = "SELECT * FROM tipos_puesto ORDER BY id";
$tipos_puesto = obtenerFilas($sql_tipos);

$nombre_parqueadero = obtenerConfiguracion('nombre_parqueadero');
$limite_puestos = obtenerConfiguracion('limite_puestos');

$mensajes = obtenerMensajes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa del Parqueadero - <?php echo htmlspecialchars($nombre_parqueadero); ?></title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link rel="stylesheet" href="../assets/css/mapa.css">
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
                <a href="mapa.php" class="nav-item active">
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
                <h1>Mapa del Parqueadero</h1>
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

                <!-- Card del Mapa -->
                <div class="card">
                    <!-- Estadísticas Rápidas -->
                    <div class="mapa-stats">
                        <div class="mapa-stat libres">
                            <i class="fa-solid fa-circle-check"></i>
                            <span><strong><?php echo $stats['libres']; ?></strong> Libres</span>
                        </div>
                        <div class="mapa-stat ocupados">
                            <i class="fa-solid fa-car"></i>
                            <span><strong><?php echo $stats['ocupados']; ?></strong> Ocupados</span>
                        </div>
                        <div class="mapa-stat total">
                            <i class="fa-solid fa-border-all"></i>
                            <span><strong><?php echo $stats['total']; ?></strong> / <?php echo $limite_puestos; ?> Total</span>
                        </div>
                    </div>

                    <!-- Controles del Mapa -->
                    <div class="mapa-controls">
                        <div class="mapa-controls-left">
                            <button id="btnModoEdicion" class="btn btn-primary">
                                <i class="fa-solid fa-pen-to-square"></i> Activar Modo Edición
                            </button>
                            <div id="modoEdicionBadge" class="modo-edicion-badge" style="display: none;">
                                <i class="fa-solid fa-edit"></i> Modo Edición Activo
                            </div>
                        </div>
                        <div class="mapa-controls-right">
                            <button id="btnAgregarPuesto" class="btn btn-success" style="display: none;">
                                <i class="fa-solid fa-plus"></i> Agregar Puesto
                            </button>
                            <button id="btnGuardarCambios" class="btn btn-success" style="display: none;">
                                <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                            </button>
                            <!-- Controles de tamaño del mapa -->
                            <div class="mapa-size-controls" style="display: inline-flex; gap:8px; align-items:center; margin-left:8px;">
                                <div style="display:flex; gap:6px; align-items:center;">
                                    <small style="color:#475569;">Ancho</small>
                                    <button id="btnReducirWidth" class="btn btn-secondary" title="Reducir ancho">
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <button id="btnAumentarWidth" class="btn btn-secondary" title="Aumentar ancho">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                                <div style="display:flex; gap:6px; align-items:center;">
                                    <small style="color:#475569;">Alto</small>
                                    <button id="btnReducirHeight" class="btn btn-secondary" title="Reducir alto">
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <button id="btnAumentarHeight" class="btn btn-secondary" title="Aumentar alto">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                                <div id="mapSizeLabel" style="font-size:13px; color:#334155;">-- x --</div>
                                <button id="btnGuardarTamano" class="btn btn-primary" title="Guardar tamaño del mapa" style="display:none;">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Canvas del Mapa -->
                    <div class="mapa-container">
                        <div class="mapa-canvas" id="mapaCanvas">
                            <!-- Los puestos se cargarán aquí dinámicamente -->
                        </div>
                    </div>

                    <!-- Leyenda -->
                    <div class="mapa-leyenda">
                        <div class="leyenda-item">
                            <div class="leyenda-color libre"></div>
                            <span>Libre</span>
                        </div>
                        <div class="leyenda-item">
                            <div class="leyenda-color ocupado"></div>
                            <span>Ocupado</span>
                        </div>
                        <div class="leyenda-item">
                            <div class="leyenda-color inactivo"></div>
                            <span>Inactivo</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Agregar Puesto -->
    <div id="modalAgregarPuesto" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3>Agregar Nuevo Puesto</h3>
                <button type="button" class="modal-close" data-target="modalAgregarPuesto" onclick="window.parkingSystem.closeModal('modalAgregarPuesto')">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="formAgregarPuesto">
                    <?php echo campoCSRF(); ?>
                    
                    <div class="form-group">
                        <label class="form-label">Tipo de Puesto</label>
                        <div class="tipo-selector">
                            <?php foreach ($tipos_puesto as $tipo): ?>
                            <div class="tipo-option" data-tipo-id="<?php echo $tipo['id']; ?>" data-tipo-nombre="<?php echo $tipo['nombre']; ?>">
                                <?php 
                                $icono = $tipo['nombre'] == 'moto' ? 'motorcycle' : 
                                        ($tipo['nombre'] == 'carro' ? 'car' : 'truck');
                                ?>
                                <i class="fa-solid fa-<?php echo $icono; ?>"></i>
                                <div><?php echo ucfirst($tipo['nombre']); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="tipo_id" id="tipoIdInput" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="codigoPuesto">Código del Puesto</label>
                        <input type="text" id="codigoPuesto" name="codigo" class="form-control" 
                               placeholder="Ej: M1, C1, CA1" required>
                        <small class="text-muted">El código debe ser único</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="window.parkingSystem.closeModal('modalAgregarPuesto')">
                    Cancelar
                </button>
                <button class="btn btn-primary" id="btnConfirmarAgregar">
                    <i class="fa-solid fa-plus"></i> Agregar Puesto
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Acciones de Puesto -->
    <div id="modalAccionesPuesto" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3>Acciones del Puesto <span id="puestoCodigoModal"></span></h3>
                <button type="button" class="modal-close" data-target="modalAccionesPuesto" onclick="window.parkingSystem.closeModal('modalAccionesPuesto')">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="puestoInfoModal" style="margin-bottom: 20px; padding: 12px; background: #f8fafc; border-radius: 6px;">
                    <!-- Info del puesto -->
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button id="btnCambiarEstado" class="btn btn-secondary btn-block">
                        <i class="fa-solid fa-toggle-on"></i> Cambiar Estado
                    </button>
                    <button id="btnRotarPuesto" class="btn btn-info btn-block">
                        <i class="fa-solid fa-rotate-right"></i> Rotar 90°
                    </button>
                    <button id="btnConvertirPuesto" class="btn btn-primary btn-block">
                        <i class="fa-solid fa-arrows-rotate"></i> Convertir Tipo
                    </button>
                    <button id="btnEliminarPuesto" class="btn btn-danger btn-block">
                        <i class="fa-solid fa-trash"></i> Eliminar Puesto
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="window.parkingSystem.closeModal('modalAccionesPuesto')">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
        // Pasar datos PHP a JavaScript
        window.puestosData = <?php echo json_encode($puestos); ?>;
        window.tiposPuesto = <?php echo json_encode($tipos_puesto); ?>;
        // Tamaño del mapa (se puede modificar desde la interfaz)
        window.mapSettings = {
            width: <?php echo (int)(obtenerConfiguracion('map_width') ?: 1200); ?>,
            height: <?php echo (int)(obtenerConfiguracion('map_height') ?: 600); ?>
        };
    </script>
    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/mapa.js"></script>
    <script src="../assets/js/dragdrop.js"></script>
</body>
</html>