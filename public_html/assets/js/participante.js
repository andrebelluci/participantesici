// participante.js - Funcionalidades comuns para novo e editar

// Função para mostrar toast
function showToast(message, type = 'error') {
  const backgroundColor = type === 'success' ? '#16a34a' : '#dc2626';
  Toastify({
    text: message,
    duration: type === 'success' ? 4000 : 5000,
    close: true,
    gravity: "top",
    position: "right",
    backgroundColor: backgroundColor,
    stopOnFocus: true,
  }).showToast();
}

// ============= MÁSCARAS =============
function mascaraCPF(input) {
  let valor = input.value.replace(/\D/g, '');
  if (valor.length > 11) valor = valor.slice(0, 11);
  valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
  valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
  valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  input.value = valor;
}

function mascaraCelular(input) {
  let valor = input.value.replace(/\D/g, '');
  if (valor.length > 11) valor = valor.slice(0, 11);
  valor = valor.replace(/^(\d{2})(\d)/g, '($1) $2');
  valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
  input.value = valor;
}

function mascaraCEP(input) {
  let valor = input.value.replace(/\D/g, '');
  if (valor.length > 8) valor = valor.slice(0, 8);
  valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
  input.value = valor;
}

// ============= UPLOAD DE IMAGEM =============
const fileInput = document.getElementById('foto-input');
const uploadArea = document.getElementById('upload-area');
const previewContainer = document.getElementById('preview-container');
const previewImage = document.getElementById('preview-image');
const substituirBtn = document.getElementById('substituir-imagem-btn');
const excluirBtn = document.getElementById('excluir-imagem-btn');

// Variáveis para crop
let cropper = null;
let originalImageSrc = null;

// Elementos do DOM para crop
const cropBtn = document.getElementById('crop-image-btn');
const cropModal = document.getElementById('crop-modal');
const cropImage = document.getElementById('crop-image');
const applyCropBtn = document.getElementById('apply-crop');
const cancelCropBtn = document.getElementById('cancel-crop');
const closeCropModalBtn = document.getElementById('close-crop-modal');

function openFileSelector() {
  fileInput?.click();
}

function showPreview(file) {
  const reader = new FileReader();
  reader.onload = (e) => {
    const imageSrc = e.target.result;
    originalImageSrc = imageSrc;

    previewImage.src = imageSrc;
    uploadArea?.classList.add('hidden');
    previewContainer?.classList.remove('hidden');

    showToast('Imagem carregada! Use "Ajustar" para fazer crop.', 'success');
  };
  reader.readAsDataURL(file);
}

function hidePreview() {
  previewImage.src = '#';
  uploadArea?.classList.remove('hidden');
  previewContainer?.classList.add('hidden');
  fileInput.value = '';
  originalImageSrc = null;

  // ✅ ADICIONAR FLAG PARA REMOÇÃO
  const removerFotoInput = document.createElement('input');
  removerFotoInput.type = 'hidden';
  removerFotoInput.name = 'remover_foto';
  removerFotoInput.value = '1';
  document.getElementById('formulario-participante').appendChild(removerFotoInput);
}

function validateFile(file) {
  if (!file.type.startsWith('image/')) {
    showToast('Por favor, selecione apenas arquivos de imagem.');
    return false;
  }

  const maxSize = 5 * 1024 * 1024; // 5MB
  if (file.size > maxSize) {
    showToast('A imagem deve ter no máximo 5MB.');
    return false;
  }

  return true;
}

// ============= CROP DE IMAGEM =============
function openCropModal() {
  if (!originalImageSrc) {
    showToast('Nenhuma imagem carregada para ajustar.');
    return;
  }

  cropImage.src = originalImageSrc;
  cropModal?.classList.remove('hidden');
  document.body.style.overflow = 'hidden';

  cropImage.onload = function () {
    if (cropper) {
      cropper.destroy();
    }

    cropper = new Cropper(cropImage, {
      aspectRatio: 1,
      viewMode: 2,
      guides: true,
      center: true,
      highlight: true,
      cropBoxMovable: true,
      cropBoxResizable: true,
      toggleDragModeOnDblclick: false,
      minCropBoxWidth: 100,
      minCropBoxHeight: 100
    });
  };
}

