/**
 * JAVASCRIPT GLOBAL DEL SISTEMA
 * Funciones reutilizables en todo el proyecto
 * Sistema de Parqueadero Inteligente
 */

// Ensure all text inherits theme colors properly
(function ensureThemeInheritance() {
    document.addEventListener('DOMContentLoaded', () => {
        const updateThemeText = () => {
            // Ensure all body text inherits theme
            const html = document.documentElement;
            if (html.classList.contains('dark-theme')) {
                document.body.style.color = 'var(--text-color, #f0f4f9)';
            }
        };
        updateThemeText();
        // Also update when theme changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'class') {
                    updateThemeText();
                }
            });
        });
        observer.observe(document.documentElement, { attributes: true });
    });
})();
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

/**
 * Accesibilidad y temas (claro/oscuro/auto)
 */
(function() {
    const ACCESSIBLE_KEY = 'parking_accessible';
    const THEME_KEY = 'parking_theme'; // 'dark' | 'light' | 'auto'

    function applyAccessible(enabled, silent = false) {
        const html = document.documentElement;
        if (enabled) {
            html.classList.add('accessible-mode');
            html.setAttribute('data-accessible', 'true');
            ensureAccessibleHelpers();
        } else {
            html.classList.remove('accessible-mode');
            html.removeAttribute('data-accessible');
            removeAccessibleHelpers();
        }
        if (!silent) showAlert(enabled ? '✓ Modo Accesible Activado - Fuentes grandes, alto contraste y navegación mejorada' : 'Modo accesible desactivado', 'success', 3000);
    }

    function setAccessible(enabled, persist = true, silent = false) {
        if (persist) localStorage.setItem(ACCESSIBLE_KEY, enabled ? '1' : '0');
        applyAccessible(enabled, silent);
    }

    function toggleAccessible() {
        const current = localStorage.getItem(ACCESSIBLE_KEY) === '1';
        if (!current) {
            // confirmar activación
            confirmAction('El Modo Accesible aumentará fuentes, contrastes y mejoras de navegación. ¿Deseas activarlo?', () => {
                setAccessible(true, true, false);
            });
        } else {
            confirmAction('¿Deseas desactivar el Modo Accesible?', () => {
                setAccessible(false, true, false);
            });
        }
    }

    // Theme handling - apply to <html> element with high specificity
    function applyTheme(theme) {
        const html = document.documentElement;
        html.classList.remove('dark-theme');
        html.classList.remove('light-theme');
        html.removeAttribute('data-theme');

        if (theme === 'dark') {
            html.classList.add('dark-theme');
            html.setAttribute('data-theme', 'dark');
        } else if (theme === 'light') {
            html.classList.add('light-theme');
            html.setAttribute('data-theme', 'light');
        } else if (theme === 'auto') {
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (prefersDark) {
                html.classList.add('dark-theme');
                html.setAttribute('data-theme', 'dark');
            } else {
                html.classList.add('light-theme');
                html.setAttribute('data-theme', 'light');
            }
        }
    }

    function setTheme(theme, persist = true) {
        if (persist) localStorage.setItem(THEME_KEY, theme);
        applyTheme(theme);
        showAlert(`Tema: ${theme}`, 'info', 2000);
    }

    function toggleTheme() {
        const current = localStorage.getItem(THEME_KEY) || 'auto';
        const next = current === 'dark' ? 'light' : (current === 'light' ? 'auto' : 'dark');
        setTheme(next);
    }

    // Auto-detect changes in OS/browser preference when in 'auto' mode
    if (window.matchMedia) {
        const mq = window.matchMedia('(prefers-color-scheme: dark)');
        mq.addEventListener && mq.addEventListener('change', (e) => {
            const mode = localStorage.getItem(THEME_KEY) || 'auto';
            if (mode === 'auto') applyTheme('auto');
        });
    }

    // Inicializar desde localStorage o preferencia (silencioso, sin notificaciones en carga)
    document.addEventListener('DOMContentLoaded', () => {
        const a = localStorage.getItem(ACCESSIBLE_KEY) === '1';
        applyAccessible(a, true);

        const t = localStorage.getItem(THEME_KEY) || 'auto';
        // Pequeño delay para asegurar que el DOM está listo
        setTimeout(() => {
            applyTheme(t);
        }, 50);
    });

    // También aplicar en el evento load para garantizar
    window.addEventListener('load', () => {
        const t = localStorage.getItem(THEME_KEY) || 'auto';
        applyTheme(t);
    });

    // Exportar a parkingSystem
    window.parkingSystem.setAccessible = setAccessible;
    window.parkingSystem.toggleAccessible = toggleAccessible;
    window.parkingSystem.setTheme = setTheme;
    window.parkingSystem.toggleTheme = toggleTheme;
})();

