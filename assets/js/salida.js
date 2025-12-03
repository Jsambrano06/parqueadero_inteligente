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
                        <span id="tiempoTranscurrido" data-entrada="${vehiculo.hora_entrada_iso || vehiculo.hora_entrada}">Calculando...</span>
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
    
    // Calcular cobro estimado (usar ISO si está disponible)
    calcularCobroEstimado(vehiculo.id, vehiculo.hora_entrada_iso || vehiculo.hora_entrada, vehiculo.tipo_vehiculo);
    
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

    const entradaRaw = elemento.dataset.entrada;
    if (!entradaRaw) return;

    let entrada;
    // Si ya contiene 'T' u offset, parsear directamente
    if (entradaRaw.indexOf('T') !== -1 || entradaRaw.indexOf('+') !== -1 || entradaRaw.indexOf('Z') !== -1) {
        entrada = new Date(entradaRaw);
    } else {
        // Fallback: reemplazar espacio por T para parseo local
        entrada = new Date(entradaRaw.replace(' ', 'T'));
    }

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
 * Mostrar modal para seleccionar método de pago
 */
function mostrarModalPago(data) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.innerHTML = `
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); color: white;">
                <h3 style="color: white;">
                    <i class="fa-solid fa-credit-card"></i> Seleccionar Método de Pago
                </h3>
                <button class="modal-close" style="color: white;" onclick="this.closest('.modal-overlay').remove()">×</button>
            </div>
            <div class="modal-body">
                <div style="background: linear-gradient(180deg, rgba(124, 58, 237, 0.05), transparent); padding: 16px; border-radius: 12px; margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 12px;">
                        <div>
                            <strong style="color: #64748b;">Placa:</strong>
                            <div style="font-size: 20px; font-weight: 700; color: #0f172a;">${data.placa}</div>
                        </div>
                        <div>
                            <strong style="color: #64748b;">Puesto:</strong>
                            <div style="font-size: 20px; font-weight: 700; color: #0f172a;">${data.puesto_codigo}</div>
                        </div>
                    </div>
                    <div style="text-align: right; padding: 12px; background: white; border-radius: 8px;">
                        <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">Total a Pagar:</div>
                        <div style="font-size: 32px; font-weight: 700; color: #7c3aed;">
                            ${window.parkingSystem.formatCurrency(data.total_pagar)}
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <!-- Efectivo -->
                    <button class="btn-metodo-pago" onclick="procesarPago('${data.movimiento_id}', 'efectivo', this, '${data.total_pagar}')">
                        <div style="font-size: 32px; margin-bottom: 8px;">💵</div>
                        <div style="font-weight: 600; font-size: 16px;">Efectivo</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Pago en caja</div>
                    </button>

                    <!-- Nequi -->
                    <button class="btn-metodo-pago" onclick="procesarPago('${data.movimiento_id}', 'nequi', this, '${data.total_pagar}')">
                        <div style="font-size: 32px; margin-bottom: 8px;">📱</div>
                        <div style="font-weight: 600; font-size: 16px;">Nequi</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Transferencia</div>
                    </button>

                    <!-- Tarjeta -->
                    <button class="btn-metodo-pago" onclick="procesarPago('${data.movimiento_id}', 'tarjeta', this, '${data.total_pagar}')">
                        <div style="font-size: 32px; margin-bottom: 8px;">💳</div>
                        <div style="font-weight: 600; font-size: 16px;">Tarjeta</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Débito/Crédito</div>
                    </button>

                    <!-- Transferencia -->
                    <button class="btn-metodo-pago" onclick="procesarPago('${data.movimiento_id}', 'transferencia', this, '${data.total_pagar}')">
                        <div style="font-size: 32px; margin-bottom: 8px;">🏦</div>
                        <div style="font-weight: 600; font-size: 16px;">Transferencia</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Bancaria</div>
                    </button>
                </div>

                <div style="padding: 12px; background: rgba(124, 58, 237, 0.08); border-radius: 8px; border-left: 3px solid #7c3aed;">
                    <strong style="color: #0f172a;">📌 Nota:</strong>
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                        Este es un sistema de simulación. Selecciona el método de pago y se generará una factura para registro.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="this.closest('.modal-overlay').remove()" style="flex: 1;">
                    <i class="fa-solid fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
}

/**
 * Procesar el pago seleccionado
 */
async function procesarPago(movimientoId, metodo, boton, total) {
    boton.disabled = true;
    
    // Guardar el método de pago y generar factura
    const datosFactura = {
        movimiento_id: movimientoId,
        metodo_pago: metodo,
        total: total,
        fecha: new Date().toLocaleString('es-CO'),
        numero_whatsapp: '3147975744'
    };
    
    // Cerrar modal de pago primero
    const pagoOverlay = document.querySelector('.modal-overlay');
    if (pagoOverlay) pagoOverlay.remove();

    // Mostrar ticket con factura
    await mostrarFactura(datosFactura);
}

/**
 * Mostrar factura con todos los detalles
 */
async function mostrarFactura(datosFactura) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    
    let iconoMetodo = '💵';
    let nombreMetodo = 'Efectivo';
    
    if (datosFactura.metodo_pago === 'nequi') {
        iconoMetodo = '📱';
        nombreMetodo = 'Nequi';
    } else if (datosFactura.metodo_pago === 'tarjeta') {
        iconoMetodo = '💳';
        nombreMetodo = 'Tarjeta';
    } else if (datosFactura.metodo_pago === 'transferencia') {
        iconoMetodo = '🏦';
        nombreMetodo = 'Transferencia';
    }
    
    const html = `
        <div class="modal" style="max-width: 700px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                <h3 style="color: white;">
                    <i class="fa-solid fa-receipt"></i> Factura de Salida
                </h3>
            </div>
            <div class="modal-body">
                <div style="text-align: center; padding: 20px; border-bottom: 2px dashed #e0e7ff; margin-bottom: 20px;">
                    <div style="font-size: 48px; color: #10b981; margin-bottom: 8px;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div style="font-size: 20px; font-weight: 700; color: #0f172a;">
                        Pago Registrado Exitosamente
                    </div>
                    <div style="font-size: 14px; color: #64748b; margin-top: 4px;">
                        Método: ${iconoMetodo} ${nombreMetodo}
                    </div>
                </div>

                <div id="facturaPDF" style="padding: 24px; background: white; border: 1px solid #e0e7ff; border-radius: 8px; font-family: Arial, sans-serif; font-size: 13px;">
                    <div style="text-align: center; margin-bottom: 20px; border-bottom: 1px solid #e0e7ff; padding-bottom: 16px;">
                        <div style="font-size: 18px; font-weight: 700; color: #0f172a;">PARQUEADERO INTELIGENTE</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                            Tel: ${datosFactura.numero_whatsapp} | Factura #${Math.floor(Math.random() * 1000000)}
                        </div>
                    </div>

                    <div style="margin-bottom: 16px; padding: 12px; background: rgba(124, 58, 237, 0.05); border-radius: 6px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                            <tr style="border-bottom: 1px solid #e0e7ff;">
                                <td style="padding: 6px 0; color: #64748b;"><strong>Concepto</strong></td>
                                <td style="padding: 6px 0; text-align: right; color: #64748b;"><strong>Valor</strong></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #e0e7ff;">
                                <td style="padding: 8px 0;">Estacionamiento</td>
                                <td style="padding: 8px 0; text-align: right; font-weight: 600;">$${Math.floor(datosFactura.total).toLocaleString('es-CO')}</td>
                            </tr>
                            <tr style="background: #f5f3ff;">
                                <td style="padding: 12px 0; font-size: 14px; font-weight: 700;">TOTAL A PAGAR</td>
                                <td style="padding: 12px 0; text-align: right; font-size: 16px; font-weight: 700; color: #7c3aed;">$${Math.floor(datosFactura.total).toLocaleString('es-CO')}</td>
                            </tr>
                        </table>
                    </div>

                    <div style="margin: 20px 0; padding: 12px; background: linear-gradient(135deg, rgba(124, 58, 237, 0.1), rgba(6, 182, 212, 0.05)); border-radius: 6px; border-left: 3px solid #7c3aed;">
                        <div style="font-weight: 600; color: #0f172a; margin-bottom: 8px;">Información de Pago:</div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">
                            <strong>Método:</strong> ${nombreMetodo}
                        </div>
                        ${datosFactura.metodo_pago === 'nequi' ? `
                        <div style="font-size: 12px; color: #0f172a; margin-top: 8px; padding: 8px; background: white; border-radius: 4px;">
                            <strong>💱 Número Nequi:</strong> +57 ${datosFactura.numero_whatsapp}
                        </div>
                        ` : ''}
                        <div style="font-size: 12px; color: #64748b; margin-top: 8px;">
                            <strong>Fecha:</strong> ${datosFactura.fecha}
                        </div>
                    </div>

                    <div style="text-align: center; padding: 16px; color: #64748b; font-size: 11px; border-top: 1px solid #e0e7ff; margin-top: 16px;">
                        Gracias por usar nuestro servicio. Este documento es válido para la entrada y salida del vehículo.
                    </div>
                </div>

                <div style="margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <button class="btn btn-secondary" onclick="imprimirFactura()">
                        <i class="fa-solid fa-print"></i> Imprimir
                    </button>
                    <button class="btn btn-primary" onclick="compartirWhatsapp('${datosFactura.numero_whatsapp}', '${Math.floor(datosFactura.total)}')">
                        <i class="fa-brands fa-whatsapp"></i> Enviar por WhatsApp
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="btnCerrarFactura" style="flex: 1;">
                    <i class="fa-solid fa-check"></i> Aceptar
                </button>
            </div>
        </div>
    `;
    
    overlay.innerHTML = html;
    document.body.appendChild(overlay);
    
    document.getElementById('btnCerrarFactura').addEventListener('click', () => {
        overlay.remove();
        // Limpiar búsqueda y recargar
        document.getElementById('buscarPlaca').value = '';
        document.getElementById('resultadoBusqueda').innerHTML = '';
        cargarVehiculosActivos();
    });
}

/**
 * Mostrar ticket de salida
 */
function mostrarTicketSalida(data) {
    // Mostrar modal para seleccionar método de pago
    mostrarModalPago(data);
}

/**
 * Imprimir factura
 */
function imprimirFactura() {
    window.print();
}

/**
 * Compartir por WhatsApp
 */
function compartirWhatsapp(numero, total) {
    const mensaje = encodeURIComponent(`¡Hola! He pagado $${Math.floor(total).toLocaleString('es-CO')} en el Parqueadero Inteligente. Este es el número de tu factura para consultas.`);
    window.open(`https://wa.me/57${numero}?text=${mensaje}`, '_blank');
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
                
                // Calcular tiempo transcurrido usando hora_entrada_iso si está disponible
                const entradaRaw = vehiculo.hora_entrada_iso || vehiculo.hora_entrada || '';
                const entrada = (entradaRaw.indexOf('T') !== -1 || entradaRaw.indexOf('+') !== -1 || entradaRaw.indexOf('Z') !== -1)
                    ? new Date(entradaRaw)
                    : new Date(entradaRaw.replace(' ', 'T'));
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