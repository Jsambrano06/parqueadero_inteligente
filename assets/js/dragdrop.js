/**
 * DRAG & DROP PARA PUESTOS DEL MAPA
 * Sistema de arrastre y soltar con validaciones
 * Sistema de Parqueadero Inteligente
 */

const DragDropManager = {
    elementoArrastrado: null,
    offsetX: 0,
    offsetY: 0,
    
    /**
     * Inicializar drag & drop
     */
    init() {
        this.configurarEventos();
        console.log('Drag & Drop inicializado');
    },

    /**
     * Configurar eventos de drag & drop
     */
    configurarEventos() {
        const canvas = document.getElementById('mapaCanvas');
        
        // Delegación de eventos para puestos dinámicos
        canvas.addEventListener('dragstart', (e) => {
            if (e.target.classList.contains('puesto') && window.MapaParqueadero.modoEdicion) {
                this.onDragStart(e);
            }
        });

        canvas.addEventListener('dragend', (e) => {
            if (e.target.classList.contains('puesto')) {
                this.onDragEnd(e);
            }
        });

        canvas.addEventListener('dragover', (e) => {
            e.preventDefault(); // Necesario para permitir drop
        });

        canvas.addEventListener('drop', (e) => {
            e.preventDefault();
            this.onDrop(e);
        });

        // Touch events para dispositivos móviles
        canvas.addEventListener('touchstart', (e) => {
            if (e.target.classList.contains('puesto') && window.MapaParqueadero.modoEdicion) {
                this.onTouchStart(e);
            }
        }, { passive: false });

        canvas.addEventListener('touchmove', (e) => {
            if (this.elementoArrastrado) {
                this.onTouchMove(e);
            }
        }, { passive: false });

        canvas.addEventListener('touchend', (e) => {
            if (this.elementoArrastrado) {
                this.onTouchEnd(e);
            }
        }, { passive: false });
    },

    /**
     * Inicio del arrastre
     */
    onDragStart(e) {
        const puesto = e.target;
        
        // Solo permitir arrastrar puestos libres o inactivos
        if (puesto.dataset.estado === 'ocupado') {
            e.preventDefault();
            window.parkingSystem.showAlert('No puedes mover un puesto ocupado', 'warning');
            return;
        }

        this.elementoArrastrado = puesto;
        puesto.classList.add('dragging');
        
        // Calcular offset para mantener posición relativa del cursor
        const rect = puesto.getBoundingClientRect();
        const canvasRect = puesto.parentElement.getBoundingClientRect();
        this.offsetX = e.clientX - rect.left;
        this.offsetY = e.clientY - rect.top;
        
        // Hacer el elemento semi-transparente
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', puesto.innerHTML);
    },

    /**
     * Fin del arrastre
     */
    onDragEnd(e) {
        if (this.elementoArrastrado) {
            this.elementoArrastrado.classList.remove('dragging');
            this.elementoArrastrado = null;
        }
    },

    /**
     * Drop (soltar elemento)
     */
    onDrop(e) {
        if (!this.elementoArrastrado) return;

        const canvas = e.currentTarget;
        const canvasRect = canvas.getBoundingClientRect();
        
        // Calcular nueva posición
        let newX = e.clientX - canvasRect.left - this.offsetX;
        let newY = e.clientY - canvasRect.top - this.offsetY;

        // Validar límites del canvas
        const puestoRect = this.elementoArrastrado.getBoundingClientRect();
        const maxX = canvasRect.width - puestoRect.width;
        const maxY = canvasRect.height - puestoRect.height;

        newX = Math.max(0, Math.min(newX, maxX));
        newY = Math.max(0, Math.min(newY, maxY));

        // Ajustar a grid de 10px para mejor alineación
        newX = Math.round(newX / 10) * 10;
        newY = Math.round(newY / 10) * 10;

        // Verificar superposición
        if (this.verificarSuperposicion(this.elementoArrastrado, newX, newY)) {
            window.parkingSystem.showAlert('No se puede colocar ahí, hay superposición con otro puesto', 'error');
            return;
        }

        // Aplicar nueva posición
        this.elementoArrastrado.style.left = newX + 'px';
        this.elementoArrastrado.style.top = newY + 'px';

        // Registrar cambio
        const puestoId = this.elementoArrastrado.dataset.id;
        window.MapaParqueadero.registrarCambio(puestoId);

        console.log(`Puesto ${puestoId} movido a X:${newX}, Y:${newY}`);
    },

    /**
     * Touch Start (móviles)
     */
    onTouchStart(e) {
        const puesto = e.target.closest('.puesto');
        if (!puesto) return;

        if (puesto.dataset.estado === 'ocupado') {
            e.preventDefault();
            window.parkingSystem.showAlert('No puedes mover un puesto ocupado', 'warning');
            return;
        }

        e.preventDefault();
        this.elementoArrastrado = puesto;
        puesto.classList.add('dragging');

        const touch = e.touches[0];
        const rect = puesto.getBoundingClientRect();
        this.offsetX = touch.clientX - rect.left;
        this.offsetY = touch.clientY - rect.top;
    },

    /**
     * Touch Move (móviles)
     */
    onTouchMove(e) {
        if (!this.elementoArrastrado) return;
        e.preventDefault();

        const touch = e.touches[0];
        const canvas = document.getElementById('mapaCanvas');
        const canvasRect = canvas.getBoundingClientRect();

        let newX = touch.clientX - canvasRect.left - this.offsetX;
        let newY = touch.clientY - canvasRect.top - this.offsetY;

        // Limitar al canvas
        const puestoRect = this.elementoArrastrado.getBoundingClientRect();
        const maxX = canvasRect.width - puestoRect.width;
        const maxY = canvasRect.height - puestoRect.height;

        newX = Math.max(0, Math.min(newX, maxX));
        newY = Math.max(0, Math.min(newY, maxY));

        this.elementoArrastrado.style.left = newX + 'px';
        this.elementoArrastrado.style.top = newY + 'px';
    },

    /**
     * Touch End (móviles)
     */
    onTouchEnd(e) {
        if (!this.elementoArrastrado) return;
        e.preventDefault();

        // Ajustar a grid
        let x = parseInt(this.elementoArrastrado.style.left);
        let y = parseInt(this.elementoArrastrado.style.top);

        x = Math.round(x / 10) * 10;
        y = Math.round(y / 10) * 10;

        // Verificar superposición
        if (this.verificarSuperposicion(this.elementoArrastrado, x, y)) {
            window.parkingSystem.showAlert('No se puede colocar ahí, hay superposición con otro puesto', 'error');
            // Revertir posición (necesitaríamos guardar posición original)
        } else {
            this.elementoArrastrado.style.left = x + 'px';
            this.elementoArrastrado.style.top = y + 'px';

            // Registrar cambio
            const puestoId = this.elementoArrastrado.dataset.id;
            window.MapaParqueadero.registrarCambio(puestoId);
        }

        this.elementoArrastrado.classList.remove('dragging');
        this.elementoArrastrado = null;
    },

    /**
     * Verificar superposición con otros puestos
     */
    verificarSuperposicion(puestoActual, newX, newY) {
        const anchoActual = parseInt(puestoActual.offsetWidth);
        const altoActual = parseInt(puestoActual.offsetHeight);

        const rect1 = {
            left: newX,
            right: newX + anchoActual,
            top: newY,
            bottom: newY + altoActual
        };

        // Verificar con todos los demás puestos
        const todosPuestos = document.querySelectorAll('.puesto');
        
        for (let otroPuesto of todosPuestos) {
            if (otroPuesto === puestoActual) continue;

            const otroX = parseInt(otroPuesto.style.left);
            const otroY = parseInt(otroPuesto.style.top);
            const otroAncho = parseInt(otroPuesto.offsetWidth);
            const otroAlto = parseInt(otroPuesto.offsetHeight);

            const rect2 = {
                left: otroX,
                right: otroX + otroAncho,
                top: otroY,
                bottom: otroY + otroAlto
            };

            // Detectar superposición
            if (this.rectanglesIntersect(rect1, rect2)) {
                return true;
            }
        }

        return false;
    },

    /**
     * Verificar si dos rectángulos se intersectan
     */
    rectanglesIntersect(rect1, rect2) {
        // Agregar margen de 5px para separación
        const margen = 5;
        
        return !(
            rect1.right < rect2.left + margen ||
            rect1.left > rect2.right - margen ||
            rect1.bottom < rect2.top + margen ||
            rect1.top > rect2.bottom - margen
        );
    },

    /**
     * Habilitar/deshabilitar drag & drop
     */
    toggle(enabled) {
        const puestos = document.querySelectorAll('.puesto.libre, .puesto.inactivo');
        puestos.forEach(puesto => {
            puesto.draggable = enabled;
            puesto.style.cursor = enabled ? 'move' : 'default';
        });
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    DragDropManager.init();
});

// Exportar para uso global
window.DragDropManager = DragDropManager;