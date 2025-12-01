/**
 * JAVASCRIPT GLOBAL DEL SISTEMA
 * Funciones reutilizables en todo el proyecto
 * Sistema de Parqueadero Inteligente
 */

// Obtener token CSRF de la página
function getCSRFToken() {
    const input = document.querySelector('input[name="csrf_token"]');
    return input ? input.value : '';
}

/**
 * Realizar petición AJAX con CSRF
 * @param {string} url - URL del endpoint
 * @param {object} options - Opciones de fetch
 * @returns {Promise}
 */
async function fetchWithCSRF(url, options = {}) {
    const token = getCSRFToken();
    
    const defaultOptions = {
        headers: {
            'X-CSRF-Token': token,
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    // Merge options
    const mergedOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers
        }
    };
    
    try {
        const response = await fetch(url, mergedOptions);
        const text = await response.text();
        let data = null;
        try {
            data = text ? JSON.parse(text) : null;
        } catch (e) {
            data = null;
        }

        // Si la respuesta no es OK, devolvemos el JSON si existe (para mostrar el error de la API)
        if (!response.ok) {
            // Si el servidor devolvió JSON con detalles de error, retornarlo
            if (data) return data;
            // Si no hay JSON, lanzar error genérico
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return data;
    } catch (error) {
        console.error('Error en petición:', error);
        throw error;
    }
}

/**
 * Mostrar alerta temporal
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo: success, error, warning, info
 * @param {number} duration - Duración en ms (default 5000)
 */
function showAlert(message, type = 'info', duration = 5000) {
    // Crear elemento de alerta
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '300px';
    alert.style.animation = 'slideInRight 0.3s ease-out';
    
    // Icono según tipo
    const icons = {
        success: 'fa-circle-check',
        error: 'fa-circle-exclamation',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info'
    };
    
    alert.innerHTML = `
        <i class="fa-solid ${icons[type] || icons.info}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(alert);
    
    // Auto-remover después de duración
    setTimeout(() => {
        alert.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => alert.remove(), 300);
    }, duration);
}

/**
 * Confirmar acción con modal
 * @param {string} message - Mensaje de confirmación
 * @param {Function} onConfirm - Callback si confirma
 * @param {Function} onCancel - Callback si cancela
 */
function confirmAction(message, onConfirm, onCancel = null) {
    // Crear overlay modal
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    
    overlay.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <h3>Confirmar acción</h3>
            </div>
            <div class="modal-body">
                <p>${message}</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelBtn">Cancelar</button>
                <button class="btn btn-danger" id="confirmBtn">Confirmar</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
    
    // Event listeners
    document.getElementById('confirmBtn').addEventListener('click', () => {
        overlay.remove();
        if (onConfirm) onConfirm();
    });
    
    document.getElementById('cancelBtn').addEventListener('click', () => {
        overlay.remove();
        if (onCancel) onCancel();
    });
    
    // Cerrar al hacer click fuera
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.remove();
            if (onCancel) onCancel();
        }
    });
}

/**
 * Formatear moneda colombiana
 * @param {number} amount - Cantidad a formatear
 * @returns {string}
 */
function formatCurrency(amount) {
    return '$' + new Intl.NumberFormat('es-CO').format(amount);
}

/**
 * Formatear fecha
 * @param {string} dateString - Fecha en formato ISO
 * @param {boolean} includeTime - Incluir hora
 * @returns {string}
 */
function formatDate(dateString, includeTime = true) {
    const date = new Date(dateString);
    const options = {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    };
    
    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    
    return date.toLocaleString('es-CO', options);
}

/**
 * Validar placa según regex obligatorio
 * @param {string} placa - Placa a validar
 * @returns {boolean}
 */
function validatePlaca(placa) {
    const regex = /^[A-Za-z0-9]{4,8}$/;
    return regex.test(placa);
}

/**
 * Deshabilitar botón mientras se procesa
 * @param {HTMLElement} button - Botón a deshabilitar
 * @param {string} loadingText - Texto durante carga
 */
function setButtonLoading(button, loadingText = 'Procesando...') {
    button.disabled = true;
    button.dataset.originalText = button.innerHTML;
    button.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${loadingText}`;
}

/**
 * Restaurar botón después de procesar
 * @param {HTMLElement} button - Botón a restaurar
 */
function resetButton(button) {
    button.disabled = false;
    button.innerHTML = button.dataset.originalText || button.innerHTML;
}

/**
 * Debounce para búsquedas
 * @param {Function} func - Función a ejecutar
 * @param {number} wait - Tiempo de espera en ms
 * @returns {Function}
 */
function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Abrir modal genérico
 * @param {string} modalId - ID del modal
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Cerrar modal genérico
 * @param {string} modalId - ID del modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';

// Handler global: cerrar modal cuando se hace click en un botón con clase .modal-close
document.addEventListener('click', function(e) {
    const btn = e.target.closest && e.target.closest('.modal-close');
    if (!btn) return;

    // Si el botón tiene atributo data-target con id del modal, usarlo
    const target = btn.getAttribute('data-target');
    if (target) {
        window.parkingSystem.closeModal(target);
        return;
    }

    // Si el botón tiene onclick inline que ya maneja el cierre, dejarlo (no interferir)
    // Pero mejor intentar cerrar el modal-overlay más cercano
    const overlay = btn.closest('.modal-overlay');
    if (overlay) {
        // Si el overlay tiene un id que coincide con el modal id, usar closeModal
        if (overlay.id) {
            window.parkingSystem.closeModal(overlay.id);
            return;
        }

        // Remover la clase active si existe
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        // Si el overlay no es un elemento persistente, removerlo del DOM
        if (!overlay.querySelector('#' + overlay.id)) {
            // overlay likely was dynamically created; remove it
            if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
        }
    }
});
    }
}

