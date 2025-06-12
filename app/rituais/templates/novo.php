<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-title">
    <h1>🪵 Novo Ritual</h1>
    <br>
    <div class="actions">
        <div class="left-actions">
            <a href="/participantesici/public_html/rituais" class="btn voltar">Voltar</a>
        </div>
        <div class="right-actions">
            <button type="submit" form="formulario-ritual" class="btn salvar">Criar ritual</button>
        </div>
    </div>
</div>
<div class="container">
    <form method="POST" action="/participantesici/public_html/rituais/novo" enctype="multipart/form-data" class="styled-form" id="formulario-ritual">
        <div class="form-columns">
            <!-- Coluna 1: Dados do Ritual -->
            <div class="form-column">
                <h3>🍃Dados do Ritual</h3>
                <label for="foto">Foto do Ritual:</label>
                <div class="foto-preview-container">
                    <input type="file" name="foto" id="foto-input" accept="image/*" style="display: none;">
                    <button type="button" id="adicionar-imagem-btn" class="btn adicionar-imagem">Adicionar
                        Imagem</button>
                    <div id="preview-container" style="display: none;">
                        <div class="image-and-button">
                            <img id="preview-image" src="#" alt="Preview" class="small-preview">
                            <button type="button" id="excluir-imagem-btn" class="btn excluir-imagem">Excluir
                                Imagem</button>
                        </div>
                    </div>
                </div>
                <br>
                <div class="horizontal-fields">
                    <div class="field-group">
                        <label for="nome">Nome do Ritual:</label>
                        <input type="text" name="nome" id="nome" required>
                    </div>

                    <div class="field-group">
                        <label for="data_ritual">Data do Ritual:</label>
                        <input type="date" name="data_ritual" id="data_ritual" required>
                    </div>

                    <div class="field-group">
                        <label for="padrinho_madrinha">Padrinho ou Madrinha:</label>
                        <select name="padrinho_madrinha" id="padrinho_madrinha" required>
                            <option value="Dirceu">Dirceu</option>
                            <option value="Gabriela">Gabriela</option>
                            <option value="Dirceu e Gabriela">Dirceu e Gabriela</option>
                        </select>
                    </div>
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

<script src="/participantesici/public_html/assets/js/ritual-novo.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>