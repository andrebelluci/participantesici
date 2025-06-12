<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($pessoa)) {
  die("Participante não encontrado.");
}
?>

<div class="page-title">
    <h1>👤 Editar Participante</h1>
    <br>
    <div class="actions">
        <div class="left-actions">
            <a href="/participantesici/public_html/participantes" class="btn voltar">Voltar</a>
        </div>
        <div class="right-actions">
            <button type="submit" form="formulario-participante" class="btn salvar">Salvar alterações</button>
        </div>
    </div>
</div>
<div class="container">
    <form method="POST" enctype="multipart/form-data" class="styled-form" id="formulario-participante">

        <!-- Dados Pessoais -->
        <div class="form-section">
            <h3>ℹ️Dados Pessoais</h3>
            <label for="foto">Foto do participante:</label>
            <div class="form-line">
                <div class="foto-preview-container">
                    <input type="file" name="foto" id="foto-input" accept="image/*" style="display: none;">
                    <button type="button" id="adicionar-imagem-btn" class="btn adicionar-imagem">Adicionar
                        Imagem</button>
                    <div id="preview-container" style="<?= $pessoa['foto'] ? 'display: block;' : 'display: none;' ?>">
                        <div class="image-and-button">
                            <img id="preview-image" src="<?= htmlspecialchars($pessoa['foto']) ?>" alt="Preview"
                                class="small-preview" onerror="this.src='/participantesici/public_html/assets/images/no-image.png';">
                            <button type="button" id="excluir-imagem-btn" class="btn excluir-imagem">Excluir
                                Imagem</button>
                        </div>
                    </div>
                </div>
            </div>
            <br>

            <div class="form-line">
                <div class="field-group" style="flex: 50%;">
                    <label for="nome_completo">Nome Completo:</label>
                    <input type="text" name="nome_completo" id="nome_completo"
                        value="<?= htmlspecialchars($pessoa['nome_completo']) ?>" required>
                </div>
                <div class="field-group" style="flex: 25%;">
                    <label for="nascimento">Data de Nascimento:</label>
                    <input type="date" name="nascimento" id="nascimento"
                        value="<?= htmlspecialchars($pessoa['nascimento']) ?>" required>
                </div>
                <div class="field-group" style="flex: 25%;">
                    <label for="sexo">Sexo:</label>
                    <select name="sexo" id="sexo" required>
                        <option value="M" <?= $pessoa['sexo'] === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= $pessoa['sexo'] === 'F' ? 'selected' : '' ?>>Feminino</option>
                    </select>
                </div>
            </div>

            <div class="form-line">
                <div class="field-group" style="flex: 10%;">
                    <label for="cpf">CPF:</label>
                    <input type="text" name="cpf" id="cpf" placeholder="___.___.___-__"
                        value="<?= htmlspecialchars($pessoa['cpf']) ?>" required oninput="mascaraCPF(this)">
                </div>
                <div class="field-group" style="flex: 10%;">
                    <label for="rg">RG:</label>
                    <input type="text" name="rg" id="rg" value="<?= htmlspecialchars($pessoa['rg']) ?>">
                </div>
                <div class="field-group" style="flex: 10%;">
                    <label for="passaporte">Passaporte:</label>
                    <input type="text" name="passaporte" id="passaporte"
                        value="<?= htmlspecialchars($pessoa['passaporte']) ?>">
                </div>
                <div class="field-group" style="flex: 10%;">
                    <label for="celular">Celular:</label>
                    <input type="text" name="celular" id="celular" placeholder="(__) _____-____"
                        value="<?= htmlspecialchars($pessoa['celular']) ?>" required oninput="mascaraCelular(this)">
                </div>
                <div class="field-group" style="flex: 60%;">
                    <label for="email">E-mail:</label>
                    <input type="email" name="email" id="email" value="<?= htmlspecialchars($pessoa['email']) ?>"
                        required>
                </div>
            </div>
        </div>

        <!-- Endereço -->
        <div class="form-section">
            <h3>📍Endereço</h3>
            <div class="form-line">
                <!-- Grupo do CEP e Botão Buscar CEP -->
                <div class="field-group" style="flex: 10%;">
                    <label for="cep">CEP:</label>
                    <input type="text" name="cep" id="cep" placeholder="_____ - ___"
                        value="<?= htmlspecialchars($pessoa['cep']) ?>" required oninput="mascaraCEP(this)">
                </div>
                <button type="button" id="buscar-cep-btn" class="btn buscar-cep" style="flex: 7%;">Buscar CEP</button>

                <!-- Grupo da Rua, Número e Complemento -->
                <div class="field-group" style="flex: 1%;"></div>
                <div class="field-group" style="flex: 40%;">
                    <label for="endereco_rua">Rua:</label>
                    <input type="text" name="endereco_rua" id="endereco_rua"
                        value="<?= htmlspecialchars($pessoa['endereco_rua']) ?>" required>
                </div>
                <div class="field-group" style="flex: 10%;">
                    <label for="endereco_numero">Número:</label>
                    <input type="text" name="endereco_numero" id="endereco_numero"
                        value="<?= htmlspecialchars($pessoa['endereco_numero']) ?>" required>
                </div>
                <div class="field-group" style="flex: 40%;">
                    <label for="endereco_complemento">Complemento:</label>
                    <input type="text" name="endereco_complemento" id="endereco_complemento"
                        value="<?= htmlspecialchars($pessoa['endereco_complemento']) ?>">
                </div>
            </div>

            <!-- Bairro, Cidade e Estado -->
            <div class="form-line">
                <div class="field-group" style="flex: 30%;">
                    <label for="bairro">Bairro:</label>
                    <input type="text" name="bairro" id="bairro" value="<?= htmlspecialchars($pessoa['bairro']) ?>"
                        required>
                </div>
                <div class="field-group" style="flex: 40%;">
                    <label for="cidade">Cidade:</label>
                    <input type="text" name="cidade" id="cidade" value="<?= htmlspecialchars($pessoa['cidade']) ?>"
                        required>
                </div>
                <div class="field-group" style="flex: 30%;">
                    <label for="estado">Estado:</label>
                    <input type="text" name="estado" id="estado" value="<?= htmlspecialchars($pessoa['estado']) ?>"
                        required>
                </div>
            </div>
        </div>

        <!-- Informações Adicionais -->
        <div class="form-section">
            <h3>➕Informações Adicionais</h3>
            <div class="form-line">
                <div class="field-group" style="flex: 40%;">
                    <label for="como_soube">Como soube do Instituto Céu Interior:</label>
                    <input type="text" name="como_soube" id="como_soube"
                        value="<?= htmlspecialchars($pessoa['como_soube']) ?>" required>
                </div>
                <div class="field-group" style="flex: 60%;">
                    <label for="sobre_participante">Sobre o Participante:</label>
                    <textarea name="sobre_participante"
                        id="sobre_participante"><?= htmlspecialchars($pessoa['sobre_participante']) ?></textarea>
                </div>
            </div>
        </div>
    </form>

    <!-- Modal de Ampliação de Imagem -->
    <div id="modal-image" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <span class="close" onclick="closeImageModal()">&times;</span>
                <img id="modal-image-content" class="modal-image" alt="Imagem Ampliada">
            </div>
        </div>
    </div>
</div>

<script src="/participantesici/public_html/assets/js/participante-editar.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>