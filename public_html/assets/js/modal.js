// Abrir modal de imagem
function openImageModal(imageSrc) {
  const modal = document.getElementById('modal-image');
  const modalImage = document.getElementById('modal-image-content');
  modalImage.src = imageSrc;
  modal.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

// Fechar modal de imagem
function closeImageModal() {
  document.body.style.overflow = 'auto';
  const modal = document.getElementById('modal-image');
  modal.classList.add('hidden');
}

// Fechar modal ao clicar fora da imagem
document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById('modal-image');
  if (modal) {
    modal.addEventListener("click", function (event) {
      if (event.target === modal) {
        closeImageModal();
      }
    });
  }
});

// Fechar modal com ESC
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const imageModal = document.getElementById('modal-image');
    if (imageModal && !imageModal.classList.contains('hidden')) {
      closeImageModal();
    }
  }
});

// Modal de confirmação
let confirmCallback = null;

function openConfirmModal(texto, callback) {
  const confirmModal = document.getElementById('confirmModal');
  if (!confirmModal) return;

  document.getElementById('confirmModalText').innerText = texto;
  confirmModal.classList.remove('hidden');

  // Garantir que a modal apareça acima de outras modais
  confirmModal.style.zIndex = '9999';
  const modalContent = confirmModal.querySelector('div');
  if (modalContent) {
    modalContent.style.zIndex = '10000';
  }

  confirmCallback = () => {
    callback();
    closeConfirmModal();
  };
  document.body.style.overflow = 'hidden';
}

function closeConfirmModal() {
  document.getElementById('confirmModal').classList.add('hidden');
  confirmCallback = null;
  document.body.style.overflow = 'auto';
}

// Modal unificada: motivo do status do participante
function abrirModalMotivoStatus(tituloStatus, motivo) {
  const modal = document.getElementById('modal-motivo-status');
  const tituloEl = document.getElementById('modal-motivo-status-titulo');
  const motivoContent = document.getElementById('modal-motivo-status-content');

  if (modal && motivoContent) {
    if (tituloEl) {
      const span = tituloEl.querySelector('span');
      if (span) {
        span.textContent = tituloStatus || 'Status do participante';
      }
    }
    motivoContent.textContent = motivo || 'Motivo não informado';
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }
}

function fecharModalMotivoStatus() {
  const modal = document.getElementById('modal-motivo-status');
  if (modal) {
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
  }
}

/** @deprecated Use abrirModalMotivoStatus */
function abrirModalMotivoBloqueioParticipante(motivo) {
  abrirModalMotivoStatus('Não pode participar', motivo);
}

/** @deprecated Use abrirModalMotivoStatus */
function abrirModalMotivoBloqueioRitual(motivo) {
  abrirModalMotivoStatus('Não pode participar', motivo);
}

/** @deprecated Use abrirModalMotivoStatus */
function abrirModalMotivoBloqueio() {
  const el = document.getElementById('modal-motivo-status-content');
  abrirModalMotivoStatus('Status do participante', el ? el.textContent : '');
}

document.addEventListener('click', function (event) {
  const btnMotivo = event.target.closest('.js-abrir-motivo-status');
  if (!btnMotivo) {
    return;
  }
  event.preventDefault();
  const titulo = btnMotivo.getAttribute('data-status-titulo') || 'Status do participante';
  const motivo = btnMotivo.getAttribute('data-status-motivo') || 'Motivo não informado';
  abrirModalMotivoStatus(titulo, motivo);
});

document.addEventListener('DOMContentLoaded', function () {
  const modalStatus = document.getElementById('modal-motivo-status');
  if (modalStatus) {
    modalStatus.addEventListener('click', function (event) {
      if (event.target === modalStatus) {
        fecharModalMotivoStatus();
      }
    });
  }

  const confirmBtn = document.getElementById('confirmModalBtn');
  if (confirmBtn && !confirmBtn.dataset.listenerAttached) {
    confirmBtn.dataset.listenerAttached = '1';
    confirmBtn.addEventListener('click', () => {
      if (typeof confirmCallback === 'function') {
        confirmCallback();
      }
    });
  }
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const modalStatus = document.getElementById('modal-motivo-status');
    if (modalStatus && !modalStatus.classList.contains('hidden')) {
      fecharModalMotivoStatus();
    }
  }
});