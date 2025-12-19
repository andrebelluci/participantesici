// participante.js - Funcionalidades comuns para novo e editar
let imageManuallyRemoved = false;

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
let processedImageData = null; // ✅ NOVA: Armazena imagem processada (cropada e comprimida)

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
  // ✅ CORREÇÃO: Reset do estado ao carregar nova imagem
  imageManuallyRemoved = false;

  const reader = new FileReader();
  reader.onload = (e) => {
    const imageSrc = e.target.result;
    originalImageSrc = imageSrc;

    previewImage.src = imageSrc;
    uploadArea?.classList.add('hidden');
    previewContainer?.classList.remove('hidden');

    showToast('Imagem carregada!', 'success');

    // Abre automaticamente o modal de crop
    setTimeout(() => {
      openCropModal();
    }, 500);
  };
  reader.readAsDataURL(file);
}

function hidePreview() {
  // ✅ Marca que foi remoção manual
  imageManuallyRemoved = true;

  // ✅ Remove o handler de erro temporariamente
  previewImage.onerror = null;

  // Limpa dados
  previewImage.src = '#';
  uploadArea?.classList.remove('hidden');
  previewContainer?.classList.add('hidden');
  fileInput.value = '';
  originalImageSrc = null;
  processedImageData = null; // ✅ NOVA: Limpa imagem processada

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

// ✅ NOVA FUNÇÃO: Comprime imagem automaticamente
function compressImageFromDataUrl(dataUrl, maxWidth = 400, maxHeight = 400, quality = 0.8) {
  return new Promise((resolve) => {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const img = new Image();

    img.onload = function () {
      // Calcula novas dimensões mantendo proporção
      let { width, height } = img;

      if (width > height) {
        if (width > maxWidth) {
          height = (height * maxWidth) / width;
          width = maxWidth;
        }
      } else {
        if (height > maxHeight) {
          width = (width * maxHeight) / height;
          height = maxHeight;
        }
      }

      // Configura canvas
      canvas.width = width;
      canvas.height = height;

      // Desenha imagem redimensionada
      ctx.drawImage(img, 0, 0, width, height);

      // Converte para blob comprimido
      canvas.toBlob((blob) => {
        const reader = new FileReader();
        reader.onload = function (e) {
          resolve({
            dataUrl: e.target.result,
            blob: blob,
            width: width,
            height: height,
            compressedSize: blob.size
          });
        };
        reader.readAsDataURL(blob);
      }, 'image/jpeg', quality);
    };

    img.src = dataUrl;
  });
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

  hidePreview();

  setTimeout(() => {
    openFileSelector();
  }, 100);
}

function closeCropModalOk() {
  cropModal?.classList.add('hidden');
  document.body.style.overflow = 'auto';

  if (cropper) {
    cropper.destroy();
    cropper = null;
  }
}

async function applyCrop() {
  if (!cropper) return;

  const canvas = cropper.getCroppedCanvas({
    width: 400,
    height: 400,
    imageSmoothingEnabled: true,
    imageSmoothingQuality: 'high'
  });

  canvas.toBlob(async (blob) => {
    const reader = new FileReader();
    reader.onload = async function (e) {
      const croppedImageSrc = e.target.result;

      // ✅ NOVO: Comprimir imagem após crop
      try {
        const compressed = await compressImageFromDataUrl(croppedImageSrc, 400, 400, 0.8);

        // Armazena imagem comprimida
        processedImageData = compressed.dataUrl;

        previewImage.src = compressed.dataUrl;

        const fotoCropadaInput = document.getElementById('foto-cropada');
        if (fotoCropadaInput) {
          fotoCropadaInput.value = compressed.dataUrl;
        }

        // ✅ NOVO: Adicionar também campo foto_comprimida para compatibilidade
        let fotoComprimidaInput = document.getElementById('foto-comprimida');
        if (!fotoComprimidaInput) {
          fotoComprimidaInput = document.createElement('input');
          fotoComprimidaInput.type = 'hidden';
          fotoComprimidaInput.id = 'foto-comprimida';
          fotoComprimidaInput.name = 'foto_comprimida';
          const form = document.getElementById('formulario-participante');
          if (form) form.appendChild(fotoComprimidaInput);
        }
        fotoComprimidaInput.value = compressed.dataUrl;

        const reducao = Math.round((1 - compressed.compressedSize / blob.size) * 100);
        console.log('Compressão:', {
          original: `${(blob.size / 1024).toFixed(2)}KB`,
          comprimida: `${(compressed.compressedSize / 1024).toFixed(2)}KB`,
          reducao: `${reducao}%`,
          dimensoes: `${compressed.width}x${compressed.height}`
        });

        closeCropModalOk();
        showToast('Imagem ajustada e comprimida com sucesso!', 'success');
      } catch (error) {
        console.error('Erro ao comprimir imagem:', error);
        // Fallback: usar imagem cropada sem compressão
        previewImage.src = croppedImageSrc;
        const fotoCropadaInput = document.getElementById('foto-cropada');
        if (fotoCropadaInput) {
          fotoCropadaInput.value = croppedImageSrc;
        }
        closeCropModalOk();
        showToast('Imagem ajustada com sucesso!', 'success');
      }
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
  const fotoPath = document.querySelector('[data-foto-path]')?.dataset.fotoPath;

  // ✅ Não executa se a imagem foi removida manualmente
  if (imageManuallyRemoved) return;

  if (fotoPath && previewImage && previewContainer && uploadArea) {
    previewImage.src = fotoPath;
    originalImageSrc = previewImage.src;

    // Mostra o preview e esconde o upload area
    previewContainer.classList.remove('hidden');
    uploadArea.classList.add('hidden');

    // ✅ CORREÇÃO: Só configura onerror se não foi removida manualmente
    previewImage.onerror = function () {
      // ✅ Só executa se não foi remoção manual
      if (!imageManuallyRemoved) {
        previewContainer.classList.add('hidden');
        uploadArea.classList.remove('hidden');
        previewImage.src = '#';
        originalImageSrc = null;
      }
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
  setupFormSubmission(); // ✅ NOVA: Configura envio com compressão
});

// ✅ NOVA FUNÇÃO: Envia imagem comprimida no formulário
function setupFormSubmission() {
  const form = document.getElementById('formulario-participante');
  if (!form) return;

  form.addEventListener('submit', function (event) {
    // Se há imagem processada, garante que está no formulário
    if (processedImageData) {
      // Remove input anterior se existir
      const existingInput = document.querySelector('input[name="foto_comprimida"]');
      if (existingInput) {
        existingInput.remove();
      }

      // Adiciona nova imagem comprimida
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'foto_comprimida';
      input.value = processedImageData;
      form.appendChild(input);

      console.log('Enviando imagem comprimida no formulário');
    }
  });
}

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