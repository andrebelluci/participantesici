// ============= DETECTOR DE MUDANÇAS NÃO SALVAS - CORRIGIDO =============

class UnsavedChangesDetector {
  constructor() {
    this.hasUnsavedChanges = false;
    this.originalFormData = new Map();
    this.trackedForms = [];
    this.trackedModals = [];
    this.isSubmitting = false;
    this.isConfirmedLeaving = false; // ✅ NOVA FLAG para controlar saída confirmada

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
    this.setupBeforeUnload();
    this.setupNavigationBlocking();
  }

  // ✅ CORRIGIDO: beforeunload agora respeita a confirmação
  setupBeforeUnload() {
    window.addEventListener('beforeunload', (e) => {
      // Se já foi confirmado que pode sair, não bloqueia
      if (this.isConfirmedLeaving || this.isSubmitting) {
        return;
      }

      if (this.hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = 'Você tem alterações não salvas. Deseja realmente sair?';
        return e.returnValue;
      }
    });
  }

  setupNavigationBlocking() {
    document.addEventListener('click', (e) => {
      const target = e.target.closest('a[href]');

      if (target && this.hasUnsavedChanges && !this.isSubmitting && !this.isConfirmedLeaving) {
        const href = target.getAttribute('href');

        if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
          e.preventDefault();

          this.showUnsavedChangesModal(() => {
            // ✅ MARCA QUE A SAÍDA FOI CONFIRMADA
            this.isConfirmedLeaving = true;

            // ✅ PEQUENO DELAY para garantir que a flag seja setada
            setTimeout(() => {
              window.location.href = href;
            }, 10);
          });
        }
      }
    });
  }

  // ✅ CORRIGIDO: Modal de confirmação com melhor controle
  showUnsavedChangesModal(onConfirm) {
    let confirmModal = document.getElementById('unsaved-changes-modal');

    if (!confirmModal) {
      confirmModal = this.createUnsavedChangesModal();
      document.body.appendChild(confirmModal);
    }

    const confirmBtn = confirmModal.querySelector('#confirm-discard');
    const cancelBtn = confirmModal.querySelector('#cancel-discard');

    // Remove listeners antigos
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    cancelBtn.replaceWith(cancelBtn.cloneNode(true));

    // Novos listeners
    confirmModal.querySelector('#confirm-discard').addEventListener('click', () => {
      confirmModal.style.display = 'none';

      // ✅ RESETA estado antes de executar callback
      this.hasUnsavedChanges = false;
      this.isConfirmedLeaving = true;

      onConfirm();
    });

    confirmModal.querySelector('#cancel-discard').addEventListener('click', () => {
      confirmModal.style.display = 'none';
      // ✅ RESETA flag de confirmação ao cancelar
      this.isConfirmedLeaving = false;
    });

    // ✅ FECHA com ESC na modal de confirmação
    const handleEscape = (e) => {
      if (e.key === 'Escape') {
        confirmModal.style.display = 'none';
        this.isConfirmedLeaving = false;
        document.removeEventListener('keydown', handleEscape);
      }
    };
    document.addEventListener('keydown', handleEscape);

    confirmModal.style.display = 'flex';
  }

  // ✅ MÉTODOS PÚBLICOS ATUALIZADOS
  markAsSaved() {
    this.hasUnsavedChanges = false;
    this.isSubmitting = false;
    this.isConfirmedLeaving = false; // ✅ RESETA flag

    this.trackedForms.forEach(form => {
      this.saveOriginalFormData(form);
    });
  }

  reset() {
    this.hasUnsavedChanges = false;
    this.isSubmitting = false;
    this.isConfirmedLeaving = false; // ✅ RESETA flag
    this.originalFormData.clear();
  }

  // ✅ NOVO: Método para forçar saída sem confirmação
  forceLeave(url) {
    this.isConfirmedLeaving = true;
    this.hasUnsavedChanges = false;

    setTimeout(() => {
      window.location.href = url;
    }, 10);
  }

  // ============= RESTO DO CÓDIGO PERMANECE IGUAL =============
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

  // ✅ MÉTODOS DE MODAL (mantidos iguais, só para completude)
  trackModals() {
    const modals = [
      {
        id: 'modal-detalhes-inscricao',
        formId: 'form-detalhes-inscricao',
        closeButtons: ['fecharModalDetalhes()']
      },
      {
        id: 'modal-observacao',
        formId: 'form-observacao',
        closeButtons: ['fecharModalObservacao()']
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
    let modalHasChanges = false;

    const observer = new MutationObserver((mutations) => {
      mutations.forEach(mutation => {
        if (mutation.attributeName === 'style') {
          const isVisible = modal.style.display === 'flex' || !modal.classList.contains('hidden');

          if (isVisible && Object.keys(modalOriginalData).length === 0) {
            setTimeout(() => {
              modalOriginalData = this.getCurrentFormData(form);
              modalHasChanges = false;
            }, 100);
          } else if (!isVisible) {
            modalOriginalData = {};
            modalHasChanges = false;
          }
        }
      });
    });

    observer.observe(modal, { attributes: true, attributeFilter: ['style', 'class'] });

    form.addEventListener('input', () => {
      if (Object.keys(modalOriginalData).length > 0) {
        const currentData = this.getCurrentFormData(form);
        modalHasChanges = !this.isFormDataEqual(currentData, modalOriginalData);
      }
    });

    form.addEventListener('change', () => {
      if (Object.keys(modalOriginalData).length > 0) {
        const currentData = this.getCurrentFormData(form);
        modalHasChanges = !this.isFormDataEqual(currentData, modalOriginalData);
      }
    });

    this.interceptModalClose(modal, () => modalHasChanges);
  }

  interceptModalClose(modal, hasChangesCallback) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal && hasChangesCallback()) {
        e.preventDefault();
        e.stopPropagation();
        this.showUnsavedChangesModal(() => {
          modal.style.display = 'none';
          this.enableScroll();
        });
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.style.display === 'flex' && hasChangesCallback()) {
        e.preventDefault();
        this.showUnsavedChangesModal(() => {
          modal.style.display = 'none';
          this.enableScroll();
        });
      }
    });

    const closeButtons = modal.querySelectorAll('[onclick*="fechar"], .fa-window-close, .fa-times, .fa-x');
    closeButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        if (hasChangesCallback()) {
          e.preventDefault();
          e.stopPropagation();
          this.showUnsavedChangesModal(() => {
            modal.style.display = 'none';
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