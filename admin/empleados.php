<?php
/**
 * GESTIÓN DE EMPLEADOS - ADMINISTRADOR
 * CRUD completo de usuarios empleados
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger ruta - solo admin
middlewareAdmin();
aplicarHeadersSeguridad();

// Obtener todos los empleados
$sql = "SELECT u.id, u.nombre, u.usuario, u.activo, u.fecha_creacion, r.nombre as rol
        FROM usuarios u
        INNER JOIN roles r ON u.rol_id = r.id
        WHERE u.rol_id = 2
        ORDER BY u.id DESC";
$empleados = obtenerFilas($sql);

// Estadísticas
$total_empleados = count($empleados);
$empleados_activos = count(array_filter($empleados, function($e) { return $e['activo'] == 1; }));
$empleados_inactivos = $total_empleados - $empleados_activos;

$nombre_parqueadero = obtenerConfiguracion('nombre_parqueadero');
$mensajes = obtenerMensajes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados - <?php echo htmlspecialchars($nombre_parqueadero); ?></title>
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
                <a href="empleados.php" class="nav-item active">
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
                <h1>Gestión de Empleados</h1>
                <div class="topbar-actions">
                    <button class="btn btn-primary" onclick="abrirModalCrear()">
                        <i class="fa-solid fa-user-plus"></i> Nuevo Empleado
                    </button>
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

                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Total Empleados</span>
                            <div class="stat-card-icon primary">
                                <i class="fa-solid fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $total_empleados; ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Empleados Activos</span>
                            <div class="stat-card-icon success">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $empleados_activos; ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Empleados Inactivos</span>
                            <div class="stat-card-icon danger">
                                <i class="fa-solid fa-user-slash"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $empleados_inactivos; ?></div>
                    </div>
                </div>

                <!-- Listado de Empleados -->
                <div class="card">
                    <div class="card-header">
                        <h3>Listado de Empleados</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($empleados)): ?>
                        <p class="text-muted text-center">No hay empleados registrados aún.</p>
                        <?php else: ?>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Usuario</th>
                                        <th>Fecha Creación</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($empleados as $empleado): ?>
                                    <tr>
                                        <td><?php echo $empleado['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($empleado['nombre']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($empleado['usuario']); ?></td>
                                        <td><?php echo formatearFecha($empleado['fecha_creacion']); ?></td>
                                        <td>
                                            <?php if ($empleado['activo']): ?>
                                                <span class="badge badge-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 4px;">
                                                <button class="btn btn-sm btn-secondary" 
                                                        onclick="abrirModalEditar(<?php echo htmlspecialchars(json_encode($empleado)); ?>)"
                                                        title="Editar">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="resetearClave(<?php echo $empleado['id']; ?>, '<?php echo htmlspecialchars($empleado['nombre']); ?>')"
                                                        title="Resetear contraseña">
                                                    <i class="fa-solid fa-key"></i>
                                                </button>
                                                
                                                <?php if ($empleado['activo']): ?>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="cambiarEstado(<?php echo $empleado['id']; ?>, 0, '<?php echo htmlspecialchars($empleado['nombre']); ?>')"
                                                        title="Desactivar">
                                                    <i class="fa-solid fa-ban"></i>
                                                </button>
                                                <?php else: ?>
                                                <button class="btn btn-sm btn-success" 
                                                        onclick="cambiarEstado(<?php echo $empleado['id']; ?>, 1, '<?php echo htmlspecialchars($empleado['nombre']); ?>')"
                                                        title="Activar">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
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

    <!-- Modal Crear Empleado -->
    <div id="modalCrearEmpleado" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3>Crear Nuevo Empleado</h3>
                <button type="button" class="modal-close" data-target="modalCrearEmpleado" onclick="window.parkingSystem.closeModal('modalCrearEmpleado')">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="formCrearEmpleado">
                    <?php echo campoCSRF(); ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="crear_nombre">Nombre Completo</label>
                        <input type="text" id="crear_nombre" name="nombre" class="form-control" 
                               placeholder="Ej: Juan Pérez" required maxlength="100">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="crear_usuario">Usuario</label>
                        <input type="text" id="crear_usuario" name="usuario" class="form-control" 
                               placeholder="Ej: jperez" required maxlength="50">
                        <small class="text-muted">El usuario debe ser único en el sistema</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="crear_clave">Contraseña</label>
                        <input type="password" id="crear_clave" name="clave" class="form-control" 
                               placeholder="Mínimo 6 caracteres" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="crear_clave_confirmacion">Confirmar Contraseña</label>
                        <input type="password" id="crear_clave_confirmacion" name="clave_confirmacion" 
                               class="form-control" placeholder="Repite la contraseña" required minlength="6">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="window.parkingSystem.closeModal('modalCrearEmpleado')">
                    Cancelar
                </button>
                <button class="btn btn-primary" id="btnCrearEmpleado">
                    <i class="fa-solid fa-save"></i> Crear Empleado
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Editar Empleado -->
    <div id="modalEditarEmpleado" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3>Editar Empleado</h3>
                <button type="button" class="modal-close" data-target="modalEditarEmpleado" onclick="window.parkingSystem.closeModal('modalEditarEmpleado')">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="formEditarEmpleado">
                    <?php echo campoCSRF(); ?>
                    <input type="hidden" id="editar_id" name="empleado_id">
                    
                    <div class="form-group">
                        <label class="form-label" for="editar_nombre">Nombre Completo</label>
                        <input type="text" id="editar_nombre" name="nombre" class="form-control" 
                               required maxlength="100">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="editar_usuario">Usuario</label>
                        <input type="text" id="editar_usuario" name="usuario" class="form-control" 
                               required maxlength="50">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="window.parkingSystem.closeModal('modalEditarEmpleado')">
                    Cancelar
                </button>
                <button class="btn btn-primary" id="btnActualizarEmpleado">
                    <i class="fa-solid fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/empleados.js"></script>
</body>
</html>