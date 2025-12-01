/**
 * LÓGICA DEL MAPA VISUAL
 * Renderizado, interacción y gestión de puestos
 * Sistema de Parqueadero Inteligente
 */

// Estado global del mapa
const MapaParqueadero = {
    puestos: [],
    modoEdicion: false,
    cambiosPendientes: new Set(),
    puestoSeleccionado: null,

    /**
     * Inicializar el mapa
     */
    init() {
        this.puestos = window.puestosData || [];
        this.renderizarPuestos();
        this.inicializarEventos();
        console.log('Mapa inicializado con', this.puestos.length, 'puestos');
    },

    /**
     * Renderizar todos los puestos en el canvas
     */
    renderizarPuestos() {
        const canvas = document.getElementById('mapaCanvas');
        canvas.innerHTML = '';

        this.puestos.forEach(puesto => {
            const elemento = this.crearElementoPuesto(puesto);
            canvas.appendChild(elemento);
        });
    },

    /**
     * Crear elemento DOM de un puesto
     */
    crearElementoPuesto(puesto) {
        const div = document.createElement('div');
        div.className = `puesto ${puesto.estado}`;
        div.dataset.id = puesto.id;
        div.dataset.tipo = puesto.tipo_id;
        div.dataset.estado = puesto.estado;
        div.style.left = puesto.x + 'px';
        div.style.top = puesto.y + 'px';

        // Icono según tipo
        const iconos = {
            '1': 'fa-motorcycle',
            '2': 'fa-car',
            '3': 'fa-truck'
        };

        div.innerHTML = `
            <i class="fa-solid ${iconos[puesto.tipo_id]}"></i>
            <div class="puesto-codigo">${puesto.codigo}</div>
            <div class="puesto-info">
                Puesto: ${puesto.codigo}<br>
                Tipo: ${puesto.tipo_nombre}<br>
                Estado: ${puesto.estado}
            </div>
        `;

        // Click en puesto (solo en modo edición)
        div.addEventListener('click', (e) => {
            if (this.modoEdicion) {
                this.mostrarAccionesPuesto(puesto);
            }
        });

        return div;
    },

    /**
     * Inicializar eventos del mapa
     */
    inicializarEventos() {
        // Botón modo edición
        document.getElementById('btnModoEdicion').addEventListener('click', () => {
            this.toggleModoEdicion();
        });

        // Botón agregar puesto
        document.getElementById('btnAgregarPuesto').addEventListener('click', () => {
            this.abrirModalAgregar();
        });

        // Botón guardar cambios
        document.getElementById('btnGuardarCambios').addEventListener('click', () => {
            this.guardarCambios();
        });

        // Selector de tipo en modal agregar
        document.querySelectorAll('.tipo-option').forEach(option => {
            option.addEventListener('click', () => {
                document.querySelectorAll('.tipo-option').forEach(opt => 
                    opt.classList.remove('selected'));
                option.classList.add('selected');
                document.getElementById('tipoIdInput').value = option.dataset.tipoId;
            });
        });

        // Confirmar agregar puesto
        document.getElementById('btnConfirmarAgregar').addEventListener('click', () => {
            this.agregarPuesto();
        });

        // Acciones del puesto
        document.getElementById('btnCambiarEstado')?.addEventListener('click', () => {
            this.cambiarEstadoPuesto();
        });

        document.getElementById('btnConvertirPuesto')?.addEventListener('click', () => {
            this.convertirPuesto();
        });

        document.getElementById('btnEliminarPuesto')?.addEventListener('click', () => {
            this.eliminarPuesto();
        });
    },

    /**
     * Toggle modo edición
     */
    toggleModoEdicion() {
        this.modoEdicion = !this.modoEdicion;
        const btn = document.getElementById('btnModoEdicion');
        const badge = document.getElementById('modoEdicionBadge');
        const btnAgregar = document.getElementById('btnAgregarPuesto');
        const canvas = document.getElementById('mapaCanvas');

        if (this.modoEdicion) {
            btn.style.display = 'none';
            badge.style.display = 'flex';
            btnAgregar.style.display = 'block';
            canvas.classList.add('modo-edicion');
            
            // Habilitar drag & drop
            this.habilitarDragDrop();
            
            window.parkingSystem.showAlert('Modo edición activado. Puedes mover y editar puestos.', 'info');
        } else {
            btn.style.display = 'block';
            badge.style.display = 'none';
            btnAgregar.style.display = 'none';
            canvas.classList.remove('modo-edicion');
            
            // Verificar cambios pendientes
            if (this.cambiosPendientes.size > 0) {
                window.parkingSystem.confirmAction(
                    '¿Deseas guardar los cambios realizados?',
                    () => this.guardarCambios(),
                    () => {
                        this.cambiosPendientes.clear();
                        document.getElementById('btnGuardarCambios').style.display = 'none';
                    }
                );
            }
        }
    },

    /**
     * Habilitar drag and drop
     */
    habilitarDragDrop() {
        const puestos = document.querySelectorAll('.puesto.libre, .puesto.inactivo');
        puestos.forEach(puesto => {
            puesto.draggable = true;
            puesto.style.cursor = 'move';
        });
    },

    /**
     * Abrir modal para agregar puesto
     */
    abrirModalAgregar() {
        // Limpiar formulario
        document.getElementById('formAgregarPuesto').reset();
        document.querySelectorAll('.tipo-option').forEach(opt => 
            opt.classList.remove('selected'));
        
        window.parkingSystem.openModal('modalAgregarPuesto');
    },

    /**
     * Agregar nuevo puesto
     */
    async agregarPuesto() {
        const form = document.getElementById('formAgregarPuesto');
        
        if (!window.parkingSystem.validateForm(form)) {
            window.parkingSystem.showAlert('Por favor completa todos los campos', 'error');
            return;
        }

        const tipoId = document.getElementById('tipoIdInput').value;
        const codigo = document.getElementById('codigoPuesto').value.trim();

        if (!tipoId) {
            window.parkingSystem.showAlert('Selecciona un tipo de puesto', 'error');
            return;
        }

        const btn = document.getElementById('btnConfirmarAgregar');
        window.parkingSystem.setButtonLoading(btn);

        try {
            const formData = new FormData();
            formData.append('tipo_id', tipoId);
            formData.append('codigo', codigo);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

            const response = await window.parkingSystem.fetchWithCSRF('../api/crear_puesto.php', {
                method: 'POST',
                body: formData
            });

            if (response.success) {
                window.parkingSystem.showAlert(response.message || 'Puesto agregado correctamente', 'success');
                window.parkingSystem.closeModal('modalAgregarPuesto');
                
                // Recargar mapa
                setTimeout(() => location.reload(), 1000);
            } else {
                window.parkingSystem.showAlert(response.error || 'Error al agregar puesto', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
        } finally {
            window.parkingSystem.resetButton(btn);
        }
    },

    /**
     * Mostrar acciones de un puesto
     */
    mostrarAccionesPuesto(puesto) {
        this.puestoSeleccionado = puesto;
        
        document.getElementById('puestoCodigoModal').textContent = puesto.codigo;
        
        const info = document.getElementById('puestoInfoModal');
        info.innerHTML = `
            <div style="font-size: 13px;">
                <strong>Código:</strong> ${puesto.codigo}<br>
                <strong>Tipo:</strong> ${puesto.tipo_nombre}<br>
                <strong>Estado:</strong> <span class="badge badge-${puesto.estado === 'libre' ? 'success' : (puesto.estado === 'ocupado' ? 'danger' : 'secondary')}">${puesto.estado}</span><br>
                <strong>Posición:</strong> X: ${puesto.x}, Y: ${puesto.y}
            </div>
        `;

        // Deshabilitar eliminar si está ocupado
        const btnEliminar = document.getElementById('btnEliminarPuesto');
        if (puesto.estado === 'ocupado') {
            btnEliminar.disabled = true;
            btnEliminar.title = 'No se puede eliminar un puesto ocupado';
        } else {
            btnEliminar.disabled = false;
            btnEliminar.title = '';
        }

        window.parkingSystem.openModal('modalAccionesPuesto');
    },

    /**
     * Cambiar estado del puesto (libre <-> inactivo)
     */
    async cambiarEstadoPuesto() {
        const puesto = this.puestoSeleccionado;
        
        if (puesto.estado === 'ocupado') {
            window.parkingSystem.showAlert('No se puede cambiar el estado de un puesto ocupado', 'error');
            return;
        }

        const nuevoEstado = puesto.estado === 'libre' ? 'inactivo' : 'libre';
        
        try {
            const formData = new FormData();
            formData.append('puesto_id', puesto.id);
            formData.append('estado', nuevoEstado);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

            const response = await window.parkingSystem.fetchWithCSRF('../api/guardar_mapa.php', {
                method: 'POST',
                body: formData
            });

            if (response.success) {
                window.parkingSystem.showAlert('Estado actualizado correctamente', 'success');
                window.parkingSystem.closeModal('modalAccionesPuesto');
                setTimeout(() => location.reload(), 800);
            } else {
                window.parkingSystem.showAlert(response.error || 'Error al actualizar estado', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
        }
    },

    /**
     * Convertir tipo de puesto
     */
    async convertirPuesto() {
        const puesto = this.puestoSeleccionado;
        
        if (puesto.estado === 'ocupado') {
            window.parkingSystem.showAlert('No se puede convertir un puesto ocupado', 'error');
            return;
        }

        // Crear selector dinámico
        const tipos = window.tiposPuesto.filter(t => t.id != puesto.tipo_id);
        
        const html = tipos.map(tipo => {
            const icono = tipo.nombre === 'moto' ? 'motorcycle' : 
                         (tipo.nombre === 'carro' ? 'car' : 'truck');
            return `<button class="tipo-option" data-tipo-id="${tipo.id}" style="margin: 5px;">
                <i class="fa-solid fa-${icono}"></i> ${tipo.nombre}
            </button>`;
        }).join('');

        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay active';
        overlay.innerHTML = `
            <div class="modal">
                <div class="modal-header">
                    <h3>Convertir Puesto ${puesto.codigo}</h3>
                </div>
                <div class="modal-body">
                    <p>Selecciona el nuevo tipo:</p>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        ${html}
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" id="btnCancelarConvertir">Cancelar</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        // Eventos
        overlay.querySelectorAll('.tipo-option').forEach(btn => {
            btn.addEventListener('click', async () => {
                const nuevoTipoId = btn.dataset.tipoId;
                
                try {
                    const formData = new FormData();
                    formData.append('puesto_id', puesto.id);
                    formData.append('nuevo_tipo_id', nuevoTipoId);
                    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

                    const response = await window.parkingSystem.fetchWithCSRF('../api/convertir_puesto.php', {
                        method: 'POST',
                        body: formData
                    });

                    if (response.success) {
                        window.parkingSystem.showAlert('Puesto convertido correctamente', 'success');
                        overlay.remove();
                        window.parkingSystem.closeModal('modalAccionesPuesto');
                        setTimeout(() => location.reload(), 800);
                    } else {
                        window.parkingSystem.showAlert(response.error || 'Error al convertir puesto', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
                }
            });
        });

        document.getElementById('btnCancelarConvertir').addEventListener('click', () => {
            overlay.remove();
        });
    },

    /**
     * Eliminar puesto
     */
    async eliminarPuesto() {
        const puesto = this.puestoSeleccionado;
        
        if (puesto.estado === 'ocupado') {
            window.parkingSystem.showAlert('No se puede eliminar un puesto ocupado', 'error');
            return;
        }

        window.parkingSystem.confirmAction(
            `¿Estás seguro de eliminar el puesto ${puesto.codigo}? Esta acción no se puede deshacer.`,
            async () => {
                try {
                    const formData = new FormData();
                    formData.append('puesto_id', puesto.id);
                    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

                    const response = await window.parkingSystem.fetchWithCSRF('../api/eliminar_puesto.php', {
                        method: 'POST',
                        body: formData
                    });

                    if (response.success) {
                        window.parkingSystem.showAlert('Puesto eliminado correctamente', 'success');
                        window.parkingSystem.closeModal('modalAccionesPuesto');
                        setTimeout(() => location.reload(), 800);
                    } else {
                        window.parkingSystem.showAlert(response.error || 'Error al eliminar puesto', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
                }
            }
        );
    },

    /**
     * Guardar cambios de posición
     */
    async guardarCambios() {
        if (this.cambiosPendientes.size === 0) {
            window.parkingSystem.showAlert('No hay cambios para guardar', 'info');
            return;
        }

        const cambios = Array.from(this.cambiosPendientes).map(id => {
            const elemento = document.querySelector(`.puesto[data-id="${id}"]`);
            return {
                id: id,
                x: parseInt(elemento.style.left),
                y: parseInt(elemento.style.top)
            };
        });

        try {
            const response = await window.parkingSystem.fetchWithCSRF('../api/guardar_mapa.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    cambios: cambios
                })
            });

            if (response.success) {
                window.parkingSystem.showAlert('Cambios guardados correctamente', 'success');
                this.cambiosPendientes.clear();
                document.getElementById('btnGuardarCambios').style.display = 'none';
            } else {
                window.parkingSystem.showAlert(response.error || 'Error al guardar cambios', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
        }
    },

    /**
     * Registrar cambio de posición
     */
    registrarCambio(puestoId) {
        this.cambiosPendientes.add(puestoId);
        document.getElementById('btnGuardarCambios').style.display = 'block';
    }
};

// Inicializar al cargar el DOM
document.addEventListener('DOMContentLoaded', () => {
    MapaParqueadero.init();
});

// Exportar para uso global
window.MapaParqueadero = MapaParqueadero;