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
  document.getElementById('confirmModalText').innerText = texto;
  document.getElementById('confirmModal').classList.remove('hidden');

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