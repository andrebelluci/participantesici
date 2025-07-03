<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-screen-md mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <a href="/home"
            class="flex items-center text-gray-600 hover:text-[#00bfff] transition text-sm">
            <i class="fa-solid fa-arrow-left mr-2"></i> Voltar
        </a>

        <button type="submit" form="formulario-senha" id="submit-btn" disabled
            class="bg-gray-500 text-black px-6 py-2 rounded transition font-semibold shadow cursor-not-allowed opacity-50">
            Salvar Alterações
        </button>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-user-lock text-yellow-600"></i> Alterar Senha</h1>

    <div class="form-container mobile-compact">
        <form method="POST" action="/participantesici/app/perfil/actions/atualizar_senha.php" id="formulario-senha"
            class="bg-white p-6 rounded-lg shadow space-y-6 border border-gray-200" novalidate>

            <h2 class="text-lg font-semibold text-gray-700 mb-4"><i class="fa-solid fa-lock text-yellow-600"></i> Alteração de Senha</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Senha Atual -->
                <div class="relative group">
                    <label for="senha_atual" class="block text-sm font-medium text-gray-700 mb-1">Senha Atual:</label>
                    <input type="password" name="senha_atual" id="senha_atual" required
                        class="senha-input w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
                    <p class="text-red-500 text-sm mt-1 hidden" id="erro-senha-atual">Campo obrigatório</p>
                </div>

                <!-- Nova Senha -->
                <div class="relative group">
                    <label for="nova_senha" class="block text-sm font-medium text-gray-700 mb-1">Nova Senha:</label>
                    <input type="password" name="nova_senha" id="nova_senha" required
                        class="senha-input w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
                    <p class="text-red-500 text-sm mt-1 hidden" id="erro-senha-atual">Campo obrigatório</p>

                    <!-- Indicadores de validação da senha -->
                    <div id="senha-validacao" class="mt-2 space-y-1 text-xs">
                        <div class="flex items-center gap-2" id="tamanho-check">
                            <i class="fa-solid fa-circle-xmark text-red-500"></i>
                            <span class="text-gray-600">Pelo menos 8 caracteres</span>
                        </div>
                        <div class="flex items-center gap-2" id="maiuscula-check">
                            <i class="fa-solid fa-circle-xmark text-red-500"></i>
                            <span class="text-gray-600">1 letra maiúscula</span>
                        </div>
                        <div class="flex items-center gap-2" id="numero-check">
                            <i class="fa-solid fa-circle-xmark text-red-500"></i>
                            <span class="text-gray-600">1 número</span>
                        </div>
                        <div class="flex items-center gap-2" id="especial-check">
                            <i class="fa-solid fa-circle-xmark text-red-500"></i>
                            <span class="text-gray-600">1 caractere especial</span>
                        </div>
                    </div>
                </div>

                <!-- Confirmar Senha -->
                <div class="relative group">
                    <label for="confirmar_senha" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nova Senha:</label>
                    <input type="password" name="confirmar_senha" id="confirmar_senha" required
                        class="senha-input w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
                    <p class="text-red-500 text-sm mt-1 hidden" id="erro-senha-atual">Campo obrigatório</p>

                    <!-- Indicador de correspondência das senhas -->
                    <div id="senha-match" class="mt-2 hidden">
                        <div class="flex items-center gap-2">
                            <i id="match-icon" class="fa-solid fa-circle-xmark text-red-500"></i>
                            <span id="match-text" class="text-xs text-gray-600">As senhas não coincidem</span>
                        </div>
                    </div>
                </div>

                <!-- Botão global para ver/ocultar todas -->
                <button type="button" onclick="toggleTodasSenhas(this, this.querySelector('i'))"
                    class="text-gray-600 text-sm hover:text-[#00bfff] flex items-center gap-2 transition self-start">
                    <i class="fa-solid fa-eye"></i> Mostrar Senhas
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Botão Voltar ao Topo -->
<button id="scroll-to-top"
    class="fixed bottom-12 right-4 bg-[#00bfff] md:hover:bg-yellow-400 text-black p-3 rounded-full shadow-lg transform transition-all duration-300 ease-in-out opacity-0 invisible translate-y-4 z-50">
    <i class="fa-solid fa-chevron-up md:text-lg"></i>
