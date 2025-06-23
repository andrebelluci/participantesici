<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-screen-lg mx-auto px-4 py-8">
    <!-- Ações -->
    <div class="flex items-center justify-between mb-6">
        <a href="/participantesici/public_html/participantes"
            class="flex items-center text-gray-600 hover:text-[#00bfff] transition text-sm">
            <i class="fa-solid fa-arrow-left mr-2"></i> Voltar
        </a>

        <button type="submit" form="formulario-participante"
            class="bg-[#00bfff] text-black px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
            <i class="fa-solid fa-plus"></i>
            Cadastrar
        </button>
    </div>

    <!-- Título -->
    <h1 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">👤 Novo Participante</h1>

    <form method="POST" enctype="multipart/form-data" id="formulario-participante"
        class="bg-white p-6 rounded-lg shadow space-y-6 border border-gray-200" novalidate>

        <!-- Dados Pessoais -->
        <div>
            <h2 class="text-lg font-semibold text-gray-700 mb-4">ℹ️ Dados Pessoais</h2>

            <div class="mb-6 w-full md:w-1/6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Foto do Participante:</label>

                <input type="file" name="foto" id="foto-input" accept="image/*" capture="environment" class="hidden">

                <input type="hidden" name="foto_cropada" id="foto-cropada">

                <!-- Área de Upload -->
                <div id="upload-area"
                    class="border-2 border-dashed border-gray-300 rounded-lg p-4 md:p-4 text-center bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer">
                    <div class="flex flex-col items-center">
                        <svg class="w-8 h-8 md:w-12 md:h-12 text-gray-400 mb-2 md:mb-3" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <p class="text-xs md:text-sm font-medium text-gray-700 mb-1">Adicionar Foto</p>
                        <p class="text-xs text-gray-500 hidden md:block">Toque para escolher</p>
                    </div>
                </div>

                <!-- Preview da Imagem QUADRADO -->
                <div id="preview-container" class="hidden mt-4">
                    <!-- Container quadrado fixo -->
                    <div class="relative bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <div class="aspect-square w-full"> <!-- Força aspecto 1:1 -->
                            <img id="preview-image" src="#" alt="Preview"
                                class="w-full h-full object-cover cursor-pointer"
                                onclick="openImageModal(this.src)">
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row gap-2 mt-3">
                        <button type="button" id="crop-image-btn"
                            class="bg-green-600 text-white py-2 px-2 md:px-4 rounded-lg hover:bg-green-700 transition-colors font-medium text-xs md:text-sm">
                            ✂️ Ajustar
                        </button>
                        <button type="button" id="substituir-imagem-btn"
                            class="bg-blue-600 text-white py-2 px-2 md:px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium text-xs md:text-sm">
                            Substituir
                        </button>
                        <button type="button" id="excluir-imagem-btn"
                            class="bg-red-600 text-white py-2 px-2 md:px-4 rounded-lg hover:bg-red-700 transition-colors font-medium text-xs md:text-sm">
                            Remover
                        </button>
                    </div>
                </div>
            </div>

            <!-- Nome, nascimento, sexo -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="col-span-2">
                    <label for="nome_completo" class="block text-sm font-medium text-gray-700 mb-1">Nome
                        Completo:</label>
                    <input type="text" name="nome_completo" id="nome_completo" required
                        class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label for="nascimento" class="block text-sm font-medium text-gray-700 mb-1">Data de
                        Nascimento:</label>
                    <input type="date" name="nascimento" id="nascimento" required
                        class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label for="sexo" class="block text-sm font-medium text-gray-700 mb-1">Sexo:</label>
                    <select name="sexo" id="sexo" required
                        class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                    </select>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label for="cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF:</label>
                    <input type="text" name="cpf" id="cpf" placeholder="___.___.___-__" required
                        oninput="mascaraCPF(this)" class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>
            </div>

            <!-- CPF, RG, etc -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mt-4">
                <div class="col-span-1 md:col-span-1">
                    <label for="rg" class="block text-sm font-medium text-gray-700 mb-1">RG:</label>
                    <input type="text" name="rg" id="rg" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div class="col-span-1 md:col-span-1">
                    <label for="passaporte" class="block text-sm font-medium text-gray-700 mb-1">Passaporte:</label>
                    <input type="text" name="passaporte" id="passaporte"
                        class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div class="col-span-1 md:col-span-1">
                    <label for="celular" class="block text-sm font-medium text-gray-700 mb-1">Celular:</label>
                    <input type="text" name="celular" id="celular" placeholder="(__) _____-____" required
                        oninput="mascaraCelular(this)" class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>
                <div class="col-span-1 md:col-span-2">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail:</label>
                    <input type="email" name="email" id="email" required
                        class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>
            </div>
        </div>

        <!-- Endereço -->
        <div>
            <h2 class="text-lg font-semibold text-gray-700 mb-4">📍 Endereço</h2>

            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">

                <div class="lg:col-span-1">
                    <label for="cep" class="block text-sm font-medium text-gray-700 mb-1">CEP:</label>
                    <input type="text" name="cep" id="cep" placeholder="_____-___" required oninput="mascaraCEP(this)"
                        class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>
                <div class="flex items-end md:col-span-1 flex-row gap-2">
                    <button type="button" id="buscar-cep-btn"
                        class="bg-[#00bfff] text-black px-2 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
                        Buscar CEP
                    </button>
                </div>
                <div class="lg:col-span-3"></div>
                <div class="col-span-2 md:col-span-3">
                    <label for="endereco_rua" class="block text-sm font-medium text-gray-700 mb-1">Rua:</label>
                    <input type="text" name="endereco_rua" id="endereco_rua" required
                        class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label for="endereco_numero" class="block text-sm font-medium text-gray-700 mb-1">Número:</label>
                    <input type="text" name="endereco_numero" id="endereco_numero" required
                        class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>
                <div class="col-span-2 md:col-span-2">
                    <label for="endereco_complemento"
                        class="block text-sm font-medium text-gray-700 mb-1">Complemento:</label>
                    <input type="text" name="endereco_complemento" id="endereco_complemento"
                        class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-4 mt-4">
                <div>
                    <label for="bairro" class="block text-sm font-medium text-gray-700 mb-1">Bairro:</label>
                    <input type="text" name="bairro" id="bairro" required
                        class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>
                <div>
                    <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">Cidade:</label>
                    <input type="text" name="cidade" id="cidade" required
                        class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>
                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado:</label>
                    <input type="text" name="estado" id="estado" required
                        class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>
            </div>
        </div>

        <!-- Informações Adicionais -->
        <div>
            <h2 class="text-lg font-semibold text-gray-700 mb-4">➕ Informações Adicionais</h2>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="como_soube" class="block text-sm font-medium text-gray-700 mb-1">Como soube do Instituto
                        Céu Interior:</label>
                    <input type="text" name="como_soube" id="como_soube" required
                        class="w-full border border-gray-300 rounded px-3 py-2">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>
                <div>
                    <label for="sobre_participante" class="block text-sm font-medium text-gray-700 mb-1">Sobre o
                        Participante:</label>
                    <textarea name="sobre_participante" id="sobre_participante"
                        class="w-full border border-gray-300 rounded px-3 py-2" rows="4"></textarea>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Botão Voltar ao Topo -->
<button id="scroll-to-top"
    class="fixed bottom-12 right-4 bg-[#00bfff] hover:bg-yellow-400 text-black p-3 rounded-full shadow-lg transform transition-all duration-300 ease-in-out opacity-0 invisible translate-y-4 z-50">
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

<!-- Modal de Crop -->
<div id="crop-modal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-lg w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Ajustar Foto</h3>
            <button id="close-crop-modal" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>

        <div class="mb-4">
            <img id="crop-image" src="#" alt="Imagem para crop" class="max-w-full">
        </div>

        <div class="flex gap-2 justify-end">
            <button id="cancel-crop" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                Cancelar
            </button>
            <button id="apply-crop" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                Aplicar
            </button>
        </div>
    </div>
</div>

<script src="/participantesici/public_html/assets/js/participante-novo.js"></script>
<script src="/participantesici/public_html/assets/js/participante.js"></script>
    <script src="/participantesici/public_html/assets/js/busca-cep.js"></script>
<script src="/participantesici/public_html/assets/js/modal.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>