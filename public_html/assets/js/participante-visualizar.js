// ============= VARIÁVEIS GLOBAIS =============
let currentRitualId = null; // Para rastrear o ritual atual

// ============= FUNÇÃO TOAST =============
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

// Variável para armazenar a posição do scroll
let scrollPosition = 0;

// Função para bloquear scroll
function disableScroll() {
  scrollPosition = window.pageYOffset;
  document.body.style.overflow = 'hidden';
  document.body.style.position = 'fixed';
  document.body.style.top = `-${scrollPosition}px`;
  document.body.style.width = '100%';
}

// Função para restaurar scroll
function enableScroll() {
  document.body.style.removeProperty('overflow');
  document.body.style.removeProperty('position');
  document.body.style.removeProperty('top');
  document.body.style.removeProperty('width');
  window.scrollTo(0, scrollPosition);
}

// ============= MODAL MANAGER - USANDO DELEGAÇÃO DE EVENTOS =============
function initModalEventListeners() {
  // Remove listeners antigos se existirem para evitar duplicação
  document.removeEventListener('click', handleModalClick);
  document.removeEventListener('keydown', handleModalKeydown);

  // Adiciona novos listeners com delegação de eventos
  document.addEventListener('click', handleModalClick);
  document.addEventListener('keydown', handleModalKeydown);
}

// Função para lidar com cliques nas modais (delegação de eventos)
function handleModalClick(event) {
  // Lista de IDs das modals que devem ter fechamento ao clicar fora
  const modalIds = [
    'modal-detalhes-inscricao',
    'modal-observacao',
    'modal-adicionar',
    'modal-cadastro'
  ];

  // Verifica se o clique foi diretamente na modal (background)
  modalIds.forEach(modalId => {
    const modal = document.getElementById(modalId);
    if (modal && event.target === modal) {
      modal.style.display = "none";
      enableScroll();
      setTimeout(() => {
        limparFiltroCompleto();
      }, 300);
    }
  });

  // Fallback para modals com classe .modal
  if (event.target.classList.contains('modal')) {
    event.target.style.display = "none";
    enableScroll();
    setTimeout(() => {
      limparFiltroCompleto();
    }, 300);
  }
}

// Função para lidar com teclas (ESC para fechar)
function handleModalKeydown(event) {
  if (event.key === 'Escape') {
    const modalIds = [
      'modal-detalhes-inscricao',
      'modal-observacao',
      'modal-adicionar',
      'modal-cadastro'
    ];

    modalIds.forEach(modalId => {
      const modal = document.getElementById(modalId);
      if (modal && modal.style.display === 'flex') {
        modal.style.display = 'none';
        enableScroll();
        setTimeout(() => {
          limparFiltroCompleto();
        }, 300);
      }
    });
  }
}

// ============= INICIALIZAÇÃO PRINCIPAL =============
document.addEventListener("DOMContentLoaded", function () {
  console.log('🚀 Inicializando modal listeners...');

  // Inicializa os event listeners das modais
  initModalEventListeners();

  // ✅ Form de detalhes da inscrição - ÚNICO
  initFormDetalhes();

  // ✅ Form de observação - ÚNICO
  initFormObservacao();

  // Outras inicializações
  setupConditionalFields();
  aplicarFocoModalAdicionar();
});

// Função para abrir o modal de detalhes da inscrição
function abrirModalDetalhes(ritualId) {
  disableScroll();
  currentRitualId = ritualId;

  // Limpa todos os campos do formulário
  document.getElementById('id').value = '';
  document.querySelector('select[name="primeira_vez_instituto"]').value = '';
  document.querySelector('select[name="primeira_vez_ayahuasca"]').value = '';
  document.querySelector('select[name="doenca_psiquiatrica"]').value = '';
  // ... demais campos

  // Remove avisos anteriores se existirem
  document.querySelectorAll('.aviso-dados-anteriores').forEach(el => el.remove());

  // Reabilita os campos por padrão
  document.querySelector('select[name="primeira_vez_instituto"]').disabled = false;
  document.querySelector('select[name="primeira_vez_ayahuasca"]').disabled = false;

  fetch(`/api/inscricoes/buscar-id?participante_id=${pessoaId}&ritual_id=${ritualId}`)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showToast(data.error, 'error');
        return;
      }
      const inscricaoId = data.inscricao_id;
      document.getElementById('id').value = inscricaoId;

      fetch(`/api/inscricoes/detalhes-inscricao?id=${inscricaoId}`)
        .then(response => response.json())
        .then(detalhes => {
          if (detalhes.error) {
            showToast(detalhes.error, 'error');
            return;
          }

          // Preenche todos os campos com os dados da inscrição
          Object.keys(detalhes).forEach(key => {
            const element = document.querySelector(`[name="${key}"]`);
            if (element) {
              element.value = detalhes[key] || '';
            }
          });

          // Verifica se os dados de primeira vez vieram de inscrição anterior
            verificarDadosAnteriores(pessoaId, inscricaoId, detalhes);


          const salvoEm = detalhes.salvo_em ?
            new Date(detalhes.salvo_em).toLocaleDateString('pt-BR') : 'Nunca salvo';
          document.getElementById('salvo_em').value = salvoEm;
        })
        .catch(error => {
          console.error('Erro ao carregar detalhes:', error);
          showToast('Erro ao carregar detalhes da inscrição', 'error');
        });
    })
    .catch(error => {
      console.error('Erro ao buscar ID da inscrição:', error);
      showToast('Erro ao buscar dados da inscrição', 'error');
    });

  document.getElementById('modal-detalhes-inscricao').style.display = 'flex';

  // Foco no primeiro campo do formulário
  const primeiroCampo = document.querySelector('#form-detalhes-inscricao input, #form-detalhes-inscricao select, #form-detalhes-inscricao textarea');
  if (primeiroCampo) {
    primeiroCampo.focus();
  }
}

