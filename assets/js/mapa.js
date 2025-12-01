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
        // Aplicar tamaño del mapa desde configuración
        const cfg = window.mapSettings || { width: 1200, height: 600 };
        // base for visual scaling
        this.BASE_WIDTH = 1200;
        this.BASE_HEIGHT = 600;
        this.aplicarTamanioMapa(cfg.width, cfg.height);
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
        div.dataset.orientacion = puesto.orientacion || 0; // 0 = horizontal, 90 = vertical
        div.style.left = puesto.x + 'px';
        div.style.top = puesto.y + 'px';
        
        // Aplicar rotación si existe
        const rotacion = puesto.orientacion || 0;
        if (rotacion === 90) {
            div.classList.add('rotado-90');
        }

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

        document.getElementById('btnRotarPuesto')?.addEventListener('click', () => {
            this.rotarPuesto();
        });

        document.getElementById('btnConvertirPuesto')?.addEventListener('click', () => {
            this.convertirPuesto();
        });

        document.getElementById('btnEliminarPuesto')?.addEventListener('click', () => {
            this.eliminarPuesto();
        });

        // Controles de tamaño del mapa (separado por ancho y alto)
        const btnAumentarWidth = document.getElementById('btnAumentarWidth');
        const btnReducirWidth = document.getElementById('btnReducirWidth');
        const btnAumentarHeight = document.getElementById('btnAumentarHeight');
        const btnReducirHeight = document.getElementById('btnReducirHeight');
        const btnGuardarTamano = document.getElementById('btnGuardarTamano');

        btnAumentarWidth?.addEventListener('click', () => {
            this.cambiarAncho(100);
        });
        btnReducirWidth?.addEventListener('click', () => {
            this.cambiarAncho(-100);
        });
        btnAumentarHeight?.addEventListener('click', () => {
            this.cambiarAlto(50);
        });
        btnReducirHeight?.addEventListener('click', () => {
            this.cambiarAlto(-50);
        });

        btnGuardarTamano?.addEventListener('click', () => {
            this.guardarTamanioMapa();
        });
    },

    /**
     * Aplicar tamaño del mapa en pixeles
     */
    aplicarTamanioMapa(width, height) {
        const container = document.querySelector('.mapa-container');
        const canvas = document.getElementById('mapaCanvas');
        if (!container || !canvas) return;

        // Ajustar ancho y alto explícitamente
        container.style.maxWidth = width + 'px';
        container.style.width = width + 'px';
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';

        // Only scale the visual sizes of puestos; do NOT change their left/top positions automatically.
        const puestos = document.querySelectorAll('.puesto');
        const scaleX = width / (this.BASE_WIDTH || 1200);
        const scaleY = height / (this.BASE_HEIGHT || 600);
        const scale = Math.min(scaleX, scaleY);

        puestos.forEach(puesto => {
            const tipo = puesto.dataset.tipo;
            let baseW = 100;
            let baseH = 50;
            if (tipo === '1') baseW = 50;
            if (tipo === '2') baseW = 100;
            if (tipo === '3') baseW = 150;

            puesto.style.width = Math.round(baseW * scale) + 'px';
            puesto.style.height = Math.round(baseH * scale) + 'px';
            // do not change left/top - user will move manually if needed
        });

        // Actualizar etiqueta
        const label = document.getElementById('mapSizeLabel');
        if (label) label.textContent = `${width} x ${height}`;

        // Mostrar botón de guardar tamaño si cambió respecto a la configuración
        const cfg = window.mapSettings || { width: 1200, height: 600 };
        const saveBtn = document.getElementById('btnGuardarTamano');
        if (saveBtn) {
            if (parseInt(width) !== parseInt(cfg.width) || parseInt(height) !== parseInt(cfg.height)) {
                saveBtn.style.display = 'inline-flex';
            } else {
                saveBtn.style.display = 'none';
            }
        }
    },

    /**
     * Resolver colisiones simples moviendo puestos a la derecha/abajo en pasos
     */
    resolverColisiones(width, height) {
        const puestos = Array.from(document.querySelectorAll('.puesto'));

        const step = 10; // grid resolution (same as snap used elsewhere)
        const cols = Math.max(1, Math.floor(width / step));
        const rows = Math.max(1, Math.floor(height / step));

        // Build occupancy grid
        const grid = new Array(rows);
        for (let r = 0; r < rows; r++) grid[r] = new Array(cols).fill(false);

        const markOccupied = (g, el, mark = true) => {
            const x = parseInt(el.style.left) || 0;
            const y = parseInt(el.style.top) || 0;
            const wCells = Math.ceil(el.offsetWidth / step);
            const hCells = Math.ceil(el.offsetHeight / step);
            const cx = Math.floor(x / step);
            const cy = Math.floor(y / step);
            for (let ry = cy; ry < cy + hCells; ry++) {
                for (let rx = cx; rx < cx + wCells; rx++) {
                    if (ry >= 0 && ry < rows && rx >= 0 && rx < cols) g[ry][rx] = mark;
                }
            }
        };

        // Initial mark
        puestos.forEach(p => markOccupied(grid, p, true));

        const fitsAt = (g, el, cx, cy) => {
            const wCells = Math.ceil(el.offsetWidth / step);
            const hCells = Math.ceil(el.offsetHeight / step);
            if (cx < 0 || cy < 0 || cx + wCells > cols || cy + hCells > rows) return false;
            for (let ry = cy; ry < cy + hCells; ry++) {
                for (let rx = cx; rx < cx + wCells; rx++) {
                    if (g[ry][rx]) return false;
                }
            }
            return true;
        };

        const bfsFind = (el) => {
            const origX = parseInt(el.style.left) || 0;
            const origY = parseInt(el.style.top) || 0;
            const startCx = Math.max(0, Math.floor(origX / step));
            const startCy = Math.max(0, Math.floor(origY / step));

            // Temporarily clear current element cells so it can move into nearby area
            markOccupied(grid, el, false);

            const visited = Array.from({ length: rows }, () => new Array(cols).fill(false));
            const q = [];
            q.push({ cx: startCx, cy: startCy });
            visited[startCy][startCx] = true;

            const dirs = [ [0,1], [1,0], [-1,0], [0,-1] ]; // prefer down, right, left, up

            while (q.length) {
                const cur = q.shift();
                if (fitsAt(grid, el, cur.cx, cur.cy)) {
                    // Found
                    return { x: cur.cx * step, y: cur.cy * step };
                }

                for (let d of dirs) {
                    const nx = cur.cx + d[0];
                    const ny = cur.cy + d[1];
                    if (nx < 0 || ny < 0 || nx >= cols || ny >= rows) continue;
                    if (visited[ny][nx]) continue;
                    visited[ny][nx] = true;
                    q.push({ cx: nx, cy: ny });
                }
            }

            // restore (in case caller expects grid unchanged)
            markOccupied(grid, el, true);
            return null;
        };

        let movedAny = false;
        // Iterate and fix overlaps
        for (let el of puestos) {
            const x = parseInt(el.style.left) || 0;
            const y = parseInt(el.style.top) || 0;
            const rectEl = { left: x, top: y, right: x + el.offsetWidth, bottom: y + el.offsetHeight };

            // check overlap
            let overlap = false;
            for (let other of puestos) {
                if (other === el) continue;
                const ox = parseInt(other.style.left) || 0;
                const oy = parseInt(other.style.top) || 0;
                const rectO = { left: ox, top: oy, right: ox + other.offsetWidth, bottom: oy + other.offsetHeight };
                if (this.rectanglesIntersect(rectEl, rectO)) { overlap = true; break; }
            }

            if (overlap) {
                const pos = bfsFind(el);
                if (pos) {
                    // unmark old, set new, mark new
                    markOccupied(grid, el, false);
                    el.style.left = pos.x + 'px';
                    el.style.top = pos.y + 'px';
                    markOccupied(grid, el, true);
                    window.MapaParqueadero.registrarCambio(el.dataset.id);
                    movedAny = true;
                }
            }
        }

        // one extra pass if movedAny
        if (movedAny) {
            for (let el of puestos) {
                const x = parseInt(el.style.left) || 0;
                const y = parseInt(el.style.top) || 0;
                const rectEl = { left: x, top: y, right: x + el.offsetWidth, bottom: y + el.offsetHeight };
                for (let other of puestos) {
                    if (other === el) continue;
                    const ox = parseInt(other.style.left) || 0;
                    const oy = parseInt(other.style.top) || 0;
                    const rectO = { left: ox, top: oy, right: ox + other.offsetWidth, bottom: oy + other.offsetHeight };
                    if (this.rectanglesIntersect(rectEl, rectO)) {
                        const pos = bfsFind(el);
                        if (pos) {
                            markOccupied(grid, el, false);
                            el.style.left = pos.x + 'px';
                            el.style.top = pos.y + 'px';
                            markOccupied(grid, el, true);
                            window.MapaParqueadero.registrarCambio(el.dataset.id);
                        }
                    }
                }
            }
        }
    },

    /**
     * Cambiar tamaño relativo del mapa (dx, dy)
     */
    cambiarAncho(dW = 100) {
        const cfg = window.mapSettings || { width: 1200, height: 600 };
        let newW = Math.max(600, parseInt(cfg.width) + dW);
        // Límite superior razonable
        newW = Math.min(5000, newW);
        const newH = parseInt(cfg.height) || 600;

        this.aplicarTamanioMapa(newW, newH);
        window.mapSettings = { width: newW, height: newH };
    },

    cambiarAlto(dH = 50) {
        const cfg = window.mapSettings || { width: 1200, height: 600 };
        let newH = Math.max(300, parseInt(cfg.height) + dH);
        newH = Math.min(3000, newH);
        const newW = parseInt(cfg.width) || 1200;

        this.aplicarTamanioMapa(newW, newH);
        window.mapSettings = { width: newW, height: newH };
    },

    /**
     * Guardar tamaño del mapa en la base de datos
     */
    async guardarTamanioMapa() {
        const cfg = window.mapSettings || { width: 1200, height: 600 };
        try {
            const formData = new FormData();
            formData.append('map_width', parseInt(cfg.width));
            formData.append('map_height', parseInt(cfg.height));
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');

            const response = await window.parkingSystem.fetchWithCSRF('../api/guardar_mapa.php', {
                method: 'POST',
                body: formData
            });

            if (response.success) {
                window.parkingSystem.showAlert('Tamaño del mapa guardado correctamente', 'success');
                // Hide save button
                document.getElementById('btnGuardarTamano').style.display = 'none';
            } else {
                window.parkingSystem.showAlert(response.error || 'Error al guardar tamaño del mapa', 'error');
            }
        } catch (error) {
            console.error('Error guardando tamaño del mapa:', error);
            window.parkingSystem.showAlert('Error al comunicarse con el servidor', 'error');
        }
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
     * Rotar puesto 90 grados
     */
    async rotarPuesto() {
        const puesto = this.puestoSeleccionado;
        const elemento = document.querySelector(`.puesto[data-id="${puesto.id}"]`);
        
        if (!elemento) return;

        // Toggle orientación (0 <-> 90)
        const orientacionActual = parseInt(elemento.dataset.orientacion) || 0;
        const nuevaOrientacion = orientacionActual === 0 ? 90 : 0;

        // Toggle clase de rotación visual
        if (nuevaOrientacion === 90) {
            elemento.classList.add('rotado-90');
        } else {
            elemento.classList.remove('rotado-90');
        }

        elemento.dataset.orientacion = nuevaOrientacion;

        try {
            const formData = new FormData();
            formData.append('puesto_id', puesto.id);
            formData.append('orientacion', nuevaOrientacion);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

            const response = await window.parkingSystem.fetchWithCSRF('../api/guardar_mapa.php', {
                method: 'POST',
                body: formData
            });

            if (response.success) {
                window.parkingSystem.showAlert('Puesto rotado correctamente', 'success');
                // Actualizar localmente sin recargar
                this.registrarCambio(puesto.id);
                window.parkingSystem.closeModal('modalAccionesPuesto');
            } else {
                // Revertir rotación local si falla
                elemento.classList.toggle('rotado-90');
                elemento.dataset.orientacion = orientacionActual;
                window.parkingSystem.showAlert(response.error || 'Error al rotar puesto', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            // Revertir rotación local
            elemento.classList.toggle('rotado-90');
            elemento.dataset.orientacion = orientacionActual;
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