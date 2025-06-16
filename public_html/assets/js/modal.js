// Abrir modal de imagem
function openImageModal(imageSrc) {
  const modal = document.getElementById('modal-image');
  const modalImage = document.getElementById('modal-image-content');
  modalImage.src = imageSrc;
  modal.classList.remove('hidden');
}

// Fechar modal de imagem
function closeImageModal() {
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

// Modal de confirmação
let confirmCallback = null;

function openConfirmModal(texto, callback) {
  document.getElementById('confirmModalText').innerText = texto;
  document.getElementById('confirmModal').classList.remove('hidden');

  confirmCallback = () => {
    callback();
    closeConfirmModal();
  };
}

function closeConfirmModal() {
  document.getElementById('confirmModal').classList.add('hidden');
  confirmCallback = null;
}

document.getElementById('confirmModalBtn').addEventListener('click', () => {
  if (typeof confirmCallback === 'function') {
    confirmCallback();
  }
});