function aplicarAvisosPrimeiraInscricao() {
  const institutoSelect = document.querySelector('select[name="primeira_vez_instituto"]');
  const ayahuascaSelect = document.querySelector('select[name="primeira_vez_ayahuasca"]');

  // Remove avisos anteriores se existirem
  document.querySelectorAll('.aviso-dados-anteriores').forEach(el => el.remove());

  // Adiciona aviso para primeira inscrição
  const avisoInstituto = document.createElement('div');
  avisoInstituto.className = 'aviso-dados-anteriores text-green-600 text-xs mt-1 italic';
  avisoInstituto.textContent = '* Essa é a primeira inscrição desse participante, por isso as próximas inscrições serão preenchidas automaticamente com "Não".';
  institutoSelect.parentNode.appendChild(avisoInstituto);

  const avisoAyahuasca = document.createElement('div');
  avisoAyahuasca.className = 'aviso-dados-anteriores text-green-600 text-xs mt-1 italic';
  avisoAyahuasca.textContent = '* Essa é a primeira inscrição desse participante, por isso as próximas inscrições serão preenchidas automaticamente com "Não".';
  ayahuascaSelect.parentNode.appendChild(avisoAyahuasca);
}


// Nova função para verificar se os dados vieram de inscrição anterior
function verificarDadosAnteriores(participanteId, inscricaoAtualId, detalhes) {
  fetch(`/api/inscricoes/verificar-primeira-inscricao?participante_id=${participanteId}&inscricao_atual_id=${inscricaoAtualId}`)
    .then(response => response.json())
    .then(data => {
      if (data.dados_anteriores) {
        aplicarDadosAnteriores(detalhes);
      } else {
        // É primeira inscrição - aplica avisos especiais
        aplicarAvisosPrimeiraInscricao();
      }
    })
    .catch(error => {
      console.error('Erro ao verificar dados anteriores:', error);
    });
}

// Função para aplicar indicação de dados anteriores na modal
function aplicarDadosAnteriores(detalhes) {
  const institutoSelect = document.querySelector('select[name="primeira_vez_instituto"]');
  const ayahuascaSelect = document.querySelector('select[name="primeira_vez_ayahuasca"]');

  // Desabilita os campos
  institutoSelect.disabled = true;
  ayahuascaSelect.disabled = true;

  // Adiciona aviso visual
  const avisoInstituto = document.createElement('div');
  avisoInstituto.className = 'aviso-dados-anteriores text-blue-600 text-xs mt-1 italic';
  avisoInstituto.textContent = '* Como este participante já tem inscrições anteriores, os campos "Primeira vez" foram automaticamente definidos como "Não" e não podem ser alterados.';
  institutoSelect.parentNode.appendChild(avisoInstituto);

  const avisoAyahuasca = document.createElement('div');
  avisoAyahuasca.className = 'aviso-dados-anteriores text-blue-600 text-xs mt-1 italic';
  avisoAyahuasca.textContent = '* Como este participante já tem inscrições anteriores, os campos "Primeira vez" foram automaticamente definidos como "Não" e não podem ser alterados.';
  ayahuascaSelect.parentNode.appendChild(avisoAyahuasca);

  // Adiciona aviso geral no topo do formulário
  const avisoGeral = document.createElement('div');
  avisoGeral.className = 'aviso-dados-anteriores bg-blue-50 border border-blue-200 rounded p-3 mb-4';
  avisoGeral.innerHTML = `
    <div class="flex items-center">
      <i class="fa-solid fa-info-circle text-blue-500 mr-2"></i>
      <span class="text-blue-700 text-sm">
        <strong>Informação:</strong> Como este participante já tem inscrições anteriores,
        os campos "Primeira vez" foram automaticamente definidos como "Não" e não podem ser alterados.
      </span>
    </div>
  `;

  const formContainer = document.querySelector('#form-detalhes-inscricao .space-y-4');
  formContainer.insertBefore(avisoGeral, formContainer.firstChild);
}

// Nova função para abrir modal com dados anteriores já aplicados
function abrirModalDetalhesComDadosAnteriores(ritualId, dadosAPI) {
  disableScroll();
  currentRitualId = ritualId;

  // Busca a inscrição recém-criada
  fetch(`/api/inscricoes/buscar-id?participante_id=${pessoaId}&ritual_id=${ritualId}`)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showToast(data.error, 'error');
        return;
      }

      document.getElementById('id').value = data.inscricao_id;

      // Preenche os campos com dados anteriores
      document.querySelector('select[name="primeira_vez_instituto"]').value = dadosAPI.primeira_vez_instituto;
      document.querySelector('select[name="primeira_vez_ayahuasca"]').value = dadosAPI.primeira_vez_ayahuasca;

      // Aplica a indicação de dados anteriores
      aplicarDadosAnteriores(dadosAPI);

      document.getElementById('modal-detalhes-inscricao').style.display = 'flex';
    })
    .catch(error => {
      console.error('Erro:', error);
    });
}

