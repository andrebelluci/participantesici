function toggleSenha() {
    const inputSenha = document.getElementById('senha'); // <- id do input de senha
    const icon = document.getElementById('iconOlho');
    const btn = document.getElementById('toggleSenhaBtn');

    if (!inputSenha || !icon || !btn) return;

    const mostrando = inputSenha.type === 'text';
    inputSenha.type = mostrando ? 'password' : 'text';

    icon.classList.toggle('fa-eye', mostrando);
    icon.classList.toggle('fa-eye-slash', !mostrando);
    btn.title = mostrando ? 'Mostrar senha' : 'Esconder senha';
}

document.addEventListener('DOMContentLoaded', function () {
    // Ativa menu mobile se houver
    const toggleButton = document.getElementById('menu-toggle');
    const mobileNav = document.getElementById('mobile-nav');
    if (toggleButton && mobileNav) {
        toggleButton.addEventListener('click', function () {
            mobileNav.classList.toggle('hidden');
        });
    }

    // Inicializa validação para todos os formulários com campos obrigatórios
    document.querySelectorAll('form').forEach((form) => {
        if (form.querySelector('[required]')) {
            configurarValidacao(form);
        }
    });

    initScrollToTop();
});

/**
 * Alterna visibilidade de todos os campos de senha
 */
function toggleTodasSenhas(btn, icone) {
    const inputsSenha = document.querySelectorAll('.senha-input');
    const exibir = Array.from(inputsSenha).some(input => input.type === 'password');

    inputsSenha.forEach(input => {
        input.type = exibir ? 'text' : 'password';
    });

    if (icone) {
        icone.classList.toggle('fa-eye');
        icone.classList.toggle('fa-eye-slash');
    }

    if (btn) {
        btn.title = exibir ? 'Esconder senhas' : 'Mostrar senhas';
    }
}

/**
 * Configura validação visual de campos obrigatórios - CORRIGIDA
 */
function configurarValidacao(form) {
    form.addEventListener('submit', function (e) {
        let valido = true;
        let primeiroErro = null; // ✅ Guarda o primeiro campo com erro

        form.querySelectorAll('[required]').forEach((input) => {
            const mensagemErro = encontrarMensagemErro(input);
            if (!input.value.trim()) {
                input.classList.add('border-red-500');
                mensagemErro?.classList.remove('hidden');

                // ✅ Só guarda o primeiro erro encontrado
                if (!primeiroErro) {
                    primeiroErro = input;
                }

                valido = false;
            } else {
                input.classList.remove('border-red-500');
                mensagemErro?.classList.add('hidden');
            }
        });

        // ✅ Só foca no primeiro campo com erro (se houver)
        if (!valido && primeiroErro) {
            primeiroErro.focus();
            // ✅ Scroll suave para o campo
            primeiroErro.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            e.preventDefault();
        }
    });

    // Validação individual no blur (mantém igual)
    form.querySelectorAll('[required]').forEach((input) => {
        input.addEventListener('blur', () => {
            const mensagemErro = encontrarMensagemErro(input);
            if (!input.value.trim()) {
                input.classList.add('border-red-500');
                mensagemErro?.classList.remove('hidden');
            } else {
                input.classList.remove('border-red-500');
                mensagemErro?.classList.add('hidden');
            }
        });
    });
}

/**
 * Encontra <p> de erro associado ao input
 * Procura por id="erro-NOME" ou <p> imediatamente após o input
 */
function encontrarMensagemErro(input) {
    const erroPorId = document.getElementById(`erro-${input.name}`);
    if (erroPorId) return erroPorId;

    const proximo = input.nextElementSibling;
    if (proximo?.tagName === 'P' && proximo.classList.contains('text-red-500')) {
        return proximo;
    }

    return null;
}

