// ritual.js - Funcionalidades comuns para novo e editar ritual

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

// ============= UPLOAD DE IMAGEM (SEM CROP) =============
const fileInput = document.getElementById('foto-input');
const uploadArea = document.getElementById('upload-area');
const previewContainer = document.getElementById('preview-container');
const previewImage = document.getElementById('preview-image');
const substituirBtn = document.getElementById('substituir-imagem-btn');
const excluirBtn = document.getElementById('excluir-imagem-btn');

function openFileSelector() {
  fileInput?.click();
}

function showPreview(file) {
  const reader = new FileReader();
  reader.onload = (e) => {
    const imageSrc = e.target.result;

    previewImage.src = imageSrc;

    // Compatível com Tailwind classes E CSS inline
    uploadArea?.classList.add('hidden');
    uploadArea.style.display = 'none';

    previewContainer?.classList.remove('hidden');
    previewContainer.style.display = 'block';

    showToast('Imagem carregada com sucesso!', 'success');
  };
  reader.readAsDataURL(file);
}

function hidePreview() {
  previewImage.src = '#';

  // Compatível com Tailwind classes E CSS inline
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

  const maxSize = 5 * 1024 * 1024; // 5MB
  if (file.size > maxSize) {
    showToast('A imagem deve ter no máximo 5MB.');
    return false;
  }

  return true;
}

// Função para carregar imagem existente (para página de editar)
function loadExistingImage() {
  const fotoPath = document.querySelector('[data-foto-path]')?.dataset.fotoPath;

  if (fotoPath && previewImage && previewContainer && uploadArea) {
    previewImage.src = fotoPath;

    // Compatível com ambos os métodos
    previewContainer.classList.remove('hidden');
    previewContainer.style.display = 'block';

    uploadArea.classList.add('hidden');
    uploadArea.style.display = 'none';

    // Se a imagem falhar ao carregar, volta para o upload
    previewImage.onerror = function () {
      previewContainer.classList.add('hidden');
      previewContainer.style.display = 'none';

      uploadArea.classList.remove('hidden');
      uploadArea.style.display = 'block';

      previewImage.src = '#';
    };
  }

  // ✅ VERIFICAÇÃO ADICIONAL - compatível com código antigo
  // Se há imagem no src mas não há data-foto-path (código antigo)
  if (previewImage?.src &&
    !previewImage.src.includes('#') &&
    !previewImage.src.includes(window.location.href) &&
    previewContainer && uploadArea) {

    previewContainer.style.display = 'block';
    previewContainer.classList.remove('hidden');

    uploadArea.style.display = 'none';
    uploadArea.classList.add('hidden');
  }
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
      showToast('Por favor, selecione o padrinho ou madrinha.');
      padrinhoInput.focus();
      hasError = true;
    }

    // Se chegou até aqui sem erros, formulário será enviado
  });
}

// ============= EVENT LISTENERS GERAIS =============
document.addEventListener('DOMContentLoaded', function () {
  // Carregar imagem existente (se houver)
  loadExistingImage();

  // Inicializar validação do formulário
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