/* Accessible helpers: skip link, aria-live announcer, keyboard shortcut */
(function accessibleHelpers() {
    function ensureAccessibleHelpers() {
        // Skip link
        if (!document.getElementById('skip-to-content')) {
            const skip = document.createElement('a');
            skip.id = 'skip-to-content';
            skip.className = 'skip-link';
            skip.href = '#main-content';
            skip.textContent = 'Saltar al contenido (Presiona Enter)';
            document.body.insertBefore(skip, document.body.firstChild);
        }

        // Ensure main content has id
        const main = document.querySelector('main.main-content');
        if (main && !main.id) main.id = 'main-content';

        // aria-live announcer
        if (!document.getElementById('live-announcer')) {
            const live = document.createElement('div');
            live.id = 'live-announcer';
            live.setAttribute('role', 'status');
            live.setAttribute('aria-live', 'polite');
            live.setAttribute('aria-atomic', 'true');
            live.style.position = 'absolute';
            live.style.width = '1px';
            live.style.height = '1px';
            live.style.margin = '-1px';
            live.style.border = '0';
            live.style.padding = '0';
            live.style.clip = 'rect(0 0 0 0)';
            live.style.overflow = 'hidden';
            document.body.appendChild(live);
        }

        // Keyboard shortcut: Shift+Alt+M focuses main content
        if (!window.__accessibleShortcutAdded) {
            window.addEventListener('keydown', function(e) {
                if (e.shiftKey && e.altKey && (e.key === 'M' || e.key === 'm')) {
                    const target = document.getElementById('main-content');
                    if (target) {
                        e.preventDefault();
                        target.setAttribute('tabindex', '-1');
                        target.focus({ preventScroll: false });
                    }
                }
            });
            window.__accessibleShortcutAdded = true;
        }
    }

    function removeAccessibleHelpers() {
        const skip = document.getElementById('skip-to-content');
        if (skip) skip.remove();
        const live = document.getElementById('live-announcer');
        if (live) live.remove();
        // Note: we don't remove the keydown listener to avoid complex handler removal; it's inert when no main-content exists
    }

    // Expose an announce helper
    window.parkingSystem.announce = function(message) {
        const live = document.getElementById('live-announcer');
        if (live) {
            // Clear then set to ensure screen-readers notice repeated messages
            live.textContent = '';
            setTimeout(() => live.textContent = message, 50);
        }
    };

    // Ensure helpers are present if accessible mode already enabled on load
    document.addEventListener('DOMContentLoaded', () => {
        if (document.documentElement.classList.contains('accessible-mode')) ensureAccessibleHelpers();
    });
})();

// Insert accessibility and theme buttons into any sidebar-footer on the page (idempotente)
(function insertAccessibilityButtons() {
    function createBtn(id, iconClass, text, onClick) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.id = id;
        btn.className = 'nav-item';
        btn.style.width = '100%';
        btn.style.textAlign = 'left';
        btn.style.background = 'none';
        btn.style.border = 'none';
        btn.style.cursor = 'pointer';
        btn.innerHTML = `<i class="fa-solid ${iconClass}"></i> ${text}`;
        btn.addEventListener('click', onClick);
        return btn;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const footers = document.querySelectorAll('.sidebar-footer');
        footers.forEach(footer => {
            // don't insert if already present by id or existing onclick handlers (handles manual additions)
            if (footer.querySelector('#accessibility-toggle-js') || footer.querySelector('button[onclick*="toggleAccessible"]')) return;

            const btnAccess = createBtn('accessibility-toggle-js', 'fa-universal-access', 'Modo Accesible', () => window.parkingSystem.toggleAccessible());
            const btnTheme = createBtn('theme-toggle-js', 'fa-circle-half-stroke', 'Tema (claro/oscuro/auto)', () => window.parkingSystem.toggleTheme());

            // Insert before logout link if exists
            const logoutLink = footer.querySelector('a[href*="logout.php"]');
            if (logoutLink) {
                footer.insertBefore(btnAccess, logoutLink);
                footer.insertBefore(btnTheme, logoutLink);
            } else {
                footer.appendChild(btnAccess);
                footer.appendChild(btnTheme);
            }
        });
    });
})();