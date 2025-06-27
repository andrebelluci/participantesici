// ============= DETECTOR DE MUDANÇAS NÃO SALVAS =============

class UnsavedChangesDetector {
  constructor() {
    this.hasUnsavedChanges = false;
    this.originalFormData = new Map();
    this.trackedForms = [];
    this.trackedModals = [];
    this.isSubmitting = false;

    this.init();
  }

  init() {
    // Aguarda DOM carregar completamente
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

  // ============= TRACKING DE FORMULÁRIOS =============
  trackForms() {
    // Formulários principais (editar/novo)
    const mainForms = document.querySelectorAll('#formulario-participante, #formulario-ritual');

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

    // Salva dados de inputs, selects e textareas
    for (let [key, value] of formData.entries()) {
      data[key] = value;
    }

    // Salva dados de checkboxes não marcados
    form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
      data[checkbox.name] = checkbox.checked;
    });

    this.originalFormData.set(form.id, data);
  }

  attachFormListeners(form) {
    // Detecta mudanças em todos os campos
    form.addEventListener('input', () => this.checkForChanges(form));
    form.addEventListener('change', () => this.checkForChanges(form));

    // Detecta submit para não alertar ao salvar
    form.addEventListener('submit', () => {
      this.isSubmitting = true;
    });
  }

  checkForChanges(form) {
    if (this.isSubmitting) return;

    const currentData = this.getCurrentFormData(form);
    const originalData = this.originalFormData.get(form.id) || {};

    // Compara dados atuais com originais
    this.hasUnsavedChanges = !this.isFormDataEqual(currentData, originalData);

    // Debug (remover em produção)
    console.log('Mudanças não salvas:', this.hasUnsavedChanges);
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

  // ============= TRACKING DE MODALS =============
  trackModals() {
    // Modals de inscrição e observação
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

    // Quando modal abre, salva estado inicial
    const observer = new MutationObserver((mutations) => {
      mutations.forEach(mutation => {
        if (mutation.attributeName === 'style') {
          const isVisible = modal.style.display === 'flex' || !modal.classList.contains('hidden');

          if (isVisible && Object.keys(modalOriginalData).length === 0) {
            // Modal acabou de abrir
            setTimeout(() => {
              modalOriginalData = this.getCurrentFormData(form);
              modalHasChanges = false;
            }, 100);
          } else if (!isVisible) {
            // Modal fechou, reseta tracking
            modalOriginalData = {};
            modalHasChanges = false;
          }
        }
      });
    });

    observer.observe(modal, { attributes: true, attributeFilter: ['style', 'class'] });

    // Detecta mudanças na modal
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

    // Intercepta fechamento da modal
    this.interceptModalClose(modal, () => modalHasChanges);
  }

  interceptModalClose(modal, hasChangesCallback) {
    // Intercepta clique no fundo da modal
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

    // Intercepta ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.style.display === 'flex' && hasChangesCallback()) {
        e.preventDefault();
        this.showUnsavedChangesModal(() => {
          modal.style.display = 'none';
          this.enableScroll();
        });
      }
    });

    // Intercepta botões de fechar
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

  // ============= INTERCEPTAÇÃO DE NAVEGAÇÃO =============
  setupBeforeUnload() {
    window.addEventListener('beforeunload', (e) => {
      if (this.hasUnsavedChanges && !this.isSubmitting) {
        e.preventDefault();
        e.returnValue = 'Você tem alterações não salvas. Deseja realmente sair?';
        return e.returnValue;
      }
    });
  }

  setupNavigationBlocking() {
    // Intercepta links de voltar
    document.addEventListener('click', (e) => {
      const target = e.target.closest('a[href]');

      if (target && this.hasUnsavedChanges && !this.isSubmitting) {
        const href = target.getAttribute('href');

        // Verifica se é um link de navegação (não âncora)
        if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
          e.preventDefault();

          this.showUnsavedChangesModal(() => {
            window.location.href = href;
          });
        }
      }
    });
  }

  // ============= MODAL DE CONFIRMAÇÃO =============
  showUnsavedChangesModal(onConfirm) {
    // Verifica se já existe modal de confirmação
    let confirmModal = document.getElementById('unsaved-changes-modal');

    if (!confirmModal) {
      confirmModal = this.createUnsavedChangesModal();
      document.body.appendChild(confirmModal);
    }

    // Configura ações
    const confirmBtn = confirmModal.querySelector('#confirm-discard');
    const cancelBtn = confirmModal.querySelector('#cancel-discard');

    // Remove listeners antigos
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    cancelBtn.replaceWith(cancelBtn.cloneNode(true));

    // Novos listeners
    confirmModal.querySelector('#confirm-discard').addEventListener('click', () => {
      confirmModal.style.display = 'none';
      onConfirm();
    });

    confirmModal.querySelector('#cancel-discard').addEventListener('click', () => {
      confirmModal.style.display = 'none';
    });

    // Mostra modal
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
                  class="px-4 py-2 bg-[#00bfff]  text-black rounded hover:bg-yellow-400 transition font-semibold">
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

    // Atualiza dados originais para o estado atual
    this.trackedForms.forEach(form => {
      this.saveOriginalFormData(form);
    });
  }

  reset() {
    this.hasUnsavedChanges = false;
    this.isSubmitting = false;
    this.originalFormData.clear();
  }
}

// ============= INICIALIZAÇÃO GLOBAL =============
let unsavedChangesDetector;

// Inicializa quando DOM carregar
document.addEventListener('DOMContentLoaded', () => {
  unsavedChangesDetector = new UnsavedChangesDetector();
});

// Marca como salvo quando formulário for enviado com sucesso
document.addEventListener('DOMContentLoaded', () => {
  // Detecta toasts de sucesso para marcar como salvo
  const originalShowToast = window.showToast;

  if (originalShowToast) {
    window.showToast = function (message, type, duration) {
      const result = originalShowToast.call(this, message, type, duration);

      // Se foi um toast de sucesso relacionado a salvar
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