// Função para abrir o modal de observação
function abrirModalObservacao(ritualId) {
  disableScroll();
  currentRitualId = ritualId; // ✅ Armazena ID atual

  fetch(`/api/inscricoes/buscar-id?participante_id=${pessoaId}&ritual_id=${ritualId}`)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showToast(data.error, 'error');
        return;
      }
      const inscricaoId = data.inscricao_id;

      document.getElementById('inscricao_id_observacao').value = inscricaoId;

      fetch(`/api/inscricoes/detalhes-inscricao?id=${inscricaoId}`)
        .then(response => response.json())
        .then(detalhes => {
          if (detalhes.error) {
            showToast(detalhes.error, 'error');
            return;
          }

          // ✅ Ajustar título e botão da modal
          const modalTitle = document.querySelector('#modal-observacao h2');
          const observacaoTextarea = document.querySelector('#modal-observacao textarea[name="observacao"]');
          const submitBtn = document.querySelector('#modal-observacao button[type="submit"]');

          if (detalhes.observacao && detalhes.observacao.trim()) {
            modalTitle.textContent = 'Observação do ritual';
            observacaoTextarea.placeholder = 'Edite a observação...';
            submitBtn.innerHTML = '<i class="fa-solid fa-save mr-1"></i> Atualizar observação';
          } else {
            modalTitle.textContent = 'Adicionar observação';
            observacaoTextarea.placeholder = 'Digite sua observação sobre este ritual...';
            submitBtn.innerHTML = '<i class="fa-solid fa-plus mr-1"></i> Salvar observação';
          }

          observacaoTextarea.value = detalhes.observacao || '';

          const obsSalvoEm = detalhes.obs_salvo_em ?
            new Date(detalhes.obs_salvo_em).toLocaleDateString('pt-BR') : 'Nunca salvo';
          document.getElementById('obs_salvo_em').value = obsSalvoEm;
        })
        .catch(error => {
          console.error('Erro ao carregar detalhes:', error);
          showToast('Erro ao carregar observação', 'error');
        });

      document.getElementById('modal-observacao').style.display = 'flex';
    })
    .catch(error => {
      console.error('Erro ao buscar ID da inscrição:', error);
      showToast('Erro ao buscar dados da inscrição', 'error');
    });
}

// Funções para fechar modais
function fecharModalDetalhes() {
  document.getElementById('modal-detalhes-inscricao').style.display = 'none';
  enableScroll();
  currentRitualId = null;
}

function fecharModalObservacao() {
  document.getElementById('modal-observacao').style.display = 'none';
  enableScroll();
  currentRitualId = null;
}

function abrirModalCadastro() {
  disableScroll();
  document.getElementById('modal-cadastro').style.display = 'flex';
}

function fecharModalCadastro() {
  document.getElementById('modal-cadastro').style.display = 'none';
  enableScroll();
}

function aplicarFocoModalAdicionar() {
  // Observa quando a modal aparece e aplica foco
  const modal = document.getElementById('modal-adicionar');

  if (modal) {
    const observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
          const isVisible = modal.style.display === 'flex';

          if (isVisible) {
            setTimeout(() => {
              const inputPesquisa = document.getElementById('nome_pesquisa');
              if (inputPesquisa) {
                inputPesquisa.focus();
                console.log('✅ Foco aplicado automaticamente');
              }
            }, 100);
          }
        }
      });
    });

    observer.observe(modal, { attributes: true, attributeFilter: ['style'] });
  }
}

function abrirModalAdicionar() {
  disableScroll();
  document.getElementById('modal-adicionar').style.display = 'flex';

  // Foco automático no campo de pesquisa
  setTimeout(() => {
    const inputPesquisa = document.getElementById('nome_pesquisa');
    if (inputPesquisa) {
      inputPesquisa.focus();
    }
  }, 100);
}

function fecharModalAdicionar() {
  enableScroll();
  document.getElementById('modal-adicionar').style.display = 'none';
  setTimeout(() => {
    limparFiltroCompleto();
  }, 300);
}

// ============= FUNÇÕES DE NOTIFICAÇÃO =============
function removerNotificacaoObservacao(ritualId) {
  console.log('Removendo notificação observação para ritual:', ritualId);

  // Busca todos os botões de observação do card específico
  const cards = document.querySelectorAll('.bg-white.p-4.rounded-lg.shadow');

  cards.forEach(card => {
    const botaoObservacao = card.querySelector('button[onclick*="abrirModalObservacao"]');
    if (botaoObservacao) {
      const onclickAttr = botaoObservacao.getAttribute('onclick');
      if (onclickAttr && onclickAttr.includes(`abrirModalObservacao(${ritualId})`)) {
        const bolinha = botaoObservacao.querySelector('.bg-red-500');
        if (bolinha) {
          bolinha.remove();
          console.log('Bolinha de observação removida para ritual:', ritualId);
        }
      }
    }
  });
}

function removerNotificacaoDetalhes(ritualId) {
  console.log('Removendo notificação detalhes para ritual:', ritualId);

  const cards = document.querySelectorAll('.bg-white.p-4.rounded-lg.shadow');

  cards.forEach(card => {
    const botaoDetalhes = card.querySelector('button[onclick*="abrirModalDetalhes"]');
    if (botaoDetalhes) {
      const onclickAttr = botaoDetalhes.getAttribute('onclick');
      if (onclickAttr && onclickAttr.includes(`abrirModalDetalhes(${ritualId})`)) {
        const bolinha = botaoDetalhes.querySelector('.bg-red-500');
        if (bolinha) {
          bolinha.remove();
          console.log('Bolinha de detalhes removida para ritual:', ritualId);
        }
      }
    }
  });
}

// ============= EVENT LISTENERS DE FORMULÁRIOS =============

