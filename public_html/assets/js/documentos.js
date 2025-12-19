// documentos.js - Gerenciamento de documentos do participante
let participanteIdAtual = null;

// Variáveis para crop de documento
let cropperDocumento = null;
let originalImageSrcDocumento = null;

// Função para mostrar toast
function showToast(message, type = 'error') {
  const backgroundColor = type === 'success' ? '#16a34a' : '#dc2626';
  if (typeof Toastify !== 'undefined') {
    Toastify({
      text: message,
      duration: type === 'success' ? 4000 : 5000,
      close: true,
      gravity: "top",
      position: "right",
      backgroundColor: backgroundColor,
      stopOnFocus: true,
    }).showToast();
  } else {
    alert(message);
  }
}

// Abrir modal de documentos
function abrirModalDocumentos(participanteId) {
  participanteIdAtual = participanteId;
  const modal = document.getElementById('modal-documentos');
  modal.classList.remove('hidden');
  document.body.style.overflow = 'hidden';

  // Atualizar contador
  atualizarContadorDocumentos(participanteId);
}

// Fechar modal de documentos
function fecharModalDocumentos() {
  // Fechar PhotoSwipe se estiver aberto
  if (window.photoSwipeLightbox && window.photoSwipeLightbox.pswp) {
    window.photoSwipeLightbox.pswp.close();
  }

  // Fechar modal de documentos
  const modal = document.getElementById('modal-documentos');
  modal.classList.add('hidden');
  document.body.style.overflow = 'auto';
  participanteIdAtual = null;
}

// Visualizar documento imagem usando PhotoSwipe
function visualizarDocumentoImagem(caminho) {
  // Verificar se PhotoSwipe está disponível
  if (!window.photoSwipeLightbox) {
    showToast('Erro: PhotoSwipe não está carregado.', 'error');
    return;
  }

  // Carregar dimensões reais da imagem primeiro
  const img = new Image();
  img.onload = function() {
    // Criar galeria temporária se não existir
    let gallery = document.getElementById('documentos-gallery');
    if (!gallery) {
      gallery = document.createElement('div');
      gallery.id = 'documentos-gallery';
      gallery.className = 'hidden';
      document.body.appendChild(gallery);
    }

    // Limpar e criar novo link
    gallery.innerHTML = '';
    const link = document.createElement('a');
    link.href = caminho;
    link.setAttribute('data-pswp-width', img.naturalWidth.toString());
    link.setAttribute('data-pswp-height', img.naturalHeight.toString());
    gallery.appendChild(link);

    // Abrir PhotoSwipe usando o índice 0 da galeria
    window.photoSwipeLightbox.loadAndOpen(0, {
      gallery: gallery
    });
  };
  img.onerror = function() {
    showToast('Erro ao carregar imagem.', 'error');
  };
  img.src = caminho;
}

// Visualizar documento PDF no navegador
function visualizarDocumentoPDF(caminho) {
  // Abrir PDF em nova aba do navegador
  window.open(caminho, '_blank');
}

// Voltar para lista de documentos (da visualização de imagem)
function voltarParaListaDocumentos() {
  const imagemViewer = document.getElementById('documento-imagem-viewer');
  const lista = document.getElementById('documentos-lista');
  const formUpload = document.getElementById('form-upload-documento');
  const btnVoltar = document.getElementById('btn-voltar-lista-documentos');
  const titulo = document.getElementById('modal-documentos-titulo');

  if (imagemViewer) imagemViewer.classList.add('hidden');
  if (lista) lista.classList.remove('hidden');
  if (formUpload) formUpload.classList.remove('hidden');
  if (titulo) titulo.classList.remove('hidden');
  if (btnVoltar) btnVoltar.classList.add('hidden');
}