function closeCropModal() {
  cropModal?.classList.add('hidden');
  document.body.style.overflow = 'auto';

  if (cropper) {
    cropper.destroy();
    cropper = null;
  }
}

function applyCrop() {
  if (!cropper) return;

  const canvas = cropper.getCroppedCanvas({
    width: 400,
    height: 400,
    imageSmoothingEnabled: true,
    imageSmoothingQuality: 'high'
  });

  canvas.toBlob((blob) => {
    const reader = new FileReader();
    reader.onload = function (e) {
      const croppedImageSrc = e.target.result;

      previewImage.src = croppedImageSrc;

      const fotoCropadaInput = document.getElementById('foto-cropada');
      if (fotoCropadaInput) {
        fotoCropadaInput.value = croppedImageSrc;
      }

      closeCropModal();
      showToast('Imagem ajustada com sucesso!', 'success');
    };
    reader.readAsDataURL(blob);
  }, 'image/jpeg', 0.9);
}

// ============= VALIDAÇÕES =============
function validarEmail(email) {
  const regex = /^[a-zA-Z0-9._%+-]{3,}@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  return regex.test(email);
}

function setupFormValidation() {
  const form = document.getElementById('formulario-participante');
  if (!form) return;

  form.addEventListener('submit', function (event) {
    const emailInput = document.getElementById('email');
    const emailValue = emailInput?.value.trim();

    // Validar email
    if (emailValue && !validarEmail(emailValue)) {
      event.preventDefault();
      showToast('Por favor, digite um e-mail válido.');
      emailInput.focus();
      return;
    }

    // Remover máscaras antes do envio
    const cpfInput = document.getElementById('cpf');
    const celularInput = document.getElementById('celular');
    const cepInput = document.getElementById('cep');

    if (cpfInput) cpfInput.value = cpfInput.value.replace(/\D/g, '');
    if (celularInput) celularInput.value = celularInput.value.replace(/\D/g, '');
    if (cepInput) cepInput.value = cepInput.value.replace(/\D/g, '');
  });
}

// ✅ NOVA FUNÇÃO PARA CARREGAR IMAGEM EXISTENTE
function loadExistingImage() {
  // Pega o caminho da foto do debug ou de um campo hidden
  const fotoPath = document.querySelector('[data-foto-path]')?.dataset.fotoPath;

  if (fotoPath && previewImage && previewContainer && uploadArea) {
    // Define o src da imagem com o caminho correto
    previewImage.src = fotoPath;
    originalImageSrc = previewImage.src;

    // Mostra o preview e esconde o upload area
    previewContainer.classList.remove('hidden');
    uploadArea.classList.add('hidden');

    // Se a imagem falhar ao carregar, volta para o upload
    previewImage.onerror = function () {
      previewContainer.classList.add('hidden');
      uploadArea.classList.remove('hidden');
      previewImage.src = '#';
      originalImageSrc = null;
    };
  }
}

// ============= EVENT LISTENERS GERAIS =============
document.addEventListener('DOMContentLoaded', function () {
  // Aplicar máscaras aos campos que já têm valor (para página de editar)
  const cpfInput = document.getElementById('cpf');
  const cepInput = document.getElementById('cep');
  const celularInput = document.getElementById('celular');

  if (cpfInput?.value) mascaraCPF(cpfInput);
  if (cepInput?.value) mascaraCEP(cepInput);
  if (celularInput?.value) mascaraCelular(celularInput);

  // ✅ ADICIONAR ESTE BLOCO PARA CARREGAR IMAGEM EXISTENTE
  loadExistingImage();

  // Inicializar funcionalidades
  setupFormValidation();
});

// Event listeners para upload
uploadArea?.addEventListener('click', openFileSelector);
substituirBtn?.addEventListener('click', openFileSelector);

fileInput?.addEventListener('change', (e) => {
  const file = e.target.files[0];
  if (file && validateFile(file)) {
    showPreview(file);
  }
});

excluirBtn?.addEventListener('click', () => {
  hidePreview();
  showToast('Imagem removida.', 'success');
});

// Event listeners para crop
cropBtn?.addEventListener('click', openCropModal);
applyCropBtn?.addEventListener('click', applyCrop);
cancelCropBtn?.addEventListener('click', closeCropModal);
closeCropModalBtn?.addEventListener('click', closeCropModal);