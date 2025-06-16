<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-screen-md mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <a href="/participantesici/public_html/home" class="flex items-center text-gray-600 hover:text-[#00bfff] transition text-sm">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>

        <button type="submit" form="formulario-senha"
            class="bg-[#00bfff] text-black px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
            Salvar Alterações
        </button>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">🔐 Alterar Senha</h1>

    <form method="POST" action="/participantesici/app/perfil/actions/atualizar_senha.php" id="formulario-senha"
        class="bg-white p-6 rounded-lg shadow space-y-6 border border-gray-200" novalidate>

        <h2 class="text-lg font-semibold text-gray-700 mb-4">🔒 Alteração de Senha</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Senha Atual -->
            <div class="relative group">
                <label for="senha_atual" class="block text-sm font-medium text-gray-700 mb-1">Senha Atual:</label>
                <input type="password" name="senha_atual" id="senha_atual" required
                    class="senha-input w-full border border-gray-300 rounded px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
                <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
            </div>

            <!-- Nova Senha -->
            <div class="relative group">
                <label for="nova_senha" class="block text-sm font-medium text-gray-700 mb-1">Nova Senha:</label>
                <input type="password" name="nova_senha" id="nova_senha" required
                    class="senha-input w-full border border-gray-300 rounded px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
                <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
            </div>

            <!-- Confirmar Senha -->
            <div class="relative group">
                <label for="confirmar_senha" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nova Senha:</label>
                <input type="password" name="confirmar_senha" id="confirmar_senha" required
                    class="senha-input w-full border border-gray-300 rounded px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
                <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
            </div>

            <!-- Botão global para ver/ocultar todas -->
<button type="button" onclick="toggleTodasSenhas(this, this.querySelector('i'))"
    class="text-gray-600 text-sm hover:text-[#00bfff] flex items-center gap-2 transition">
  <i class="fa-solid fa-eye"></i> Mostrar Senhas
</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
