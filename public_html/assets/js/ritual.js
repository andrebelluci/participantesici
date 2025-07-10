// ritual.js - Funcionalidades para novo e editar ritual COM COMPRESSÃO
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

// ============= UPLOAD DE IMAGEM COM COMPRESSÃO =============
const fileInput = document.getElementById('foto-input');
const uploadArea = document.getElementById('upload-area');
const previewContainer = document.getElementById('preview-container');
const previewImage = document.getElementById('preview-image');
const substituirBtn = document.getElementById('substituir-imagem-btn');
const excluirBtn = document.getElementById('excluir-imagem-btn');

// ✅ NOVA: Variável para armazenar imagem processada
let processedImageData = null;

function openFileSelector() {
  fileInput?.click();
}

// ✅ NOVA FUNÇÃO: Comprime imagem automaticamente
function compressImage(file, maxWidth = 800, maxHeight = 800, quality = 0.8) {
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
            originalSize: file.size,
            compressedSize: blob.size
          });
        };
        reader.readAsDataURL(blob);
      }, 'image/jpeg', quality);
    };

    img.src = URL.createObjectURL(file);
  });
}

// ✅ ATUALIZADA: Processa e comprime imagem
async function showPreview(file) {
  try {
    // ✅ CORREÇÃO: Reset do estado ao carregar nova imagem
    imageManuallyRemoved = false;

    // Comprime a imagem
    const compressed = await compressImage(file, 800, 800, 0.8);

    // Armazena dados processados
    processedImageData = compressed.dataUrl;

    // Mostra preview
    previewImage.src = compressed.dataUrl;

    // Atualiza interface
    uploadArea?.classList.add('hidden');
    uploadArea.style.display = 'none';

    previewContainer?.classList.remove('hidden');
    previewContainer.style.display = 'block';

    // Feedback para o usuário
    const reducao = Math.round((1 - compressed.compressedSize / compressed.originalSize) * 100);
    showToast('Imagem carregada!', 'success');

    console.log('Compressão:', {
      original: `${(compressed.originalSize / 1024 / 1024).toFixed(2)}MB`,
      comprimida: `${(compressed.compressedSize / 1024 / 1024).toFixed(2)}MB`,
      reducao: `${reducao}%`,
      dimensoes: `${compressed.width}x${compressed.height}`
    });

  } catch (error) {
    console.error('Erro ao processar imagem:', error);
    showToast('Erro ao processar imagem. Tente novamente.', 'error');
  }
}

function hidePreview() {
  // ✅ Marca que foi remoção manual
  imageManuallyRemoved = true;

  // ✅ Remove o handler de erro temporariamente
  previewImage.onerror = null;

  // Limpa a imagem
  previewImage.src = '#';
  processedImageData = null;

  // Atualiza interface
  uploadArea?.classList.remove('hidden');
  uploadArea.style.display = 'block';

  previewContainer?.classList.add('hidden');
  previewContainer.style.display = 'none';

  fileInput.value = '';

  // Adicionar flag para remoção (se estiver editando)
  const removerFotoInput = document.querySelector('input[name="remover_foto"]');
  if (!removerFotoInput) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'remover_foto';
    input.value = '1';
    document.getElementById('formulario-ritual')?.appendChild(input);
  }
}

function validateFile(file) {
  if (!file.type.startsWith('image/')) {
    showToast('Por favor, selecione apenas arquivos de imagem.');
    return false;
  }

  const maxSize = 10 * 1024 * 1024; // ✅ AUMENTADO para 10MB (será comprimido)
  if (file.size > maxSize) {
    showToast('A imagem deve ter no máximo 10MB.');
    return false;
  }

  return true;
}

// Função para carregar imagem existente (para página de editar)
function loadExistingImage() {
  const fotoPath = document.querySelector('[data-foto-path]')?.dataset.fotoPath;

  // ✅ Não executa se a imagem foi removida manualmente
  if (imageManuallyRemoved) return;

  if (fotoPath && previewImage && previewContainer && uploadArea) {
    previewImage.src = fotoPath;

    // Compatível com ambos os métodos
    previewContainer.classList.remove('hidden');
    previewContainer.style.display = 'block';

    uploadArea.classList.add('hidden');
    uploadArea.style.display = 'none';

    // ✅ CORREÇÃO: Só configura onerror se não foi removida manualmente
    previewImage.onerror = function () {
      // ✅ Só executa se não foi remoção manual
      if (!imageManuallyRemoved) {
        previewContainer.classList.add('hidden');
        previewContainer.style.display = 'none';

        uploadArea.classList.remove('hidden');
        uploadArea.style.display = 'block';

        previewImage.src = '#';
      }
    };
  }
}

// ✅ NOVA FUNÇÃO: Envia imagem comprimida no formulário
function setupFormSubmission() {
  const form = document.getElementById('formulario-ritual');
  if (!form) return;

  form.addEventListener('submit', function (event) {
    // Se há imagem processada, adiciona ao formulário
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

// ============= VALIDAÇÕES =============
function setupFormValidation() {
  const form = document.getElementById('formulario-ritual');
  if (!form) return;

  form.addEventListener('submit', function (event) {
    const nomeInput = document.getElementById('nome');
    const dataInput = document.getElementById('data_ritual');
    const padrinhoInput = document.getElementById('padrinho_madrinha');

    let hasError = false;

    // Validar nome
    if (!nomeInput?.value.trim()) {
      event.preventDefault();
      showToast('Por favor, preencha o nome do ritual.');
      nomeInput.focus();
      hasError = true;
    }

    // Validar data
    if (!dataInput?.value && !hasError) {
      event.preventDefault();
      showToast('Por favor, selecione a data do ritual.');
      dataInput.focus();
      hasError = true;
    }

    // Validar padrinho/madrinha
    if (!padrinhoInput?.value && !hasError) {
      event.preventDefault();
      showToast('Por favor, selecione o padrinho ou madrinha.');      padrinhoInput.focus();
    }
  });
}

// ============= EVENT LISTENERS =============
document.addEventListener('DOMContentLoaded', function () {
  loadExistingImage();
  setupFormValidation();
  setupFormSubmission(); // ✅ NOVA: Configura envio com compressão
});

// Event listeners para upload
uploadArea?.addEventListener('click', openFileSelector);
substituirBtn?.addEventListener('click', openFileSelector);

fileInput?.addEventListener('change', async (e) => {
  const file = e.target.files[0];
  if (file && validateFile(file)) {
    await showPreview(file); // ✅ ATUALIZADO: async para compressão
  }
});

excluirBtn?.addEventListener('click', () => {
  hidePreview();
  showToast('Imagem removida.', 'success');
});

// Event listeners para preview modal (se existir)
function openImageModal(src) {
  if (src && src !== '#') {
    window.open(src, '_blank');
  }
}