// ✅ Função para inicializar form de detalhes (evita duplicação)
function initFormDetalhes() {
  const formDetalhes = document.getElementById('form-detalhes-inscricao');
  if (formDetalhes && !formDetalhes.hasAttribute('data-initialized')) {
    formDetalhes.setAttribute('data-initialized', 'true');
    console.log('📝 Inicializando form detalhes...');

    formDetalhes.addEventListener('submit', function (event) {
      event.preventDefault();

      console.log('Form detalhes submetido');

      // Validação manual completa
      let formularioValido = true;
      const campos = {
        primeira_vez_instituto: formDetalhes.querySelector('[name="primeira_vez_instituto"]'),
        primeira_vez_ayahuasca: formDetalhes.querySelector('[name="primeira_vez_ayahuasca"]'),
        doenca_psiquiatrica: formDetalhes.querySelector('[name="doenca_psiquiatrica"]'),
        uso_medicao: formDetalhes.querySelector('[name="uso_medicao"]')
      };

      // Valida campos obrigatórios
      Object.keys(campos).forEach(nome => {
        const campo = campos[nome];
        if (campo && !campo.value.trim()) {
          campo.classList.add('border-red-500');
          campo.focus();
          formularioValido = false;
        } else if (campo) {
          campo.classList.remove('border-red-500');
        }
      });

      // Validação condicional
      const doencaSelect = campos.doenca_psiquiatrica;
      const nomeDoencaInput = formDetalhes.querySelector('[name="nome_doenca"]');

      if (doencaSelect && doencaSelect.value === 'Sim' &&
        nomeDoencaInput && !nomeDoencaInput.value.trim()) {
        nomeDoencaInput.classList.add('border-red-500');
        nomeDoencaInput.focus();
        formularioValido = false;
      } else if (nomeDoencaInput) {
        nomeDoencaInput.classList.remove('border-red-500');
      }

      const medicacaoSelect = campos.uso_medicao;
      const nomeMedicacaoInput = formDetalhes.querySelector('[name="nome_medicao"]');

      if (medicacaoSelect && medicacaoSelect.value === 'Sim' &&
        nomeMedicacaoInput && !nomeMedicacaoInput.value.trim()) {
        nomeMedicacaoInput.classList.add('border-red-500');
        nomeMedicacaoInput.focus();
        formularioValido = false;
      } else if (nomeMedicacaoInput) {
        nomeMedicacaoInput.classList.remove('border-red-500');
      }

      // Só envia se válido
      if (!formularioValido) {
        showToast("Por favor, preencha todos os campos obrigatórios.", 'error');
        return;
      }

      // Prossegue com o AJAX
      const formData = new FormData(formDetalhes);

      fetch('/api/inscricoes/salvar-inscricao', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showToast("Detalhes da inscrição salvos com sucesso!", 'success');
            fecharModalDetalhes();

            // AGORA remove a bolinha porque os dados foram salvos (salvo_em será preenchido)
            if (currentRitualId) {
              removerNotificacaoDetalhes(currentRitualId);
            }

            setTimeout(() => {
              location.reload();
            }, 1000);
          } else {
            showToast("Erro ao salvar detalhes da inscrição: " + data.error, 'error');
          }
        })
        .catch(error => {
          console.error('Erro ao enviar requisição:', error);
          showToast("Erro ao salvar detalhes da inscrição. Tente novamente.", 'error');
        });
    });
  }
}

// ✅ Função para inicializar form de observação (evita duplicação)
function initFormObservacao() {
  const formObservacao = document.getElementById('form-observacao');
  if (formObservacao && !formObservacao.hasAttribute('data-initialized')) {
    formObservacao.setAttribute('data-initialized', 'true');
    console.log('📝 Inicializando form observação...');

    formObservacao.addEventListener('submit', function (event) {
      event.preventDefault();
      console.log('Form observação submetido');

      const formData = new FormData(formObservacao);
      const observacao = formData.get('observacao');

      if (!observacao.trim()) {
        showToast("A observação não pode estar vazia.", 'error');
        return;
      }

      console.log('Enviando observação:', observacao, 'Ritual ID:', currentRitualId);

      fetch('/api/inscricoes/salvar-observacao', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          console.log('Resposta da API observação:', data);
          if (data.success) {
            showToast("Observação salva com sucesso!", 'success');
            fecharModalObservacao();

            // Remove notificação se tiver ritual ID
            if (currentRitualId) {
              removerNotificacaoObservacao(currentRitualId);
            }

            setTimeout(() => {
              location.reload();
            }, 1000);
          } else {
            showToast("Erro ao salvar observação: " + data.error, 'error');
          }
        })
        .catch(error => {
          console.error('Erro ao enviar requisição:', error);
          showToast("Erro ao salvar observação. Tente novamente.", 'error');
        });
    });
  }
}

// ============= PRESENÇA E CONTADORES =============
function togglePresenca(button) {
  const ritualId = button.getAttribute('data-ritual-id');
  const currentStatus = button.getAttribute('data-current-status');
  const newStatus = currentStatus === 'Sim' ? 'Não' : 'Sim';

  fetch(`/api/inscricoes/buscar-id?participante_id=${pessoaId}&ritual_id=${ritualId}`)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showToast(data.error, 'error');
        return;
      }
      const inscricaoId = data.inscricao_id;

      fetch(`/api/inscricoes/atualizar-presenca`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          inscricao_id: inscricaoId,
          novo_status: newStatus
        })
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // ✅ Atualizar botão com ícone + texto
            if (newStatus === 'Sim') {
              button.innerHTML = `
                <i class="fa-solid fa-check"></i>
                <span>Sim</span>
              `;
              button.classList.remove('bg-red-100', 'text-red-700', 'hover:bg-red-200');
              button.classList.add('bg-green-100', 'text-green-700', 'hover:bg-green-200', 'active');
            } else {
              button.innerHTML = `
                <i class="fa-solid fa-xmark"></i>
                <span>Não</span>
              `;
              button.classList.remove('bg-green-100', 'text-green-700', 'hover:bg-green-200', 'active');
              button.classList.add('bg-red-100', 'text-red-700', 'hover:bg-red-200');
            }

            button.setAttribute('data-current-status', newStatus);
            setTimeout(() => {
              atualizarContadores(newStatus);
            }, 200);
            showToast(`Presença atualizada para: ${newStatus}`, 'success');
          } else {
            showToast('Erro ao atualizar presença: ' + data.error, 'error');
          }
        })
        .catch(error => {
          console.error('Erro ao atualizar presença:', error);
          showToast('Erro ao atualizar presença', 'error');
        });
    })
    .catch(error => {
      console.error('Erro ao buscar ID da inscrição:', error);
      showToast('Erro ao buscar dados da inscrição', 'error');
    });
}

