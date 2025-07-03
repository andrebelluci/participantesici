<?php
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-screen-md mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <a href="/usuarios"
            class="flex items-center text-gray-600 hover:text-[#00bfff] transition text-sm">
            <i class="fa-solid fa-arrow-left mr-2"></i> Voltar
        </a>

        <button type="submit" form="formulario-usuario" id="submit-btn"
            class="bg-[#00bfff] text-black px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
            <i class="fa-solid fa-save mr-2"></i>Criar Usuário
        </button>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
        <i class="fa-solid fa-user-plus text-blue-500"></i> Novo Usuário
    </h1>

    <div class="form-container">
        <form method="POST" id="formulario-usuario"
            class="bg-white p-6 rounded-lg shadow space-y-6 border border-gray-200" novalidate>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Nome -->
                <div>
                    <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo: <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="nome" id="nome" required
                        value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
                    <p class="text-red-500 text-sm mt-1 hidden" id="erro-nome">Nome é obrigatório</p>
                </div>

                <!-- Usuário -->
                <div>
                    <label for="usuario" class="block text-sm font-medium text-gray-700 mb-1">Nome de Usuário: <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="usuario" id="usuario" required
                        value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
                    <p class="text-red-500 text-sm mt-1 hidden" id="erro-usuario">Usuário é obrigatório</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail: <span
                            class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
                    <p class="text-red-500 text-sm mt-1 hidden" id="erro-email">E-mail é obrigatório</p>
                </div>

                <!-- Perfil -->
                <div>
                    <label for="perfil_id" class="block text-sm font-medium text-gray-700 mb-1">Perfil: <span
                            class="text-red-500">*</span></label>
                    <select name="perfil_id" id="perfil_id" required
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
                        <option value="">Selecione o perfil...</option>
                        <?php foreach ($perfis as $perfil): ?>
                            <option value="<?= $perfil['id'] ?>" <?= ($_POST['perfil_id'] ?? '') == $perfil['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($perfil['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-red-500 text-sm mt-1 hidden" id="erro-perfil">Perfil é obrigatório</p>
                </div>
            </div>

            <!-- Senha com validação -->
            <div>
                <label for="senha" class="block text-sm font-medium text-gray-700 mb-1">Senha: <span
                        class="text-red-500">*</span></label>
                <input type="password" name="senha" id="senha" required
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff] senha-input">

                <!-- Critérios de validação -->
                <div class="mt-2 text-sm space-y-1">
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

                <!-- Botão para mostrar/ocultar senha -->
                <button type="button" onclick="toggleTodasSenhas(this, this.querySelector('i'))"
                    class="text-gray-600 text-sm hover:text-[#00bfff] flex items-center gap-2 transition mt-2">
                    <i class="fa-solid fa-eye"></i> Mostrar Senha
                </button>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fa-solid fa-info-circle text-blue-600 mr-2 mt-0.5"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium">Sobre os perfis:</p>
                        <ul class="mt-1 list-disc list-inside space-y-1">
                            <li><strong>Administrador:</strong> Acesso completo ao sistema (gerenciar usuários,
                                participantes, rituais)</li>
                            <li><strong>Usuário:</strong> Acesso limitado (apenas participantes e rituais)</li>
                        </ul>
                    </div>
                </div>
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
        const form = document.getElementById('formulario-usuario');
        const senhaInput = document.getElementById('senha');
        const submitBtn = document.getElementById('submit-btn');

        // Elementos de validação
        const tamanhoCheck = document.getElementById('tamanho-check');
        const maiusculaCheck = document.getElementById('maiuscula-check');
        const numeroCheck = document.getElementById('numero-check');
        const especialCheck = document.getElementById('especial-check');

        // Função para atualizar ícone de validação
        function updateCheckIcon(element, isValid) {
            const icon = element.querySelector('i');
            const text = element.querySelector('span');

            if (isValid) {
                icon.className = 'fa-solid fa-circle-check text-green-500';
                text.classList.remove('text-gray-600');
                text.classList.add('text-green-600');
            } else {
                icon.className = 'fa-solid fa-circle-xmark text-red-500';
                text.classList.remove('text-green-600');
                text.classList.add('text-gray-600');
            }
        }

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

            return validacoes.tamanho && validacoes.maiuscula && validacoes.numero && validacoes.especial;
        }

        // Event listener para validação em tempo real
        senhaInput.addEventListener('input', function () {
            validarSenha(this.value);
        });

        // Configurar validação do formulário
        if (form) {
            configurarValidacao(form);
        }

        // Validação no submit
        form.addEventListener('submit', function (e) {
            const senhaValida = validarSenha(senhaInput.value);

            if (!senhaValida) {
                e.preventDefault();
                showToast('A senha deve atender todos os critérios de segurança', 'error');
                senhaInput.focus();
                return;
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>