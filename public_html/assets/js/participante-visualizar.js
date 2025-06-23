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

// ============= MODAL MANAGER =============
document.addEventListener("DOMContentLoaded", function () {
  // Lista de IDs das modais que devem ter fechamento ao clicar fora
  const modalIds = [
    'modal-detalhes-inscricao',
    'modal-observacao',
    'modal-adicionar',
    'modal-cadastro'
  ];

  // Aplica o evento de fechamento para cada modal
  modalIds.forEach(modalId => {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.addEventListener("click", function (event) {
        if (event.target === modal) {
          modal.style.display = "none";
        }
      });
    }
  });

  // Para modals com classe .modal (fallback)
  const modals = document.querySelectorAll(".modal");
  modals.forEach(modal => {
    modal.addEventListener("click", function (event) {
      if (event.target === modal) {
        modal.style.display = "none";
      }
    });
  });
});

// ============= FUNÇÕES DE MODAL =============

// Função para abrir o modal de detalhes da inscrição
function abrirModalDetalhes(ritualId) {
  currentRitualId = ritualId; // ✅ Armazena ID atual

  // Limpa todos os campos do formulário
  document.getElementById('id').value = '';
  document.querySelector('select[name="primeira_vez_instituto"]').value = '';
  document.querySelector('select[name="primeira_vez_ayahuasca"]').value = '';
  document.querySelector('select[name="doenca_psiquiatrica"]').value = '';
  document.querySelector('input[name="nome_doenca"]').value = '';
  document.querySelector('select[name="uso_medicao"]').value = '';
  document.querySelector('input[name="nome_medicao"]').value = '';
  document.querySelector('textarea[name="mensagem"]').value = '';

  // Busca o ID da inscrição via AJAX
  fetch(`/participantesici/public_html/api/inscricoes/buscar-id?participante_id=${pessoaId}&ritual_id=${ritualId}`)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showToast(data.error, 'error');
        return;
      }
      const inscricaoId = data.inscricao_id;

      document.getElementById('id').value = inscricaoId;

      fetch(`/participantesici/public_html/api/inscricoes/detalhes-inscricao?id=${inscricaoId}`)
        .then(response => response.json())
        .then(detalhes => {
          if (detalhes.error) {
            showToast(detalhes.error, 'error');
            return;
          }

          // Preenche os campos
          document.querySelector('select[name="primeira_vez_instituto"]').value = detalhes.primeira_vez_instituto || '';
          document.querySelector('select[name="primeira_vez_ayahuasca"]').value = detalhes.primeira_vez_ayahuasca || '';
          document.querySelector('select[name="doenca_psiquiatrica"]').value = detalhes.doenca_psiquiatrica || '';
          document.querySelector('input[name="nome_doenca"]').value = detalhes.nome_doenca || '';
          document.querySelector('select[name="uso_medicao"]').value = detalhes.uso_medicao || '';
          document.querySelector('input[name="nome_medicao"]').value = detalhes.nome_medicao || '';
          document.querySelector('textarea[name="mensagem"]').value = detalhes.mensagem || '';

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
}

// Função para abrir o modal de observação
function abrirModalObservacao(ritualId) {
  currentRitualId = ritualId; // ✅ Armazena ID atual

  fetch(`/participantesici/public_html/api/inscricoes/buscar-id?participante_id=${pessoaId}&ritual_id=${ritualId}`)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showToast(data.error, 'error');
        return;
      }
      const inscricaoId = data.inscricao_id;

      document.getElementById('inscricao_id_observacao').value = inscricaoId;

      fetch(`/participantesici/public_html/api/inscricoes/detalhes-inscricao?id=${inscricaoId}`)
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
  currentRitualId = null;
}

function fecharModalObservacao() {
  document.getElementById('modal-observacao').style.display = 'none';
  currentRitualId = null;
}

function abrirModalCadastro() {
  document.getElementById('modal-cadastro').style.display = 'flex';
}

function fecharModalCadastro() {
  document.getElementById('modal-cadastro').style.display = 'none';
}

function fecharModalAdicionar() {
  document.getElementById('modal-adicionar').style.display = 'none';
  limparFiltroCompleto();
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
document.addEventListener("DOMContentLoaded", function () {
  // ✅ Form de detalhes da inscrição - ÚNICO
  initFormDetalhes();

  // ✅ Form de observação - ÚNICO
  initFormObservacao();

  // Outras inicializações
  setupConditionalFields();
});

// ✅ Função para inicializar form de detalhes (evita duplicação)
function initFormDetalhes() {
  const formDetalhes = document.getElementById('form-detalhes-inscricao');
  if (formDetalhes && !formDetalhes.hasAttribute('data-initialized')) {
    formDetalhes.setAttribute('data-initialized', 'true');

    formDetalhes.addEventListener('submit', function (event) {
      event.preventDefault();
      console.log('Form detalhes submetido');

      const formData = new FormData(formDetalhes);

      fetch('/participantesici/public_html/api/inscricoes/salvar-inscricao', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showToast("Detalhes salvos com sucesso!", 'success');
            fecharModalDetalhes();

            // Remove notificação se tiver ritual ID
            if (currentRitualId) {
              removerNotificacaoDetalhes(currentRitualId);
            }

            setTimeout(() => {
              location.reload();
            }, 1000);
          } else {
            showToast("Erro ao salvar detalhes: " + data.error, 'error');
          }
        })
        .catch(error => {
          console.error('Erro ao enviar requisição:', error);
          showToast("Erro ao salvar detalhes. Tente novamente.", 'error');
        });
    });
  }
}