// Atualizar contador de documentos
function atualizarContadorDocumentos(participanteId) {
  const lista = document.getElementById('documentos-lista');
  if (!lista) return;

  const documentos = lista.querySelectorAll('.bg-gray-50.border');
  const count = documentos.length;
  const contador = document.getElementById(`documentos-count-${participanteId}`);

  if (contador) {
    contador.textContent = count;
    contador.classList.toggle('hidden', count === 0);
  }
}

// ============= CROP DE DOCUMENTO =============
function abrirCropModalDocumento() {
  if (!originalImageSrcDocumento) {
    showToast('Nenhuma imagem carregada para ajustar.', 'error');
    return;
  }

  const cropImage = document.getElementById('crop-image-documento');
  const cropModal = document.getElementById('crop-modal-documento');

  if (!cropImage || !cropModal) {
    showToast('Erro: Elementos de crop não encontrados.', 'error');
    return;
  }

  cropImage.src = originalImageSrcDocumento;
  cropModal.classList.remove('hidden');
  document.body.style.overflow = 'hidden';

  cropImage.onload = function () {
    if (cropperDocumento) {
      cropperDocumento.destroy();
    }

    // Crop flexível - sem proporção fixa
    cropperDocumento = new Cropper(cropImage, {
      aspectRatio: NaN, // Sem proporção fixa - permite livre escolha
      viewMode: 1, // Permite redimensionar a área de crop
      guides: true,
      center: true,
      highlight: true,
      cropBoxMovable: true,
      cropBoxResizable: true,
      toggleDragModeOnDblclick: false,
      minCropBoxWidth: 50,
      minCropBoxHeight: 50,
      ready: function () {
        // Ajustar tamanho inicial do crop para ocupar boa parte da imagem
        const imageData = cropperDocumento.getImageData();

        // Definir tamanho inicial do crop como 80% da imagem (mantendo proporção original)
        const newWidth = imageData.width * 0.8;
        const newHeight = imageData.height * 0.8;

        cropperDocumento.setCropBoxData({
          width: newWidth,
          height: newHeight,
          left: (imageData.width - newWidth) / 2,
          top: (imageData.height - newHeight) / 2
        });
      }
    });
  };
}

function fecharCropModalDocumento() {
  const cropModal = document.getElementById('crop-modal-documento');
  if (cropModal) {
    cropModal.classList.add('hidden');
    document.body.style.overflow = 'auto';
  }

  if (cropperDocumento) {
    cropperDocumento.destroy();
    cropperDocumento = null;
  }

  originalImageSrcDocumento = null;

  // Limpar inputs de arquivo
  const uploadInput = document.getElementById('documento-upload-input');
  const uploadInputCamera = document.getElementById('documento-upload-input-camera');
  if (uploadInput) {
    uploadInput.value = '';
  }
  if (uploadInputCamera) {
    uploadInputCamera.value = '';
  }
}

function aplicarCropDocumento() {
  if (!cropperDocumento) {
    showToast('Erro: Nenhuma área selecionada para cortar.', 'error');
    return;
  }

  // Validar nome do arquivo - sempre obrigatório
  const tipoNomeFicha = document.getElementById('tipo-nome-ficha');
  const tipoNomeOutro = document.getElementById('tipo-nome-outro');
  const nomeOutroInput = document.getElementById('nome-outro-input');

  // Se "Outro" estiver selecionado, validar campo obrigatório
  if (tipoNomeOutro && tipoNomeOutro.checked) {
    if (!nomeOutroInput || !nomeOutroInput.value.trim()) {
      showToast('Por favor, digite um nome para o arquivo.', 'error');
      fecharCropModalDocumento();
      if (nomeOutroInput) nomeOutroInput.focus();
      return;
    }
  }

  // Se nenhum estiver selecionado (não deveria acontecer, mas por segurança)
  if ((!tipoNomeFicha || !tipoNomeFicha.checked) && (!tipoNomeOutro || !tipoNomeOutro.checked)) {
    showToast('Por favor, selecione um tipo de nome para o arquivo.', 'error');
    fecharCropModalDocumento();
    return;
  }

  // Obter canvas cortado mantendo o tamanho original da área selecionada
  const canvas = cropperDocumento.getCroppedCanvas({
    imageSmoothingEnabled: true,
    imageSmoothingQuality: 'high'
  });

  canvas.toBlob((blob) => {
    const reader = new FileReader();
    reader.onload = async function (e) {
      const croppedImageSrc = e.target.result;

      // Comprimir a imagem cortada antes de enviar (mantendo proporção original)
      const compressed = await compressImageFromDataUrl(croppedImageSrc, canvas.width, canvas.height, 0.85);

      const inputComprimido = document.getElementById('documento-comprimido');
      const formUpload = document.getElementById('form-upload-documento');

      if (inputComprimido) {
        inputComprimido.value = compressed.dataUrl;
      }

      fecharCropModalDocumento();

      // Enviar via AJAX
      if (formUpload && participanteIdAtual) {
        await enviarDocumentoAjax(formUpload, participanteIdAtual);
      } else {
        showToast('Erro: Formulário não encontrado.', 'error');
      }
    };
    reader.readAsDataURL(blob);
  }, 'image/jpeg', 0.9);
}

