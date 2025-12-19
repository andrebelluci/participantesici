// Reaplica máscara CPF se já houver valor preenchido
const cpfInput = document.getElementById('filtro_cpf');
if (cpfInput?.value) {
  cpfInput.value = cpfInput.value.replace(/\D/g, '');
  mascaraCPF(cpfInput);
}

// Remover máscara no submit
const form = document.querySelector('form.filters');
if (form) {
  form.addEventListener('submit', function () {
    const cpfInput = document.getElementById('filtro_cpf');
    if (cpfInput) {
      cpfInput.value = cpfInput.value.replace(/\D/g, '');
    }
  });
}

// Máscara CPF
function mascaraCPF(input) {
  let valor = input.value.replace(/\D/g, '');
  if (valor.length > 11) valor = valor.slice(0, 11);
  valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
  valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
  valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  input.value = valor;
}

// Fecha os filtros no mobile após envio
document.addEventListener("DOMContentLoaded", () => {
  const filtros = document.getElementById("filtros");

  // Fecha os filtros no mobile após envio
  document.querySelector('form#filtros')?.addEventListener('submit', () => {
    if (window.innerWidth < 768 && filtros) {
      filtros.classList.add('hidden');
      localStorage.setItem('fechouFiltrosMobile', 'true');
    }
  });

  // No carregamento da página, oculta os filtros se o flag estiver setado
  if (window.innerWidth < 768 && localStorage.getItem('fechouFiltrosMobile') === 'true') {
    filtros?.classList.add('hidden');
    localStorage.removeItem('fechouFiltrosMobile');
  }
});

// Variável para armazenar URL de exclusão após download
let urlExclusaoPendente = null;

// Abrir modal de download antes da exclusão
function abrirModalDownload(idParticipante, urlExclusao) {
  urlExclusaoPendente = urlExclusao;
  const downloadModal = document.getElementById('downloadModal');
  if (downloadModal) {
    downloadModal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }
}

// Fechar modal de download
function fecharModalDownload(limparUrl = false) {
  const downloadModal = document.getElementById('downloadModal');
  if (downloadModal) {
    downloadModal.classList.add('hidden');
    document.body.style.overflow = 'auto';
  }
  if (limparUrl) {
    urlExclusaoPendente = null;
  }
}

// Baixar documentos e foto do participante
function baixarDocumentosParticipante(idParticipante) {
  const url = `/api/participantes/baixar-documentos?id=${idParticipante}`;

  // Criar iframe temporário para download (não intercepta como fetch)
  const iframe = document.createElement('iframe');
  iframe.style.display = 'none';
  iframe.src = url;
  document.body.appendChild(iframe);

  // Remover iframe após um tempo
  setTimeout(() => {
    document.body.removeChild(iframe);
  }, 2000);

  // Fechar modal e abrir modal de exclusão após um delay
  setTimeout(() => {
    const urlParaExclusao = urlExclusaoPendente; // Salvar URL antes de fechar
    fecharModalDownload(false); // Não limpar URL ainda
    // Abrir modal de exclusão após download
    if (urlParaExclusao) {
      abrirConfirmacaoExcluir(urlParaExclusao);
      urlExclusaoPendente = null; // Limpar após abrir modal de exclusão
    }
  }, 800);
}

// Confirmação excluir participante (chamada após decisão do download)
function abrirConfirmacaoExcluir(url) {
  openConfirmModal('Esta ação irá excluir permanentemente este participante e qualquer documento vinculado a ele!', () => {
    window.location.href = url;
  });
}

// Confirmação excluir participante (nova função que abre modal de download primeiro)
function abrirConfirmacaoExcluirComDownload(idParticipante, urlExclusao) {
  abrirModalDownload(idParticipante, urlExclusao);
}

// Event listeners para modal de download
document.addEventListener('DOMContentLoaded', () => {
  const downloadModal = document.getElementById('downloadModal');
  const downloadSimBtn = document.getElementById('downloadModalSimBtn');
  const downloadNaoBtn = document.getElementById('downloadModalNaoBtn');

  if (downloadSimBtn) {
    downloadSimBtn.addEventListener('click', () => {
      // Extrair ID do participante da URL de exclusão
      const match = urlExclusaoPendente?.match(/\/participante\/(\d+)\/excluir/);
      if (match && match[1]) {
        baixarDocumentosParticipante(match[1]);
      } else {
        // Se não conseguir extrair, apenas fecha e abre modal de exclusão
        const urlParaExclusao = urlExclusaoPendente; // Salvar URL antes de fechar
        fecharModalDownload(false); // Não limpar URL ainda
        if (urlParaExclusao) {
          abrirConfirmacaoExcluir(urlParaExclusao);
          urlExclusaoPendente = null; // Limpar após abrir modal de exclusão
        }
      }
    });
  }

  if (downloadNaoBtn) {
    downloadNaoBtn.addEventListener('click', () => {
      const urlParaExclusao = urlExclusaoPendente; // Salvar URL antes de fechar
      fecharModalDownload(false); // Não limpar URL ainda
      // Abrir modal de exclusão direto
      if (urlParaExclusao) {
        abrirConfirmacaoExcluir(urlParaExclusao);
        urlExclusaoPendente = null; // Limpar após abrir modal de exclusão
      }
    });
  }

  // Fechar modal ao clicar fora
  if (downloadModal) {
    downloadModal.addEventListener('click', (e) => {
      if (e.target === downloadModal) {
        fecharModalDownload(true); // Limpar URL ao cancelar
      }
    });
  }

  // Fechar modal com ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const downloadModal = document.getElementById('downloadModal');
      if (downloadModal && !downloadModal.classList.contains('hidden')) {
        fecharModalDownload(true); // Limpar URL ao cancelar
      }
    }
  });
});