// ============= SCROLL TO TOP =============
function initScrollToTop() {
    const scrollToTopBtn = document.getElementById('scroll-to-top');
    if (!scrollToTopBtn) return;

    const scrollThreshold = 300;

    function toggleScrollButton() {
        if (window.pageYOffset > scrollThreshold) {
            scrollToTopBtn.classList.remove('opacity-0', 'invisible', 'translate-y-4');
            scrollToTopBtn.classList.add('opacity-100', 'visible', 'translate-y-0');
        } else {
            scrollToTopBtn.classList.remove('opacity-100', 'visible', 'translate-y-0');
            scrollToTopBtn.classList.add('opacity-0', 'invisible', 'translate-y-4');
        }
    }

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    window.addEventListener('scroll', toggleScrollButton);
    scrollToTopBtn.addEventListener('click', scrollToTop);
    toggleScrollButton();
}

/**
 * Toast corrigido - resolve problemas de posicionamento e tamanho
 */
function showToast(message, type = 'error', duration = null) {
    const isMobile = window.innerWidth <= 768;

    const colors = {
        success: '#16a34a',
        error: '#dc2626',
        info: '#2563eb',
        warning: '#d97706'
    };

    if (!duration) {
        duration = type === 'success' ? 4000 : 5000;
    }

    // ✅ CONFIGURAÇÃO SIMPLIFICADA - SEM POSICIONAMENTO CUSTOMIZADO
    const config = {
        text: message,
        duration: duration,
        close: true,
        backgroundColor: colors[type] || colors.error,
        stopOnFocus: true,
        className: `toast-${type}`,

        // 📱 MOBILE E DESKTOP: Ambos à direita agora
        gravity: "top",
        position: "right",

        // ❌ REMOVIDO: Estilos customizados que causavam conflito
        // Deixa o Toastify gerenciar o posicionamento base
    };

    const toast = Toastify(config).showToast();

    // ✅ APLICA ESTILOS APÓS CRIAÇÃO (evita o "pulo")
    setTimeout(() => {
        const toastElement = document.querySelector('.toastify:last-child');
        if (toastElement) {
            // 🎯 Calcula posição baseada nos toasts existentes
            const existingToasts = document.querySelectorAll('.toastify');
            const toastIndex = Array.from(existingToasts).indexOf(toastElement);
            const topPosition = 20 + (toastIndex * 80); // 80px entre cada toast

            // 🎯 Força posicionamento correto
            toastElement.style.position = 'fixed';
            toastElement.style.zIndex = '10000';
            toastElement.style.top = topPosition + 'px';
            toastElement.style.right = '20px';

            // 📏 Tamanho responsivo baseado no conteúdo
            if (isMobile) {
                toastElement.style.width = 'auto';
                toastElement.style.maxWidth = 'calc(100vw - 40px)';
                toastElement.style.minWidth = '200px';
                toastElement.style.right = '20px';
                toastElement.style.left = 'auto';
            } else {
                toastElement.style.width = 'auto';
                toastElement.style.maxWidth = '35vw';
                toastElement.style.minWidth = '250px';
            }

            // 🎨 Estilos visuais
            toastElement.style.borderRadius = '8px';
            toastElement.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.15)';
            toastElement.style.fontFamily = 'system-ui, -apple-system, sans-serif';
            toastElement.style.fontSize = '14px';
            toastElement.style.fontWeight = '500';
            toastElement.style.lineHeight = '1.4';

            // 📐 Padding responsivo
            if (isMobile) {
                toastElement.style.padding = '12px 16px';
            } else {
                toastElement.style.padding = '12px 16px';
            }
        }
    }, 10); // Timeout mínimo para evitar conflito

    return toast;
}

/**
 * Toast para login com redirecionamento
 */
function showLoginSuccessToast(redirectUrl = '/home') {
    showToast('Login efetuado com sucesso! Redirecionando...', 'success', 2000);

    setTimeout(() => {
        window.location.href = redirectUrl;
    }, 1500);
}

function showPasswordChangeToast(redirectUrl = '/login') {
    showToast('Senha alterada! Redirecionando para login.', 'success', 3000);

    setTimeout(() => {
        window.location.href = redirectUrl;
    }, 2000);
}

/**
 * CSS otimizado - sem conflitos de posicionamento
 */
