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

function showToastPresenca(message, type = 'error') {
  const backgroundColor = type === 'success' ? '#dbfce7' : '#ffe2e2';

  // ✅ Força criação de elemento HTML correto
  const toastContainer = document.createElement('span');
  toastContainer.innerHTML = message;

  Toastify({
    node: toastContainer,
    duration: 2000,
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
// Função para copiar dados da última inscrição salva
function copiarDadosUltimaInscricao(participanteId, inscricaoAtualId) {
  console.log('🔄 Buscando última inscrição salva...', { participanteId, inscricaoAtualId });
  return fetch(`/api/inscricoes/buscar-ultima-inscricao-salva?participante_id=${participanteId}&inscricao_atual_id=${inscricaoAtualId}`)
    .then(response => {
      console.log('📡 Resposta da API:', response.status);
      return response.json();
    })
    .then(data => {
      console.log('📦 Dados recebidos:', data);
      if (data.error) {
        console.error('❌ Erro na API:', data.error);
        return { copiado: false, erro: data.error };
      }
      if (data.encontrada && data.dados) {
        console.log('✅ Dados encontrados, copiando...', data.dados);
        // Copia os dados (exceto primeira_vez_instituto e primeira_vez_ayahuasca)
        const camposParaCopiar = ['doenca_psiquiatrica', 'nome_doenca', 'uso_medicao', 'nome_medicao', 'mensagem'];
        let camposCopiados = 0;

        camposParaCopiar.forEach(campo => {
          const element = document.querySelector(`[name="${campo}"]`);
          if (element) {
            const valorAnterior = element.value;
            const valorFonte = data.dados[campo];

            // Verifica se o valor não é null, undefined ou string vazia
            if (valorFonte !== null && valorFonte !== undefined && valorFonte !== '') {
              // Para campos de texto, também verifica se não é apenas espaços
              if (typeof valorFonte === 'string' && valorFonte.trim() === '') {
                console.log(`  ⊘ Campo "${campo}" contém apenas espaços`);
              } else {
                element.value = valorFonte;
                camposCopiados++;
                console.log(`  ✓ Campo "${campo}" copiado: "${valorFonte}"`);
              }
            } else {
              console.log(`  ⊘ Campo "${campo}" vazio na fonte (valor: ${valorFonte})`);
            }
          } else {
            console.warn(`  ⚠ Campo "${campo}" não encontrado no formulário`);
          }
        });

        console.log(`📊 Total de campos copiados: ${camposCopiados}`);

        // Atualiza o estado dos campos condicionais após copiar
        atualizarCamposCondicionais();

        // Mostra mensagem informando de qual ritual os dados foram copiados
        mostrarMensagemDadosCopiados(data.ritual_nome, data.ritual_id);

        // Salva dados originais e muda botão para "Fechar"
        setTimeout(() => {
          salvarDadosOriginaisModal();
          mudarBotaoParaFechar();
        }, 100);

        return { copiado: true, ritual_nome: data.ritual_nome, ritual_id: data.ritual_id, camposCopiados };
      } else {
        console.log('ℹ️ Nenhuma inscrição anterior encontrada');
        return { copiado: false };
      }
    })
    .catch(error => {
      console.error('❌ Erro ao copiar dados da última inscrição:', error);
      return { copiado: false, erro: error.message };
    });
}

// Função para mostrar mensagem de dados copiados
function mostrarMensagemDadosCopiados(ritualNome, ritualId) {
  // Remove mensagens anteriores
  document.querySelectorAll('.aviso-dados-copiados').forEach(el => el.remove());

  const avisoGeral = document.createElement('div');
  avisoGeral.className = 'aviso-dados-copiados bg-yellow-50 border border-yellow-200 rounded p-3 mb-4';
  avisoGeral.innerHTML = `
    <div class="flex items-center">
      <i class="fa-solid fa-info-circle text-yellow-600 mr-2"></i>
      <span class="text-yellow-800 text-sm">
        <strong>Informação:</strong> Os dados foram copiados automaticamente do ritual "<strong>${ritualNome}</strong>".
        Você pode alterar qualquer informação conforme necessário.
      </span>
    </div>
  `;

  // Busca o campo primeira_vez_ayahuasca para inserir a mensagem após ele
  const campoPrimeiraVezAyahuasca = document.querySelector('select[name="primeira_vez_ayahuasca"]');
  if (campoPrimeiraVezAyahuasca) {
    // Encontra o div pai que contém o campo (geralmente é um <div> com a classe do campo)
    const divPai = campoPrimeiraVezAyahuasca.closest('div');
    if (divPai && divPai.parentNode) {
      // Insere a mensagem após o div que contém o campo primeira_vez_ayahuasca
      divPai.parentNode.insertBefore(avisoGeral, divPai.nextSibling);
      return;
    }
  }

  // Fallback: se não encontrar o campo, insere no início do formulário
  const formContainer = document.querySelector('#form-detalhes-inscricao .space-y-4');
  if (formContainer) {
    formContainer.insertBefore(avisoGeral, formContainer.firstChild);
  } else {
    const form = document.querySelector('#form-detalhes-inscricao');
    if (form) {
      form.insertBefore(avisoGeral, form.firstChild);
    }
  }
}

// Função para verificar se os dados foram copiados de uma inscrição anterior
function verificarSeDadosForamCopiados(participanteId, inscricaoAtualId, detalhes) {
  // Se a inscrição não foi salva ainda (salvo_em é null), pode ter sido copiada
  if (detalhes.salvo_em) {
    // Se já foi salva, não mostra mensagem de cópia
    return;
  }

  // Verifica se há uma última inscrição salva anterior
  fetch(`/api/inscricoes/buscar-ultima-inscricao-salva?participante_id=${participanteId}&inscricao_atual_id=${inscricaoAtualId}`)
    .then(response => response.json())
    .then(data => {
      if (data.encontrada && data.dados) {
        // Compara os dados atuais com os dados da última inscrição salva
        const camposParaComparar = ['doenca_psiquiatrica', 'nome_doenca', 'uso_medicao', 'nome_medicao', 'mensagem'];
        let dadosIguais = true;

        for (const campo of camposParaComparar) {
          const valorAtual = detalhes[campo] || '';
          const valorOrigem = data.dados[campo] || '';

          // Compara os valores (normaliza strings vazias e null)
          const valorAtualNormalizado = valorAtual === null ? '' : String(valorAtual).trim();
          const valorOrigemNormalizado = valorOrigem === null ? '' : String(valorOrigem).trim();

          if (valorAtualNormalizado !== valorOrigemNormalizado) {
            dadosIguais = false;
            break;
          }
        }

        // Se os dados são iguais, significa que foram copiados
        if (dadosIguais) {
          console.log('✅ Dados foram copiados do ritual:', data.ritual_nome);
          mostrarMensagemDadosCopiados(data.ritual_nome, data.ritual_id);
          // Salva dados originais e muda botão para "Fechar"
          setTimeout(() => {
            salvarDadosOriginaisModal();
            mudarBotaoParaFechar();
          }, 100);
        }
      }
    })
    .catch(error => {
      console.error('Erro ao verificar se dados foram copiados:', error);
    });
}

// Função auxiliar para preencher campos do formulário
function preencherCamposFormulario(detalhes) {
  Object.keys(detalhes).forEach(key => {
    const element = document.querySelector(`[name="${key}"]`);
    if (element && detalhes[key] !== null && detalhes[key] !== '') {
      // Só preenche se o campo estiver vazio (para não sobrescrever dados copiados)
      if (!element.value || element.value === '') {
        element.value = detalhes[key];
      }
    }
  });

  // Atualiza o estado dos campos condicionais após preencher
  atualizarCamposCondicionais();
}

// Função para atualizar o estado dos campos condicionais baseado nos valores atuais
function atualizarCamposCondicionais() {
  const doencaPsiquiatrica = document.getElementById("doenca_psiquiatrica");
  const nomeDoenca = document.getElementById("nome_doenca");
  const usoMedicacao = document.getElementById("uso_medicao");
  const nomeMedicacao = document.getElementById("nome_medicao");

  if (doencaPsiquiatrica && nomeDoenca) {
    if (doencaPsiquiatrica.value === "Sim") {
      nomeDoenca.disabled = false;
      nomeDoenca.required = true;
    } else {
      nomeDoenca.disabled = true;
      nomeDoenca.required = false;
      // Só limpa o valor se não foi copiado (para não apagar dados copiados)
      if (!nomeDoenca.value || nomeDoenca.value === '') {
        nomeDoenca.value = "";
      }
    }
  }

  if (usoMedicacao && nomeMedicacao) {
    if (usoMedicacao.value === "Sim") {
      nomeMedicacao.disabled = false;
      nomeMedicacao.required = true;
    } else {
      nomeMedicacao.disabled = true;
      nomeMedicacao.required = false;
      // Só limpa o valor se não foi copiado (para não apagar dados copiados)
      if (!nomeMedicacao.value || nomeMedicacao.value === '') {
        nomeMedicacao.value = "";
      }
    }
  }
}

function abrirModalDetalhes(participanteId) {
  disableScroll();
  currentParticipanteId = participanteId;

  // Limpa todos os campos do formulário
  document.getElementById('id').value = '';
  document.querySelector('select[name="primeira_vez_instituto"]').value = '';
  document.querySelector('select[name="primeira_vez_ayahuasca"]').value = '';
  document.querySelector('select[name="doenca_psiquiatrica"]').value = '';
  document.querySelector('input[name="nome_doenca"]').value = '';
  document.querySelector('select[name="uso_medicao"]').value = '';
  document.querySelector('input[name="nome_medicao"]').value = '';
  const mensagemField = document.querySelector('textarea[name="mensagem"]');
  if (mensagemField) mensagemField.value = '';

  // Remove avisos anteriores se existirem
  document.querySelectorAll('.aviso-dados-anteriores').forEach(el => el.remove());
  document.querySelectorAll('.aviso-dados-copiados').forEach(el => el.remove());

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

          // Verifica se a inscrição está vazia (campos que devem ser copiados estão vazios)
          // Considera vazia se os campos copiáveis estão vazios (independente de ter salvo_em ou não)
          const camposCopiaveisVazios =
            (!detalhes.doenca_psiquiatrica || detalhes.doenca_psiquiatrica === '') &&
            (!detalhes.nome_doenca || detalhes.nome_doenca.trim() === '') &&
            (!detalhes.uso_medicao || detalhes.uso_medicao === '') &&
            (!detalhes.nome_medicao || detalhes.nome_medicao.trim() === '') &&
            (!detalhes.mensagem || detalhes.mensagem.trim() === '');

          const inscricaoVazia = camposCopiaveisVazios;

          console.log('🔍 Verificando inscrição:', {
            inscricaoId,
            camposCopiaveisVazios,
            inscricaoVazia,
            detalhes: {
              doenca_psiquiatrica: detalhes.doenca_psiquiatrica,
              nome_doenca: detalhes.nome_doenca,
              uso_medicao: detalhes.uso_medicao,
              nome_medicao: detalhes.nome_medicao,
              mensagem: detalhes.mensagem,
              salvo_em: detalhes.salvo_em
            }
          });

          // Se a inscrição está vazia, tenta copiar dados da última inscrição salva
          if (inscricaoVazia) {
            console.log('📋 Inscrição vazia detectada, copiando dados...');
            copiarDadosUltimaInscricao(participanteId, inscricaoId).then(resultado => {
              console.log('✅ Resultado da cópia:', resultado);
              // Preenche apenas os campos que não foram copiados (como primeira_vez_instituto e primeira_vez_ayahuasca)
              // Esses campos serão tratados pela função verificarDadosAnteriores
              if (detalhes.primeira_vez_instituto) {
                const element = document.querySelector('[name="primeira_vez_instituto"]');
                if (element) element.value = detalhes.primeira_vez_instituto;
              }
              if (detalhes.primeira_vez_ayahuasca) {
                const element = document.querySelector('[name="primeira_vez_ayahuasca"]');
                if (element) element.value = detalhes.primeira_vez_ayahuasca;
              }
              // Verifica dados anteriores para primeira vez
              verificarDadosAnteriores(participanteId, inscricaoId, detalhes).then(() => {
                // Após verificar dados anteriores, verifica se pode remover a notificação
                setTimeout(() => verificarECondicionalmenteRemoverNotificacao(), 200);
              }).catch(() => {
                // Mesmo em caso de erro, tenta verificar
                setTimeout(() => verificarECondicionalmenteRemoverNotificacao(), 200);
              });
            });
          } else {
            console.log('📝 Inscrição já tem dados, preenchendo normalmente...');
            // Se já tem dados, preenche todos os campos normalmente
            preencherCamposFormulario(detalhes);

            // Verifica se os dados foram copiados de uma inscrição anterior
            verificarSeDadosForamCopiados(participanteId, inscricaoId, detalhes);

            verificarDadosAnteriores(participanteId, inscricaoId, detalhes).then(() => {
              // Após verificar dados anteriores, verifica se pode remover a notificação
              setTimeout(() => verificarECondicionalmenteRemoverNotificacao(), 200);
            }).catch(() => {
              // Mesmo em caso de erro, tenta verificar
              setTimeout(() => verificarECondicionalmenteRemoverNotificacao(), 200);
            });
          }

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

  // Reseta estado do botão e dados originais
  dadosCopiadosSemAlteracoes = false;
  dadosOriginaisModal = {};
  mudarBotaoParaSalvar();

  // Adiciona listeners para detectar mudanças após um pequeno delay
  // para garantir que os dados já foram preenchidos
  setTimeout(() => {
    const form = document.getElementById('form-detalhes-inscricao');
    if (form) {
      // Salva dados originais após preencher
      salvarDadosOriginaisModal();

      // Função para verificar mudanças e atualizar botão
      const verificarEMudarBotao = () => {
        if (dadosCopiadosSemAlteracoes && verificarMudancasModal()) {
          mudarBotaoParaSalvar();
        }
      };

      // Adiciona listeners (remove antes para evitar duplicação)
      form.removeEventListener('input', verificarEMudarBotao);
      form.removeEventListener('change', verificarEMudarBotao);
      form.addEventListener('input', verificarEMudarBotao);
      form.addEventListener('change', verificarEMudarBotao);
    }
  }, 300);

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

  // Garante que os campos estão habilitados
  institutoSelect.disabled = false;
  ayahuascaSelect.disabled = false;

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
  const institutoSelect = document.querySelector('select[name="primeira_vez_instituto"]');
  const ayahuascaSelect = document.querySelector('select[name="primeira_vez_ayahuasca"]');

  // Verifica se os campos estão NULL (não preenchidos)
  const camposNulos = (!detalhes.primeira_vez_instituto || detalhes.primeira_vez_instituto === '') &&
    (!detalhes.primeira_vez_ayahuasca || detalhes.primeira_vez_ayahuasca === '');

  // Se a inscrição atual tem "Sim" em algum campo, mostra mensagem de primeira vez
  const temSimAtual = (detalhes.primeira_vez_instituto === 'Sim' || detalhes.primeira_vez_ayahuasca === 'Sim');

  if (temSimAtual) {
    // Se a inscrição atual tem "Sim", mostra mensagem de primeira vez
    aplicarAvisosPrimeiraInscricao();
    return Promise.resolve();
  }

  // Se os campos estão NULL, verifica se há inscrição anterior SALVA
  if (camposNulos) {
    return fetch(`/api/inscricoes/verificar-primeira-inscricao?participante_id=${participanteId}&inscricao_atual_id=${inscricaoAtualId}`)
      .then(response => response.json())
      .then(data => {
        if (data.dados_anteriores) {
          // Preenche automaticamente com "Não" se houver inscrição anterior salva
          institutoSelect.value = 'Não';
          ayahuascaSelect.value = 'Não';
          // Atualiza detalhes para refletir os valores preenchidos
          detalhes.primeira_vez_instituto = 'Não';
          detalhes.primeira_vez_ayahuasca = 'Não';
          // Aplica a lógica de bloqueio
          aplicarDadosAnteriores(detalhes, data.tem_sim, data.ambos_nao);
        } else {
          // É primeira inscrição - aplica avisos especiais
          aplicarAvisosPrimeiraInscricao();
        }
      })
      .catch(error => {
        console.error('Erro ao verificar dados anteriores:', error);
        throw error;
      });
  } else {
    // Se os campos já estão preenchidos, verifica normalmente
    return fetch(`/api/inscricoes/verificar-primeira-inscricao?participante_id=${participanteId}&inscricao_atual_id=${inscricaoAtualId}`)
      .then(response => response.json())
      .then(data => {
        if (data.dados_anteriores) {
          // Passa informações adicionais sobre se tinha "Sim" ou ambos "Não"
          aplicarDadosAnteriores(detalhes, data.tem_sim, data.ambos_nao);
        } else {
          // É primeira inscrição - aplica avisos especiais
          aplicarAvisosPrimeiraInscricao();
        }
      })
      .catch(error => {
        console.error('Erro ao verificar dados anteriores:', error);
        throw error;
      });
  }
}

// Função para aplicar indicação de dados anteriores na modal
function aplicarDadosAnteriores(detalhes, temSim = false, ambosNao = false) {
  const institutoSelect = document.querySelector('select[name="primeira_vez_instituto"]');
  const ayahuascaSelect = document.querySelector('select[name="primeira_vez_ayahuasca"]');

  // Remove avisos anteriores
  document.querySelectorAll('.aviso-dados-anteriores').forEach(el => el.remove());

  // Desabilita os campos se já tiver dados anteriores
  institutoSelect.disabled = true;
  ayahuascaSelect.disabled = true;

  // Define mensagens baseado na situação
  let mensagemInstituto, mensagemAyahuasca, mensagemGeral;

  if (temSim) {
    // Se algum campo anterior era "Sim", mostra mensagem específica
    mensagemInstituto = '* Participante já foi inscrito em outro ritual, por isso foi salvo como "Não".';
    mensagemAyahuasca = '* Participante já foi inscrito em outro ritual, por isso foi salvo como "Não".';
    mensagemGeral = '<strong>Informação:</strong> Como este participante já teve "Sim" em uma inscrição anterior, os campos "Primeira vez" foram automaticamente definidos como "Não" e não podem ser alterados.';
  } else if (ambosNao) {
    // Se ambos eram "Não", mostra mensagem diferente
    mensagemInstituto = '* Participante não é a primeira vez, por isso foi salvo como "Não".';
    mensagemAyahuasca = '* Participante não é a primeira vez, por isso foi salvo como "Não".';
    mensagemGeral = '<strong>Informação:</strong> Como este participante não é a primeira vez, os campos "Primeira vez" foram automaticamente definidos como "Não" e não podem ser alterados.';
  } else {
    // Fallback (caso não tenha as flags)
    mensagemInstituto = '* Como este participante já tem inscrições anteriores, os campos "Primeira vez" foram automaticamente definidos como "Não" e não podem ser alterados.';
    mensagemAyahuasca = '* Como este participante já tem inscrições anteriores, os campos "Primeira vez" foram automaticamente definidos como "Não" e não podem ser alterados.';
    mensagemGeral = '<strong>Informação:</strong> Como este participante já tem inscrições anteriores, os campos "Primeira vez" foram automaticamente definidos como "Não" e não podem ser alterados.';
  }

  // Adiciona aviso visual em cada campo
  const avisoInstituto = document.createElement('div');
  avisoInstituto.className = 'aviso-dados-anteriores text-blue-600 text-xs mt-1 italic';
  avisoInstituto.textContent = mensagemInstituto;
  institutoSelect.parentNode.appendChild(avisoInstituto);

  const avisoAyahuasca = document.createElement('div');
  avisoAyahuasca.className = 'aviso-dados-anteriores text-blue-600 text-xs mt-1 italic';
  avisoAyahuasca.textContent = mensagemAyahuasca;
  ayahuascaSelect.parentNode.appendChild(avisoAyahuasca);

  // Adiciona aviso geral no topo do formulário
  const avisoGeral = document.createElement('div');
  avisoGeral.className = 'aviso-dados-anteriores bg-blue-50 border border-blue-200 rounded p-3 mb-4';
  avisoGeral.innerHTML = `
    <div class="flex items-center">
      <i class="fa-solid fa-info-circle text-blue-500 mr-2"></i>
      <span class="text-blue-700 text-sm">
        ${mensagemGeral}
      </span>
    </div>
  `;

  const formContainer = document.querySelector('#form-detalhes-inscricao .space-y-4');
  if (formContainer) {
    formContainer.insertBefore(avisoGeral, formContainer.firstChild);
  } else {
    // Fallback: adiciona no início do formulário
    const form = document.querySelector('#form-detalhes-inscricao');
    if (form) {
      form.insertBefore(avisoGeral, form.firstChild);
    }
  }
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

      // Verifica se tem "Sim" nos dados anteriores
      const temSim = (dadosAPI.primeira_vez_instituto === 'Sim' || dadosAPI.primeira_vez_ayahuasca === 'Sim');
      const ambosNao = (dadosAPI.primeira_vez_instituto === 'Não' && dadosAPI.primeira_vez_ayahuasca === 'Não');

      // Aplica a indicação de dados anteriores
      aplicarDadosAnteriores(dadosAPI, temSim, ambosNao);

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

// Variável para rastrear se os dados foram copiados (sem alterações)
let dadosCopiadosSemAlteracoes = false;
let dadosOriginaisModal = {};

// Função para mudar o botão para "Fechar"
function mudarBotaoParaFechar() {
  const btnSalvar = document.getElementById('btn-salvar-detalhes');
  if (btnSalvar) {
    btnSalvar.innerHTML = '<i class="fa-solid fa-times mr-1"></i> Fechar';
    btnSalvar.type = 'button';
    btnSalvar.onclick = () => fecharModalDetalhes();
    // Muda para estilo vermelho com texto branco
    btnSalvar.className = 'w-full bg-red-600 text-white py-2 rounded hover:bg-red-700 transition font-semibold';
    dadosCopiadosSemAlteracoes = true;
  }
}

// Função para mudar o botão para "Salvar"
function mudarBotaoParaSalvar() {
  const btnSalvar = document.getElementById('btn-salvar-detalhes');
  if (btnSalvar) {
    btnSalvar.innerHTML = '<i class="fa-solid fa-save mr-1"></i> Salvar';
    btnSalvar.type = 'submit';
    btnSalvar.onclick = null;
    // Volta para estilo azul com texto preto (original)
    btnSalvar.className = 'w-full bg-[#00bfff] text-black py-2 rounded hover:bg-yellow-400 transition font-semibold';
    dadosCopiadosSemAlteracoes = false;
  }
}

// Função para salvar dados originais do modal
function salvarDadosOriginaisModal() {
  const form = document.getElementById('form-detalhes-inscricao');
  if (!form) return;

  dadosOriginaisModal = {};
  const formData = new FormData(form);
  for (let [key, value] of formData.entries()) {
    dadosOriginaisModal[key] = value;
  }

  // Também salva valores de campos disabled
  form.querySelectorAll('select[name="primeira_vez_instituto"], select[name="primeira_vez_ayahuasca"]').forEach(el => {
    dadosOriginaisModal[el.name] = el.value;
  });
}

// Função para verificar se houve mudanças no modal
function verificarMudancasModal() {
  const form = document.getElementById('form-detalhes-inscricao');
  if (!form || Object.keys(dadosOriginaisModal).length === 0) return false;

  const dadosAtuais = {};
  const formData = new FormData(form);
  for (let [key, value] of formData.entries()) {
    dadosAtuais[key] = value;
  }

  // Também verifica valores de campos disabled
  form.querySelectorAll('select[name="primeira_vez_instituto"], select[name="primeira_vez_ayahuasca"]').forEach(el => {
    dadosAtuais[el.name] = el.value;
  });

  // Compara os dados
  for (let key in dadosOriginaisModal) {
    if (dadosOriginaisModal[key] !== dadosAtuais[key]) {
      return true;
    }
  }

  return false;
}

// Funções para fechar modals
function fecharModalDetalhes() {
  const modal = document.getElementById('modal-detalhes-inscricao');
  if (!modal) return;

  // Se o botão é "Fechar" e não há mudanças, fecha diretamente
  if (dadosCopiadosSemAlteracoes && !verificarMudancasModal()) {
    modal.style.display = 'none';
    enableScroll();
    currentParticipanteId = null;
    dadosCopiadosSemAlteracoes = false;
    dadosOriginaisModal = {};
    if (unsavedChangesDetector) {
      unsavedChangesDetector.modalChangesMap.set('modal-detalhes-inscricao', false);
    }
    return;
  }

  // Verifica se há mudanças não salvas
  const temMudancas = verificarMudancasModal();

  if (temMudancas && unsavedChangesDetector) {
    // Usa o detector de mudanças não salvas para mostrar confirmação
    unsavedChangesDetector.showUnsavedChangesModal(() => {
      // Confirmou saída sem salvar
      modal.style.display = 'none';
      enableScroll();
      currentParticipanteId = null;
      dadosCopiadosSemAlteracoes = false;
      dadosOriginaisModal = {};
      if (unsavedChangesDetector) {
        unsavedChangesDetector.modalChangesMap.set('modal-detalhes-inscricao', false);
      }
    });
  } else {
    // Fecha normalmente se não houver mudanças
    modal.style.display = 'none';
    enableScroll();
    currentParticipanteId = null;
    dadosCopiadosSemAlteracoes = false;
    dadosOriginaisModal = {};
    if (unsavedChangesDetector) {
      unsavedChangesDetector.modalChangesMap.set('modal-detalhes-inscricao', false);
    }
  }
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

// Função auxiliar para verificar se todos os campos obrigatórios estão preenchidos e remover notificação
function verificarECondicionalmenteRemoverNotificacao() {
  if (!currentParticipanteId) return;

  const formDetalhes = document.querySelector('#form-detalhes-inscricao');
  if (!formDetalhes) return;

  const primeiraVezInstituto = formDetalhes.querySelector('[name="primeira_vez_instituto"]')?.value;
  const primeiraVezAyahuasca = formDetalhes.querySelector('[name="primeira_vez_ayahuasca"]')?.value;
  const doencaPsiquiatrica = formDetalhes.querySelector('[name="doenca_psiquiatrica"]')?.value;
  const nomeDoenca = formDetalhes.querySelector('[name="nome_doenca"]')?.value || '';
  const usoMedicao = formDetalhes.querySelector('[name="uso_medicao"]')?.value;
  const nomeMedicao = formDetalhes.querySelector('[name="nome_medicao"]')?.value || '';

  // Verifica se todos os campos obrigatórios estão preenchidos
  const todosPreenchidos = primeiraVezInstituto && primeiraVezAyahuasca && doencaPsiquiatrica && usoMedicao &&
    (doencaPsiquiatrica !== 'Sim' || nomeDoenca.trim()) &&
    (usoMedicao !== 'Sim' || nomeMedicao.trim());

  // Remove a bolinha apenas se todos os campos obrigatórios estiverem preenchidos
  if (todosPreenchidos) {
    console.log('✅ Todos os campos obrigatórios preenchidos, removendo notificação...');
    removerNotificacaoDetalhes(currentParticipanteId);
  } else {
    console.log('⚠️ Campos ainda não estão todos preenchidos:', {
      primeiraVezInstituto,
      primeiraVezAyahuasca,
      doencaPsiquiatrica,
      nomeDoenca: nomeDoenca.trim() || '(vazio)',
      usoMedicao,
      nomeMedicao: nomeMedicao.trim() || '(vazio)'
    });
  }
}

function removerNotificacaoDetalhes(participanteId) {
  console.log('Removendo notificação detalhes para participante:', participanteId);

  // Remove bolinha tanto em cards quanto em tabelas
  const bolinhaCard = document.querySelector(`#notificacao-detalhes-${participanteId}`);
  if (bolinhaCard) {
    bolinhaCard.remove();
    console.log('Bolinha de detalhes removida para participante:', participanteId);
  }

  // Fallback: busca por onclick também
  const cards = document.querySelectorAll('.bg-white.p-4.rounded-lg.shadow');
  cards.forEach(card => {
    const botaoDetalhes = card.querySelector('button[onclick*="abrirModalDetalhes"]');
    if (botaoDetalhes) {
      const onclickAttr = botaoDetalhes.getAttribute('onclick');
      if (onclickAttr && onclickAttr.includes(`abrirModalDetalhes(${participanteId})`)) {
        const bolinha = botaoDetalhes.querySelector('.bg-red-500');
        if (bolinha) {
          bolinha.remove();
        }
      }
    }
  });

  // Busca também em tabelas
  const tabelaBotoes = document.querySelectorAll('button[onclick*="abrirModalDetalhes"]');
  tabelaBotoes.forEach(botao => {
    const onclickAttr = botao.getAttribute('onclick');
    if (onclickAttr && onclickAttr.includes(`abrirModalDetalhes(${participanteId})`)) {
      const bolinha = botao.querySelector('.bg-red-500');
      if (bolinha) {
        bolinha.remove();
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

      // Inclui manualmente valores de campos desabilitados (campos disabled não são incluídos no FormData)
      const primeiraVezInstituto = formDetalhes.querySelector('[name="primeira_vez_instituto"]');
      const primeiraVezAyahuasca = formDetalhes.querySelector('[name="primeira_vez_ayahuasca"]');
      const nomeDoenca = formDetalhes.querySelector('[name="nome_doenca"]');
      const nomeMedicao = formDetalhes.querySelector('[name="nome_medicao"]');

      if (primeiraVezInstituto && primeiraVezInstituto.disabled && primeiraVezInstituto.value) {
        formData.set('primeira_vez_instituto', primeiraVezInstituto.value);
      }
      if (primeiraVezAyahuasca && primeiraVezAyahuasca.disabled && primeiraVezAyahuasca.value) {
        formData.set('primeira_vez_ayahuasca', primeiraVezAyahuasca.value);
      }
      if (nomeDoenca && nomeDoenca.disabled) {
        formData.set('nome_doenca', nomeDoenca.value || '');
      }
      if (nomeMedicao && nomeMedicao.disabled) {
        formData.set('nome_medicao', nomeMedicao.value || '');
      }

      fetch('/api/inscricoes/salvar-inscricao', {
        method: 'POST',
        body: formData
      })
        .then(response => {
          // Primeiro lê o texto da resposta
          return response.text().then(text => {
            try {
              // Remove warnings/notices do PHP que podem aparecer antes do JSON
              // Procura pelo último objeto JSON válido na resposta (mais completo)
              const jsonMatches = text.match(/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/g);
              if (jsonMatches && jsonMatches.length > 0) {
                // Pega o último match (geralmente o mais completo)
                const jsonStr = jsonMatches[jsonMatches.length - 1];
                return JSON.parse(jsonStr);
              }
              // Se não encontrar JSON com regex, tenta encontrar manualmente
              const lastBrace = text.lastIndexOf('}');
              const firstBrace = text.lastIndexOf('{', lastBrace);
              if (firstBrace !== -1 && lastBrace !== -1) {
                const jsonStr = text.substring(firstBrace, lastBrace + 1);
                return JSON.parse(jsonStr);
              }
              // Se não encontrar JSON, tenta parsear o texto completo
              return JSON.parse(text);
            } catch (e) {
              // Se não for JSON válido, retorna erro
              console.error('Resposta não é JSON válido:', text);
              throw new Error('Resposta do servidor não é JSON válido');
            }
          });
        })
        .then(data => {
          if (data.success) {
            showToast("Detalhes da inscrição salvos com sucesso!", 'success');
            // Reseta estado antes de fechar
            dadosCopiadosSemAlteracoes = false;
            dadosOriginaisModal = {};
            mudarBotaoParaSalvar();
            fecharModalDetalhes();

            // Verifica se todos os campos obrigatórios estão preenchidos antes de remover a bolinha
            const primeiraVezInstituto = formDetalhes.querySelector('[name="primeira_vez_instituto"]').value;
            const primeiraVezAyahuasca = formDetalhes.querySelector('[name="primeira_vez_ayahuasca"]').value;
            const doencaPsiquiatrica = formDetalhes.querySelector('[name="doenca_psiquiatrica"]').value;
            const nomeDoenca = formDetalhes.querySelector('[name="nome_doenca"]').value || '';
            const usoMedicao = formDetalhes.querySelector('[name="uso_medicao"]').value;
            const nomeMedicao = formDetalhes.querySelector('[name="nome_medicao"]').value || '';

            // Verifica se todos os campos obrigatórios estão preenchidos
            const todosPreenchidos = primeiraVezInstituto && primeiraVezAyahuasca && doencaPsiquiatrica && usoMedicao &&
              (doencaPsiquiatrica !== 'Sim' || nomeDoenca.trim()) &&
              (usoMedicao !== 'Sim' || nomeMedicao.trim());

            // Remove a bolinha apenas se todos os campos obrigatórios estiverem preenchidos
            if (currentParticipanteId && todosPreenchidos) {
              removerNotificacaoDetalhes(currentParticipanteId);
            }

            setTimeout(() => {
              location.reload();
            }, 1000);
          } else {
            showToast("Erro ao salvar detalhes da inscrição: " + (data.error || 'Erro desconhecido'), 'error');
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

            // Atualizar estado do botão de assinatura baseado na presença
            atualizarBotaoAssinaturaPorPresenca(inscricaoId, newStatus);

            setTimeout(() => {
              atualizarContadorParticipantes(newStatus);
            }, 200);
            const presenca = newStatus === 'Sim' ? 'success' : 'error';
            const mensagem = newStatus === 'Sim'
              ? `<i class="fa-solid fa-user-check text-green-700 text-lg"></i><span class="text-green-100">...</span><span class="text-green-700 text-lg">Presente</span>`
              : `<i class="fa-solid fa-user-xmark text-red-700 text-lg"></i><span class="text-red-100">...</span><span class="text-red-700 text-lg">Ausente</span>`;
            showToastPresenca(mensagem, presenca);
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

// Atualizar botão de assinatura baseado na presença
function atualizarBotaoAssinaturaPorPresenca(inscricaoId, novoStatus) {
  // Encontrar todos os botões de assinatura relacionados
  const assinaturaButtons = document.querySelectorAll('button[onclick*="abrirModalAssinatura"]');
  assinaturaButtons.forEach(btn => {
    const onclickAttr = btn.getAttribute('onclick');
    if (onclickAttr && onclickAttr.includes(inscricaoId)) {
      if (novoStatus === 'Sim') {
        // Habilitar botão se presença for 'Sim'
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
        btn.removeAttribute('title');
      } else {
        // Desabilitar botão se presença for 'Não'
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        btn.setAttribute('title', 'Marque como presente para assinar');
      }
    }
  });
}

function atualizarContadorParticipantes(novoStatus) {
  try {
    // Método 1: Busca por classe específica
    let contadorPresentes = document.querySelector('.contador-presentes span.text-green-700');
    let contadorAusentes = document.querySelector('.contador-presentes span.text-red-700');

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

// ✅ FUNÇÃO pesquisarParticipantes()
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

  // ✅ CORREÇÃO: Aguarda AMBAS as requisições completarem
  Promise.all([
    fetch(`/api/ritual/buscar-participante?nome=${encodeURIComponent(nomePesquisa)}`),
    fetch(`/api/inscricoes/participantes-vinculados?ritual_id=${ritualId}`)
  ])
    .then(responses => Promise.all(responses.map(r => r.json())))
    .then(([participantesData, participantesVinculadosData]) => {
      console.log('📱 Dados recebidos:', {
        participantes: participantesData.length,
        vinculados: participantesVinculadosData.participantes_ids?.length || 0
      });

      if (participantesData.error) {
        showToast(participantesData.error, 'error');
        return;
      }

      // ✅ SEGURANÇA: Garante que participantesVinculados sempre seja um array
      const participantesVinculados = Array.isArray(participantesVinculadosData.participantes_ids)
        ? participantesVinculadosData.participantes_ids
        : [];

      console.log('📱 Participantes vinculados:', participantesVinculados);

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

      // ✅ CORREÇÃO: Renderiza items COM DELAY para garantir dados
      renderizarParticipantesComDelay(participantesData, participantesVinculados, nomePesquisa, ehCPF, listaParticipantes);

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

// ✅ NOVA FUNÇÃO: Renderiza participantes com verificação segura
function renderizarParticipantesComDelay(participantesData, participantesVinculados, nomePesquisa, ehCPF, listaParticipantes) {
  console.log('📱 Iniciando renderização participantes:', {
    encontrados: participantesData.length,
    vinculados: participantesVinculados
  });

  // ✅ Pequeno delay para garantir que dados estão prontos
  setTimeout(() => {
    participantesData.forEach((participante, index) => {
      // ✅ SEGURANÇA: Converte IDs para mesmo tipo para comparação
      const participanteId = parseInt(participante.id);
      const participantesVinculadosInt = participantesVinculados.map(id => parseInt(id));
      const jaAdicionado = participantesVinculadosInt.includes(participanteId);

      console.log(`📱 Participante ${participante.nome_completo}:`, {
        id: participanteId,
        vinculados: participantesVinculadosInt,
        jaAdicionado: jaAdicionado
      });

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
            <div class="pt-1" id="acao-participante-${participanteId}">
              ${renderizarBotaoAcaoParticipante(participanteId, jaAdicionado, participante.pode_vincular_rituais || 'Sim', participante.motivo_bloqueio_vinculacao || null)}
            </div>
          </div>
        </div>
      `;

      if (listaParticipantes) {
        listaParticipantes.appendChild(li);
      }

      // ✅ VERIFICAÇÃO ADICIONAL: Re-verifica após 100ms (mobile safety)
      setTimeout(() => {
        verificarEAtualizarBotaoParticipante(participanteId, participantesVinculadosInt, participante.pode_vincular_rituais || 'Sim', participante.motivo_bloqueio_vinculacao || null);
      }, 100 + (index * 10)); // Escalonado para evitar sobrecarga
    });
  }, 50); // Delay inicial pequeno
}

// ✅ NOVA FUNÇÃO: Renderiza botão/tag baseado no status do participante
function renderizarBotaoAcaoParticipante(participanteId, jaAdicionado, podeVincular = 'Sim', motivoBloqueio = null) {
  if (jaAdicionado) {
    return `
      <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
        <i class="fa-solid fa-check"></i>
        Já adicionado
      </span>
    `;
  } else if (podeVincular === 'Não') {
    // Escapar o motivo corretamente para evitar problemas com aspas e quebras de linha
    const motivoEscapado = motivoBloqueio
      ? motivoBloqueio
        .replace(/\\/g, '\\\\')  // Escapar barras invertidas primeiro
        .replace(/'/g, "\\'")     // Escapar aspas simples
        .replace(/"/g, '&quot;')  // Escapar aspas duplas
        .replace(/\n/g, ' ')      // Substituir quebras de linha por espaços
        .replace(/\r/g, '')       // Remover retornos de carro
      : 'Motivo não informado';
    return `
      <button onclick="abrirModalMotivoBloqueioRitual('${motivoEscapado}')"
              class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded text-sm font-semibold transition-colors shadow-sm">
        <i class="fa-solid fa-ban mr-1"></i>
        Não pode adicionar (ver motivo)
      </button>
    `;
  } else {
    return `
      <button onclick="adicionarParticipante(${participanteId})"
              class="bg-[#00bfff] hover:bg-yellow-400 text-black px-4 py-2 rounded text-sm font-semibold transition-colors shadow-sm">
        <i class="fa-solid fa-plus mr-1"></i>
        Adicionar
      </button>
    `;
  }
}

// ✅ NOVA FUNÇÃO: Verificação adicional para mobile (participantes)
function verificarEAtualizarBotaoParticipante(participanteId, participantesVinculados, podeVincular = 'Sim', motivoBloqueio = null) {
  const containerAcao = document.getElementById(`acao-participante-${participanteId}`);
  if (!containerAcao) return;

  const jaAdicionado = participantesVinculados.includes(parseInt(participanteId));
  const temBotaoAdicionar = containerAcao.querySelector('button');
  const temTagAdicionado = containerAcao.querySelector('span.bg-green-100');

  // ✅ Corrige inconsistências
  if (jaAdicionado && temBotaoAdicionar) {
    console.log(`📱 Corrigindo botão para "Já adicionado" - Participante ${participanteId}`);
    containerAcao.innerHTML = renderizarBotaoAcaoParticipante(participanteId, true, podeVincular, motivoBloqueio);
  } else if (!jaAdicionado && temTagAdicionado) {
    console.log(`📱 Corrigindo tag para "Adicionar" - Participante ${participanteId}`);
    containerAcao.innerHTML = renderizarBotaoAcaoParticipante(participanteId, false, podeVincular, motivoBloqueio);
  }
}

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
        const mensagem = data.dados_copiados
          ? `Participante adicionado com sucesso! Dados copiados do ritual "${data.ritual_nome_origem}".`
          : 'Participante adicionado com sucesso!';
        showToast(mensagem, 'success');

        // 1. Atualiza o botão na lista para "Já adicionado"
        atualizarBotaoParaJaAdicionado(participanteId);

        // 2. Atualiza a página de fundo (sem fechar modal)
        atualizarPaginaFundo().then(() => {
          // 3. Após atualizar a página, verifica se pode remover a bolinha
          // Se os dados foram copiados e os campos de primeira vez foram preenchidos automaticamente,
          // verifica se todos os campos obrigatórios estão preenchidos
          if (data.dados_copiados && data.dados_anteriores) {
            // Os campos primeira_vez já foram preenchidos como "Não" na API
            // Se os dados foram copiados, todos os campos devem estar preenchidos
            setTimeout(() => {
              removerNotificacaoDetalhes(participanteId);
            }, 500);
          }
        });

        // 4. Expande filtro e limpa para nova pesquisa
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

  return fetch(currentUrl)
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
      throw error;
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