function atualizarContadores(novoStatus) {
  try {
    // Método 1: Busca por classe específica
    let contadorPresentes = document.querySelector('span.text-green-700');
    let contadorAusentes = document.querySelector('span.text-red-700');

    // Método 2: Se não encontrar, busca por contexto
    if (!contadorPresentes) {
      const spans = document.querySelectorAll('span');
      spans.forEach(span => {
        const parent = span.parentElement;
        if (parent && parent.textContent.includes('Participados') &&
          (span.classList.contains('text-green-700') || span.style.backgroundColor)) {
          contadorPresentes = span;
        }
      });
    }

    if (!contadorAusentes) {
      const spans = document.querySelectorAll('span');
      spans.forEach(span => {
        const parent = span.parentElement;
        if (parent && parent.textContent.includes('Não participados') &&
          (span.classList.contains('text-red-700') || span.style.backgroundColor)) {
          contadorAusentes = span;
        }
      });
    }

    if (contadorPresentes) {
      let presente = parseInt(contadorPresentes.textContent.trim()) || 0;
      let ausente = parseInt(contadorAusentes.textContent.trim()) || 0;

      if (novoStatus === 'Sim') {
        presente++;
        ausente--;
      } else {
        presente--;
        ausente++;
      }

      contadorPresentes.textContent = presente;
      contadorAusentes.textContent = ausente;
      console.log(`Contador atualizado: ${presente} (Status: ${novoStatus})`);
      console.log(`Contador atualizado: ${ausente} (Status: ${novoStatus})`);
    } else {
      console.warn('Contador não encontrado');
    }
  } catch (error) {
    console.error('Erro ao atualizar contador:', error);
  }
}

// ============= PESQUISA DE RITUAIS =============
// Event listener para Enter no campo de pesquisa - USANDO DELEGAÇÃO DE EVENTOS
document.addEventListener('keypress', function (event) {
  if (event.target && event.target.id === 'nome_pesquisa' && event.key === 'Enter') {
    event.preventDefault();
    pesquisarRituais();
  }
});

// Função para remover acentos e normalizar texto
function removerAcentos(texto) {
  return texto.normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();
}

