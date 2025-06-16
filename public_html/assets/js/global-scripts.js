document.addEventListener('DOMContentLoaded', function () {
    const toggleButton = document.getElementById('menu-toggle');
    const mobileNav = document.getElementById('mobile-nav');

    toggleButton.addEventListener('click', function () {
        mobileNav.classList.toggle('hidden');
    });
});

// Alternar visibilidade de todos os campos de senha
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

// Validação simples de campos obrigatórios (visual)
function validarCamposObrigatorios(formSelector = 'form') {
    const formulario = document.querySelector(formSelector);

    if (!formulario) return;

    formulario.addEventListener('submit', function (e) {
        let valido = true;

        formulario.querySelectorAll('input[required]').forEach(input => {
            const erro = input.parentElement.querySelector('p');
            if (!input.value.trim()) {
                input.classList.add('border-red-500');
                if (erro) erro.classList.remove('hidden');
                valido = false;
            } else {
                input.classList.remove('border-red-500');
                if (erro) erro.classList.add('hidden');
            }
        });

        if (!valido) e.preventDefault();
    });

    // validação em blur
    formulario.querySelectorAll('input[required]').forEach(input => {
        input.addEventListener('blur', () => {
            const erro = input.parentElement.querySelector('p');
            if (!input.value.trim()) {
                input.classList.add('border-red-500');
                if (erro) erro.classList.remove('hidden');
            } else {
                input.classList.remove('border-red-500');
                if (erro) erro.classList.add('hidden');
            }
        });
    });
}

// Executa automaticamente se tiver senha-input ou campos obrigatórios
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.senha-input')) {
        validarCamposObrigatorios();
    }
});

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formulario-senha");

    if (form) {
        form.addEventListener("submit", (e) => {
            let isValid = true;

            form.querySelectorAll("input[required]").forEach((input) => {
                const erroMsg = document.getElementById(`erro-${input.name}`);
                if (!input.value.trim()) {
                    input.classList.add("border-red-500");
                    erroMsg?.classList.remove("hidden");
                    isValid = false;
                } else {
                    input.classList.remove("border-red-500");
                    erroMsg?.classList.add("hidden");
                }
            });

            if (!isValid) {
                e.preventDefault(); // impede o envio do formulário
            }
        });
    }
});

