/**
 * LÓGICA DE REGISTRO DE SALIDA
 * Búsqueda, cálculo de cobro y salida
 * Sistema de Parqueadero Inteligente
 */

document.addEventListener('DOMContentLoaded', function() {
    const formBuscar = document.getElementById('formBuscarVehiculo');
    const inputBuscar = document.getElementById('buscarPlaca');
    
    // Convertir a mayúsculas automáticamente
    inputBuscar.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    // Búsqueda de vehículo
    formBuscar.addEventListener('submit', async function(e) {
        e.preventDefault();
        const termino = inputBuscar.value.trim();
        
        if (!termino) {
            window.parkingSystem.showAlert('Ingresa una placa o código de puesto', 'warning');
            return;
        }
        
        await buscarVehiculo(termino);
    });
    
    // Cargar vehículos activos
    cargarVehiculosActivos();
});

/**
 * Buscar vehículo por placa o puesto
 */
async function buscarVehiculo(termino) {
    const contenedor = document.getElementById('resultadoBusqueda');
    contenedor.innerHTML = '<p class="text-muted">Buscando...</p>';
    
    try {
        const response = await fetch(`../api/buscar_vehiculo.php?q=${encodeURIComponent(termino)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.vehiculo) {
            mostrarDetalleVehiculo(data.vehiculo);
        } else {
            contenedor.innerHTML = `
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>${data.error || 'Vehículo no encontrado'}</span>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
        contenedor.innerHTML = `
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Error al buscar vehículo</span>
            </div>
        `;
    }
}

/**
 * Mostrar detalle del vehículo encontrado
 */
function mostrarDetalleVehiculo(vehiculo) {
    const contenedor = document.getElementById('resultadoBusqueda');
    
    const icono = vehiculo.tipo_vehiculo === 'moto' ? 'motorcycle' : 
                 (vehiculo.tipo_vehiculo === 'carro' ? 'car' : 'truck');
    
    const html = `
        <div style="border: 2px solid #10b981; border-radius: 8px; padding: 20px; background: #f0fdf4;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="color: #10b981; margin: 0;">
                    <i class="fa-solid fa-circle-check"></i> Vehículo Encontrado
                </h3>
                <div style="font-size: 32px; color: #10b981;">
                    <i class="fa-solid fa-${icono}"></i>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px;">
                <div>
                    <strong>Puesto:</strong><br>
                    <span style="font-size: 24px; font-weight: bold; color: #1e293b;">${vehiculo.puesto_codigo}</span>
                </div>
                <div>
                    <strong>Placa:</strong><br>
                    <span style="font-size: 20px; color: #1e293b;">${vehiculo.placa}</span>
                </div>
                <div>
                    <strong>Tipo:</strong><br>
                    <span style="font-size: 18px; color: #1e293b;">${vehiculo.tipo_vehiculo}</span>
                </div>
                ${vehiculo.color ? `
                <div>
                    <strong>Color:</strong><br>
                    <span style="font-size: 18px; color: #1e293b;">${vehiculo.color}</span>
                </div>
                ` : ''}
            </div>
            
            <div style="background: white; padding: 16px; border-radius: 6px; margin-bottom: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <strong>Hora Entrada:</strong><br>
                        ${window.parkingSystem.formatDate(vehiculo.hora_entrada)}
                    </div>
                    <div>
                        <strong>Tiempo transcurrido:</strong><br>
                        <span id="tiempoTranscurrido" data-entrada="${vehiculo.hora_entrada}">Calculando...</span>
                    </div>
                </div>
            </div>
            
            <div style="background: #dbeafe; padding: 16px; border-radius: 6px; margin-bottom: 16px;">
                <div style="text-align: center;">
                    <div style="font-size: 14px; color: #1e40af; margin-bottom: 4px;">Total Estimado a Pagar:</div>
                    <div style="font-size: 32px; font-weight: bold; color: #1e40af;" id="totalEstimado">
                        Calculando...
                    </div>
                </div>
            </div>
            
            <button class="btn btn-primary btn-lg" style="width: 100%;" onclick="registrarSalida(${vehiculo.id})">
                <i class="fa-solid fa-right-from-bracket"></i> Registrar Salida y Cobrar
            </button>
        </div>
    `;
    
    contenedor.innerHTML = html;
    
    // Calcular cobro estimado
    calcularCobroEstimado(vehiculo.id, vehiculo.hora_entrada, vehiculo.tipo_vehiculo);
    
    // Actualizar tiempo cada segundo
    actualizarTiempo();
    setInterval(actualizarTiempo, 1000);
}

/**
 * Actualizar tiempo transcurrido en pantalla
 */
function actualizarTiempo() {
    const elemento = document.getElementById('tiempoTranscurrido');
    if (!elemento) return;
    
    const entrada = new Date(elemento.dataset.entrada);
    const ahora = new Date();
    const diff = ahora - entrada;
    
    const horas = Math.floor(diff / 3600000);
    const minutos = Math.floor((diff % 3600000) / 60000);
    
    elemento.textContent = `${horas}h ${minutos}m`;
}

/**
 * Calcular cobro estimado
 */
async function calcularCobroEstimado(movimientoId, horaEntrada, tipoVehiculo) {
    try {
        const response = await fetch('../api/calcular_cobro.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                movimiento_id: movimientoId,
                hora_entrada: horaEntrada,
                tipo_vehiculo: tipoVehiculo
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('totalEstimado').textContent = 
                window.parkingSystem.formatCurrency(data.total);
        }
    } catch (error) {
        console.error('Error al calcular cobro:', error);
    }
}

/**
 * Registrar salida del vehículo
 */
async function registrarSalida(movimientoId) {
    window.parkingSystem.confirmAction(
        '¿Confirmar salida del vehículo y registrar cobro?',
        async () => {
            try {
                const formData = new FormData();
                formData.append('movimiento_id', movimientoId);
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
                
                const response = await window.parkingSystem.fetchWithCSRF('../api/registrar_salida.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.success) {
                    mostrarTicketSalida(response);
                } else {
                    window.parkingSystem.showAlert(response.error || 'Error al registrar salida', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
            }
        }
    );
}

/**
 * Mostrar ticket de salida
 */
function mostrarTicketSalida(data) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.innerHTML = `
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header" style="background: #10b981; color: white;">
                <h3 style="color: white;">
                    <i class="fa-solid fa-receipt"></i> Ticket de Salida
                </h3>
            </div>
            <div class="modal-body">
                <div style="text-align: center; padding: 20px; border-bottom: 2px dashed #e2e8f0; margin-bottom: 20px;">
                    <div style="font-size: 48px; color: #10b981; margin-bottom: 8px;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div style="font-size: 18px; font-weight: 600; color: #1e293b;">
                        Salida Registrada
                    </div>
                </div>
                
                <div style="padding: 0 20px 20px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 8px 0; font-weight: 500;">Puesto:</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: 600;">${data.puesto_codigo}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 8px 0; font-weight: 500;">Placa:</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: 600;">${data.placa}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 8px 0; font-weight: 500;">Tipo:</td>
                            <td style="padding: 8px 0; text-align: right;">${data.tipo_vehiculo}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 8px 0; font-weight: 500;">Entrada:</td>
                            <td style="padding: 8px 0; text-align: right;">${data.hora_entrada}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 8px 0; font-weight: 500;">Salida:</td>
                            <td style="padding: 8px 0; text-align: right;">${data.hora_salida}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 8px 0; font-weight: 500;">Tiempo:</td>
                            <td style="padding: 8px 0; text-align: right;">${data.tiempo_total}</td>
                        </tr>
                        <tr style="background: #f8fafc;">
                            <td style="padding: 16px 8px; font-size: 18px; font-weight: 600;">TOTAL A PAGAR:</td>
                            <td style="padding: 16px 8px; text-align: right; font-size: 24px; font-weight: 700; color: #10b981;">
                                ${window.parkingSystem.formatCurrency(data.total_pagar)}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="btnCerrarTicket" style="flex: 1;">
                    <i class="fa-solid fa-check"></i> Aceptar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
    
    document.getElementById('btnCerrarTicket').addEventListener('click', () => {
        overlay.remove();
        // Limpiar búsqueda y recargar
        document.getElementById('buscarPlaca').value = '';
        document.getElementById('resultadoBusqueda').innerHTML = '';
        cargarVehiculosActivos();
    });
}

/**
 * Cargar vehículos activos
 */
async function cargarVehiculosActivos() {
    const contenedor = document.getElementById('vehiculosActivos');
    
    try {
        const response = await fetch('../api/vehiculos_activos.php', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.vehiculos && data.vehiculos.length > 0) {
            let html = '<div class="table-wrapper"><table class="table">';
            html += `
                <thead>
                    <tr>
                        <th>Puesto</th>
                        <th>Tipo</th>
                        <th>Placa</th>
                        <th>Color</th>
                        <th>Hora Entrada</th>
                        <th>Tiempo</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
            `;
            
            data.vehiculos.forEach(vehiculo => {
                const icono = vehiculo.tipo_vehiculo === 'moto' ? 'motorcycle' : 
                             (vehiculo.tipo_vehiculo === 'carro' ? 'car' : 'truck');
                
                // Calcular tiempo transcurrido
                const entrada = new Date(vehiculo.hora_entrada);
                const ahora = new Date();
                const diff = ahora - entrada;
                const horas = Math.floor(diff / 3600000);
                const minutos = Math.floor((diff % 3600000) / 60000);
                
                html += `
                    <tr>
                        <td><strong>${vehiculo.puesto_codigo}</strong></td>
                        <td><i class="fa-solid fa-${icono}"></i> ${vehiculo.tipo_vehiculo}</td>
                        <td><strong>${vehiculo.placa}</strong></td>
                        <td>${vehiculo.color || '-'}</td>
                        <td>${window.parkingSystem.formatDate(vehiculo.hora_entrada)}</td>
                        <td>${horas}h ${minutos}m</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="buscarVehiculo('${vehiculo.placa}')">
                                <i class="fa-solid fa-right-from-bracket"></i> Salida
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
            contenedor.innerHTML = html;
        } else {
            contenedor.innerHTML = '<p class="text-muted text-center">No hay vehículos en el parqueadero actualmente.</p>';
        }
        
    } catch (error) {
        console.error('Error:', error);
        contenedor.innerHTML = '<p class="text-muted text-center">Error al cargar datos.</p>';
    }
}

// Hacer función global
window.registrarSalida = registrarSalida;
window.buscarVehiculo = buscarVehiculo;