function pesquisarRituais() {
  const nomePesquisa = document.getElementById('nome_pesquisa').value.trim();

  // Validação mínima de 3 caracteres
  if (nomePesquisa.length < 3) {
    showToast("Digite pelo menos 3 caracteres para pesquisar.", 'error');
    document.getElementById('nome_pesquisa').focus();
    return;
  }

  // Loading state no botão
  const pesquisarBtn = document.getElementById('pesquisar-btn');
  const originalText = pesquisarBtn ? pesquisarBtn.textContent : 'Pesquisar';

  if (pesquisarBtn) {
    pesquisarBtn.textContent = 'Pesquisando...';
    pesquisarBtn.disabled = true;
  }

  // Colapsa o filtro
  const formularioFiltro = document.getElementById('pesquisa-ritual-form');
  const botaoToggle = document.getElementById('botao-toggle-filtro');
  const limparFiltroBtn = document.getElementById('limpar-filtro-btn');

  if (formularioFiltro) formularioFiltro.style.display = 'none';
  if (botaoToggle) botaoToggle.classList.remove('hidden');
  if (limparFiltroBtn) limparFiltroBtn.style.display = 'inline-flex';

  // Mostra resultados
  const limparPesquisaBtn = document.getElementById('limpar-pesquisa-btn');
  const resultadosPesquisa = document.getElementById('resultados-pesquisa');
  if (limparPesquisaBtn) limparPesquisaBtn.style.display = 'inline-block';
  if (resultadosPesquisa) resultadosPesquisa.style.display = 'block';

  const listaRituais = document.getElementById('lista-rituais');
  if (listaRituais) listaRituais.innerHTML = '';

  // Texto de pesquisa normalizado (sem acentos)
  const pesquisaNormalizada = removerAcentos(nomePesquisa);

  // Executa pesquisa
  Promise.all([
    fetch(`/api/participante/buscar-ritual?nome=${encodeURIComponent(nomePesquisa)}`),
    fetch(`/api/inscricoes/rituais-vinculados?participante_id=${pessoaId}`)
  ])
    .then(responses => Promise.all(responses.map(r => r.json())))
    .then(([rituaisData, rituaisVinculadosData]) => {
      if (rituaisData.error) {
        showToast(rituaisData.error, 'error');
        return;
      }

      const rituaisVinculados = rituaisVinculadosData.rituais_ids || [];

      // Filtro adicional no frontend para ignorar acentos
      const rituaisFiltrados = rituaisData.filter(ritual => {
        const nomeNormalizado = removerAcentos(ritual.nome || '');
        return nomeNormalizado.includes(pesquisaNormalizada);
      });

      if (rituaisFiltrados.length === 0) {
        if (listaRituais) {
          listaRituais.innerHTML = `
            <li class="p-4 text-center text-gray-500">
              <i class="fa-solid fa-search text-2xl mb-2 block"></i>
              <p class="mt-2">Nenhum ritual encontrado para "<strong>${nomePesquisa}</strong>"</p>
              <p class="text-xs mt-1">Pode ser que esse ritual ainda não exista, crie pelo botão abaixo.</p>
            </li>
          `;
        }
        return;
      }

      // Exibe contador de resultados
      if (listaRituais) {
        const contadorResultados = document.createElement('li');
        contadorResultados.className = 'p-2 bg-blue-50 border-b border-blue-200 text-blue-700 text-sm font-medium';
        contadorResultados.innerHTML = `
          <i class="fa-solid fa-info-circle mr-1"></i>
          ${rituaisFiltrados.length} ritual(is) encontrado(s) para "<strong>${nomePesquisa}</strong>"
        `;
        listaRituais.appendChild(contadorResultados);
      }

      // Cria lista de rituais filtrados
      rituaisFiltrados.forEach(ritual => {
        const jaAdicionado = rituaisVinculados.includes(ritual.id);
        const li = document.createElement('li');
        li.className = 'p-4 hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-b-0';

        // Destacar termo pesquisado no nome
        const nomeDestacado = ritual.nome ?
          ritual.nome.replace(
            new RegExp(`(${nomePesquisa})`, 'gi'),
            '<mark class="bg-yellow-200 px-1 rounded">$1</mark>'
          ) : 'Nome não informado';

        const dataFormatada = formatarData(ritual.data_ritual);

        li.innerHTML = `
          <div class="grid grid-cols-[auto_1fr] gap-4">
            <div class="flex-shrink-0">
              <img src="${ritual.foto || '/assets/images/no-image.png'}"
                   onerror="this.src='/assets/images/no-image.png';"
                   alt="Foto do ritual"
                   class="w-16 h-16 rounded-lg object-cover border border-gray-200">
            </div>
            <div class="space-y-2">
              <h3 class="!font-semibold !text-gray-900 !text-lg !leading-tight !m-0 !p-0">
                ${nomeDestacado}
              </h3>
              <div class="flex items-center gap-1">
                <span class="text-sm font-semibold">Data:</span>
                <p class="text-sm text-gray-600">
                  ${ritual.data_ritual ? `<i class="fa-solid fa-calendar mr-1"></i>${dataFormatada}` : 'Data não informada'}
                </p>
              </div>
              <div class="pt-1">
                ${jaAdicionado ?
            `<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fa-solid fa-check"></i>
                    Já adicionado
                  </span>` :
            `<button onclick="adicionarRitual(${ritual.id})"
                           class="bg-[#00bfff] hover:bg-yellow-400 text-black px-4 py-2 rounded text-sm font-semibold transition-colors shadow-sm">
                    <i class="fa-solid fa-plus mr-1"></i>
                    Adicionar
                  </button>`
          }
              </div>
            </div>
          </div>
        `;

        if (listaRituais) listaRituais.appendChild(li);
      });

      // Feedback de sucesso
      showToast(`${rituaisFiltrados.length} ritual(is) encontrado(s)!`, 'success');
    })
    .catch(error => {
      console.error('Erro ao buscar rituais:', error);
      showToast('Erro ao carregar rituais. Verifique sua conexão e tente novamente.', 'error');

      // Exibe mensagem de erro na lista
      if (listaRituais) {
        listaRituais.innerHTML = `
          <li class="p-4 text-center text-red-500">
            <i class="fa-solid fa-exclamation-triangle text-2xl mb-2 block"></i>
            <p>Erro ao carregar rituais</p>
            <button onclick="pesquisarRituais()"
                    class="mt-2 text-sm underline hover:no-underline">
              Tentar novamente
            </button>
          </li>
        `;
      }
    })
    .finally(() => {
      // Restaura o botão após pesquisa
      if (pesquisarBtn) {
        pesquisarBtn.textContent = originalText;
        pesquisarBtn.disabled = false;
      }
    });
}

// Funções de filtro
function toggleFiltroRitual() {
  const formularioFiltro = document.getElementById('pesquisa-ritual-form');
  const botaoToggle = document.getElementById('botao-toggle-filtro');

  if (formularioFiltro && formularioFiltro.style.display === 'none') {
    formularioFiltro.style.display = 'block';
    if (botaoToggle) botaoToggle.classList.add('hidden');
  } else {
    if (formularioFiltro) formularioFiltro.style.display = 'none';
    if (botaoToggle) botaoToggle.classList.remove('hidden');
  }
}

function limparFiltroCompleto() {
  const elements = {
    nomePesquisa: document.getElementById('nome_pesquisa'),
    listaRituais: document.getElementById('lista-rituais'),
    resultados: document.getElementById('resultados-pesquisa'),
    limparPesquisa: document.getElementById('limpar-pesquisa-btn'),
    formulario: document.getElementById('pesquisa-ritual-form'),
    botaoToggle: document.getElementById('botao-toggle-filtro'),
    limparFiltro: document.getElementById('limpar-filtro-btn')
  };

  if (elements.nomePesquisa) elements.nomePesquisa.value = '';
  if (elements.listaRituais) elements.listaRituais.innerHTML = '';
  if (elements.resultados) elements.resultados.style.display = 'none';
  if (elements.limparPesquisa) elements.limparPesquisa.style.display = 'none';
  if (elements.formulario) elements.formulario.style.display = 'block';
  if (elements.botaoToggle) elements.botaoToggle.classList.add('hidden');
  if (elements.limparFiltro) elements.limparFiltro.style.display = 'none';

  setTimeout(() => {
    if (elements.nomePesquisa) {
      elements.nomePesquisa.focus();
    }
  }, 100);
}

function limparPesquisa() {
  const elements = {
    nomePesquisa: document.getElementById('nome_pesquisa'),
    listaRituais: document.getElementById('lista-rituais'),
    resultados: document.getElementById('resultados-pesquisa'),
    limparPesquisa: document.getElementById('limpar-pesquisa-btn')
  };

  if (elements.nomePesquisa) elements.nomePesquisa.value = '';
  if (elements.listaRituais) elements.listaRituais.innerHTML = '';
  if (elements.resultados) elements.resultados.style.display = 'none';
  if (elements.limparPesquisa) elements.limparPesquisa.style.display = 'none';

  setTimeout(() => {
    if (elements.nomePesquisa) {
      elements.nomePesquisa.focus();
    }
  }, 100);
}

