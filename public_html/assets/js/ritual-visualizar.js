// ============= VARIÁVEIS GLOBAIS =============
let currentParticipanteId = null; // Para rastrear o participante atual

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
  // Lista de IDs das modais que devem ter fechamento ao clicar fora
  const modalIds = [
    'modal-detalhes-inscricao',
    'modal-observacao',
    'modal-adicionar'
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
      'modal-adicionar'
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

  // Form de detalhes da inscrição
  initFormDetalhes();

  // Form de observação
  initFormObservacao();

  // Outras inicializações
  setupConditionalFields();
  aplicarFocoModalAdicionar();
});

// ============= FUNÇÕES DE MODAL =============
function abrirModalDetalhes(participanteId) {
  disableScroll();
  currentParticipanteId = participanteId;

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

  fetch(`/api/inscricoes/buscar-id?participante_id=${participanteId}&ritual_id=${ritualId}`)
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
            verificarDadosAnteriores(participanteId, inscricaoId, detalhes);

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
function abrirModalDetalhesComDadosAnteriores(participanteId, dadosAPI) {
  disableScroll();
  currentParticipanteId = participanteId;

  // Busca a inscrição recém-criada
  fetch(`/api/inscricoes/buscar-id?participante_id=${participanteId}&ritual_id=${ritualId}`)
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
function abrirModalObservacao(participanteId) {
  disableScroll();
  currentParticipanteId = participanteId;

  fetch(`/api/inscricoes/buscar-id?participante_id=${participanteId}&ritual_id=${ritualId}`)
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

          // Ajustar título e botão da modal
          const modalTitle = document.querySelector('#modal-observacao h2');
          const observacaoTextarea = document.querySelector('#modal-observacao textarea[name="observacao"]');
          const submitBtn = document.querySelector('#modal-observacao button[type="submit"]');

          if (detalhes.observacao && detalhes.observacao.trim()) {
            modalTitle.textContent = 'Observação do participante';
            observacaoTextarea.placeholder = 'Edite a observação...';
            submitBtn.innerHTML = '<i class="fa-solid fa-save mr-1"></i> Atualizar observação';
          } else {
            modalTitle.textContent = 'Adicionar observação';
            observacaoTextarea.placeholder = 'Digite sua observação sobre este participante...';
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

// Funções para fechar modals
function fecharModalDetalhes() {
  document.getElementById('modal-detalhes-inscricao').style.display = 'none';
  enableScroll();
  currentParticipanteId = null;
}

function fecharModalObservacao() {
  document.getElementById('modal-observacao').style.display = 'none';
  enableScroll();
  currentParticipanteId = null;
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
  limparPesquisa();
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

// ============= FUNÇÕES DE NOTIFICAÇÃO =============
function removerNotificacaoObservacao(participanteId) {
  console.log('Removendo notificação observação para participante:', participanteId);

  // Busca todos os botões de observação do card específico
  const cards = document.querySelectorAll('.bg-white.p-4.rounded-lg.shadow');

  cards.forEach(card => {
    const botaoObservacao = card.querySelector('button[onclick*="abrirModalObservacao"]');
    if (botaoObservacao) {
      const onclickAttr = botaoObservacao.getAttribute('onclick');
      if (onclickAttr && onclickAttr.includes(`abrirModalObservacao(${participanteId})`)) {
        const bolinha = botaoObservacao.querySelector('.bg-red-500');
        if (bolinha) {
          bolinha.remove();
          console.log('Bolinha de observação removida para participante:', participanteId);
        }
      }
    }
  });
}

function removerNotificacaoDetalhes(participanteId) {
  console.log('Removendo notificação detalhes para participante:', participanteId);

  const cards = document.querySelectorAll('.bg-white.p-4.rounded-lg.shadow');

  cards.forEach(card => {
    const botaoDetalhes = card.querySelector('button[onclick*="abrirModalDetalhes"]');
    if (botaoDetalhes) {
      const onclickAttr = botaoDetalhes.getAttribute('onclick');
      if (onclickAttr && onclickAttr.includes(`abrirModalDetalhes(${participanteId})`)) {
        const bolinha = botaoDetalhes.querySelector('.bg-red-500');
        if (bolinha) {
          bolinha.remove();
          console.log('Bolinha de detalhes removida para participante:', participanteId);
        }
      }
    }
  });
}

// ============= EVENT LISTENERS DE FORMULÁRIOS =============

// Função para inicializar form de detalhes
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
          if (data.success && data.doenca_psiquiatrica !== '') {
            showToast("Detalhes da inscrição salvos com sucesso!", 'success');
            fecharModalDetalhes();

            if (currentParticipanteId) {
              removerNotificacaoDetalhes(currentParticipanteId);
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

// Função para inicializar form de observação
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

      console.log('Enviando observação:', observacao, 'Participante ID:', currentParticipanteId);

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

            // Remove notificação se tiver participante ID
            if (currentParticipanteId) {
              removerNotificacaoObservacao(currentParticipanteId);
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

// ============= PRESENÇA =============
function togglePresenca(button) {
  const participanteId = button.getAttribute('data-participante-id');
  const currentStatus = button.getAttribute('data-current-status');
  const newStatus = currentStatus === 'Sim' ? 'Não' : 'Sim';

  fetch(`/api/inscricoes/buscar-id?participante_id=${participanteId}&ritual_id=${ritualId}`)
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
            // Atualizar botão com ícone + texto
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
              atualizarContadorParticipantes(newStatus);
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

function atualizarContadorParticipantes(novoStatus) {
  try {
    // Método 1: Busca por classe específica
    let contadorPresentes = document.querySelector('span.text-green-700');
    let contadorAusentes = document.querySelector('span.text-red-700');

    // Método 2: Se não encontrar, busca por contexto
    if (!contadorPresentes) {
      const spans = document.querySelectorAll('span');
      spans.forEach(span => {
        const parent = span.parentElement;
        if (parent && parent.textContent.includes('Presentes') &&
          (span.classList.contains('text-green-700') || span.style.backgroundColor)) {
          contadorPresentes = span;
        }
      });
    }

    if (!contadorAusentes) {
      const spans = document.querySelectorAll('span');
      spans.forEach(span => {
        const parent = span.parentElement;
        if (parent && parent.textContent.includes('Ausentes') &&
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

// ============= PESQUISA DE PARTICIPANTES =============
// Event listener para Enter no campo de pesquisa - USANDO DELEGAÇÃO DE EVENTOS
document.addEventListener('keypress', function (event) {
  if (event.target && event.target.id === 'nome_pesquisa' && event.key === 'Enter') {
    event.preventDefault();
    pesquisarParticipantes();
  }
});

function pesquisarParticipantes() {
  const nomePesquisa = document.getElementById('nome_pesquisa').value.trim();

  // Validação melhorada: CPF precisa ter exatamente 11 dígitos, nome pelo menos 3 caracteres
  const apenasNumeros = nomePesquisa.replace(/\D/g, '');
  const ehCPF = apenasNumeros.length === 11; // Exatamente 11 dígitos
  const ehNome = nomePesquisa.length >= 3 && apenasNumeros.length !== 11;

  if (!ehCPF && !ehNome) {
    if (apenasNumeros.length > 0 && apenasNumeros.length !== 11) {
      showToast("CPF deve ter exatamente 11 dígitos.", 'error');
    } else {
      showToast("Digite pelo menos 3 caracteres para pesquisar pelo nome.", 'error');
    }
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
  const formularioFiltro = document.getElementById('pesquisa-participante-form');
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

  const listaParticipantes = document.getElementById('lista-participantes');
  if (listaParticipantes) listaParticipantes.innerHTML = '';

  const apiUrl = `/api/ritual/buscar-participante?nome=${encodeURIComponent(nomePesquisa)}`;
  console.log('🌐 URL da API:', apiUrl);
  // Executa pesquisa
  Promise.all([
    fetch(`/api/ritual/buscar-participante?nome=${encodeURIComponent(nomePesquisa)}`),
    fetch(`/api/inscricoes/participantes-vinculados?ritual_id=${ritualId}`)
  ])
    .then(responses => Promise.all(responses.map(r => r.json())))
    .then(([participantesData, participantesVinculadosData]) => {
      if (participantesData.error) {
        showToast(participantesData.error, 'error');
        return;
      }

      const participantesVinculados = participantesVinculadosData.participantes_ids || [];

      if (participantesData.length === 0) {
        if (listaParticipantes) {
          const tipoTermoBusca = ehCPF ? 'CPF' : 'nome';
          listaParticipantes.innerHTML = `
            <li class="p-4 text-center text-gray-500">
              <i class="fa-solid fa-search text-2xl mb-2 block"></i>
              <p class="mt-2">Nenhum participante encontrado para o ${tipoTermoBusca} "<strong>${nomePesquisa}</strong>"</p>
              <p class="text-xs mt-1">Pode ser que esse participante ainda não exista, crie pelo botão abaixo.</p>
            </li>
          `;
        }
        return;
      }

      // Exibe contador de resultados
      if (listaParticipantes) {
        const contadorResultados = document.createElement('li');
        contadorResultados.className = 'p-2 bg-blue-50 border-b border-blue-200 text-blue-700 text-sm font-medium';
        contadorResultados.innerHTML = `
          <i class="fa-solid fa-info-circle mr-1"></i>
          ${participantesData.length} participante(s) encontrado(s) para "<strong>${nomePesquisa}</strong>"
        `;
        listaParticipantes.appendChild(contadorResultados);
      }

      // Cria lista de participantes
      participantesData.forEach(participante => {
        const jaAdicionado = participantesVinculados.includes(participante.id);
        const li = document.createElement('li');
        li.className = 'p-4 hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-b-0';

        // Destacar termo pesquisado no nome
        let nomeDestacado = participante.nome_completo || 'Nome não informado';

        // Se busca foi por nome, destacar no nome
        if (!ehCPF && participante.nome_completo) {
          nomeDestacado = participante.nome_completo.replace(
            new RegExp(`(${nomePesquisa.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi'),
            '<mark class="bg-yellow-200 px-1 rounded">$1</mark>'
          );
        }

        const cpfFormatado = formatarCPF(participante.cpf);

        li.innerHTML = `
          <div class="grid grid-cols-[auto_1fr] gap-4">
            <div class="flex-shrink-0">
              <img src="${participante.foto || '/assets/images/no-image.png'}"
                   onerror="this.src='/assets/images/no-image.png';"
                   alt="Foto do participante"
                   class="w-16 h-16 rounded-lg object-cover border border-gray-200">
            </div>
            <div class="space-y-2">
              <h3 class="!font-semibold !text-gray-900 !text-lg !leading-tight !m-0 !p-0">
                ${nomeDestacado}
              </h3>
              <div class="flex items-center gap-1">
                <span class="text-sm font-semibold">CPF:</span>
                <p class="text-sm text-gray-600">
                  ${cpfFormatado ? `<i class="fa-solid fa-id-card mr-1"></i>${cpfFormatado}` : 'CPF não informado'}
                </p>
              </div>
              <div class="pt-1">
                ${jaAdicionado ?
            `<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fa-solid fa-check"></i>
                    Já adicionado
                  </span>` :
            `<button onclick="adicionarParticipante(${participante.id})"
                           class="bg-[#00bfff] hover:bg-yellow-400 text-black px-4 py-2 rounded text-sm font-semibold transition-colors shadow-sm">
                    <i class="fa-solid fa-plus mr-1"></i>
                    Adicionar
                  </button>`
          }
              </div>
            </div>
          </div>
        `;

        if (listaParticipantes) listaParticipantes.appendChild(li);
      });

      // Feedback de sucesso
      showToast(`${participantesData.length} participante(s) encontrado(s)!`, 'success');
    })
    .catch(error => {
      console.error('Erro ao buscar participantes:', error);
      showToast('Erro ao carregar participantes. Verifique sua conexão e tente novamente.', 'error');

      // Exibe mensagem de erro na lista
      if (listaParticipantes) {
        listaParticipantes.innerHTML = `
          <li class="p-4 text-center text-red-500">
            <i class="fa-solid fa-exclamation-triangle text-2xl mb-2 block"></i>
            <p>Erro ao carregar participantes</p>
            <button onclick="pesquisarParticipantes()"
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
  const formularioFiltro = document.getElementById('pesquisa-participante-form');
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
    listaParticipantes: document.getElementById('lista-participantes'),
    resultados: document.getElementById('resultados-pesquisa'),
    limparPesquisa: document.getElementById('limpar-pesquisa-btn'),
    formulario: document.getElementById('pesquisa-participante-form'),
    botaoToggle: document.getElementById('botao-toggle-filtro'),
    limparFiltro: document.getElementById('limpar-filtro-btn')
  };

  if (elements.nomePesquisa) elements.nomePesquisa.value = '';
  if (elements.listaParticipantes) elements.listaParticipantes.innerHTML = '';
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
    listaParticipantes: document.getElementById('lista-participantes'),
    resultados: document.getElementById('resultados-pesquisa'),
    limparPesquisa: document.getElementById('limpar-pesquisa-btn')
  };

  if (elements.nomePesquisa) elements.nomePesquisa.value = '';
  if (elements.listaParticipantes) elements.listaParticipantes.innerHTML = '';
  if (elements.resultados) elements.resultados.style.display = 'none';
  if (elements.limparPesquisa) elements.limparPesquisa.style.display = 'none';

  setTimeout(() => {
    if (elements.nomePesquisa) {
      elements.nomePesquisa.focus();
    }
  }, 100);
}

// ============= PARTICIPANTE ACTIONS =============
function adicionarNovaPessoa() {
  const ritualIdInput = document.querySelector('#modal-adicionar input[name="ritual_id"]');
  if (ritualIdInput) {
    const ritualIdValue = ritualIdInput.value;
    window.location.href = `/participante/novo?redirect=/ritual/${ritualIdValue}`;
  }
}

function adicionarParticipante(participanteId) {
  const ritualIdInput = document.querySelector('#modal-adicionar input[name="ritual_id"]');
  if (!ritualIdInput) {
    showToast('Erro: ID do ritual não encontrado', 'error');
    return;
  }

  const ritualIdValue = ritualIdInput.value;

  fetch('/api/ritual/adicionar-participante', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      participante_id: participanteId,
      ritual_id: ritualIdValue
    })
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showToast('Participante adicionado com sucesso!', 'success');

        // 1. Atualiza o botão na lista para "Já adicionado"
        atualizarBotaoParaJaAdicionado(participanteId);

        // 2. Atualiza a página de fundo (sem fechar modal)
        atualizarPaginaFundo();

        // 3. Expande filtro e limpa para nova pesquisa
        setTimeout(() => {
          expandirFiltroELimpar();
        }, 1000);

      } else {
        showToast('Erro ao adicionar participante: ' + data.error, 'error');
      }
    })
    .catch(error => {
      console.error('Erro ao adicionar participante:', error);
      showToast('Erro ao adicionar participante', 'error');
    });
}

// Nova função para atualizar botão na lista
function atualizarBotaoParaJaAdicionado(participanteId) {
  const listaParticipantes = document.getElementById('lista-participantes');
  if (!listaParticipantes) return;

  // Encontra o item da lista do participante
  const botaoAdicionar = listaParticipantes.querySelector(`button[onclick="adicionarParticipante(${participanteId})"]`);

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

// Nova função para atualizar página de fundo
function atualizarPaginaFundo() {
  // Faz uma requisição silenciosa para buscar a lista atualizada
  const currentUrl = window.location.href;

  fetch(currentUrl)
    .then(response => response.text())
    .then(html => {
      // Cria um parser temporário
      const parser = new DOMParser();
      const novoDoc = parser.parseFromString(html, 'text/html');

      // Atualiza apenas a seção de cards dos participantes
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
      const novoContador = novoDoc.querySelector('span.text-blue-500');
      const contadorAtual = document.querySelector('span.text-blue-500');

      if (novoContador && contadorAtual) {
        contadorAtual.textContent = novoContador.textContent;
      }

      const novoContadorPresenca = novoDoc.querySelector('span.text-green-700');
      const contadorPresencaAtual = document.querySelector('span.text-green-700');
      if (novoContadorPresenca && contadorPresencaAtual) {
        contadorPresencaAtual.textContent = novoContadorPresenca.textContent;
      }

      const novoContadorAusente = novoDoc.querySelector('span.text-red-700');
      const contadorAusenteAtual = document.querySelector('span.text-red-700');
      if (novoContadorAusente && contadorAusenteAtual) {
        contadorAusenteAtual.textContent = novoContadorAusente.textContent;
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

// Nova função para expandir filtro e limpar
function expandirFiltroELimpar() {
  const elements = {
    nomePesquisa: document.getElementById('nome_pesquisa'),
    listaParticipantes: document.getElementById('lista-participantes'),
    resultados: document.getElementById('resultados-pesquisa'),
    limparPesquisa: document.getElementById('limpar-pesquisa-btn'),
    formulario: document.getElementById('pesquisa-participante-form'),
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

  showToast('Pronto para adicionar outro participante!', 'success');
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

// ============= UTILITÁRIOS =============
function aplicarMascaraCPF(input) {
  let valor = input.value;

  // Remove tudo que não é número
  let apenasNumeros = valor.replace(/\D/g, '');

  // Se digitou só números e tem pelo menos 1 dígito, aplica a máscara
  if (apenasNumeros.length > 0) {
    // Limita a 11 dígitos
    apenasNumeros = apenasNumeros.substring(0, 11);

    // Aplica a máscara progressivamente
    let valorFormatado = apenasNumeros;

    if (apenasNumeros.length >= 4) {
      valorFormatado = apenasNumeros.replace(/(\d{3})(\d+)/, '$1.$2');
    }
    if (apenasNumeros.length >= 7) {
      valorFormatado = apenasNumeros.replace(/(\d{3})(\d{3})(\d+)/, '$1.$2.$3');
    }
    if (apenasNumeros.length >= 10) {
      valorFormatado = apenasNumeros.replace(/(\d{3})(\d{3})(\d{3})(\d+)/, '$1.$2.$3-$4');
    }

    // Só atualiza se o valor mudou (evita loop infinito)
    if (input.value !== valorFormatado) {
      input.value = valorFormatado;
    }
  }
}

function formatarCPF(cpf) {
  // Remove todos os caracteres que não são números
  const apenasNumeros = cpf.replace(/\D/g, '');

  // Verifica se tem 11 dígitos (CPF válido)
  if (apenasNumeros.length !== 11) {
    return cpf; // Retorna o valor original se não tiver 11 dígitos
  }

  // Aplica a máscara: 000.000.000-00
  return apenasNumeros.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

// Confirmação excluir participante
function abrirConfirmacaoExcluir(url) {
  openConfirmModal('Tem certeza que deseja desvincular este participante do ritual? Observação e dados de inscrição serão apagados!', () => {
    window.location.href = url;
  });
}