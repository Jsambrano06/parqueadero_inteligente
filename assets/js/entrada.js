/**
 * LÓGICA DE REGISTRO DE ENTRADA
 * Validaciones y envío de formulario
 * Sistema de Parqueadero Inteligente
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formEntrada');
    const placaInput = document.getElementById('placa');
    
    // Convertir placa a mayúsculas automáticamente
    placaInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    // Envío del formulario
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validar formulario
        if (!window.parkingSystem.validateForm(form)) {
            window.parkingSystem.showAlert('Por favor completa todos los campos requeridos', 'error');
            return;
        }
        
        const placa = placaInput.value.trim();
        const tipoVehiculo = document.getElementById('tipo_vehiculo').value;
        const color = document.getElementById('color').value.trim();
        
        // Validar placa con regex obligatorio
        if (!window.parkingSystem.validatePlaca(placa)) {
            window.parkingSystem.showAlert('La placa debe ser alfanumérica y tener entre 4 y 8 caracteres', 'error');
            placaInput.focus();
            return;
        }
        
        // Confirmar registro
        const mensaje = `¿Confirmar entrada de ${tipoVehiculo.toUpperCase()} con placa ${placa}?`;
        
        window.parkingSystem.confirmAction(mensaje, async () => {
            await registrarEntrada(tipoVehiculo, placa, color);
        });
    });
    
    // Cargar últimas entradas
    cargarUltimasEntradas();
});

/**
 * Registrar entrada de vehículo
 */
async function registrarEntrada(tipoVehiculo, placa, color) {
    const btn = document.getElementById('btnRegistrar');
    window.parkingSystem.setButtonLoading(btn, 'Registrando...');
    
    try {
        const formData = new FormData();
        formData.append('tipo_vehiculo', tipoVehiculo);
        formData.append('placa', placa);
        formData.append('color', color);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        
        const response = await window.parkingSystem.fetchWithCSRF('../api/registrar_entrada.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.success) {
            // Mostrar información del puesto asignado
            const mensaje = `
                ✅ Entrada registrada correctamente<br>
                <strong>Puesto asignado:</strong> ${response.puesto_codigo}<br>
                <strong>Placa:</strong> ${placa}<br>
                <strong>Hora entrada:</strong> ${response.hora_entrada}
            `;
            
            const overlay = document.createElement('div');
            overlay.className = 'modal-overlay active';
            overlay.innerHTML = `
                <div class="modal">
                    <div class="modal-header">
                        <h3 style="color: #10b981;">
                            <i class="fa-solid fa-circle-check"></i> Entrada Registrada
                        </h3>
                    </div>
                    <div class="modal-body">
                        <div style="text-align: center; padding: 20px;">
                            <div style="font-size: 48px; color: #10b981; margin-bottom: 16px;">
                                <i class="fa-solid fa-square-parking"></i>
                            </div>
                            <div style="font-size: 32px; font-weight: bold; color: #1e293b; margin-bottom: 20px;">
                                ${response.puesto_codigo}
                            </div>
                            <div style="background: #f8fafc; padding: 16px; border-radius: 8px; text-align: left;">
                                <p><strong>Placa:</strong> ${placa}</p>
                                <p><strong>Tipo:</strong> ${tipoVehiculo}</p>
                                <p><strong>Hora entrada:</strong> ${response.hora_entrada}</p>
                                ${color ? `<p><strong>Color:</strong> ${color}</p>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" id="btnCerrarModal">Aceptar</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(overlay);
            
            document.getElementById('btnCerrarModal').addEventListener('click', () => {
                overlay.remove();
                // Limpiar formulario
                document.getElementById('formEntrada').reset();
                // Recargar últimas entradas
                cargarUltimasEntradas();
                // Recargar stats
                setTimeout(() => location.reload(), 1000);
            });
            
        } else {
            window.parkingSystem.showAlert(response.error || 'Error al registrar entrada', 'error');
        }
        
    } catch (error) {
        console.error('Error:', error);
        window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
    } finally {
        window.parkingSystem.resetButton(btn);
    }
}

/**
 * Cargar últimas entradas registradas
 */
async function cargarUltimasEntradas() {
    const contenedor = document.getElementById('ultimasEntradas');
    
    try {
        const response = await fetch('../api/ultimas_entradas.php', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.entradas && data.entradas.length > 0) {
            let html = '<div class="table-wrapper"><table class="table">';
            html += `
                <thead>
                    <tr>
                        <th>Puesto</th>
                        <th>Tipo</th>
                        <th>Placa</th>
                        <th>Color</th>
                        <th>Hora Entrada</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
            `;
            
            data.entradas.forEach(entrada => {
                const icono = entrada.tipo_vehiculo === 'moto' ? 'motorcycle' : 
                             (entrada.tipo_vehiculo === 'carro' ? 'car' : 'truck');
                
                html += `
                    <tr>
                        <td><strong>${entrada.puesto_codigo}</strong></td>
                        <td><i class="fa-solid fa-${icono}"></i> ${entrada.tipo_vehiculo}</td>
                        <td>${entrada.placa}</td>
                        <td>${entrada.color || '-'}</td>
                        <td>${window.parkingSystem.formatDate(entrada.hora_entrada)}</td>
                        <td><span class="badge badge-success">Activo</span></td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
            contenedor.innerHTML = html;
        } else {
            contenedor.innerHTML = '<p class="text-muted text-center">No hay entradas registradas todavía.</p>';
        }
        
    } catch (error) {
        console.error('Error al cargar entradas:', error);
        contenedor.innerHTML = '<p class="text-muted text-center">Error al cargar datos.</p>';
    }
}