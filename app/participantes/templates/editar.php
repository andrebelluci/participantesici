<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($pessoa)) {
    die("Participante não encontrado.");
}
?>

<div class="max-w-screen-lg mx-auto px-4 py-8">
    <!-- Ações -->
    <div class="flex items-center justify-between mb-6">
        <a href="<?= isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : '/participantesici/public_html/participantes' ?>"
            class="flex items-center text-gray-600 hover:text-[#00bfff] transition text-sm">
            <i class="fa-solid fa-arrow-left mr-2"></i> Voltar
        </a>

        <button type="submit" form="formulario-participante"
            class="bg-[#00bfff] text-black px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
            <i class="fa-solid fa-save mr-1"></i>
            Salvar alterações
        </button>
    </div>

    <!-- Título -->
    <h1 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
        <i class="fa-solid fa-user-pen text-blue-500"></i>Editar Participante

        </h1>

    <div class="form-container mobile-compact">
        <form method="POST" enctype="multipart/form-data" id="formulario-participante"
            class="bg-white p-6 rounded-lg shadow space-y-6 border border-gray-200" novalidate>

            <?php if (isset($_GET['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect']) ?>">
            <?php endif; ?>

            <!-- Dados Pessoais -->
            <div>
                <h2 class="text-lg font-semibold text-gray-700 mb-4"><i class="fa-solid fa-id-card text-purple-500"></i>  Dados Pessoais</h2>

                <div class="mb-6 w-full md:w-1/6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto do Participante:</label>

                    <input type="file" name="foto" id="foto-input" accept="image/*" capture="environment"
                        class="hidden">

                    <input type="hidden" name="foto_cropada" id="foto-cropada">
                    <!-- ✅ ADICIONAR ESTA LINHA -->
                    <div data-foto-path="<?= htmlspecialchars($pessoa['foto']) ?>" style="display: none;"></div>


                    <!-- Área de Upload -->
                    <div id="upload-area"
                        class="border-2 border-dashed border-gray-300 rounded-lg p-4 md:p-6 text-center bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer <?= $pessoa['foto'] ? 'hidden' : '' ?>">
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
                    <div id="preview-container" class="mt-4 <?= $pessoa['foto'] ? '' : 'hidden' ?>">
                        <!-- Container quadrado fixo -->
                        <div class="relative bg-white rounded-lg border border-gray-200 overflow-hidden">
                            <div class="aspect-square w-full"> <!-- Força aspecto 1:1 -->
                                <img id="preview-image" src="#" alt="Preview"
                                    class="w-full h-full object-cover cursor-pointer"
                                    onclick="openImageModal(this.src)">
                            </div>
                        </div>

                        <div class="flex flex-row md:flex-row gap-2 mt-3">
                            <button type="button" id="crop-image-btn"
                                class="bg-green-600 text-white py-2 px-2 md:px-4 rounded-lg hover:bg-green-700 transition-colors font-medium text-xs md:text-sm">
                                <div class="flex items-center gap-1">
                                    <i class="fa-solid fa-scissors"></i>
                                    Ajustar
                                </div>
                            </button>
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

                <!-- Nome, nascimento, sexo -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="col-span-2">
                        <label for="nome_completo" class="block text-sm font-medium text-gray-700 mb-1">Nome
                            Completo:</label>
                        <input type="text" name="nome_completo" id="nome_completo" required
                            value="<?= htmlspecialchars($pessoa['nome_completo']) ?>"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                        <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                    </div>
                    <div class="col-span-2 md:col-span-1">
                        <label for="nascimento" class="block text-sm font-medium text-gray-700 mb-1">Data de
                            Nascimento:</label>
                        <input type="date" name="nascimento" id="nascimento" required
                            value="<?= htmlspecialchars($pessoa['nascimento']) ?>"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                        <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                    </div>
                    <div class="col-span-2 md:col-span-1">
                        <label for="sexo" class="block text-sm font-medium text-gray-700 mb-1">Sexo:</label>
                        <select name="sexo" id="sexo" required
                            class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
                            <option value="M" <?= $pessoa['sexo'] === 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= $pessoa['sexo'] === 'F' ? 'selected' : '' ?>>Feminino</option>
                        </select>
                    </div>
                    <div class="col-span-2 md:col-span-1">
                        <label for="cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF:</label>
                        <input type="text" inputmode="numeric" pattern="[0-9]\s\-]*" name="cpf" id="cpf"
                            placeholder="___.___.___-__" required value="<?= htmlspecialchars($pessoa['cpf']) ?>"
                            oninput="mascaraCPF(this)" class="w-full border border-gray-300 rounded px-3 py-2">
                        <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                    </div>
                </div>

                <!-- RG, Passaporte, Celular, Email -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mt-4">
                    <div class="col-span-1 md:col-span-1">
                        <label for="rg" class="block text-sm font-medium text-gray-700 mb-1">RG:</label>
                        <input type="text" name="rg" id="rg" value="<?= htmlspecialchars($pessoa['rg']) ?>"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                    <div class="col-span-1 md:col-span-1">
                        <label for="passaporte" class="block text-sm font-medium text-gray-700 mb-1">Passaporte:</label>
                        <input type="text" name="passaporte" id="passaporte"
                            value="<?= htmlspecialchars($pessoa['passaporte']) ?>"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                    <div class="col-span-1 md:col-span-1">
                        <label for="celular" class="block text-sm font-medium text-gray-700 mb-1">Celular:</label>
                        <input type="tel" inputmode="tel" pattern="[0-9\s\(\)\-]*" oninput="mascaraCelular(this)"
                            name="celular" id="celular" placeholder="(__) _____-____" required
                            value="<?= htmlspecialchars($pessoa['celular']) ?>" oninput="mascaraCelular(this)"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                        <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                    </div>
                    <div class="col-span-1 md:col-span-2">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail:</label>
                        <input type="email" inputmode="email" autocomplete="email" name="email" id="email" required
                            value="<?= htmlspecialchars($pessoa['email']) ?>"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                        <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                    </div>
                </div>
            </div>

            <!-- Endereço -->
            <div>
                <h2 class="text-lg font-semibold text-gray-700 mb-4"><i class="fa-solid fa-location-dot text-red-500"></i> Endereço</h2>

                <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                    <div class="lg:col-span-1">
                        <label for="cep" class="block text-sm font-medium text-gray-700 mb-1">CEP:</label>
                        <input type="text" inputmode="numeric" pattern="[0-9]\s\-]*" name="cep" id="cep"
                            placeholder="_____-___" required value="<?= htmlspecialchars($pessoa['cep']) ?>"
                            oninput="mascaraCEP(this)" class="w-full border border-gray-300 rounded px-3 py-2">
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
                            value="<?= htmlspecialchars($pessoa['endereco_rua']) ?>"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                        <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                    </div>
                    <div class="col-span-2 md:col-span-1">
                        <label for="endereco_numero"
                            class="block text-sm font-medium text-gray-700 mb-1">Número:</label>
                        <input type="text" inputmode="text" name="endereco_numero" id="endereco_numero" required
                            value="<?= htmlspecialchars($pessoa['endereco_numero']) ?>"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                        <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                    </div>
                    <div class="col-span-2 md:col-span-2">
                        <label for="endereco_complemento"
                            class="block text-sm font-medium text-gray-700 mb-1">Complemento:</label>
                        <input type="text" name="endereco_complemento" id="endereco_complemento"
                            value="<?= htmlspecialchars($pessoa['endereco_complemento']) ?>"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label for="bairro" class="block text-sm font-medium text-gray-700 mb-1">Bairro:</label>
                        <input type="text" name="bairro" id="bairro" required
                            value="<?= htmlspecialchars($pessoa['bairro']) ?>"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                        <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                    </div>
                    <div>
                        <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">Cidade:</label>
                        <input type="text" name="cidade" id="cidade" required
                            value="<?= htmlspecialchars($pessoa['cidade']) ?>"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                        <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                    </div>
                    <div>
                        <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado:</label>
                        <select name="estado" id="estado" required
                            class="w-full border border-gray-300 rounded px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
                            <option value="">Selecione o estado...</option>
                            <option value="AC" <?= $pessoa['estado'] === 'AC' ? 'selected' : '' ?>>Acre</option>
                            <option value="AL" <?= $pessoa['estado'] === 'AL' ? 'selected' : '' ?>>Alagoas</option>
                            <option value="AP" <?= $pessoa['estado'] === 'AP' ? 'selected' : '' ?>>Amapá</option>
                            <option value="AM" <?= $pessoa['estado'] === 'AM' ? 'selected' : '' ?>>Amazonas</option>
                            <option value="BA" <?= $pessoa['estado'] === 'BA' ? 'selected' : '' ?>>Bahia</option>
                            <option value="CE" <?= $pessoa['estado'] === 'CE' ? 'selected' : '' ?>>Ceará</option>
                            <option value="DF" <?= $pessoa['estado'] === 'DF' ? 'selected' : '' ?>>Distrito Federal
                            </option>
                            <option value="ES" <?= $pessoa['estado'] === 'ES' ? 'selected' : '' ?>>Espírito Santo</option>
                            <option value="GO" <?= $pessoa['estado'] === 'GO' ? 'selected' : '' ?>>Goiás</option>
                            <option value="MA" <?= $pessoa['estado'] === 'MA' ? 'selected' : '' ?>>Maranhão</option>
                            <option value="MT" <?= $pessoa['estado'] === 'MT' ? 'selected' : '' ?>>Mato Grosso</option>
                            <option value="MS" <?= $pessoa['estado'] === 'MS' ? 'selected' : '' ?>>Mato Grosso do Sul
                            </option>
                            <option value="MG" <?= $pessoa['estado'] === 'MG' ? 'selected' : '' ?>>Minas Gerais</option>
                            <option value="PA" <?= $pessoa['estado'] === 'PA' ? 'selected' : '' ?>>Pará</option>
                            <option value="PB" <?= $pessoa['estado'] === 'PB' ? 'selected' : '' ?>>Paraíba</option>
                            <option value="PR" <?= $pessoa['estado'] === 'PR' ? 'selected' : '' ?>>Paraná</option>
                            <option value="PE" <?= $pessoa['estado'] === 'PE' ? 'selected' : '' ?>>Pernambuco</option>
                            <option value="PI" <?= $pessoa['estado'] === 'PI' ? 'selected' : '' ?>>Piauí</option>
                            <option value="RJ" <?= $pessoa['estado'] === 'RJ' ? 'selected' : '' ?>>Rio de Janeiro</option>
                            <option value="RN" <?= $pessoa['estado'] === 'RN' ? 'selected' : '' ?>>Rio Grande do Norte
                            </option>
                            <option value="RS" <?= $pessoa['estado'] === 'RS' ? 'selected' : '' ?>>Rio Grande do Sul
                            </option>
                            <option value="RO" <?= $pessoa['estado'] === 'RO' ? 'selected' : '' ?>>Rondônia</option>
                            <option value="RR" <?= $pessoa['estado'] === 'RR' ? 'selected' : '' ?>>Roraima</option>
                            <option value="SC" <?= $pessoa['estado'] === 'SC' ? 'selected' : '' ?>>Santa Catarina</option>
                            <option value="SP" <?= $pessoa['estado'] === 'SP' ? 'selected' : '' ?>>São Paulo</option>
                            <option value="SE" <?= $pessoa['estado'] === 'SE' ? 'selected' : '' ?>>Sergipe</option>
                            <option value="TO" <?= $pessoa['estado'] === 'TO' ? 'selected' : '' ?>>Tocantins</option>
                        </select>
                        <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                    </div>
                </div>
            </div>

            <!-- Informações Adicionais -->
            <div>
                <h2 class="text-lg font-semibold text-gray-700 mb-4"><i class="fa-solid fa-info-circle text-yellow-700"></i> Informações Adicionais</h2>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label for="como_soube" class="block text-sm font-medium text-gray-700 mb-1">Como soube do
                            Instituto
                            Céu Interior:</label>
                        <input type="text" name="como_soube" id="como_soube" required
                            value="<?= htmlspecialchars($pessoa['como_soube']) ?>"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                        <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                    </div>
                    <div>
                        <label for="sobre_participante" class="block text-sm font-medium text-gray-700 mb-1">Sobre o
                            Participante:</label>
                        <textarea name="sobre_participante" id="sobre_participante"
                            class="w-full border border-gray-300 rounded px-3 py-2"
                            rows="4"><?= htmlspecialchars($pessoa['sobre_participante']) ?></textarea>
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

<!-- Modal de Crop -->
<div id="crop-modal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-lg w-full p-6 max-h-[90vh]">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Ajustar Foto</h3>
        </div>

        <div class="flex-1 overflow-hidden mb-4 max-h-[70vh]">
            <img id="crop-image" src="#" alt="Imagem para crop" class="max-w-full max-h-full object-contain">
        </div>

        <div class="flex gap-2 justify-end">
            <button id="cancel-crop"
                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-900 transition font-semibold">
                Tirar outra
            </button>
            <button id="apply-crop"
                class="bg-[#00bfff] text-black px-4 py-2 rounded hover:bg-yellow-400 transition font-semibold">
                Cortar e salvar
            </button>
        </div>
    </div>
</div>

<script src="/participantesici/public_html/assets/js/participante.js"></script>
<script src="/participantesici/public_html/assets/js/participante-editar.js"></script>
<script src="/participantesici/public_html/assets/js/busca-cep.js"></script>
<script src="/participantesici/public_html/assets/js/modal.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>