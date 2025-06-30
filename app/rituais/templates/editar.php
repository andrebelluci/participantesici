<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($ritual)) {
  die("Ritual não encontrado.");
}
?>

<div class="max-w-screen-lg mx-auto px-4 py-8">
    <!-- Ações -->
    <div class="flex items-center justify-between mb-6">
        <a href="<?= isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : '/participantesici/public_html/rituais' ?>"
            class="flex items-center text-gray-600 hover:text-[#00bfff] transition text-sm">
            <i class="fa-solid fa-arrow-left mr-2"></i> Voltar
        </a>

        <button type="submit" form="formulario-ritual"
            class="bg-[#00bfff] text-black px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
            <i class="fa-solid fa-save mr-1"></i>
            Salvar alterações
        </button>
    </div>

    <!-- Título -->
    <h1 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
    <div class="flex-shrink-0 text-orange-500">
        <i class="fa-solid fa-fire-flame-simple"></i><i class="fa-solid fa-pen text-sm"></i>
    </div>
    Editar Ritual</h1>

    <div class="form-container mobile-compact">
    <form method="POST" enctype="multipart/form-data" id="formulario-ritual"
        class="bg-white p-6 rounded-lg shadow space-y-6 border border-gray-200" novalidate>

        <?php if (isset($_GET['redirect'])): ?>
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect']) ?>">
        <?php endif; ?>

        <!-- Dados do Ritual -->
        <div>
            <h2 class="text-lg font-semibold text-gray-700 mb-4"><i class="fa-solid fa-leaf text-green-600"></i> Dados do Ritual</h2>

            <!-- Upload de imagem -->
            <div class="mb-6 w-full md:w-1/6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Foto do Ritual:</label>

                <input type="file" name="foto" id="foto-input" accept="image/*" class="hidden">

                <!-- Campo hidden para armazenar caminho da foto para JS -->
                <div data-foto-path="<?= htmlspecialchars($ritual['foto'] ?? '') ?>" style="display: none;"></div>

                <!-- Área de Upload -->
                <div id="upload-area"
                    class="border-2 border-dashed border-gray-300 rounded-lg p-4 md:p-4 text-center bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer <?= $ritual['foto'] ? 'hidden' : '' ?>">
                    <div class="flex flex-col items-center">
                        <svg class="w-8 h-8 md:w-12 md:h-12 text-gray-400 mb-2 md:mb-3" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <p class="text-xs md:text-sm font-medium text-gray-700 mb-1">Adicionar Foto</p>
                        <p class="text-xs text-gray-500 hidden md:block">Clique para escolher</p>
                    </div>
                </div>

                <!-- Preview da Imagem -->
                <div id="preview-container" class="mt-4 <?= $ritual['foto'] ? '' : 'hidden' ?>">
                    <!-- Container quadrado fixo -->
                    <div class="relative bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <div class="aspect-square w-full"> <!-- Força aspecto 1:1 -->
                            <img id="preview-image"
                                 src="<?= htmlspecialchars($ritual['foto'] ?? '#') ?>"
                                 alt="Preview"
                                 class="w-full h-full object-cover cursor-pointer"
                                 onclick="openImageModal(this.src)">
                        </div>
                    </div>

                    <div class="flex flex-row md:flex-row gap-2 mt-2">
                        <button type="button" id="substituir-imagem-btn"
                            class="bg-blue-600 text-white py-2 px-2 md:px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium text-xs md:text-sm">
                            <div class="flex items-center gap-1">
                                <i class="fa-solid fa-arrows-rotate"></i>
                                Substituir
                            </div>
                        </button>
                        <button type="button" id="excluir-imagem-btn"
                            class="bg-red-600 text-white py-2 px-2 md:px-4 rounded-lg hover:bg-red-700 transition-colors font-medium text-xs md:text-sm">
                            <div class="flex items-center gap-1">
                                <i class="fa-solid fa-trash"></i>
                                Remover
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Campos do Ritual -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome do Ritual:</label>
                    <input type="text" name="nome" id="nome" required
                        value="<?= htmlspecialchars($ritual['nome']) ?>"
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>

                <div>
                    <label for="data_ritual" class="block text-sm font-medium text-gray-700 mb-1">Data do Ritual:</label>
                    <input type="date" name="data_ritual" id="data_ritual" required
                        value="<?= htmlspecialchars($ritual['data_ritual']) ?>"
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>

                <div>
                    <label for="padrinho_madrinha" class="block text-sm font-medium text-gray-700 mb-1">Padrinho ou Madrinha:</label>
                    <select name="padrinho_madrinha" id="padrinho_madrinha" required
                        class="w-full border border-gray-300 rounded px-3 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Selecione...</option>
                        <option value="Dirceu" <?= $ritual['padrinho_madrinha'] == 'Dirceu' ? 'selected' : '' ?>>Dirceu</option>
                        <option value="Gabriela" <?= $ritual['padrinho_madrinha'] == 'Gabriela' ? 'selected' : '' ?>>Gabriela</option>
                        <option value="Dirceu e Gabriela" <?= $ritual['padrinho_madrinha'] == 'Dirceu e Gabriela' ? 'selected' : '' ?>>Dirceu e Gabriela</option>
                    </select>
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
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

<!-- Modal de Ampliação de Imagem -->
<div id="modal-image" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-lg overflow-hidden shadow-lg relative max-w-sm w-full mx-4">
    <button onclick="closeImageModal()" class="absolute top-2 right-2 text-red-600 hover:text-red-800 text-lg">
      <i class="fa-solid fa-window-close"></i>
    </button>
    <img id="modal-image-content" class="w-full h-auto object-contain max-h-[80vh]" alt="Imagem Ampliada">
  </div>
</div>

<script src="/participantesici/public_html/assets/js/ritual.js"></script>
<script src="/participantesici/public_html/assets/js/ritual-editar.js"></script>
<script src="/participantesici/public_html/assets/js/modal.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>