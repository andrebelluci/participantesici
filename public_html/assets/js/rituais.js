// Funções para a modal de imagem
function openImageModal(src) {
  const modal = document.getElementById('modal-image');
  document.getElementById('modal-image-content').src = src;
  modal.style.display = 'flex';
}

function closeImageModal() {
  document.getElementById('modal-image').style.display = 'none';
}

// Fechar modal ao clicar fora
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal')) {
    closeImageModal();
  }
});