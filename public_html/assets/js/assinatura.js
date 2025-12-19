// assinatura.js - Gerenciamento de assinatura digital
// Usar IIFE para evitar conflitos se o script for carregado múltiplas vezes
(function () {
  'use strict';

  // Flag de debug (alterar para false em produção)
  const DEBUG = false;

  // Verificar se já foi inicializado
  if (window.assinaturaJSInicializado) {
    if (DEBUG) console.warn('assinatura.js: Script já foi carregado anteriormente. Ignorando segunda carga.');
    return;
  }
  window.assinaturaJSInicializado = true;

  let canvas = null;
  let ctx = null;
  let isDrawing = false;
  let lastX = 0;
  let lastY = 0;
  let inscricaoIdAtual = null;
  let participanteIdAtual = null;
  let ritualIdAtual = null;
  let temAssinaturaSalva = false; // Flag para indicar se há assinatura salva

  // Inicializar canvas
  function inicializarCanvas(limpar = true) {
    canvas = document.getElementById('canvas-assinatura');
    if (!canvas) return;

    // Se já tem contexto, não reinicializar (evita limpar assinatura existente)
    if (ctx && canvas.width > 0 && canvas.height > 0 && !limpar) {
      return;
    }

    ctx = canvas.getContext('2d');

    // Configurar canvas
    const rect = canvas.getBoundingClientRect();
    if (rect.width > 0) {
      canvas.width = rect.width;
    } else {
      canvas.width = 600; // Valor padrão
    }
    canvas.height = 200; // Altura fixa

    // Configurar estilo de desenho
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    // Limpar canvas apenas se solicitado
    if (limpar) {
      ctx.fillStyle = '#FFFFFF';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
    }
  }

  // Obter coordenadas do evento (mouse ou touch)
  function getEventPos(e) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;

    if (e.touches && e.touches.length > 0) {
      return {
        x: (e.touches[0].clientX - rect.left) * scaleX,
        y: (e.touches[0].clientY - rect.top) * scaleY
      };
    } else {
      return {
        x: (e.clientX - rect.left) * scaleX,
        y: (e.clientY - rect.top) * scaleY
      };
    }
  }

  // Iniciar desenho
  function iniciarDesenho(e) {
    e.preventDefault();
    isDrawing = true;
    const pos = getEventPos(e);
    lastX = pos.x;
    lastY = pos.y;
  }

  // Desenhar
  function desenhar(e) {
    if (!isDrawing) return;
    e.preventDefault();

    const pos = getEventPos(e);

    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();

    lastX = pos.x;
    lastY = pos.y;
  }

  // Parar desenho
  function pararDesenho(e) {
    if (isDrawing) {
      isDrawing = false;
    }
  }

  // Limpar assinatura
  function limparAssinatura() {
    if (!ctx || !canvas) return;

    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Se estava em modo excluir, voltar para modo salvar
    if (temAssinaturaSalva) {
      temAssinaturaSalva = false;
      atualizarBotaoSalvarExcluir(false);
    } else {
      atualizarEstadoBotaoSalvar();
    }
  }

  // Verificar se há desenho no canvas
  function temDesenho() {
    if (!canvas) return false;

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;

    // Verificar se há pixels não brancos
    for (let i = 0; i < data.length; i += 4) {
      const r = data[i];
      const g = data[i + 1];
      const b = data[i + 2];
      // Se não for branco (255, 255, 255)
      if (r !== 255 || g !== 255 || b !== 255) {
        return true;
      }
    }
    return false;
  }

  // Atualizar estado do botão salvar
  // Helper para mostrar toast (usa função global se disponível)
  function showToastHelper(message, type = 'error') {
    const showToastFn = window.showToast || (typeof showToast !== 'undefined' ? showToast : null);
    if (showToastFn) {
      showToastFn(message, type);
    } else {
      alert(message);
    }
  }

  function atualizarEstadoBotaoSalvar() {
    const btnSalvar = document.getElementById('btn-salvar-assinatura');
    if (btnSalvar) {
      // Se tem assinatura salva, não precisa verificar desenho (já está no modo excluir)
      if (temAssinaturaSalva) {
        btnSalvar.disabled = false;
        btnSalvar.classList.remove('opacity-50', 'cursor-not-allowed');
      } else if (temDesenho()) {
        btnSalvar.disabled = false;
        btnSalvar.classList.remove('opacity-50', 'cursor-not-allowed');
      } else {
        btnSalvar.disabled = true;
        btnSalvar.classList.add('opacity-50', 'cursor-not-allowed');
      }
    }
  }

  // Atualizar botão entre modo "Salvar" e "Excluir"
  function atualizarBotaoSalvarExcluir(isExcluir) {
    const btnSalvar = document.getElementById('btn-salvar-assinatura');
    if (!btnSalvar) return;

    if (isExcluir) {
      // Modo Excluir
      btnSalvar.innerHTML = '<i class="fa-solid fa-trash mr-2"></i> Excluir';
      btnSalvar.className = 'px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition font-semibold';
      btnSalvar.onclick = excluirAssinatura;
      btnSalvar.disabled = false;
      btnSalvar.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
      // Modo Salvar
      btnSalvar.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Salvar';
      btnSalvar.className = 'px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition font-semibold disabled:opacity-50 disabled:cursor-not-allowed';
      btnSalvar.onclick = salvarAssinatura;
      atualizarEstadoBotaoSalvar();
    }
  }

  // Abrir modal de assinatura
  function abrirModalAssinatura(inscricaoId, participanteId, ritualId) {
    if (DEBUG) console.log('abrirModalAssinatura chamada:', { inscricaoId, participanteId, ritualId });

    // Verificar se o botão está desabilitado (presence não é 'Sim')
    let button = null;
    try {
      if (window.event && window.event.target) {
        button = window.event.target.closest('button');
      } else {
        const buttons = document.querySelectorAll('button[onclick*="abrirModalAssinatura"]');
        for (let btn of buttons) {
          const onclickAttr = btn.getAttribute('onclick');
          if (onclickAttr && (onclickAttr.includes(String(inscricaoId)) ||
            (inscricaoId == 0 && onclickAttr.includes(String(participanteId))))) {
            button = btn;
            break;
          }
        }
      }
    } catch (e) {
      if (DEBUG) console.warn('Erro ao verificar botão:', e);
    }

    if (button && button.disabled) {
      return;
    }

    inscricaoIdAtual = inscricaoId;
    participanteIdAtual = participanteId;
    ritualIdAtual = ritualId;

    // Tentar encontrar a modal - aguardar um pouco se necessário
    let modal = document.getElementById('modal-assinatura');

    if (!modal) {
      setTimeout(() => {
        modal = document.getElementById('modal-assinatura');
        if (modal) {
          abrirModalAssinaturaInterno(modal);
        } else {
          showToastHelper('Erro: Modal de assinatura não encontrada.', 'error');
        }
      }, 100);
      return;
    }

    abrirModalAssinaturaInterno(modal);
  }

  // Função interna para abrir a modal (evita duplicação de código)
  function abrirModalAssinaturaInterno(modal) {
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Inicializar canvas primeiro (configurar dimensões e contexto)
    setTimeout(() => {
      inicializarCanvas(false); // Não limpar ainda - apenas configurar

      // Carregar assinatura existente se houver
      if (inscricaoIdAtual) {
        carregarAssinaturaExistente(inscricaoIdAtual).then((temAssinatura) => {
          // A função já atualiza o botão internamente
        });
      } else {
        // Se não tem inscricaoId, limpar canvas agora
        temAssinaturaSalva = false;
        if (ctx && canvas) {
          ctx.fillStyle = '#FFFFFF';
          ctx.fillRect(0, 0, canvas.width, canvas.height);
        }
        atualizarBotaoSalvarExcluir(false);
      }
    }, 100);
  }

  // Carregar assinatura existente
  async function carregarAssinaturaExistente(inscricaoId) {
    return new Promise((resolve) => {
      // Aguardar um pouco para garantir que canvas está inicializado
      setTimeout(() => {
        if (!canvas || !ctx) {
          // Se ainda não tem canvas, tentar inicializar
          inicializarCanvas(false);
        }

        if (!canvas || !ctx) {
          console.error('Canvas não disponível');
          temAssinaturaSalva = false;
          resolve(false);
          return;
        }

        fetch(`/api/inscricoes/buscar-assinatura?id=${inscricaoId}`)
          .then(response => response.json())
          .then(result => {
            if (result.success && result.assinatura) {
              temAssinaturaSalva = true;
              const img = new Image();
              img.onload = function () {
                if (ctx && canvas) {
                  // Limpar canvas antes de desenhar
                  ctx.fillStyle = '#FFFFFF';
                  ctx.fillRect(0, 0, canvas.width, canvas.height);
                  // Desenhar assinatura existente
                  ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                }
                atualizarBotaoSalvarExcluir(true); // Mudar para modo "Excluir"
                resolve(true);
              };
              img.onerror = function () {
                if (DEBUG) console.error('Erro ao carregar imagem da assinatura');
                temAssinaturaSalva = false;
                // Limpar canvas em caso de erro
                if (ctx && canvas) {
                  ctx.fillStyle = '#FFFFFF';
                  ctx.fillRect(0, 0, canvas.width, canvas.height);
                }
                atualizarBotaoSalvarExcluir(false);
                resolve(false);
              };
              img.src = result.assinatura;
            } else {
              // Se não há assinatura, limpar canvas
              temAssinaturaSalva = false;
              if (ctx && canvas) {
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
              }
              atualizarBotaoSalvarExcluir(false);
              resolve(false);
            }
          })
          .catch(error => {
            if (DEBUG) console.error('Erro ao carregar assinatura:', error);
            temAssinaturaSalva = false;
            // Limpar canvas em caso de erro
            if (ctx && canvas) {
              ctx.fillStyle = '#FFFFFF';
              ctx.fillRect(0, 0, canvas.width, canvas.height);
            }
            atualizarBotaoSalvarExcluir(false);
            resolve(false);
          });
      }, 50);
    });
  }

  // Fechar modal de assinatura
  function fecharModalAssinatura() {
    const modal = document.getElementById('modal-assinatura');
    if (modal) {
      modal.classList.add('hidden');
      document.body.style.overflow = 'auto';
    }

    // Limpar variáveis
    inscricaoIdAtual = null;
    participanteIdAtual = null;
    ritualIdAtual = null;
    isDrawing = false;
    temAssinaturaSalva = false;

    // Resetar botão para modo salvar
    atualizarBotaoSalvarExcluir(false);
  }

  // DEFINIR FUNÇÕES GLOBAIS IMEDIATAMENTE APÓS DEFINIR AS FUNÇÕES
  // Isso garante que estejam disponíveis mesmo antes do DOM estar pronto
  (function () {
    // Verificar se as funções já foram definidas
    if (typeof abrirModalAssinatura !== 'undefined') {
      window.abrirModalAssinatura = abrirModalAssinatura;
    }
    if (typeof fecharModalAssinatura !== 'undefined') {
      window.fecharModalAssinatura = fecharModalAssinatura;
    }
    if (typeof limparAssinatura !== 'undefined') {
      window.limparAssinatura = limparAssinatura;
    }
    if (typeof salvarAssinatura !== 'undefined') {
      window.salvarAssinatura = salvarAssinatura;
    }
  })();

  // Salvar assinatura
  async function salvarAssinatura() {
    if (!canvas || !temDesenho()) {
      showToastHelper('Por favor, desenhe sua assinatura antes de salvar.', 'error');
      return;
    }

    if (!inscricaoIdAtual) {
      showToastHelper('Erro: ID da inscrição não encontrado.', 'error');
      return;
    }

    try {
      // Converter canvas para base64
      const assinaturaBase64 = canvas.toDataURL('image/png');

      // Enviar via AJAX
      const response = await fetch('/api/inscricoes/salvar-assinatura', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          inscricao_id: inscricaoIdAtual,
          participante_id: participanteIdAtual,
          ritual_id: ritualIdAtual,
          assinatura: assinaturaBase64
        })
      });

      const result = await response.json();

      if (result.success) {
        showToastHelper('Assinatura salva com sucesso!', 'success');
        temAssinaturaSalva = true;
        atualizarBotaoSalvarExcluir(true); // Mudar para modo excluir

        // Desabilitar presence-btn após salvar assinatura
        desabilitarPresenceBtn(inscricaoIdAtual);

        // Atualizar botão de assinatura
        atualizarBotaoAssinatura(inscricaoIdAtual, true);

        // Recarregar página para atualizar tudo após 1 segundo
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        showToastHelper(result.message || 'Erro ao salvar assinatura.', 'error');
      }
    } catch (error) {
      if (DEBUG) console.error('Erro ao salvar assinatura:', error);
      showToastHelper('Erro ao salvar assinatura: ' + error.message, 'error');
    }
  }

  // Excluir assinatura
  function excluirAssinatura() {
    if (!inscricaoIdAtual || !participanteIdAtual || !ritualIdAtual) {
      showToastHelper('Erro: Dados da inscrição não encontrados.', 'error');
      return;
    }

    // Usar a função openConfirmModal se disponível, senão usar confirm nativo
    const confirmar = typeof openConfirmModal !== 'undefined'
      ? () => {
          openConfirmModal('Excluir assinatura e marcar presença como "NÃO"?', executarExclusaoAssinatura);
        }
      : () => {
          if (confirm('Excluir assinatura e marcar presença como "NÃO"?')) {
            executarExclusaoAssinatura();
          }
        };

    confirmar();
  }

  // Executar exclusão da assinatura
  async function executarExclusaoAssinatura() {
    try {
      const response = await fetch('/api/inscricoes/excluir-assinatura', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          inscricao_id: inscricaoIdAtual,
          participante_id: participanteIdAtual,
          ritual_id: ritualIdAtual
        })
      });

      const result = await response.json();

      if (result.success) {
        showToastHelper('Assinatura excluída e presença atualizada para "Não".', 'success');

        // Limpar canvas
        if (ctx && canvas) {
          ctx.fillStyle = '#FFFFFF';
          ctx.fillRect(0, 0, canvas.width, canvas.height);
        }

        temAssinaturaSalva = false;
        atualizarBotaoSalvarExcluir(false);

        // Habilitar presence-btn novamente e atualizar para "Não"
        habilitarPresenceBtn(inscricaoIdAtual);
        atualizarPresencaParaNao(inscricaoIdAtual);

        // Atualizar botão de assinatura na página
        atualizarBotaoAssinatura(inscricaoIdAtual, false);

        // Recarregar página para atualizar tudo após 1 segundo
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        showToastHelper(result.message || 'Erro ao excluir assinatura.', 'error');
      }
    } catch (error) {
      if (DEBUG) console.error('Erro ao excluir assinatura:', error);
      showToastHelper('Erro ao excluir assinatura: ' + error.message, 'error');
    }
  }

  // Habilitar presence-btn após excluir assinatura
  function habilitarPresenceBtn(inscricaoId) {
    const presenceButtons = document.querySelectorAll('.presence-btn');
    presenceButtons.forEach(btn => {
      const btnInscricaoId = btn.getAttribute('data-inscricao-id');
      if (btnInscricaoId == inscricaoId) {
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
      }
    });
  }

  // Atualizar presença para "Não" após excluir assinatura
  function atualizarPresencaParaNao(inscricaoId) {
    const presenceButtons = document.querySelectorAll('.presence-btn');
    presenceButtons.forEach(btn => {
      const btnInscricaoId = btn.getAttribute('data-inscricao-id');
      if (btnInscricaoId == inscricaoId) {
        btn.innerHTML = `
          <i class="fa-solid fa-xmark"></i>
          <span>Não</span>
        `;
        btn.classList.remove('bg-green-100', 'text-green-700', 'hover:bg-green-200', 'active');
        btn.classList.add('bg-red-100', 'text-red-700', 'hover:bg-red-200');
        btn.setAttribute('data-current-status', 'Não');
      }
    });
  }

  // Desabilitar presence-btn após assinar
  function desabilitarPresenceBtn(inscricaoId) {
    // Encontrar todos os botões de presença com o mesmo inscricao_id
    const presenceButtons = document.querySelectorAll('.presence-btn');
    presenceButtons.forEach(btn => {
      const btnInscricaoId = btn.getAttribute('data-inscricao-id');
      if (btnInscricaoId == inscricaoId) {
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
      }
    });
  }

  // Atualizar botão de assinatura após salvar
  function atualizarBotaoAssinatura(inscricaoId, assinado) {
    // Encontrar todos os botões de assinatura relacionados
    const assinaturaButtons = document.querySelectorAll('button[onclick*="abrirModalAssinatura"]');
    assinaturaButtons.forEach(btn => {
      const onclickAttr = btn.getAttribute('onclick');
      if (onclickAttr && onclickAttr.includes(inscricaoId)) {
        if (assinado) {
          btn.innerHTML = '<i class="fa-solid fa-check-circle"></i><span>Assinado</span>';
          btn.classList.remove('opacity-50', 'cursor-not-allowed');
          btn.removeAttribute('disabled');
        }
      }
    });
  }

  // Função para mostrar toast (reutilizar se já existir globalmente)
  if (typeof window.showToast === 'undefined') {
    window.showToast = function (message, type = 'error') {
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
    };
  }

  // Garantir que as funções sejam globais (disponíveis via onclick nos templates)
  try {
    window.abrirModalAssinatura = abrirModalAssinatura;
    window.fecharModalAssinatura = fecharModalAssinatura;
    window.limparAssinatura = limparAssinatura;
    window.salvarAssinatura = salvarAssinatura;
    window.excluirAssinatura = excluirAssinatura;

    if (DEBUG) {
      console.log('assinatura.js: Funções globais definidas:', {
        abrirModalAssinatura: typeof window.abrirModalAssinatura,
        fecharModalAssinatura: typeof window.fecharModalAssinatura,
        limparAssinatura: typeof window.limparAssinatura,
        salvarAssinatura: typeof window.salvarAssinatura
      });
    }
  } catch (e) {
    console.error('ERRO ao definir funções globais:', e);
  }

  // Inicialização quando DOM estiver pronto
  document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('canvas-assinatura');
    if (canvas) {
      // Event listeners para mouse
      canvas.addEventListener('mousedown', iniciarDesenho);
      canvas.addEventListener('mousemove', desenhar);
      canvas.addEventListener('mouseup', pararDesenho);
      canvas.addEventListener('mouseleave', pararDesenho);

      // Event listeners para touch
      canvas.addEventListener('touchstart', iniciarDesenho);
      canvas.addEventListener('touchmove', desenhar);
      canvas.addEventListener('touchend', pararDesenho);
      canvas.addEventListener('touchcancel', pararDesenho);

      // Atualizar estado do botão durante o desenho
      canvas.addEventListener('mouseup', atualizarEstadoBotaoSalvar);
      canvas.addEventListener('touchend', atualizarEstadoBotaoSalvar);
    }

    // Fechar modal ao clicar fora
    const modal = document.getElementById('modal-assinatura');
    if (modal) {
      modal.addEventListener('click', function (e) {
        if (e.target === modal) {
          fecharModalAssinatura();
        }
      });
    }

    // Fechar modal com ESC
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        const modal = document.getElementById('modal-assinatura');
        if (modal && !modal.classList.contains('hidden')) {
          fecharModalAssinatura();
        }
      }
    });
  }); // Fechar document.addEventListener('DOMContentLoaded')

})(); // Fechar IIFE