// ============= RITUAL ACTIONS =============
function adicionarNovoRitual() {
  const participanteIdInput = document.querySelector('#modal-adicionar input[name="participante_id"]');
  if (participanteIdInput) {
    const participanteId = participanteIdInput.value;
    window.location.href = `/ritual/novo?redirect=/participante/${participanteId}`;
  }
}

function adicionarRitual(ritualId) {
  const participanteIdInput = document.querySelector('#modal-adicionar input[name="participante_id"]');
  if (!participanteIdInput) {
    showToast('Erro: ID do participante não encontrado', 'error');
    return;
  }

  const participanteId = participanteIdInput.value;

  fetch('/api/participante/adicionar-ritual', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      ritual_id: ritualId,
      participante_id: participanteId
    })
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showToast('Ritual adicionado com sucesso!', 'success');

        // 1. Atualiza o botão na lista para "Já adicionado"
        atualizarBotaoParaJaAdicionado(ritualId);

        // 2. Atualiza a página de fundo (sem fechar modal)
        atualizarPaginaFundo();

        // 3. Expande filtro e limpa para nova pesquisa
        setTimeout(() => {
          expandirFiltroELimpar();
        }, 1000);

      } else {
        showToast('Erro ao adicionar ritual: ' + data.error, 'error');
      }
    })
    .catch(error => {
      console.error('Erro ao adicionar ritual:', error);
      showToast('Erro ao adicionar ritual', 'error');
    });
}

function atualizarBotaoParaJaAdicionado(ritualId) {
  const listaRituais = document.getElementById('lista-rituais');
  if (!listaRituais) return;

  // Encontra o item da lista do ritual
  const botaoAdicionar = listaRituais.querySelector(`button[onclick="adicionarRitual(${ritualId})"]`);

  if (botaoAdicionar) {
    // Substitui o botão por uma tag "Já adicionado"
    botaoAdicionar.outerHTML = `
      <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
        <i class="fa-solid fa-check"></i>
        Já adicionado
      </span>
    `;
  }
}

function atualizarPaginaFundo() {
  // Faz uma requisição silenciosa para buscar a lista atualizada
  const currentUrl = window.location.href;

  fetch(currentUrl)
    .then(response => response.text())
    .then(html => {
      // Cria um parser temporário
      const parser = new DOMParser();
      const novoDoc = parser.parseFromString(html, 'text/html');

      // Atualiza apenas a seção de cards dos rituais
      const novaListaCards = novoDoc.querySelector('.grid.gap-4.grid-cols-1.md\\:grid-cols-2.xl\\:grid-cols-3');
      const listaCardsAtual = document.querySelector('.grid.gap-4.grid-cols-1.md\\:grid-cols-2.xl\\:grid-cols-3');

      //Atualiza tabela
      const tabelaNova = novoDoc.querySelector('table');
      const tabelaAtual = document.querySelector('table');

      if (novaListaCards && listaCardsAtual) {
        listaCardsAtual.innerHTML = novaListaCards.innerHTML;
        tabelaAtual.innerHTML = tabelaNova.innerHTML;

        // Reaplica os event listeners nos novos elementos
        reaplicarEventListeners();
      }

      // Atualiza contador no cabeçalho
      const novoContador = novoDoc.querySelector('span.bg-\\[\\#00bfff\\]');
      const contadorAtual = document.querySelector('span.bg-\\[\\#00bfff\\]');

      if (novoContador && contadorAtual) {
        contadorAtual.textContent = novoContador.textContent;
      }
    })
    .catch(error => {
      console.error('Erro ao atualizar página de fundo:', error);
    });
}

// Nova função para reativar event listeners após atualização
function reaplicarEventListeners() {
  // Reaplica listeners para botões de presença
  document.querySelectorAll('.presence-btn').forEach(button => {
    button.removeEventListener('click', handlePresenceClick);
    button.addEventListener('click', handlePresenceClick);
  });

  // Reaplica listeners para modais
  initModalEventListeners();
}

// Handler separado para botões de presença
function handlePresenceClick(event) {
  const button = event.currentTarget;
  togglePresenca(button);
}

function expandirFiltroELimpar() {
  const elements = {
    nomePesquisa: document.getElementById('nome_pesquisa'),
    listaRituais: document.getElementById('lista-rituais'),
    resultados: document.getElementById('resultados-pesquisa'),
    limparPesquisa: document.getElementById('limpar-pesquisa-btn'),
    formulario: document.getElementById('pesquisa-ritual-form'),
    botaoToggle: document.getElementById('botao-toggle-filtro'),
    limparFiltro: document.getElementById('limpar-filtro-btn')
  };

  // Limpa campos e resultados
  if (elements.nomePesquisa) elements.nomePesquisa.value = '';
  if (elements.limparPesquisa) elements.limparPesquisa.style.display = 'none';

  // Expande o filtro
  if (elements.formulario) elements.formulario.style.display = 'block';
  if (elements.botaoToggle) elements.botaoToggle.classList.add('hidden');

  // Foca no campo de pesquisa
  setTimeout(() => {
    if (elements.nomePesquisa) {
      elements.nomePesquisa.focus();
    }
  }, 100);

  showToast('Pronto para adicionar outro ritual!', 'success');
}