function addToastStyles() {
    if (document.getElementById('custom-toast-styles')) return;

    const style = document.createElement('style');
    style.id = 'custom-toast-styles';
    style.textContent = `
      /* 🍞 Estilos que NÃO interferem no posicionamento do Toastify */
      .toastify {
        font-family: system-ui, -apple-system, sans-serif !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        border: none !important;
        font-weight: 500 !important;
        line-height: 1.4 !important;
        font-size: 14px !important;

        /* ✅ Força z-index alto */
        z-index: 10000 !important;

        /* ✅ Garante posicionamento fixo */
        position: fixed !important;

        /* 🔄 Transição suave para reposicionamento */
        transition: top 0.3s ease !important;
      }

      /* 📱 Mobile: Responsivo mas ainda à direita */
      @media (max-width: 768px) {
        .toastify {
          max-width: calc(100vw - 40px) !important;
          min-width: 200px !important;
          width: auto !important;
          right: 20px !important;
          padding: 12px 16px !important;
        }
      }

      /* 🖥️ Desktop */
      @media (min-width: 769px) {
        .toastify {
          max-width: 35vw !important;
          min-width: 250px !important;
          width: auto !important;
          right: 20px !important;
          padding: 12px 16px !important;
        }
      }

      /* 🎨 Gradientes por tipo */
      .toast-success {
        background: linear-gradient(135deg, #16a34a, #15803d) !important;
      }

      .toast-error {
        background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
      }

      .toast-warning {
        background: linear-gradient(135deg, #d97706, #c2410c) !important;
      }

      .toast-info {
        background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
      }

      /* 🔄 Animação suave sem interferir na posição */
      .toastify.on {
        animation: fadeInRight 0.3s ease-out !important;
      }

      @keyframes fadeInRight {
        from {
          opacity: 0;
          transform: translateX(20px);
        }
        to {
          opacity: 1;
          transform: translateX(0);
        }
      }

      /* ❌ Remove qualquer margin/padding que possa causar "pulo" */
      .toastify {
        margin: 0 !important;
      }
    `;

    document.head.appendChild(style);
}

// Inicializa quando DOM carregar
document.addEventListener('DOMContentLoaded', addToastStyles);

// 🔄 Função para reposicionar toasts quando um é removido
function repositionToasts() {
    const toasts = document.querySelectorAll('.toastify');
    toasts.forEach((toast, index) => {
        const newTop = 20 + (index * 80);
        toast.style.top = newTop + 'px';
    });
}

// 👁️ Observer para detectar quando toasts são removidos
const toastObserver = new MutationObserver(() => {
    repositionToasts();
});

// Inicia observação quando DOM carregar
document.addEventListener('DOMContentLoaded', () => {
    // Observa mudanças no body para detectar toasts sendo adicionados/removidos
    toastObserver.observe(document.body, {
        childList: true,
        subtree: true
    });
});

// 📱 Função para lidar com foco em inputs mobile
function initMobileKeyboardHandling() {
    // Detecta se é mobile
    const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent) || window.innerWidth <= 768;

    if (!isMobile) return;

    let isKeyboardOpen = false;
    let originalViewportHeight = window.innerHeight;

    // Detecta quando viewport muda (teclado abre/fecha)
    function handleViewportChange() {
        const currentHeight = window.innerHeight;
        const heightDifference = originalViewportHeight - currentHeight;

        // Se diminuiu mais de 150px, provavelmente é teclado
        isKeyboardOpen = heightDifference > 150;

        document.body.classList.toggle('keyboard-open', isKeyboardOpen);
    }

    // Escuta mudanças no viewport
    window.addEventListener('resize', handleViewportChange);

    // Foco em inputs
    document.addEventListener('focusin', function (e) {
        if (e.target.matches('input, textarea, select')) {
            setTimeout(() => {
                // Scroll para o elemento com padding extra
                e.target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'nearest'
                });

                // Padding extra para garantir visibilidade
                setTimeout(() => {
                    window.scrollBy(0, -50);
                }, 300);
            }, 300); // Delay para aguardar teclado abrir
        }
    });

    // Quando perde foco, volta ao normal
    document.addEventListener('focusout', function (e) {
        if (e.target.matches('input, textarea, select')) {
            setTimeout(() => {
                handleViewportChange();
            }, 300);
        }
    });
}

