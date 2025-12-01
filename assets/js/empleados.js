/**
 * LÓGICA DE GESTIÓN DE EMPLEADOS
 * CRUD de empleados
 * Sistema de Parqueadero Inteligente
 */

document.addEventListener('DOMContentLoaded', function() {
    // Eventos para crear empleado
    document.getElementById('btnCrearEmpleado').addEventListener('click', crearEmpleado);
    
    // Eventos para editar empleado
    document.getElementById('btnActualizarEmpleado').addEventListener('click', actualizarEmpleado);
});

/**
 * Abrir modal para crear empleado
 */
function abrirModalCrear() {
    document.getElementById('formCrearEmpleado').reset();
    window.parkingSystem.openModal('modalCrearEmpleado');
}

/**
 * Abrir modal para editar empleado
 */
function abrirModalEditar(empleado) {
    document.getElementById('editar_id').value = empleado.id;
    document.getElementById('editar_nombre').value = empleado.nombre;
    document.getElementById('editar_usuario').value = empleado.usuario;
    
    window.parkingSystem.openModal('modalEditarEmpleado');
}

/**
 * Crear nuevo empleado
 */
async function crearEmpleado() {
    const form = document.getElementById('formCrearEmpleado');
    
    if (!window.parkingSystem.validateForm(form)) {
        window.parkingSystem.showAlert('Por favor completa todos los campos', 'error');
        return;
    }
    
    const nombre = document.getElementById('crear_nombre').value.trim();
    const usuario = document.getElementById('crear_usuario').value.trim();
    const clave = document.getElementById('crear_clave').value;
    const claveConfirmacion = document.getElementById('crear_clave_confirmacion').value;
    
    // Validar contraseñas
    if (clave !== claveConfirmacion) {
        window.parkingSystem.showAlert('Las contraseñas no coinciden', 'error');
        return;
    }
    
    if (clave.length < 6) {
        window.parkingSystem.showAlert('La contraseña debe tener al menos 6 caracteres', 'error');
        return;
    }
    
    const btn = document.getElementById('btnCrearEmpleado');
    window.parkingSystem.setButtonLoading(btn, 'Creando...');
    
    try {
        const formData = new FormData();
        formData.append('nombre', nombre);
        formData.append('usuario', usuario);
        formData.append('clave', clave);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        
        const response = await window.parkingSystem.fetchWithCSRF('../api/crear_empleado.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.success) {
            window.parkingSystem.showAlert(response.message || 'Empleado creado correctamente', 'success');
            window.parkingSystem.closeModal('modalCrearEmpleado');
            setTimeout(() => location.reload(), 1000);
        } else {
            window.parkingSystem.showAlert(response.error || 'Error al crear empleado', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
    } finally {
        window.parkingSystem.resetButton(btn);
    }
}

/**
 * Actualizar empleado existente
 */
async function actualizarEmpleado() {
    const form = document.getElementById('formEditarEmpleado');
    
    if (!window.parkingSystem.validateForm(form)) {
        window.parkingSystem.showAlert('Por favor completa todos los campos', 'error');
        return;
    }
    
    const empleadoId = document.getElementById('editar_id').value;
    const nombre = document.getElementById('editar_nombre').value.trim();
    const usuario = document.getElementById('editar_usuario').value.trim();
    
    const btn = document.getElementById('btnActualizarEmpleado');
    window.parkingSystem.setButtonLoading(btn, 'Actualizando...');
    
    try {
        const formData = new FormData();
        formData.append('empleado_id', empleadoId);
        formData.append('nombre', nombre);
        formData.append('usuario', usuario);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        
        const response = await window.parkingSystem.fetchWithCSRF('../api/editar_empleado.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.success) {
            window.parkingSystem.showAlert(response.message || 'Empleado actualizado correctamente', 'success');
            window.parkingSystem.closeModal('modalEditarEmpleado');
            setTimeout(() => location.reload(), 1000);
        } else {
            window.parkingSystem.showAlert(response.error || 'Error al actualizar empleado', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
    } finally {
        window.parkingSystem.resetButton(btn);
    }
}

/**
 * Resetear contraseña de empleado
 */
async function resetearClave(empleadoId, nombreEmpleado) {
    // Generar contraseña aleatoria de 8 caracteres
    const nuevaClave = Math.random().toString(36).slice(-8);
    
    const mensaje = `
        ¿Resetear contraseña de <strong>${nombreEmpleado}</strong>?<br><br>
        La nueva contraseña será: <strong style="font-family: monospace; font-size: 18px;">${nuevaClave}</strong><br><br>
        <small style="color: #ef4444;">⚠️ Asegúrate de copiar esta contraseña, no se volverá a mostrar.</small>
    `;
    
    window.parkingSystem.confirmAction(mensaje, async () => {
        try {
            const formData = new FormData();
            formData.append('empleado_id', empleadoId);
            formData.append('nueva_clave', nuevaClave);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            const response = await window.parkingSystem.fetchWithCSRF('../api/resetear_clave.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.success) {
                // Mostrar modal con la contraseña
                const overlay = document.createElement('div');
                overlay.className = 'modal-overlay active';
                overlay.innerHTML = `
                    <div class="modal">
                        <div class="modal-header" style="background: #10b981; color: white;">
                            <h3 style="color: white;">Contraseña Reseteada</h3>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-success">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>La contraseña se ha reseteado correctamente</span>
                            </div>
                            <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                                <p style="margin-bottom: 8px;"><strong>Nueva contraseña para ${nombreEmpleado}:</strong></p>
                                <div style="font-family: monospace; font-size: 24px; font-weight: bold; color: #1e293b; padding: 16px; background: white; border: 2px solid #e2e8f0; border-radius: 6px; margin: 12px 0;">
                                    ${nuevaClave}
                                </div>
                                <button class="btn btn-secondary btn-sm" onclick="copiarAlPortapapeles('${nuevaClave}')">
                                    <i class="fa-solid fa-copy"></i> Copiar al Portapapeles
                                </button>
                            </div>
                            <div class="alert alert-warning" style="margin-top: 16px;">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Asegúrate de entregar esta contraseña al empleado. No se volverá a mostrar.</span>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-primary" onclick="this.closest('.modal-overlay').remove()">
                                Entendido
                            </button>
                        </div>
                    </div>
                `;
                document.body.appendChild(overlay);
            } else {
                window.parkingSystem.showAlert(response.error || 'Error al resetear contraseña', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
        }
    });
}

/**
 * Cambiar estado de empleado (activar/desactivar)
 */
async function cambiarEstado(empleadoId, nuevoEstado, nombreEmpleado) {
    const accion = nuevoEstado ? 'activar' : 'desactivar';
    const mensaje = `¿Estás seguro de ${accion} a <strong>${nombreEmpleado}</strong>?`;
    
    window.parkingSystem.confirmAction(mensaje, async () => {
        try {
            const formData = new FormData();
            formData.append('empleado_id', empleadoId);
            formData.append('activo', nuevoEstado);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            const response = await window.parkingSystem.fetchWithCSRF('../api/cambiar_estado_empleado.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.success) {
                window.parkingSystem.showAlert(response.message || `Empleado ${accion === 'activar' ? 'activado' : 'desactivado'} correctamente`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                window.parkingSystem.showAlert(response.error || 'Error al cambiar estado', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
        }
    });
}

/**
 * Copiar texto al portapapeles
 */
function copiarAlPortapapeles(texto) {
    navigator.clipboard.writeText(texto).then(() => {
        window.parkingSystem.showAlert('Contraseña copiada al portapapeles', 'success');
    }).catch(err => {
        console.error('Error al copiar:', err);
        window.parkingSystem.showAlert('No se pudo copiar la contraseña', 'error');
    });
}

// Exportar funciones globales
window.abrirModalCrear = abrirModalCrear;
window.abrirModalEditar = abrirModalEditar;
window.resetearClave = resetearClave;
window.cambiarEstado = cambiarEstado;
window.copiarAlPortapapeles = copiarAlPortapapeles;