// Comprimir imagem a partir de data URL
function compressImageFromDataUrl(dataUrl, maxWidth, maxHeight, quality = 0.85) {
  return new Promise((resolve) => {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const img = new Image();

    img.onload = function () {
      let { width, height } = img;

      // Se maxWidth e maxHeight foram fornecidos, redimensiona mantendo proporção
      let newWidth = width;
      let newHeight = height;

      if (maxWidth && maxHeight) {
        // Calcula dimensões mantendo proporção
        const aspectRatio = maxWidth / maxHeight;
        const imgAspectRatio = width / height;

        if (imgAspectRatio > aspectRatio) {
          // Imagem mais larga
          newWidth = Math.min(width, maxWidth);
          newHeight = newWidth / imgAspectRatio;
        } else {
          // Imagem mais alta
          newHeight = Math.min(height, maxHeight);
          newWidth = newHeight * imgAspectRatio;
        }
      }

      canvas.width = newWidth;
      canvas.height = newHeight;
      ctx.drawImage(img, 0, 0, newWidth, newHeight);

      canvas.toBlob((blob) => {
        const reader = new FileReader();
        reader.onload = function (e) {
          resolve({
            dataUrl: e.target.result,
            blob: blob,
            width: newWidth,
            height: newHeight,
            compressedSize: blob.size
          });
        };
        reader.readAsDataURL(blob);
      }, 'image/jpeg', quality);
    };

    img.src = dataUrl;
  });
}

