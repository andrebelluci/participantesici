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

document.getElementById('confirmModalBtn').addEventListener('click', () => {
  if (typeof confirmCallback === 'function') {
    confirmCallback();
  }
});

// Modal de Motivo de Bloqueio (Participantes)
function abrirModalMotivoBloqueioParticipante(motivo) {
  const modal = document.getElementById('modal-motivo-bloqueio-participante');
  const motivoContent = document.getElementById('motivo-bloqueio-participante-content');

  if (modal && motivoContent) {
    motivoContent.textContent = motivo || 'Motivo não informado';
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }
}

function fecharModalMotivoBloqueioParticipante() {
  const modal = document.getElementById('modal-motivo-bloqueio-participante');
  if (modal) {
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
  }
}

// Modal de Motivo de Bloqueio (Rituais)
function abrirModalMotivoBloqueioRitual(motivo) {
  const modal = document.getElementById('modal-motivo-bloqueio-ritual');
  const motivoContent = document.getElementById('motivo-bloqueio-ritual-content');

  if (modal && motivoContent) {
    motivoContent.textContent = motivo || 'Motivo não informado';
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }
}

function fecharModalMotivoBloqueioRitual() {
  const modal = document.getElementById('modal-motivo-bloqueio-ritual');
  if (modal) {
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
  }
}

// Modal de Motivo de Bloqueio (Visualizar Participante)
function abrirModalMotivoBloqueio() {
  const modal = document.getElementById('modal-motivo-bloqueio');
  if (modal) {
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }
}

function fecharModalMotivoBloqueio() {
  const modal = document.getElementById('modal-motivo-bloqueio');
  if (modal) {
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
  }
}

// Fechar modais de motivo ao clicar fora
document.addEventListener("DOMContentLoaded", function () {
  // Modal motivo-bloqueio-participante
  const modalParticipante = document.getElementById('modal-motivo-bloqueio-participante');
  if (modalParticipante) {
    modalParticipante.addEventListener("click", function (event) {
      if (event.target === modalParticipante) {
        fecharModalMotivoBloqueioParticipante();
      }
    });
  }

  // Modal motivo-bloqueio-ritual
  const modalRitual = document.getElementById('modal-motivo-bloqueio-ritual');
  if (modalRitual) {
    modalRitual.addEventListener("click", function (event) {
      if (event.target === modalRitual) {
        fecharModalMotivoBloqueioRitual();
      }
    });
  }

  // Modal motivo-bloqueio (visualizar)
  const modalVisualizar = document.getElementById('modal-motivo-bloqueio');
  if (modalVisualizar) {
    modalVisualizar.addEventListener("click", function (event) {
      if (event.target === modalVisualizar) {
        fecharModalMotivoBloqueio();
      }
    });
  }
});

// Fechar modais de motivo com ESC
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const modalParticipante = document.getElementById('modal-motivo-bloqueio-participante');
    if (modalParticipante && !modalParticipante.classList.contains('hidden')) {
      fecharModalMotivoBloqueioParticipante();
      return;
    }

    const modalRitual = document.getElementById('modal-motivo-bloqueio-ritual');
    if (modalRitual && !modalRitual.classList.contains('hidden')) {
      fecharModalMotivoBloqueioRitual();
      return;
    }

    const modalVisualizar = document.getElementById('modal-motivo-bloqueio');
    if (modalVisualizar && !modalVisualizar.classList.contains('hidden')) {
      fecharModalMotivoBloqueio();
      return;
    }
  }
});