</button>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const senhaAtualInput = document.getElementById('senha_atual');
    const novaSenhaInput = document.getElementById('nova_senha');
    const confirmarSenhaInput = document.getElementById('confirmar_senha');
    const submitBtn = document.getElementById('submit-btn');
    const form = document.getElementById('formulario-senha');

    // Elementos de validação
    const tamanhoCheck = document.getElementById('tamanho-check');
    const maiusculaCheck = document.getElementById('maiuscula-check');
    const numeroCheck = document.getElementById('numero-check');
    const especialCheck = document.getElementById('especial-check');
    const senhaMatch = document.getElementById('senha-match');
    const matchIcon = document.getElementById('match-icon');
    const matchText = document.getElementById('match-text');

    // Função para validar senha
    function validarSenha(senha) {
        const validacoes = {
            tamanho: senha.length >= 8,
            maiuscula: /[A-Z]/.test(senha),
            numero: /[0-9]/.test(senha),
            especial: /[^a-zA-Z0-9]/.test(senha)
        };

        // Atualiza indicadores visuais
        updateCheckIcon(tamanhoCheck, validacoes.tamanho);
        updateCheckIcon(maiusculaCheck, validacoes.maiuscula);
        updateCheckIcon(numeroCheck, validacoes.numero);
        updateCheckIcon(especialCheck, validacoes.especial);

        return Object.values(validacoes).every(v => v);
    }

    // Função para atualizar ícone de check
    function updateCheckIcon(element, isValid) {
        const icon = element.querySelector('i');
        const text = element.querySelector('span');

        if (isValid) {
            icon.className = 'fa-solid fa-circle-check text-green-500';
            text.className = 'text-green-600';
        } else {
            icon.className = 'fa-solid fa-circle-xmark text-red-500';
            text.className = 'text-gray-600';
        }
    }

    // Função para verificar se senhas coincidem
    function verificarCorrespondencia() {
        const novaSenha = novaSenhaInput.value;
        const confirmarSenha = confirmarSenhaInput.value;

        if (confirmarSenha.length > 0) {
            senhaMatch.classList.remove('hidden');

            if (novaSenha === confirmarSenha) {
                matchIcon.className = 'fa-solid fa-circle-check text-green-500';
                matchText.textContent = 'As senhas coincidem';
                matchText.className = 'text-xs text-green-600';
                return true;
            } else {
                matchIcon.className = 'fa-solid fa-circle-xmark text-red-500';
                matchText.textContent = 'As senhas não coincidem';
                matchText.className = 'text-xs text-red-600';
                return false;
            }
        } else {
            senhaMatch.classList.add('hidden');
            return false;
        }
    }

    // Função para habilitar/desabilitar botão
    function atualizarBotao() {
        const senhaAtualPreenchida = senhaAtualInput.value.length > 0;
        const senhaValida = validarSenha(novaSenhaInput.value);
        const senhasCorrespondem = verificarCorrespondencia();
        const podeSubmeter = senhaAtualPreenchida && senhaValida && senhasCorrespondem && novaSenhaInput.value.length > 0;

        if (podeSubmeter) {
            submitBtn.disabled = false;
            submitBtn.className = 'bg-[#00bfff] text-black px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow cursor-pointer';
        } else {
            submitBtn.disabled = true;
            submitBtn.className = 'bg-gray-500 text-black px-6 py-2 rounded transition font-semibold shadow cursor-not-allowed opacity-50';
        }
    }

    // Event listeners
    senhaAtualInput.addEventListener('input', atualizarBotao);

    novaSenhaInput.addEventListener('input', function() {
        validarSenha(this.value);
        verificarCorrespondencia();
        atualizarBotao();
    });

    confirmarSenhaInput.addEventListener('input', function() {
        verificarCorrespondencia();
        atualizarBotao();
    });

    // Validação no submit do formulário
    form.addEventListener('submit', function(e) {
        console.log('🔍 Submit do formulário iniciado');

        const senhaAtualPreenchida = senhaAtualInput.value.length > 0;
        const senhaValida = validarSenha(novaSenhaInput.value);
        const senhasCorrespondem = novaSenhaInput.value === confirmarSenhaInput.value;

        console.log('🔍 Validações:', {
            senhaAtualPreenchida,
            senhaValida,
            senhasCorrespondem
        });

        if (!senhaAtualPreenchida) {
            e.preventDefault();
            showToast('Por favor, informe sua senha atual', 'error');
            senhaAtualInput.focus();
            return;
        }

        if (!senhaValida) {
            e.preventDefault();
            showToast('A nova senha deve atender todos os critérios de segurança', 'error');
            novaSenhaInput.focus();
            return;
        }

        if (!senhasCorrespondem) {
            e.preventDefault();
            showToast('As senhas não coincidem', 'error');
            confirmarSenhaInput.focus();
            return;
        }

        // Se chegou até aqui, está tudo válido
        console.log('✅ Formulário válido, enviando...');
        showToast('Alterando senha...', 'info');

        // Não previne o submit - deixa o formulário ser enviado
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>