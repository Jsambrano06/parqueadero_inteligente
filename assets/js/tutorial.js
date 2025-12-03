class InteractiveTutorial {
  constructor(config = {}) {
    this.currentStep = 0;
    this.steps = config.steps || [];
    this._activeSteps = null;
    this.isActive = false;
    this.blockClicksHandler = null;
    this.blockWheelHandler = null;
    this.resizeHandler = null;
    this.config = {
      overlayColor: config.overlayColor || 'rgba(0, 0, 0, 0.7)',
      borderRadius: config.borderRadius || 8,
      padding: config.padding || 10,
      animationDuration: config.animationDuration || 300,
      ...config
    };

    this.createOverlay();
    this.createHighlight();
    this.createTooltip();
  }

  createOverlay() {
    this.overlay = document.createElement('div');
    this.overlay.id = 'tutorial-overlay';
    this.overlay.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: ${this.config.overlayColor};
      z-index: 9998;
      display: none;
      transition: opacity ${this.config.animationDuration}ms ease;
    `;
    document.body.appendChild(this.overlay);
  }

  createHighlight() {
    this.highlight = document.createElement('div');
    this.highlight.id = 'tutorial-highlight';
    this.highlight.style.cssText = `
      position: fixed;
      border: 3px solid rgba(255, 255, 255, 0.8);
      box-shadow: 0 0 0 9999px ${this.config.overlayColor};
      z-index: 9999;
      display: none;
      transition: all ${this.config.animationDuration}ms ease;
      pointer-events: none;
      border-radius: ${this.config.borderRadius}px;
    `;
    document.body.appendChild(this.highlight);
  }

  createTooltip() {
    this.tooltip = document.createElement('div');
    this.tooltip.id = 'tutorial-tooltip';
    this.tooltip.style.cssText = `
      position: fixed;
      background: var(--surface-color, #ffffff);
      color: var(--text-color, #333);
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
      z-index: 10000;
      display: none;
      max-width: 380px;
      font-family: Arial, sans-serif;
      animation: tooltipAppear ${this.config.animationDuration}ms ease;
    `;

    if (!document.getElementById('tutorial-styles')) {
      const style = document.createElement('style');
      style.id = 'tutorial-styles';
      style.textContent = `
        @keyframes tooltipAppear {
          from {
            opacity: 0;
            transform: scale(0.9);
          }
          to {
            opacity: 1;
            transform: scale(1);
          }
        }

        @keyframes pulse {
          0%, 100% {
            box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7), inset 0 0 0 3px rgba(255, 255, 255, 0.8);
          }
          50% {
            box-shadow: 0 0 0 15px rgba(255, 255, 255, 0), inset 0 0 0 3px rgba(255, 255, 255, 0.8);
          }
        }

        .tutorial-pulse {
          animation: pulse 2s infinite !important;
        }

        .tutorial-button {
          padding: 10px 20px;
          margin-right: 10px;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          font-size: 14px;
          font-weight: bold;
          transition: all 0.3s ease;
        }

        .tutorial-btn-next {
          background: var(--color-primario, #007bff);
          color: var(--color-blanco, #ffffff);
        }

        .tutorial-btn-next:hover {
          filter: brightness(0.95);
        }

        .tutorial-btn-skip {
          background: var(--surface-color, #f0f0f0);
          color: var(--text-color, #333);
          border: 1px solid var(--border-color, #e0e0e0);
        }

        .tutorial-btn-skip:hover {
          filter: brightness(0.98);
        }

        .tutorial-btn-prev {
          background: var(--color-muted, #6c757d);
          color: var(--color-blanco, #ffffff);
        }

        .tutorial-btn-prev:hover {
          filter: brightness(0.95);
        }

        body.tutorial-active {
          overflow: hidden !important;
        }
      `;
      document.head.appendChild(style);
    }

    document.body.appendChild(this.tooltip);
  }

  disableScroll() {
    document.body.classList.add('tutorial-active');
    document.body.style.overflow = 'hidden';
  }

  enableScroll() {
    document.body.classList.remove('tutorial-active');
    document.body.style.overflow = '';
  }

  blockClicks(e) {
    if (!this.isActive) return;
    if (e.target.classList && e.target.classList.contains('tutorial-button')) {
      return;
    }
    // Usar comprobaciones seguras: el target puede ser un nodo de texto sin `closest`
    const target = e.target;
    const hasClosest = target && typeof target.closest === 'function';
    if (hasClosest && target.closest('.tutorial-button')) {
      return;
    }
    if ((target && target.id === 'tutorial-tooltip') || (hasClosest && target.closest('#tutorial-tooltip'))) {
      return;
    }
    e.preventDefault();
    e.stopPropagation();
  }

  start() {
    if (this.steps.length === 0) return;
    // Calcular solo los pasos cuyos selectores existen en el DOM
    this._activeSteps = this.steps.filter(s => {
      try {
        return !!document.querySelector(s.selector);
      } catch (e) {
        return false;
      }
    });

    // Si no hay pasos visibles, usar la lista original como fallback
    if (!this._activeSteps || this._activeSteps.length === 0) {
      this._activeSteps = this.steps.slice();
    }

    this.isActive = true;
    this.currentStep = 0;
    this.disableScroll();
    this.overlay.style.display = 'block';
    this.blockClicksHandler = this.blockClicks.bind(this);
    this.blockWheelHandler = this.blockClicks.bind(this);
    document.addEventListener('click', this.blockClicksHandler, true);
    document.addEventListener('wheel', this.blockWheelHandler, { passive: false });
    this.showStep(0);
  }

  showStep(stepIndex) {
    if (!this._activeSteps) {
      this._activeSteps = this.steps.slice();
    }

    if (stepIndex < 0 || stepIndex >= this._activeSteps.length) {
      this.end();
      return;
    }

    this.currentStep = stepIndex;
    const step = this._activeSteps[stepIndex];
    const element = document.querySelector(step.selector);

    if (!element) {
      this.nextStep();
      return;
    }

    // Remover resize handler anterior si existe
    if (this.resizeHandler) {
      window.removeEventListener('resize', this.resizeHandler);
    }

    this.highlightElement(element);
    this.showTooltip(step, element);
    
    // Agregar listener para reposicionar si hay cambios de tamaño de ventana
    this.resizeHandler = () => this.updatePositions(element);
    window.addEventListener('resize', this.resizeHandler);

    if (step.onShow) {
      step.onShow();
    }
  }

  updatePositions(element) {
    if (!element || !this.isActive) return;
    
    const rect = element.getBoundingClientRect();
    const padding = this.config.padding;

    this.highlight.style.left = (rect.left - padding) + 'px';
    this.highlight.style.top = (rect.top - padding) + 'px';
    this.highlight.style.width = (rect.width + padding * 2) + 'px';
    this.highlight.style.height = (rect.height + padding * 2) + 'px';

    this.positionTooltip(rect);
  }

  highlightElement(element) {
    // Primero, hacer scroll hacia el elemento ANTES de obtener sus coordenadas
    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Esperar a que el scroll termine y luego actualizar el highlight
    setTimeout(() => {
      const rect = element.getBoundingClientRect();
      const padding = this.config.padding;

      this.highlight.style.left = (rect.left - padding) + 'px';
      this.highlight.style.top = (rect.top - padding) + 'px';
      this.highlight.style.width = (rect.width + padding * 2) + 'px';
      this.highlight.style.height = (rect.height + padding * 2) + 'px';
      this.highlight.style.display = 'block';

      if (this.config.usePulse) {
        this.highlight.classList.add('tutorial-pulse');
      } else {
        this.highlight.classList.remove('tutorial-pulse');
      }
    }, 150);
  }

  showTooltip(step, element) {
    let tooltipHtml = `<h3 style="margin-top: 0; color: var(--color-primario, #007bff); font-size: 16px;"><strong>Paso ${this.currentStep + 1} de ${this._activeSteps ? this._activeSteps.length : this.steps.length}</strong></h3>`;
    tooltipHtml += `<h4 style="margin: 8px 0; color: var(--text-color, #333); font-size: 15px;">${step.title}</h4>`;
    tooltipHtml += `<p style="margin: 10px 0; color: var(--text-muted, #666); font-size: 14px; line-height: 1.6;">${step.description}</p>`;

    let controls = '<div style="margin-top: 20px; text-align: right; display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap;">';

    if (this.currentStep > 0) {
      controls += '<button class="tutorial-button tutorial-btn-prev" onclick="window.currentTutorial.previousStep()">← Anterior</button>';
    }

    const totalSteps = this._activeSteps ? this._activeSteps.length : this.steps.length;
    if (this.currentStep < totalSteps - 1) {
      controls += '<button class="tutorial-button tutorial-btn-next" onclick="window.currentTutorial.nextStep()">Siguiente →</button>';
    } else {
      controls += '<button class="tutorial-button tutorial-btn-next" onclick="window.currentTutorial.end()">Finalizar ✓</button>';
    }

    controls += '<button class="tutorial-button tutorial-btn-skip" onclick="window.currentTutorial.end()">Salir</button>';
    controls += '</div>';

    this.tooltip.innerHTML = tooltipHtml + controls;
    this.tooltip.style.display = 'block';
    
    // Esperar a que el scroll finalice antes de posicionar el tooltip
    setTimeout(() => {
      this.positionTooltip(element.getBoundingClientRect());
    }, 150);
  }

  positionTooltip(elementRect) {
    const tooltipRect = this.tooltip.getBoundingClientRect();
    let top, left;
    const gap = 20;
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;

    // Intentar posicionar abajo del elemento
    if (elementRect.bottom + tooltipRect.height + gap < viewportHeight) {
      top = elementRect.bottom + gap;
    }
    // Si no cabe abajo, intentar arriba
    else if (elementRect.top - tooltipRect.height - gap > 0) {
      top = elementRect.top - tooltipRect.height - gap;
    }
    // Si tampoco cabe arriba, centrar en la pantalla
    else {
      top = viewportHeight / 2 - tooltipRect.height / 2;
    }

    // Posicionar horizontalmente
    left = elementRect.left + elementRect.width / 2 - tooltipRect.width / 2;

    // Ajustar si se sale por la izquierda
    if (left < gap) {
      left = gap;
    }
    // Ajustar si se sale por la derecha
    else if (left + tooltipRect.width + gap > viewportWidth) {
      left = viewportWidth - tooltipRect.width - gap;
    }

    // Asegurarse de que top nunca sea negativo
    if (top < gap) {
      top = gap;
    }

    this.tooltip.style.top = top + 'px';
    this.tooltip.style.left = left + 'px';
  }

  nextStep() {
    this.showStep(this.currentStep + 1);
  }

  previousStep() {
    this.showStep(this.currentStep - 1);
  }

  end() {
    this.isActive = false;
    this.enableScroll();
    this.overlay.style.display = 'none';
    this.highlight.style.display = 'none';
    this.tooltip.style.display = 'none';
    this.highlight.classList.remove('tutorial-pulse');
    
    // Remover event listeners
    if (this.blockClicksHandler) {
      document.removeEventListener('click', this.blockClicksHandler, true);
    }
    if (this.blockWheelHandler) {
      document.removeEventListener('wheel', this.blockWheelHandler);
    }
    if (this.resizeHandler) {
      window.removeEventListener('resize', this.resizeHandler);
    }
  }
}

window.currentTutorial = null;
