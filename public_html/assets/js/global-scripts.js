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
 * Configura validação visual de campos obrigatórios
 */
function configurarValidacao(form) {
    form.addEventListener('submit', function (e) {
        let valido = true;

        form.querySelectorAll('[required]').forEach((input) => {
            const mensagemErro = encontrarMensagemErro(input);
            if (!input.value.trim()) {
                input.classList.add('border-red-500');
                input.focus();
                mensagemErro?.classList.remove('hidden');
                valido = false;
            } else {
                input.classList.remove('border-red-500');
                mensagemErro?.classList.add('hidden');
            }
        });

        if (!valido) e.preventDefault();
    });

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