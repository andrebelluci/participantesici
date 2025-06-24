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

// ============= MODAL MANAGER =============
document.addEventListener("DOMContentLoaded", function () {
  // Lista de IDs das modals que devem ter fechamento ao clicar fora
  const modalIds = [
    'modal-detalhes-inscricao',
    'modal-observacao',
    'modal-adicionar'
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
function abrirModalDetalhes(participanteId) {
  currentParticipanteId = participanteId;

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
  fetch(`/participantesici/public_html/api/inscricoes/buscar-id?participante_id=${participanteId}&ritual_id=${ritualId}`)
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

  // Foco no primeiro campo do formulário
  const primeiroCampo = document.querySelector('#form-detalhes-inscricao input, #form-detalhes-inscricao select, #form-detalhes-inscricao textarea');
  if (primeiroCampo) {
    primeiroCampo.focus();
  }
}

// Função para abrir o modal de observação
function abrirModalObservacao(participanteId) {
  currentParticipanteId = participanteId;

  fetch(`/participantesici/public_html/api/inscricoes/buscar-id?participante_id=${participanteId}&ritual_id=${ritualId}`)
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
  currentParticipanteId = null;
}

function fecharModalObservacao() {
  document.getElementById('modal-observacao').style.display = 'none';
  currentParticipanteId = null;
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

// Chama a função quando a página carrega
document.addEventListener('DOMContentLoaded', aplicarFocoModalAdicionar);

function fecharModalAdicionar() {
  document.getElementById('modal-adicionar').style.display = 'none';
  limparPesquisa();
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
document.addEventListener("DOMContentLoaded", function () {
  // Form de detalhes da inscrição
  initFormDetalhes();

  // Form de observação
  initFormObservacao();

  // Outras inicializações
  setupConditionalFields();
});

// Função para inicializar form de detalhes
function initFormDetalhes() {
  const formDetalhes = document.getElementById('form-detalhes-inscricao');
  if (formDetalhes && !formDetalhes.hasAttribute('data-initialized')) {
    formDetalhes.setAttribute('data-initialized', 'true');

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

      fetch('/participantesici/public_html/api/inscricoes/salvar-inscricao', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
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

  fetch(`/participantesici/public_html/api/inscricoes/buscar-id?participante_id=${participanteId}&ritual_id=${ritualId}`)
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
            atualizarContadorParticipantes();
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

function atualizarContadorParticipantes() {
  try {
    // Conta participantes presentes nos cards
    const botoesPresenca = document.querySelectorAll('[data-current-status="Sim"]');
    const totalParticipantes = botoesPresenca.length;

    // Atualiza contador no cabeçalho
    const contadorSpan = document.querySelector('span.bg-\\[\\#00bfff\\]');
    if (contadorSpan && contadorSpan.parentElement.textContent.includes('Total de participantes')) {
      contadorSpan.textContent = totalParticipantes;
      console.log(`Contador atualizado: ${totalParticipantes} participantes`);
    }
  } catch (error) {
    console.error('Erro ao atualizar contador:', error);
  }
}

// ============= PESQUISA DE PARTICIPANTES =============
// Event listener para Enter no campo de pesquisa
document.addEventListener('DOMContentLoaded', function () {
  const nomePesquisaInput = document.getElementById('nome_pesquisa');

  if (nomePesquisaInput) {
    nomePesquisaInput.addEventListener('keypress', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        pesquisarParticipantes();
      }
    });
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

  const apiUrl = `/participantesici/public_html/api/ritual/buscar-participante?nome=${encodeURIComponent(nomePesquisa)}`;
  console.log('🌐 URL da API:', apiUrl);
  // Executa pesquisa
  Promise.all([
    fetch(`/participantesici/public_html/api/ritual/buscar-participante?nome=${encodeURIComponent(nomePesquisa)}`),
    fetch(`/participantesici/public_html/api/inscricoes/participantes-vinculados?ritual_id=${ritualId}`)
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
              <img src="${participante.foto || '/participantesici/public_html/assets/images/no-image.png'}"
                   onerror="this.src='/participantesici/public_html/assets/images/no-image.png';"
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
}

// ============= PARTICIPANTE ACTIONS =============
function adicionarNovaPessoa() {
  const ritualIdInput = document.querySelector('#modal-adicionar input[name="ritual_id"]');
  if (ritualIdInput) {
    const ritualIdValue = ritualIdInput.value;
    window.location.href = `/participantesici/public_html/participante/novo?redirect=/participantesici/public_html/ritual/${ritualIdValue}`;
  }
}

function adicionarParticipante(participanteId) {
  const ritualIdInput = document.querySelector('#modal-adicionar input[name="ritual_id"]');
  if (!ritualIdInput) {
    showToast('Erro: ID do ritual não encontrado', 'error');
    return;
  }

  const ritualIdValue = ritualIdInput.value;

  fetch('/participantesici/public_html/api/ritual/adicionar-participante', {
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
        setTimeout(() => {
          location.reload();
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