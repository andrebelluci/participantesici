<?php
session_start();
require_once __DIR__ . '/../../functions/check_auth.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: /participantesici/public_html/login");
    exit;
}require_once __DIR__ . '/../../includes/header.php';

$mensagem = '';
if (isset($_SESSION['mensagem'])) {
  $mensagem = '<div class="' . $_SESSION['mensagem']['tipo'] . '">' . htmlspecialchars($_SESSION['mensagem']['texto']) . '</div>';
  unset($_SESSION['mensagem']);
}
?>

<div class="page-title">
    <h1>🔐 Alterar Senha</h1>
    <br>
    <div class="actions">
        <div class="left-actions">
            <a href="/participantesici/public_html/home" class="btn voltar">Voltar</a>
        </div>
        <div class="right-actions">
            <button type="submit" form="formulario-senha" class="btn salvar">Salvar alterações</button>
        </div>
    </div>
</div>

<div class="container">
    <?= $mensagem ?>

    <form method="POST" action="/participantesici/app/perfil/actions/atualizar_senha.php" class="styled-form" id="formulario-senha">
        <div class="form-section">
            <h3>🔒 Alteração de Senha</h3>
            <div class="form-line">
                <div class="field-group" style="flex: 33.33%;">
                    <label for="senha_atual">Senha Atual:</label>
                    <input type="password" name="senha_atual" id="senha_atual" required>
                </div>
                <div class="field-group" style="flex: 33.33%;">
                    <label for="nova_senha">Nova Senha:</label>
                    <input type="password" name="nova_senha" id="nova_senha" required>
                </div>
                <div class="field-group" style="flex: 33.33%;">
                    <label for="confirmar_senha">Confirmar Nova Senha:</label>
                    <input type="password" name="confirmar_senha" id="confirmar_senha" required>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>