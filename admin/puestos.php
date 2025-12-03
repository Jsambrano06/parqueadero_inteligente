<?php
/**
 * GESTIÓN DE PUESTOS - ADMINISTRADOR
 * CRUD completo de puestos del parqueadero
 * Sistema de Parqueadero Inteligente
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/funciones.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/../core/middleware.php';

// Proteger ruta - solo admin
middlewareAdmin();
aplicarHeadersSeguridad();

// Obtener todos los puestos con información completa
$sql = "SELECT p.*, tp.nombre as tipo_nombre, tp.ancho as tipo_ancho, tp.alto as tipo_alto
        FROM puestos p
        INNER JOIN tipos_puesto tp ON p.tipo_id = tp.id
        ORDER BY p.codigo ASC";
$puestos = obtenerFilas($sql);

// Estadísticas por estado
$sql_stats = "SELECT 
    estado,
    COUNT(*) as total
    FROM puestos
    GROUP BY estado";
$stats_raw = obtenerFilas($sql_stats);

// Organizar estadísticas
$stats = [
    'libre' => 0,
    'ocupado' => 0,
    'inactivo' => 0,
    'total' => count($puestos)
];

foreach ($stats_raw as $stat) {
    $stats[$stat['estado']] = $stat['total'];
}

// Estadísticas por tipo
$sql_tipos = "SELECT 
    tp.id, tp.nombre,
    COUNT(p.id) as total,
    SUM(CASE WHEN p.estado = 'libre' THEN 1 ELSE 0 END) as libres,
    SUM(CASE WHEN p.estado = 'ocupado' THEN 1 ELSE 0 END) as ocupados,
    SUM(CASE WHEN p.estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
    FROM tipos_puesto tp
    LEFT JOIN puestos p ON tp.id = p.tipo_id
    GROUP BY tp.id, tp.nombre
    ORDER BY tp.id";
$stats_tipos = obtenerFilas($sql_tipos);

// Obtener tipos de puesto para modales
$sql_tipos_puesto = "SELECT * FROM tipos_puesto ORDER BY id";
$tipos_puesto = obtenerFilas($sql_tipos_puesto);

$nombre_parqueadero = obtenerConfiguracion('nombre_parqueadero');
$limite_puestos = obtenerConfiguracion('limite_puestos');

$mensajes = obtenerMensajes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Puestos - <?php echo htmlspecialchars($nombre_parqueadero); ?></title>
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
                <a href="puestos.php" class="nav-item active">
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
                <h1>Gestión de Puestos</h1>
                <div class="topbar-actions">
                    <button class="btn btn-primary" onclick="window.location.href='mapa.php'">
                        <i class="fa-solid fa-map"></i> Ver Mapa
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

                <!-- Estadísticas Generales -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Total Puestos</span>
                            <div class="stat-card-icon primary">
                                <i class="fa-solid fa-border-all"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $stats['total']; ?></div>
                        <div class="text-muted" style="font-size: 12px;">
                            Límite: <?php echo $limite_puestos; ?> puestos
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Puestos Libres</span>
                            <div class="stat-card-icon success">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $stats['libre']; ?></div>
                        <div class="text-muted" style="font-size: 12px;">
                            <?php 
                            $porcentaje = $stats['total'] > 0 
                                ? round(($stats['libre'] / $stats['total']) * 100, 1)
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
                        <div class="stat-card-value"><?php echo $stats['ocupado']; ?></div>
                        <div class="text-muted" style="font-size: 12px;">
                            <?php 
                            $porcentaje_ocupado = $stats['total'] > 0 
                                ? round(($stats['ocupado'] / $stats['total']) * 100, 1)
                                : 0;
                            echo $porcentaje_ocupado . '% ocupación';
                            ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-card-title">Puestos Inactivos</span>
                            <div class="stat-card-icon">
                                <i class="fa-solid fa-ban"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $stats['inactivo']; ?></div>
                    </div>
                </div>

                <!-- Distribución por Tipo -->
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
                                        <th>Total</th>
                                        <th>Libres</th>
                                        <th>Ocupados</th>
                                        <th>Inactivos</th>
                                        <th>% Ocupación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats_tipos as $tipo): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $icono = $tipo['nombre'] == 'moto' ? 'motorcycle' : 
                                                    ($tipo['nombre'] == 'carro' ? 'car' : 'truck');
                                            ?>
                                            <i class="fa-solid fa-<?php echo $icono; ?>"></i>
                                            <strong><?php echo ucfirst($tipo['nombre']); ?></strong>
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
                                            <span class="badge badge-secondary">
                                                <?php echo $tipo['inactivos']; ?>
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

                <!-- Listado de Puestos -->
                <div class="card">
                    <div class="card-header">
                        <h3>Listado Completo de Puestos</h3>
                    </div>
                    <div class="card-body">
                        <!-- Filtros -->
                        <div style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
                            <input 
                                type="text" 
                                id="filtrarCodigo" 
                                class="form-control" 
                                placeholder="Buscar por código..."
                                style="max-width: 300px;"
                            >
                            <select id="filtrarTipo" class="form-control" style="max-width: 200px;">
                                <option value="">Todos los tipos</option>
                                <option value="moto">Moto</option>
                                <option value="carro">Carro</option>
                                <option value="camion">Camión</option>
                            </select>
                            <select id="filtrarEstado" class="form-control" style="max-width: 200px;">
                                <option value="">Todos los estados</option>
                                <option value="libre">Libre</option>
                                <option value="ocupado">Ocupado</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                            <button class="btn btn-secondary" onclick="limpiarFiltros()">
                                <i class="fa-solid fa-rotate-left"></i> Limpiar
                            </button>
                        </div>

                        <?php if (empty($puestos)): ?>
                        <p class="text-muted text-center">No hay puestos registrados aún.</p>
                        <?php else: ?>
                        <div class="table-wrapper">
                            <table class="table" id="tablaPuestos">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Código</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Ancho</th>
                                        <th>Posición</th>
                                        <th>Fecha Creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($puestos as $puesto): ?>
                                    <tr data-codigo="<?php echo strtolower($puesto['codigo']); ?>" 
                                        data-tipo="<?php echo $puesto['tipo_nombre']; ?>" 
                                        data-estado="<?php echo $puesto['estado']; ?>">
                                        <td><?php echo $puesto['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($puesto['codigo']); ?></strong></td>
                                        <td>
                                            <?php 
                                            $icono = $puesto['tipo_nombre'] == 'moto' ? 'motorcycle' : 
                                                    ($puesto['tipo_nombre'] == 'carro' ? 'car' : 'truck');
                                            ?>
                                            <i class="fa-solid fa-<?php echo $icono; ?>"></i>
                                            <?php echo ucfirst($puesto['tipo_nombre']); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = $puesto['estado'] === 'libre' ? 'badge-success' : 
                                                          ($puesto['estado'] === 'ocupado' ? 'badge-danger' : 'badge-secondary');
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo ucfirst($puesto['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $puesto['ancho_unidades']; ?> unidades</td>
                                        <td>X: <?php echo $puesto['x']; ?>, Y: <?php echo $puesto['y']; ?></td>
                                        <td><?php echo formatearFecha($puesto['creado_en']); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 4px;">
                                                <?php if ($puesto['estado'] === 'ocupado'): ?>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="liberarPuesto(<?php echo $puesto['id']; ?>, '<?php echo htmlspecialchars($puesto['codigo']); ?>')">
                                                    <i class="fa-solid fa-unlock"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-sm btn-secondary" 
                                                        onclick="verDetalles(<?php echo $puesto['id']; ?>)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
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

    <!-- Modal Detalles del Puesto -->
    <div id="modalDetalles" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3>Detalles del Puesto</h3>
                <button type="button" class="modal-close" data-target="modalDetalles" onclick="window.parkingSystem.closeModal('modalDetalles')">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="detallesContent">
                <p class="text-muted text-center">Cargando...</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="window.parkingSystem.closeModal('modalDetalles')">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/global.js"></script>
    <script>
        // Filtros en tiempo real
        document.getElementById('filtrarCodigo').addEventListener('input', aplicarFiltros);
        document.getElementById('filtrarTipo').addEventListener('change', aplicarFiltros);
        document.getElementById('filtrarEstado').addEventListener('change', aplicarFiltros);

        function aplicarFiltros() {
            const filtroCodigo = document.getElementById('filtrarCodigo').value.toLowerCase();
            const filtroTipo = document.getElementById('filtrarTipo').value.toLowerCase();
            const filtroEstado = document.getElementById('filtrarEstado').value.toLowerCase();

            const filas = document.querySelectorAll('#tablaPuestos tbody tr');

            filas.forEach(fila => {
                const codigo = fila.dataset.codigo || '';
                const tipo = fila.dataset.tipo || '';
                const estado = fila.dataset.estado || '';

                const cumpleCodigo = codigo.includes(filtroCodigo);
                const cumpleTipo = !filtroTipo || tipo === filtroTipo;
                const cumpleEstado = !filtroEstado || estado === filtroEstado;

                if (cumpleCodigo && cumpleTipo && cumpleEstado) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        }

        function limpiarFiltros() {
            document.getElementById('filtrarCodigo').value = '';
            document.getElementById('filtrarTipo').value = '';
            document.getElementById('filtrarEstado').value = '';
            aplicarFiltros();
        }

        // Ver detalles del puesto
        async function verDetalles(puestoId) {
            const modal = document.getElementById('modalDetalles');
            const content = document.getElementById('detallesContent');
            
            window.parkingSystem.openModal('modalDetalles');
            content.innerHTML = '<p class="text-muted text-center">Cargando detalles...</p>';

            try {
                const response = await fetch(`../api/detalle_puesto.php?id=${puestoId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    const puesto = data.puesto;
                    const icono = puesto.tipo_nombre === 'moto' ? 'motorcycle' : 
                                 (puesto.tipo_nombre === 'carro' ? 'car' : 'truck');

                    let html = `
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="font-size: 48px; color: #3b82f6; margin-bottom: 8px;">
                                <i class="fa-solid fa-${icono}"></i>
                            </div>
                            <div style="font-size: 28px; font-weight: bold;">${puesto.codigo}</div>
                        </div>

                        <div style="background: #f8fafc; padding: 16px; border-radius: 8px; margin-bottom: 16px;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 8px 0; font-weight: 500;">ID:</td>
                                    <td style="padding: 8px 0; text-align: right;">${puesto.id}</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 8px 0; font-weight: 500;">Tipo:</td>
                                    <td style="padding: 8px 0; text-align: right;">${puesto.tipo_nombre}</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 8px 0; font-weight: 500;">Estado:</td>
                                    <td style="padding: 8px 0; text-align: right;">
                                        <span class="badge badge-${puesto.estado === 'libre' ? 'success' : (puesto.estado === 'ocupado' ? 'danger' : 'secondary')}">
                                            ${puesto.estado}
                                        </span>
                                    </td>
                                </tr>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 8px 0; font-weight: 500;">Ancho:</td>
                                    <td style="padding: 8px 0; text-align: right;">${puesto.ancho_unidades} unidades</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 8px 0; font-weight: 500;">Posición X:</td>
                                    <td style="padding: 8px 0; text-align: right;">${puesto.x}px</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 8px 0; font-weight: 500;">Posición Y:</td>
                                    <td style="padding: 8px 0; text-align: right;">${puesto.y}px</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: 500;">Fecha Creación:</td>
                                    <td style="padding: 8px 0; text-align: right;">${window.parkingSystem.formatDate(puesto.creado_en)}</td>
                                </tr>
                            </table>
                        </div>
                    `;

                    if (data.movimiento) {
                        const mov = data.movimiento;
                        html += `
                            <div class="alert alert-info">
                                <i class="fa-solid fa-circle-info"></i>
                                <div>
                                    <strong>Vehículo Activo:</strong><br>
                                    Placa: ${mov.placa} | Tipo: ${mov.tipo_vehiculo}<br>
                                    Entrada: ${window.parkingSystem.formatDate(mov.hora_entrada)}
                                </div>
                            </div>
                        `;
                    }

                    content.innerHTML = html;
                } else {
                    content.innerHTML = `<div class="alert alert-error">${data.error}</div>`;
                }
            } catch (error) {
                console.error('Error:', error);
                content.innerHTML = '<div class="alert alert-error">Error al cargar detalles</div>';
            }
        }

        // Liberar puesto ocupado manualmente
        async function liberarPuesto(puestoId, codigo) {
            window.parkingSystem.confirmAction(
                `¿Estás seguro de liberar manualmente el puesto ${codigo}?<br><br><strong>Advertencia:</strong> Esta acción NO registrará cobro. Solo úsala en casos excepcionales.`,
                async () => {
                    try {
                        const formData = new FormData();
                        formData.append('puesto_id', puestoId);
                        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '<?php echo generarTokenCSRF(); ?>');

                        const response = await window.parkingSystem.fetchWithCSRF('../api/liberar_puesto.php', {
                            method: 'POST',
                            body: formData
                        });

                        if (response.success) {
                            window.parkingSystem.showAlert('Puesto liberado correctamente', 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            window.parkingSystem.showAlert(response.error || 'Error al liberar puesto', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
                    }
                }
            );
        }
    </script>
    
    <!-- Token CSRF oculto para JavaScript -->
    <?php echo campoCSRF(); ?>
</body>
</html>