// ✅ Função para inicializar form de observação (evita duplicação)
function initFormObservacao() {
  const formObservacao = document.getElementById('form-observacao');
  if (formObservacao && !formObservacao.hasAttribute('data-initialized')) {
    formObservacao.setAttribute('data-initialized', 'true');

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

      fetch('/participantesici/public_html/api/inscricoes/salvar-observacao', {
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

  fetch(`/participantesici/public_html/api/inscricoes/buscar-id?participante_id=${pessoaId}&ritual_id=${ritualId}`)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showToast(data.error, 'error');
        return;
      }
      const inscricaoId = data.inscricao_id;

      fetch(`/participantesici/public_html/api/inscricoes/atualizar-presenca`, {
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
            atualizarContadores(newStatus);
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
    let contadorParticipados = document.querySelector('span.bg-\\[\\#00bfff\\]');

    // Método 2: Se não encontrar, busca por contexto
    if (!contadorParticipados) {
      const spans = document.querySelectorAll('span');
      spans.forEach(span => {
        const parent = span.parentElement;
        if (parent && parent.textContent.includes('Rituais participados') &&
          (span.classList.contains('bg-[#00bfff]') || span.style.backgroundColor)) {
          contadorParticipados = span;
        }
      });
    }

    if (contadorParticipados) {
      let participados = parseInt(contadorParticipados.textContent.trim()) || 0;

      if (novoStatus === 'Sim') {
        participados++;
      } else {
        participados = Math.max(0, participados - 1);
      }

      contadorParticipados.textContent = participados;
      console.log(`Contador atualizado: ${participados} (Status: ${novoStatus})`);
    } else {
      console.warn('Contador não encontrado');
    }
  } catch (error) {
    console.error('Erro ao atualizar contador:', error);
  }
}

// ============= PESQUISA DE RITUAIS =============
function pesquisarRituaisComColapso() {
  const nomePesquisa = document.getElementById('nome_pesquisa').value.trim();
  if (!nomePesquisa) {
    showToast("Digite um nome para pesquisar.", 'error');
    return;
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

  // Executa pesquisa
  Promise.all([
    fetch(`/participantesici/public_html/api/participante/buscar-ritual?nome=${encodeURIComponent(nomePesquisa)}`),
    fetch(`/participantesici/public_html/api/inscricoes/rituais-vinculados?participante_id=${pessoaId}`)
  ])
    .then(responses => Promise.all(responses.map(r => r.json())))
    .then(([rituaisData, rituaisVinculadosData]) => {
      if (rituaisData.error) {
        showToast(rituaisData.error, 'error');
        return;
      }

      const rituaisVinculados = rituaisVinculadosData.rituais_ids || [];

      if (rituaisData.length === 0) {
        if (listaRituais) {
          listaRituais.innerHTML = `
            <li class="p-4 text-center text-gray-500">
              <i class="fa-solid fa-search text-2xl mb-2 block"></i>
              Nenhum ritual encontrado.
            </li>
          `;
        }
        return;
      }

      // Cria lista de rituais
      rituaisData.forEach(ritual => {
        const jaAdicionado = rituaisVinculados.includes(ritual.id);
        const li = document.createElement('li');
        li.className = 'p-4 hover:bg-gray-50 transition-colors';

        li.innerHTML = `
          <div class="grid grid-cols-[auto_1fr] gap-4">
            <div class="flex-shrink-0">
              <img src="${ritual.foto || '/participantesici/public_html/assets/images/no-image.png'}"
                   onerror="this.src='/participantesici/public_html/assets/images/no-image.png';"
                   alt="Foto do ritual"
                   class="w-16 h-16 rounded-lg object-cover border border-gray-200">
            </div>
            <div class="space-y-2">
              <h3 class="!font-semibold !text-gray-900 !text-lg !leading-tight !m-0 !p-0">
                ${ritual.nome || 'Nome não informado'}
              </h3>
              <div class="flex items-center gap-1">
                <span class="text-sm font-semibold">Data:</span>
                <p class="text-sm text-gray-600">
                  ${ritual.data_ritual ? formatarData(ritual.data_ritual) : 'Data não informada'}
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
    })
    .catch(error => {
      console.error('Erro ao buscar rituais:', error);
      showToast('Erro ao carregar rituais. Tente novamente.', 'error');
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
}

// ============= RITUAL ACTIONS =============
function adicionarNovoRitual() {
  const participanteIdInput = document.querySelector('#modal-adicionar input[name="participante_id"]');
  if (participanteIdInput) {
    const participanteId = participanteIdInput.value;
    window.location.href = `/participantesici/public_html/ritual/novo?redirect=/participantesici/public_html/participante/${participanteId}`;
  }
}

function adicionarRitual(ritualId) {
  const participanteIdInput = document.querySelector('#modal-adicionar input[name="participante_id"]');
  if (!participanteIdInput) {
    showToast('Erro: ID do participante não encontrado', 'error');
    return;
  }

  const participanteId = participanteIdInput.value;

  fetch('/participantesici/public_html/api/participante/adicionar-ritual', {
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
        setTimeout(() => {
          location.reload();
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