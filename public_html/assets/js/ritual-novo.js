// Função para abrir a modal de imagem
function openImageModal(imageSrc) {
  const modal = document.getElementById('modal-image');
  const modalImage = document.getElementById('modal-image-content');
  modalImage.src = imageSrc;
  modal.style.display = 'flex';
}

// Função para fechar a modal de imagem
function closeImageModal() {
  const modal = document.getElementById('modal-image');
  modal.style.display = 'none';
}

// Preview da imagem
const fileInput = document.getElementById('foto-input');
const adicionarImagemBtn = document.getElementById('adicionar-imagem-btn');
const previewContainer = document.getElementById('preview-container');
const previewImage = document.getElementById('preview-image');
const excluirImagemBtn = document.getElementById('excluir-imagem-btn');

adicionarImagemBtn.addEventListener('click', () => {
  fileInput.click();
});

fileInput.addEventListener('change', () => {
  const file = fileInput.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      previewImage.src = e.target.result;
      previewContainer.style.display = 'block';
      adicionarImagemBtn.style.display = 'none';
    };
    reader.readAsDataURL(file);
  }
});

excluirImagemBtn.addEventListener('click', () => {
  previewImage.src = '#';
  previewContainer.style.display = 'none';
  adicionarImagemBtn.style.display = 'inline-block';
  fileInput.value = '';
});

// Abrir modal ao clicar na imagem de preview
previewImage.addEventListener('click', () => {
  openImageModal(previewImage.src);
});

document.addEventListener("DOMContentLoaded", function () {
  const modals = document.querySelectorAll(".modal");

  modals.forEach(modal => {
    modal.addEventListener("click", function (event) {
      if (event.target === modal) {
        modal.style.display = "none";
      }
    });
  });
});