/**
 * Validar formulario
 * @param {HTMLFormElement} form - Formulario a validar
 * @returns {boolean}
 */
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('error');
            input.style.borderColor = '#ef4444';
        } else {
            input.classList.remove('error');
            input.style.borderColor = '';
        }
    });
    
    return isValid;
}

/**
 * Limpiar formulario
 * @param {HTMLFormElement} form - Formulario a limpiar
 */
function clearForm(form) {
    form.reset();
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.classList.remove('error');
        input.style.borderColor = '';
    });
}

/**
 * Timeout de sesión - Advertir al usuario
 */
let sessionTimeout;
let warningTimeout;

function resetSessionTimeout() {
    clearTimeout(sessionTimeout);
    clearTimeout(warningTimeout);
    
    // Advertir 2 minutos antes de expirar
    warningTimeout = setTimeout(() => {
        showAlert('Tu sesión expirará en 2 minutos. Realiza alguna acción para mantenerla activa.', 'warning', 10000);
    }, 18 * 60 * 1000); // 18 minutos
    
    // Redirigir al login después de 20 minutos
    sessionTimeout = setTimeout(() => {
        showAlert('Tu sesión ha expirado por inactividad.', 'error', 3000);
        setTimeout(() => {
            window.location.href = '/public/logout.php';
        }, 3000);
    }, 20 * 60 * 1000); // 20 minutos
}

// Reiniciar timeout con actividad del usuario
document.addEventListener('click', resetSessionTimeout);
document.addEventListener('keypress', resetSessionTimeout);
document.addEventListener('scroll', resetSessionTimeout);

// Iniciar timeout al cargar
resetSessionTimeout();

/**
 * Animaciones CSS adicionales
 */
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Exportar funciones globales
window.parkingSystem = {
    fetchWithCSRF,
    showAlert,
    confirmAction,
    formatCurrency,
    formatDate,
    validatePlaca,
    setButtonLoading,
    resetButton,
    debounce,
    openModal,
    closeModal,
    validateForm,
    clearForm
};