// ============= UTILITÁRIOS =============
function formatarData(dataString) {
  if (!dataString) return 'Data não informada';

  try {
    if (dataString.includes('-')) {
      const [ano, mes, dia] = dataString.split(' ')[0].split('-');
      const data = new Date(parseInt(ano), parseInt(mes) - 1, parseInt(dia));
      if (isNaN(data.getTime())) return dataString;
      return data.toLocaleDateString('pt-BR');
    }

    const data = new Date(dataString);
    if (isNaN(data.getTime())) return dataString;
    return data.toLocaleDateString('pt-BR');
  } catch (error) {
    console.error('Erro ao formatar data:', error);
    return dataString;
  }
}

// ============= SETUP FUNCTIONS =============
function setupConditionalFields() {
  function toggleNomeDoenca() {
    const doencaPsiquiatrica = document.getElementById("doenca_psiquiatrica");
    const nomeDoenca = document.getElementById("nome_doenca");
    if (doencaPsiquiatrica && nomeDoenca) {
      if (doencaPsiquiatrica.value === "Sim") {
        nomeDoenca.disabled = false;
        nomeDoenca.required = true;
      } else {
        nomeDoenca.disabled = true;
        nomeDoenca.required = false;
        nomeDoenca.value = "";
      }
    }
  }

  function toggleNomeMedicacao() {
    const usoMedicacao = document.getElementById("uso_medicao");
    const nomeMedicacao = document.getElementById("nome_medicao");
    if (usoMedicacao && nomeMedicacao) {
      if (usoMedicacao.value === "Sim") {
        nomeMedicacao.disabled = false;
        nomeMedicacao.required = true;
      } else {
        nomeMedicacao.disabled = true;
        nomeMedicacao.required = false;
        nomeMedicacao.value = "";
      }
    }
  }

  const doencaSelect = document.getElementById("doenca_psiquiatrica");
  const medicacaoSelect = document.getElementById("uso_medicao");

  if (doencaSelect) {
    doencaSelect.addEventListener("change", toggleNomeDoenca);
    toggleNomeDoenca();
  }

  if (medicacaoSelect) {
    medicacaoSelect.addEventListener("change", toggleNomeMedicacao);
    toggleNomeMedicacao();
  }
}

// Confirmação excluir participante
function abrirConfirmacaoExcluir(url) {
  openConfirmModal('Tem certeza que deseja desvincular este ritual do participante? Observação e dados de inscrição serão apagados!', () => {
    window.location.href = url;
  });
}

// ============= VALIDAÇÃO PERSONALIZADA PARA MODAL DETALHES =============
document.addEventListener('DOMContentLoaded', function () {
  const formDetalhes = document.getElementById('form-detalhes-inscricao');

  if (formDetalhes) {
    // Aplica a validação personalizada do global-scripts.js
    if (typeof configurarValidacao === 'function') {
      configurarValidacao(formDetalhes);
    }

    // ✅ VALIDAÇÃO ESPECÍFICA PARA CAMPOS CONDICIONAIS
    const doencaSelect = document.getElementById('doenca_psiquiatrica');
    const nomeDoencaInput = document.getElementById('nome_doenca');
    const medicacaoSelect = document.getElementById('uso_medicao');
    const nomeMedicacaoInput = document.getElementById('nome_medicao');

    // Função para validar campos condicionais
    function validarCamposCondicionais() {
      let valido = true;

      // Validação nome da doença
      if (doencaSelect && nomeDoencaInput) {
        const mensagemDoenca = nomeDoencaInput.nextElementSibling;

        if (doencaSelect.value === 'Sim' && !nomeDoencaInput.value.trim()) {
          nomeDoencaInput.classList.add('border-red-500');
          if (mensagemDoenca && mensagemDoenca.classList.contains('text-red-500')) {
            mensagemDoenca.classList.remove('hidden');
          }
          valido = false;
        } else {
          nomeDoencaInput.classList.remove('border-red-500');
          if (mensagemDoenca && mensagemDoenca.classList.contains('text-red-500')) {
            mensagemDoenca.classList.add('hidden');
          }
        }
      }

      // Validação nome da medicação
      if (medicacaoSelect && nomeMedicacaoInput) {
        const mensagemMedicacao = nomeMedicacaoInput.nextElementSibling;

        if (medicacaoSelect.value === 'Sim' && !nomeMedicacaoInput.value.trim()) {
          nomeMedicacaoInput.classList.add('border-red-500');
          if (mensagemMedicacao && mensagemMedicacao.classList.contains('text-red-500')) {
            mensagemMedicacao.classList.remove('hidden');
          }
          valido = false;
        } else {
          nomeMedicacaoInput.classList.remove('border-red-500');
          if (mensagemMedicacao && mensagemMedicacao.classList.contains('text-red-500')) {
            mensagemMedicacao.classList.add('hidden');
          }
        }
      }

      return valido;
    }

    // Intercepta o submit para incluir validação condicional
    formDetalhes.addEventListener('submit', function (e) {
      // Valida campos condicionais
      const camposCondicionaisValidos = validarCamposCondicionais();

      if (!camposCondicionaisValidos) {
        e.preventDefault();
        showToast('Por favor, preencha todos os campos obrigatórios.', 'error');
      }
    });

    // Validação em tempo real nos campos condicionais
    if (nomeDoencaInput) {
      nomeDoencaInput.addEventListener('blur', validarCamposCondicionais);
      nomeDoencaInput.addEventListener('input', validarCamposCondicionais);
    }

    if (nomeMedicacaoInput) {
      nomeMedicacaoInput.addEventListener('blur', validarCamposCondicionais);
      nomeMedicacaoInput.addEventListener('input', validarCamposCondicionais);
    }

    // Limpa validação quando campos condicionais mudam
    if (doencaSelect) {
      doencaSelect.addEventListener('change', function () {
        validarCamposCondicionais();
      });
    }

    if (medicacaoSelect) {
      medicacaoSelect.addEventListener('change', function () {
        validarCamposCondicionais();
      });
    }
  }
});