// Comprimir imagem (tamanho A4 - 2480x3508 pixels em 300 DPI)
function compressImage(file, maxWidth = 2480, maxHeight = 3508, quality = 0.85) {
  return new Promise((resolve) => {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const img = new Image();

    img.onload = function () {
      let { width, height } = img;

      // Calcula dimensões mantendo proporção
      const aspectRatio = maxWidth / maxHeight;
      const imgAspectRatio = width / height;

      let newWidth, newHeight;

      if (imgAspectRatio > aspectRatio) {
        // Imagem mais larga que A4
        newWidth = Math.min(width, maxWidth);
        newHeight = newWidth / imgAspectRatio;
      } else {
        // Imagem mais alta que A4
        newHeight = Math.min(height, maxHeight);
        newWidth = newHeight * imgAspectRatio;
      }

      canvas.width = newWidth;
      canvas.height = newHeight;
      ctx.drawImage(img, 0, 0, newWidth, newHeight);

      canvas.toBlob((blob) => {
        const reader = new FileReader();
        reader.onload = function (e) {
          resolve({
            dataUrl: e.target.result,
            blob: blob,
            width: newWidth,
            height: newHeight,
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

// Função para processar arquivo (reutilizável)
async function processarArquivoDocumento(file) {
  const formUpload = document.getElementById('form-upload-documento');
  const inputComprimido = document.getElementById('documento-comprimido');

  if (!file) return;

  // Validar nome do arquivo - sempre obrigatório
  const tipoNomeFicha = document.getElementById('tipo-nome-ficha');
  const tipoNomeOutro = document.getElementById('tipo-nome-outro');
  const nomeOutroInput = document.getElementById('nome-outro-input');

  // Se "Outro" estiver selecionado, validar campo obrigatório
  if (tipoNomeOutro && tipoNomeOutro.checked) {
    if (!nomeOutroInput || !nomeOutroInput.value.trim()) {
      showToast('Por favor, digite um nome para o arquivo.', 'error');
      if (nomeOutroInput) nomeOutroInput.focus();
      return;
    }
  }

  // Se nenhum estiver selecionado (não deveria acontecer, mas por segurança)
  if ((!tipoNomeFicha || !tipoNomeFicha.checked) && (!tipoNomeOutro || !tipoNomeOutro.checked)) {
    showToast('Por favor, selecione um tipo de nome para o arquivo.', 'error');
    return;
  }

  // Validar tipo
  const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
  if (!tiposPermitidos.includes(file.type)) {
    showToast('Tipo de arquivo não permitido. Use imagens ou PDF.', 'error');
    return;
  }

  // Validar tamanho (10MB)
  if (file.size > 10 * 1024 * 1024) {
    showToast('Arquivo muito grande. Máximo 10MB.', 'error');
    return;
  }

  // Se for imagem, abrir modal de crop
  if (file.type.startsWith('image/')) {
    try {
      const reader = new FileReader();
      reader.onload = function (e) {
        originalImageSrcDocumento = e.target.result;
        abrirCropModalDocumento();
      };
      reader.readAsDataURL(file);
    } catch (error) {
      console.error('Erro ao ler imagem:', error);
      showToast('Erro ao processar imagem: ' + error.message, 'error');
    }
  } else {
    // PDF ou outro arquivo - enviar via AJAX
    console.log('Enviando PDF:', file.name, file.size, 'bytes');
    if (!formUpload) {
      console.error('Formulário não encontrado!');
      showToast('Erro: Formulário não encontrado.', 'error');
      return;
    }

    // Criar FormData e adicionar o arquivo manualmente
    const formData = new FormData();
    formData.append('upload_documento', '1');
    formData.append('documento', file);

    // Adicionar nome personalizado sempre (obrigatório)
    const nomePersonalizado = obterNomeArquivoPersonalizado();
    if (!nomePersonalizado) {
      showToast('Por favor, selecione ou digite um nome para o arquivo.', 'error');
      return;
    }
    formData.append('nome_arquivo_personalizado', nomePersonalizado);

    // Enviar via fetch diretamente
    try {
      const response = await fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      // Verificar se a resposta é JSON válida
      const contentType = response.headers.get('content-type');
      let result;

      if (contentType && contentType.includes('application/json')) {
        try {
          result = await response.json();
        } catch (e) {
          console.error('Erro ao parsear resposta JSON:', e);
          const text = await response.text();
          console.error('Resposta recebida:', text.substring(0, 500));
          showToast('Erro ao processar resposta do servidor.', 'error');
          return;
        }
      } else {
        // Se não for JSON, provavelmente é HTML (erro do servidor)
        const text = await response.text();
        console.error('Resposta não é JSON. Content-Type:', contentType);
        console.error('Resposta recebida (primeiros 500 chars):', text.substring(0, 500));
        showToast('Erro: O servidor retornou uma resposta inválida. Verifique o console para mais detalhes.', 'error');
        return;
      }

      if (result.success) {
        showToast(result.message || 'Documento adicionado com sucesso!', 'success');
        // Limpar inputs
        const uploadInput = document.getElementById('documento-upload-input');
        if (uploadInput) uploadInput.value = '';
        const uploadInputCamera = document.getElementById('documento-upload-input-camera');
        if (uploadInputCamera) uploadInputCamera.value = '';
        // Resetar seleção de nome
        const tipoNomeFicha = document.getElementById('tipo-nome-ficha');
        if (tipoNomeFicha) tipoNomeFicha.checked = true;
        const tipoNomeOutro = document.getElementById('tipo-nome-outro');
        if (tipoNomeOutro) tipoNomeOutro.checked = false;
        const campoNomeOutro = document.getElementById('campo-nome-outro');
        if (campoNomeOutro) campoNomeOutro.classList.add('hidden');
        const nomeOutroInput = document.getElementById('nome-outro-input');
        if (nomeOutroInput) nomeOutroInput.value = '';

        // Recarregar lista de documentos
        await recarregarListaDocumentos(participanteIdAtual);
      } else {
        showToast(result.message || 'Erro ao adicionar documento.', 'error');
      }
    } catch (error) {
      console.error('Erro ao enviar documento:', error);
      showToast('Erro ao enviar documento: ' + error.message, 'error');
    }
  }
}

// Função para obter o nome do arquivo escolhido pelo usuário
function obterNomeArquivoPersonalizado() {
  const tipoNomeFicha = document.getElementById('tipo-nome-ficha');
  const tipoNomeOutro = document.getElementById('tipo-nome-outro');
  const nomeOutroInput = document.getElementById('nome-outro-input');

  if (tipoNomeOutro && tipoNomeOutro.checked && nomeOutroInput) {
    const nomeDigitado = nomeOutroInput.value.trim();
    if (nomeDigitado) {
      return nomeDigitado;
    }
  }

  if (tipoNomeFicha && tipoNomeFicha.checked) {
    return 'Ficha de inscrição';
  }

  return null;
}

// Inicialização
document.addEventListener('DOMContentLoaded', function () {
  // Upload de arquivo (desktop e escolher arquivo mobile)
  const uploadInput = document.getElementById('documento-upload-input');
  // Upload de câmera (mobile)
  const uploadInputCamera = document.getElementById('documento-upload-input-camera');
  const formUpload = document.getElementById('form-upload-documento');
  const inputComprimido = document.getElementById('documento-comprimido');

  // Gerenciar exibição do campo "Outro"
  const tipoNomeFicha = document.getElementById('tipo-nome-ficha');
  const tipoNomeOutro = document.getElementById('tipo-nome-outro');
  const campoNomeOutro = document.getElementById('campo-nome-outro');
  const nomeOutroInput = document.getElementById('nome-outro-input');

  if (tipoNomeFicha && tipoNomeOutro && campoNomeOutro) {
    tipoNomeFicha.addEventListener('change', function() {
      if (this.checked) {
        campoNomeOutro.classList.add('hidden');
        if (nomeOutroInput) nomeOutroInput.value = '';
      }
    });

    tipoNomeOutro.addEventListener('change', function() {
      if (this.checked) {
        campoNomeOutro.classList.remove('hidden');
        if (nomeOutroInput) nomeOutroInput.focus();
      }
    });
  }

  // Event listener para input de arquivo (desktop e escolher arquivo mobile)
  if (uploadInput && formUpload) {
    uploadInput.addEventListener('change', async function (e) {
      const file = e.target.files[0];
      if (!file) return;

      // Limpar input após processar
      uploadInput.value = '';

      await processarArquivoDocumento(file);
    });
  }

  // Event listener para input de câmera (mobile)
  if (uploadInputCamera && formUpload) {
    uploadInputCamera.addEventListener('change', async function (e) {
      const file = e.target.files[0];
      if (!file) return;

      // Limpar input após processar
      uploadInputCamera.value = '';

      await processarArquivoDocumento(file);
    });
  }


  // Fechar modais ao clicar fora
  const modalDocumentos = document.getElementById('modal-documentos');
  if (modalDocumentos) {
    modalDocumentos.addEventListener('click', function (e) {
      // Só fecha se clicar no overlay (não no conteúdo)
      if (e.target === modalDocumentos) {
        fecharModalDocumentos();
      }
    });
  }

  // Event listeners para crop de documento
  const cancelCropBtn = document.getElementById('cancel-crop-documento');
  const applyCropBtn = document.getElementById('apply-crop-documento');

  if (cancelCropBtn) {
    cancelCropBtn.addEventListener('click', function () {
      fecharCropModalDocumento();
    });
  }

  if (applyCropBtn) {
    applyCropBtn.addEventListener('click', function () {
      aplicarCropDocumento();
    });
  }

  // Fechar com ESC
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      const cropModal = document.getElementById('crop-modal-documento');
      if (cropModal && !cropModal.classList.contains('hidden')) {
        fecharCropModalDocumento();
        return;
      }

      const imagemViewer = document.getElementById('documento-imagem-viewer');
      if (imagemViewer && !imagemViewer.classList.contains('hidden')) {
        // Se estiver visualizando imagem, volta para lista
        voltarParaListaDocumentos();
      } else {
        // Se estiver na lista, fecha a modal
        fecharModalDocumentos();
      }
    }
  });

  // Carregar contador inicial se houver participanteId
  if (typeof participanteId !== 'undefined' && participanteId) {
    atualizarContadorDocumentos(participanteId);
  }
});

// Função para enviar documento via AJAX
async function enviarDocumentoAjax(form, participanteId) {
  try {
    const formData = new FormData(form);

    // Adicionar nome personalizado sempre (obrigatório)
    const nomePersonalizado = obterNomeArquivoPersonalizado();
    if (!nomePersonalizado) {
      showToast('Por favor, selecione ou digite um nome para o arquivo.', 'error');
      return;
    }
    formData.set('nome_arquivo_personalizado', nomePersonalizado);

    const response = await fetch(window.location.href, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    // Verificar se a resposta é JSON válida
    const contentType = response.headers.get('content-type');
    let result;

    if (contentType && contentType.includes('application/json')) {
      try {
        result = await response.json();
      } catch (e) {
        console.error('Erro ao parsear resposta JSON:', e);
        const text = await response.text();
        console.error('Resposta recebida:', text.substring(0, 500));
        showToast('Erro ao processar resposta do servidor.', 'error');
        return;
      }
    } else {
      // Se não for JSON, provavelmente é HTML (erro do servidor)
      const text = await response.text();
      console.error('Resposta não é JSON. Content-Type:', contentType);
      console.error('Resposta recebida (primeiros 500 chars):', text.substring(0, 500));
      showToast('Erro: O servidor retornou uma resposta inválida. Verifique o console para mais detalhes.', 'error');
      return;
    }

    if (result.success) {
      showToast(result.message || 'Documento adicionado com sucesso!', 'success');
      // Limpar inputs
      const uploadInput = document.getElementById('documento-upload-input');
      if (uploadInput) uploadInput.value = '';
      const uploadInputCamera = document.getElementById('documento-upload-input-camera');
      if (uploadInputCamera) uploadInputCamera.value = '';
      const inputComprimido = document.getElementById('documento-comprimido');
      if (inputComprimido) inputComprimido.value = '';
      // Resetar seleção de nome
      const tipoNomeFicha = document.getElementById('tipo-nome-ficha');
      if (tipoNomeFicha) tipoNomeFicha.checked = true;
      const tipoNomeOutro = document.getElementById('tipo-nome-outro');
      if (tipoNomeOutro) tipoNomeOutro.checked = false;
      const campoNomeOutro = document.getElementById('campo-nome-outro');
      if (campoNomeOutro) campoNomeOutro.classList.add('hidden');
      const nomeOutroInput = document.getElementById('nome-outro-input');
      if (nomeOutroInput) nomeOutroInput.value = '';

      // Recarregar lista de documentos
      await recarregarListaDocumentos(participanteId);
    } else {
      showToast(result.message || 'Erro ao adicionar documento.', 'error');
    }
  } catch (error) {
    console.error('Erro ao enviar documento:', error);
    showToast('Erro ao enviar documento: ' + error.message, 'error');
  }
}

// Função para recarregar lista de documentos
async function recarregarListaDocumentos(participanteId) {
  try {
    const response = await fetch(`/api/participantes/listar-documentos?id=${participanteId}`);
    const result = await response.json();

    if (result.success && result.documentos) {
      const listaContainer = document.getElementById('documentos-lista');
      if (!listaContainer) return;

      // Limpar lista atual
      listaContainer.innerHTML = '';

      if (result.documentos.length === 0) {
        listaContainer.innerHTML = `
          <div class="text-center text-gray-500 py-8">
            <i class="fa-solid fa-file-lines text-4xl mb-2"></i>
            <p>Nenhum documento cadastrado</p>
          </div>
        `;
      } else {
        // Renderizar documentos
        result.documentos.forEach(doc => {
          const docElement = criarElementoDocumento(doc, participanteId);
          listaContainer.appendChild(docElement);
        });
      }

      // Atualizar contador
      atualizarContadorDocumentos(participanteId);
    }
  } catch (error) {
    console.error('Erro ao recarregar lista de documentos:', error);
  }
}

// Função para criar elemento de documento
function criarElementoDocumento(doc, participanteId) {
  const div = document.createElement('div');
  div.className = 'bg-gray-50 border border-gray-200 rounded-lg p-4 flex items-center justify-between hover:bg-gray-100 transition';

  const botoes = doc.is_imagem
    ? `<button onclick="visualizarDocumentoImagem('${doc.caminho}')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg transition text-sm" title="Visualizar imagem">
         <i class="fa-solid fa-eye"></i>
       </button>
       <a href="${doc.caminho}" target="_blank" download class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg transition text-sm" title="Baixar imagem">
         <i class="fa-solid fa-download"></i>
       </a>`
    : `<button onclick="visualizarDocumentoPDF('${doc.caminho}')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg transition text-sm" title="Visualizar PDF">
         <i class="fa-solid fa-eye"></i>
       </button>
       <a href="${doc.caminho}" target="_blank" download class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg transition text-sm" title="Baixar PDF">
         <i class="fa-solid fa-download"></i>
       </a>`;

  div.innerHTML = `
    <div class="flex items-center gap-3 flex-1 min-w-0">
      <div class="bg-blue-100 p-3 rounded-lg flex-shrink-0">
        <i class="fa-solid ${doc.icone} text-blue-600 text-xl"></i>
      </div>
      <div class="flex-1 min-w-0">
        <p class="font-medium text-gray-800 truncate">${escapeHtml(doc.nome_arquivo)}</p>
        <p class="text-xs text-gray-500">${doc.tamanho_formatado} • ${doc.data_formatada}</p>
      </div>
    </div>
    <div class="flex gap-2 flex-shrink-0">
      ${botoes}
      <button onclick="excluirDocumento(${doc.id}, ${participanteId})" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg transition text-sm" title="Excluir documento">
        <i class="fa-solid fa-trash"></i>
      </button>
    </div>
  `;

  return div;
}

// Função para escapar HTML
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Função para excluir documento com confirmação
async function excluirDocumento(documentoId, participanteId) {
  openConfirmModal('Tem certeza que deseja excluir este documento?', async () => {
    try {
      const formData = new FormData();
      formData.append('excluir_documento', '1');
      formData.append('documento_id', documentoId);

      const response = await fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();

      if (result.success) {
        showToast(result.message || 'Documento excluído com sucesso!', 'success');
        // Recarregar lista de documentos
        await recarregarListaDocumentos(participanteId);
      } else {
        showToast(result.message || 'Erro ao excluir documento.', 'error');
      }
    } catch (error) {
      console.error('Erro ao excluir documento:', error);
      showToast('Erro ao excluir documento: ' + error.message, 'error');
    }
  });
}
