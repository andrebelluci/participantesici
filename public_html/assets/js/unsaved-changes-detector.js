// ============= DETECTOR DE MUDANÇAS NÃO SALVAS - VERSÃO FINAL =============
// Tenta interceptar o máximo possível, aceita limitações do navegador

class UnsavedChangesDetector {
  constructor() {
    this.hasUnsavedChanges = false;
    this.originalFormData = new Map();
    this.trackedForms = [];
    this.trackedModals = [];
    this.modalChangesMap = new Map();
    this.isSubmitting = false;
    this.isConfirmedLeaving = false;
    this.isNavigatingHistory = false;

    this.init();
  }

  init() {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.setup());
    } else {
      this.setup();
    }
  }

  setup() {
    this.trackForms();
    this.trackModals();
    this.setupAdvancedInterception(); // Tenta interceptação avançada
    this.setupBeforeUnload(); // Fallback confiável
    this.setupLinkInterception(); // Links sempre funcionam
  }

  // ✅ TENTATIVA AVANÇADA: Intercepta o que conseguir
  setupAdvancedInterception() {
    try {
      // 1. Adiciona estado inicial para detecção
      if (!history.state || !history.state.unsavedDetector) {
        history.replaceState({ unsavedDetector: true, page: 'current' }, '', window.location.href);
      }

      // 2. Intercepta popstate (funciona em alguns casos)
      window.addEventListener('popstate', (e) => {
        if (this.hasAnyUnsavedChanges() && !this.isConfirmedLeaving && !this.isNavigatingHistory) {
          // Tenta cancelar navegação
          this.isNavigatingHistory = true;

          // Re-adiciona estado para "cancelar" navegação
          history.pushState({ unsavedDetector: true, page: 'current' }, '', window.location.href);

          // Mostra modal personalizada
          this.showUnsavedChangesModal(() => {
            // Confirmou saída
            this.isConfirmedLeaving = true;
            this.hasUnsavedChanges = false;
            this.clearAllModalChanges();
            this.isNavigatingHistory = false;

            // Executa navegação original
            setTimeout(() => {
              history.back();
            }, 10);
          }, () => {
            // Cancelou saída
            this.isNavigatingHistory = false;
          });

          return; // Tenta prevenir navegação
        }

        this.isNavigatingHistory = false;
      });

      // 3. Sobrescreve métodos de história (quando possível)
      const originalBack = history.back;
      const originalForward = history.forward;
      const originalGo = history.go;

      history.back = (...args) => {
        if (this.hasAnyUnsavedChanges() && !this.isConfirmedLeaving) {
          this.showUnsavedChangesModal(() => {
            this.isConfirmedLeaving = true;
            this.hasUnsavedChanges = false;
            this.clearAllModalChanges();
            originalBack.apply(history, args);
          });
        } else {
          originalBack.apply(history, args);
        }
      };

      history.forward = (...args) => {
        if (this.hasAnyUnsavedChanges() && !this.isConfirmedLeaving) {
          this.showUnsavedChangesModal(() => {
            this.isConfirmedLeaving = true;
            this.hasUnsavedChanges = false;
            this.clearAllModalChanges();
            originalForward.apply(history, args);
          });
        } else {
          originalForward.apply(history, args);
        }
      };

      history.go = (...args) => {
        if (this.hasAnyUnsavedChanges() && !this.isConfirmedLeaving) {
          this.showUnsavedChangesModal(() => {
            this.isConfirmedLeaving = true;
            this.hasUnsavedChanges = false;
            this.clearAllModalChanges();
            originalGo.apply(history, args);
          });
        } else {
          originalGo.apply(history, args);
        }
      };

    } catch (error) {
      // Se a interceptação avançada falhar, apenas continua
      console.warn('Interceptação avançada não suportada:', error);
    }
  }

  // ✅ FALLBACK CONFIÁVEL: beforeunload sempre funciona
  setupBeforeUnload() {
    window.addEventListener('beforeunload', (e) => {
      // Não interfere se estamos navegando via modal personalizada
      if (this.isNavigatingHistory) {
        return;
      }

      if (this.isConfirmedLeaving || this.isSubmitting) {
        return;
      }

      if (this.hasAnyUnsavedChanges()) {
        // Esta é a única confirmação 100% confiável para botão voltar/fechar aba
        e.preventDefault();
        e.returnValue = 'Você tem alterações não salvas. Deseja realmente sair?';
        return e.returnValue;
      }
    });
  }

  // ✅ SEMPRE FUNCIONA: Intercepta links clicados
  setupLinkInterception() {
    document.addEventListener('click', (e) => {
      const target = e.target.closest('a[href]');

      if (target && this.hasAnyUnsavedChanges() && !this.isSubmitting && !this.isConfirmedLeaving) {
        const href = target.getAttribute('href');

        if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
          e.preventDefault();

          this.showUnsavedChangesModal(() => {
            this.isConfirmedLeaving = true;
            this.hasUnsavedChanges = false;
            this.clearAllModalChanges();

            setTimeout(() => {
              window.location.href = href;
            }, 10);
          });
        }
      }
    });
  }

  hasAnyUnsavedChanges() {
    // Mudanças em formulários principais
    if (this.hasUnsavedChanges) {
      return true;
    }

    // Mudanças em modais abertas
    for (let [modalId, hasChanges] of this.modalChangesMap) {
      const modal = document.getElementById(modalId);
      const isModalVisible = modal && (
        modal.style.display === 'flex' ||
        !modal.classList.contains('hidden')
      );

      if (isModalVisible && hasChanges) {
        return true;
      }
    }

    return false;
  }

  clearAllModalChanges() {
    this.modalChangesMap.clear();
  }

  // ✅ MODAL PERSONALIZADA (para quando conseguimos interceptar)
  showUnsavedChangesModal(onConfirm, onCancel = null) {
    let confirmModal = document.getElementById('unsaved-changes-modal');

    if (!confirmModal) {
      confirmModal = this.createUnsavedChangesModal();
      document.body.appendChild(confirmModal);
    }

    const confirmBtn = confirmModal.querySelector('#confirm-discard');
    const cancelBtn = confirmModal.querySelector('#cancel-discard');

    // Remove listeners antigos para evitar duplicação
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    cancelBtn.replaceWith(cancelBtn.cloneNode(true));

    // Handler de confirmação
    confirmModal.querySelector('#confirm-discard').addEventListener('click', () => {
      confirmModal.style.display = 'none';
      this.hasUnsavedChanges = false;
      this.isConfirmedLeaving = true;
      this.clearAllModalChanges();
      onConfirm();
    });

    // Handler de cancelamento
    const handleCancel = () => {
      confirmModal.style.display = 'none';
      this.isConfirmedLeaving = false;
      if (onCancel) onCancel();
    };

    confirmModal.querySelector('#cancel-discard').addEventListener('click', handleCancel);

    // ESC para cancelar
    const handleEscape = (e) => {
      if (e.key === 'Escape') {
        handleCancel();
        document.removeEventListener('keydown', handleEscape);
      }
    };
    document.addEventListener('keydown', handleEscape);

    confirmModal.style.display = 'flex';
  }

  createUnsavedChangesModal() {
    const modal = document.createElement('div');
    modal.id = 'unsaved-changes-modal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-[9999]';

    modal.innerHTML = `
      <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative mx-4">
        <h2 class="text-xl font-bold mb-4 text-red-600">
          <i class="fa-solid fa-exclamation-triangle mr-2"></i>
          Alterações não salvas
        </h2>
        <p class="text-gray-700 mb-6">
          Você fez alterações que não foram salvas. Deseja realmente sair sem salvar?
        </p>
        <div class="flex justify-end gap-3">
          <button id="confirm-discard"
                  class="px-4 py-2 bg-[#00bfff] text-black rounded hover:bg-yellow-400 transition font-semibold">
            Sair sem salvar
          </button>
          <button id="cancel-discard"
                  class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-900 transition font-semibold">
            Cancelar
          </button>
        </div>
      </div>
    `;

    return modal;
  }

  // ============= MÉTODOS PÚBLICOS =============
  markAsSaved() {
    this.hasUnsavedChanges = false;
    this.isSubmitting = false;
    this.isConfirmedLeaving = false;
    this.isNavigatingHistory = false;
    this.clearAllModalChanges();

    this.trackedForms.forEach(form => {
      this.saveOriginalFormData(form);
    });
  }

  reset() {
    this.hasUnsavedChanges = false;
    this.isSubmitting = false;
    this.isConfirmedLeaving = false;
    this.isNavigatingHistory = false;
    this.originalFormData.clear();
    this.clearAllModalChanges();
  }

  forceLeave(url) {
    this.isConfirmedLeaving = true;
    this.hasUnsavedChanges = false;
    this.clearAllModalChanges();

    setTimeout(() => {
      window.location.href = url;
    }, 10);
  }

  // ============= RASTREAMENTO DE FORMULÁRIOS =============
  trackForms() {
    const mainForms = document.querySelectorAll('#formulario-participante, #formulario-ritual, #formulario-usuario');

    mainForms.forEach(form => {
      if (form) {
        this.trackedForms.push(form);
        this.saveOriginalFormData(form);
        this.attachFormListeners(form);
      }
    });
  }

  saveOriginalFormData(form) {
    const formData = new FormData(form);
    const data = {};

    for (let [key, value] of formData.entries()) {
      data[key] = value;
    }

    form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
      data[checkbox.name] = checkbox.checked;
    });

    this.originalFormData.set(form.id, data);
  }

  attachFormListeners(form) {
    form.addEventListener('input', () => this.checkForChanges(form));
    form.addEventListener('change', () => this.checkForChanges(form));

    form.addEventListener('submit', () => {
      this.isSubmitting = true;
    });
  }

  checkForChanges(form) {
    if (this.isSubmitting || this.isConfirmedLeaving) return;

    const currentData = this.getCurrentFormData(form);
    const originalData = this.originalFormData.get(form.id) || {};

    this.hasUnsavedChanges = !this.isFormDataEqual(currentData, originalData);
  }

  getCurrentFormData(form) {
    const formData = new FormData(form);
    const data = {};

    for (let [key, value] of formData.entries()) {
      data[key] = value;
    }

    form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
      data[checkbox.name] = checkbox.checked;
    });

    return data;
  }

  isFormDataEqual(data1, data2) {
    const keys1 = Object.keys(data1);
    const keys2 = Object.keys(data2);

    if (keys1.length !== keys2.length) return false;

    for (let key of keys1) {
      if (data1[key] !== data2[key]) return false;
    }

    return true;
  }

  // ============= RASTREAMENTO DE MODAIS =============
  trackModals() {
    const modals = [
      {
        id: 'modal-detalhes-inscricao',
        formId: 'form-detalhes-inscricao'
      },
      {
        id: 'modal-observacao',
        formId: 'form-observacao'
      }
    ];

    modals.forEach(modalConfig => {
      const modal = document.getElementById(modalConfig.id);
      const form = document.getElementById(modalConfig.formId);

      if (modal && form) {
        this.trackedModals.push({ modal, form, config: modalConfig });
        this.setupModalTracking(modal, form);
      }
    });
  }

  setupModalTracking(modal, form) {
    let modalOriginalData = {};
    const modalId = modal.id;

    this.modalChangesMap.set(modalId, false);

    const observer = new MutationObserver((mutations) => {
      mutations.forEach(mutation => {
        if (mutation.attributeName === 'style') {
          const isVisible = modal.style.display === 'flex' || !modal.classList.contains('hidden');

          if (isVisible && Object.keys(modalOriginalData).length === 0) {
            setTimeout(() => {
              modalOriginalData = this.getCurrentFormData(form);
              this.modalChangesMap.set(modalId, false);
            }, 100);
          } else if (!isVisible) {
            modalOriginalData = {};
            this.modalChangesMap.set(modalId, false);
          }
        }
      });
    });

    observer.observe(modal, { attributes: true, attributeFilter: ['style', 'class'] });

    const checkModalChanges = () => {
      if (Object.keys(modalOriginalData).length > 0) {
        const currentData = this.getCurrentFormData(form);
        const hasChanges = !this.isFormDataEqual(currentData, modalOriginalData);
        this.modalChangesMap.set(modalId, hasChanges);
      }
    };

    form.addEventListener('input', checkModalChanges);
    form.addEventListener('change', checkModalChanges);

    this.interceptModalClose(modal, modalId);
  }

  // ✅ SEMPRE FUNCIONA: Intercepta fechamento de modais
  interceptModalClose(modal, modalId) {
    const hasChanges = () => this.modalChangesMap.get(modalId) || false;

    // Clique fora da modal
    modal.addEventListener('click', (e) => {
      if (e.target === modal && hasChanges()) {
        e.preventDefault();
        e.stopPropagation();
        this.showUnsavedChangesModal(() => {
          modal.style.display = 'none';
          this.modalChangesMap.set(modalId, false);
          this.enableScroll();
        });
      }
    });

    // ESC na modal
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.style.display === 'flex' && hasChanges()) {
        e.preventDefault();
        this.showUnsavedChangesModal(() => {
          modal.style.display = 'none';
          this.modalChangesMap.set(modalId, false);
          this.enableScroll();
        });
      }
    });

    // Botões de fechar
    const closeButtons = modal.querySelectorAll('[onclick*="fechar"], .fa-window-close, .fa-times, .fa-x');
    closeButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        if (hasChanges()) {
          e.preventDefault();
          e.stopPropagation();
          this.showUnsavedChangesModal(() => {
            modal.style.display = 'none';
            this.modalChangesMap.set(modalId, false);
            this.enableScroll();
          });
        }
      });
    });
  }

  enableScroll() {
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('position');
    document.body.style.removeProperty('top');
    document.body.style.removeProperty('width');
  }
}

// ============= INICIALIZAÇÃO GLOBAL =============
let unsavedChangesDetector;

document.addEventListener('DOMContentLoaded', () => {
  unsavedChangesDetector = new UnsavedChangesDetector();
});

// ✅ INTEGRAÇÃO COM TOASTS DE SUCESSO
document.addEventListener('DOMContentLoaded', () => {
  const originalShowToast = window.showToast;

  if (originalShowToast) {
    window.showToast = function (message, type, duration) {
      const result = originalShowToast.call(this, message, type, duration);

      if (type === 'success' &&
        (message.includes('salv') || message.includes('criado') || message.includes('atualizado'))) {
        setTimeout(() => {
          if (unsavedChangesDetector) {
            unsavedChangesDetector.markAsSaved();
          }
        }, 100);
      }

      return result;
    };
  }
});