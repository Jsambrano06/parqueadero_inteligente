/**
 * LÓGICA DE CONFIGURACIONES
 * Actualización de configuraciones del sistema
 * Sistema de Parqueadero Inteligente
 */

document.addEventListener('DOMContentLoaded', function() {
    // Formulario de configuración general
    document.getElementById('formConfigGeneral').addEventListener('submit', async function(e) {
        e.preventDefault();
        await guardarConfiguracion('general');
    });

    // Formulario de configuración de cobro
    document.getElementById('formConfigCobro').addEventListener('submit', async function(e) {
        e.preventDefault();
        await guardarConfiguracion('cobro');
    });

    // Formulario de configuración de capacidad
    document.getElementById('formConfigCapacidad').addEventListener('submit', async function(e) {
        e.preventDefault();
        await guardarConfiguracion('capacidad');
    });
});

/**
 * Guardar configuraciones
 */
async function guardarConfiguracion(tipo) {
    let formId, btnId;
    
    switch(tipo) {
        case 'general':
            formId = 'formConfigGeneral';
            btnId = 'btnGuardarGeneral';
            break;
        case 'cobro':
            formId = 'formConfigCobro';
            btnId = 'btnGuardarCobro';
            break;
        case 'capacidad':
            formId = 'formConfigCapacidad';
            btnId = 'btnGuardarCapacidad';
            break;
        default:
            return;
    }
    
    const form = document.getElementById(formId);
    const btn = document.getElementById(btnId);
    
    if (!window.parkingSystem.validateForm(form)) {
        window.parkingSystem.showAlert('Por favor completa todos los campos', 'error');
        return;
    }
    
    window.parkingSystem.setButtonLoading(btn, 'Guardando...');
    
    try {
        const formData = new FormData(form);
        formData.append('tipo', tipo);
        
        const response = await window.parkingSystem.fetchWithCSRF('../api/actualizar_configuraciones.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.success) {
            window.parkingSystem.showAlert(response.message || 'Configuración actualizada correctamente', 'success');
            
            // Si se cambió el nombre del parqueadero, recargar la página
            if (tipo === 'general') {
                setTimeout(() => location.reload(), 1500);
            }
        } else {
            window.parkingSystem.showAlert(response.error || 'Error al actualizar configuración', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
    } finally {
        window.parkingSystem.resetButton(btn);
    }
}

/**
 * Ver auditoría completa
 */
async function verAuditoria() {
    try {
        const response = await fetch('../api/obtener_auditoria.php', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.registros) {
            mostrarModalAuditoria(data.registros);
        } else {
            window.parkingSystem.showAlert('No se pudo cargar la auditoría', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        window.parkingSystem.showAlert('Error al cargar auditoría', 'error');
    }
}

/**
 * Mostrar modal con auditoría
 */
function mostrarModalAuditoria(registros) {
    let html = '<div class="table-wrapper"><table class="table">';
    html += `
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Detalles</th>
            </tr>
        </thead>
        <tbody>
    `;
    
    registros.forEach(reg => {
        html += `
            <tr>
                <td>${window.parkingSystem.formatDate(reg.fecha)}</td>
                <td>${reg.usuario_nombre || 'Sistema'}</td>
                <td><strong>${reg.accion}</strong></td>
                <td>${reg.detalles || '-'}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.innerHTML = `
        <div class="modal" style="max-width: 900px;">
            <div class="modal-header">
                <h3>Auditoría del Sistema</h3>
                <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="max-height: 600px; overflow-y: auto;">
                ${html}
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="this.closest('.modal-overlay').remove()">
                    Cerrar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
}

/**
 * Ver log del mapa
 */
async function verLogMapa() {
    try {
        const response = await fetch('../api/obtener_log_mapa.php', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.logs) {
            mostrarModalLogMapa(data.logs);
        } else {
            window.parkingSystem.showAlert('No se pudo cargar el log del mapa', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        window.parkingSystem.showAlert('Error al cargar log del mapa', 'error');
    }
}

/**
 * Mostrar modal con log del mapa
 */
function mostrarModalLogMapa(logs) {
    let html = '<div class="table-wrapper"><table class="table">';
    html += `
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
    `;
    
    logs.forEach(log => {
        html += `
            <tr>
                <td>${window.parkingSystem.formatDate(log.creado_en)}</td>
                <td>${log.usuario_nombre || 'Sistema'}</td>
                <td>${log.descripcion}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.innerHTML = `
        <div class="modal" style="max-width: 800px;">
            <div class="modal-header">
                <h3>Log de Cambios del Mapa</h3>
                <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="max-height: 600px; overflow-y: auto;">
                ${html}
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="this.closest('.modal-overlay').remove()">
                    Cerrar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
}

/**
 * Limpiar auditoría antigua
 */
function limpiarAuditoriaAntigua() {
    window.parkingSystem.confirmAction(
        '¿Estás seguro de eliminar registros de auditoría antiguos?<br><br>' +
        '<small>Se eliminarán registros con más de 6 meses de antigüedad. Esta acción es irreversible.</small>',
        async () => {
            try {
                const formData = new FormData();
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
                
                const response = await window.parkingSystem.fetchWithCSRF('../api/limpiar_auditoria.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.success) {
                    window.parkingSystem.showAlert(
                        `Auditoría limpiada. ${response.registros_eliminados} registro(s) eliminado(s).`, 
                        'success'
                    );
                } else {
                    window.parkingSystem.showAlert(response.error || 'Error al limpiar auditoría', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
            }
        }
    );
}

// Exportar funciones globales
window.verAuditoria = verAuditoria;
window.verLogMapa = verLogMapa;
window.limpiarAuditoriaAntigua = limpiarAuditoriaAntigua;