// Inicia quando DOM carregar
document.addEventListener('DOMContentLoaded', initMobileKeyboardHandling);

// ============= HIDE ADDRESS BAR ON SCROLL =============
function initHideAddressBar() {
    let isFirstScroll = true;
    let lastScrollY = window.scrollY;
    let hideTimeout;

    function hideAddressBar() {
        const currentScrollY = window.scrollY;

        // Na primeira rolagem para cima OU rolagem normal para cima
        if ((isFirstScroll && currentScrollY < lastScrollY) ||
            (currentScrollY < lastScrollY && currentScrollY > 5)) {

            clearTimeout(hideTimeout);

            hideTimeout = setTimeout(() => {
                window.scrollTo(0, 1);
            }, isFirstScroll ? 50 : 150);

            isFirstScroll = false;
        }

        lastScrollY = currentScrollY;
    }

    let ticking = false;
    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(() => {
                hideAddressBar();
                ticking = false;
            });
            ticking = true;
        }
    }

    window.addEventListener('scroll', requestTick, { passive: true });

    // Esconde na carga inicial
    window.addEventListener('load', () => {
        setTimeout(() => window.scrollTo(0, 1), 500);
    });
  }

/**
 * Toggle entre visualização Cards e Lista
 * Apenas para desktop (mobile sempre fica em cards)
 */
function initViewToggle() {
    // Verifica se existem os elementos necessários
    const cardsContainer = document.getElementById('cards-view');
    const tableContainer = document.getElementById('table-view');
    const toggleButton = document.getElementById('view-toggle');

    if (!cardsContainer || !tableContainer || !toggleButton) {
        return; // Se não existir os elementos, não faz nada
    }

    // Recupera preferência salva ou usa 'cards' como padrão
    let currentView = localStorage.getItem('viewPreference') || 'table';

    // Se for mobile, força sempre cards
    if (window.innerWidth < 768) {
        currentView = 'cards';
        toggleButton.style.display = 'none'; // Esconde botão no mobile
    }

    // Aplica a visualização inicial
    setView(currentView);

    // Event listener para o toggle
    toggleButton.addEventListener('click', function() {
        // Só funciona em desktop
        if (window.innerWidth < 768) return;

        currentView = currentView === 'cards' ? 'table' : 'cards';
        setView(currentView);

        // Salva preferência
        localStorage.setItem('viewPreference', currentView);
    });

    // Event listener para mudanças de tamanho da tela
    window.addEventListener('resize', function() {
        if (window.innerWidth < 768) {
            // Mobile: força cards e esconde toggle
            setView('cards');
            toggleButton.style.display = 'none';
            currentView = 'cards';
        } else {
            // Desktop: mostra toggle e restaura preferência
            toggleButton.style.display = 'flex';
            const savedView = localStorage.getItem('viewPreference') || 'cards';
            if (savedView !== currentView) {
                currentView = savedView;
                setView(currentView);
            }
        }
    });

    function setView(view) {
        const icon = toggleButton.querySelector('i');

        if (view === 'cards') {
            // Mostra cards, esconde tabela
            cardsContainer.classList.remove('hidden');
            tableContainer.classList.add('hidden');

            // Atualiza ícone para "lista"
            icon.className = 'fa-solid fa-list text-lg';
            toggleButton.title = 'Alternar para visualização em lista';
        } else {
            // Mostra tabela, esconde cards
            cardsContainer.classList.add('hidden');
            tableContainer.classList.remove('hidden');

            // Atualiza ícone para "cards"
            icon.className = 'fa-solid fa-th-large text-lg';
            toggleButton.title = 'Alternar para visualização em cards';
        }

        currentView = view;
    }
}

// Inicializa quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    initViewToggle();
});