<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';
require_once 'includes/header.php';

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $senha_atual = hash('sha256', $_POST['senha_atual']);
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // Verificar se a senha atual está correta
    $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch();

    if ($usuario && $usuario['senha'] === $senha_atual) {
        if ($nova_senha === $confirmar_senha) {
            // Atualizar a senha no banco de dados
            $nova_senha_hash = hash('sha256', $nova_senha);
            $stmt_update = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt_update->execute([$nova_senha_hash, $_SESSION['user_id']]);
            $mensagem = '<div class="success">Senha alterada com sucesso!</div>';
        } else {
            $mensagem = '<div class="error">As senhas não coincidem.</div>';
        }
    } else {
        $mensagem = '<div class="error">Senha atual incorreta.</div>';
    }
}
?>

<div class="page-title">
    <h1>🔐 Alterar Senha</h1>
    <br>
    <div class="actions">
        <div class="left-actions">
            <a href="home" class="btn voltar">Voltar</a>
        </div>
        <div class="right-actions">
            <button type="submit" form="formulario-senha" class="btn salvar">Salvar alterações</button>
        </div>
    </div>
</div>

<div class="container">
    <?= $mensagem ?>

    <form method="POST" class="styled-form" id="formulario-senha">
        <!-- Dados da Senha -->
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

<?php require_once 'includes/footer.php'; ?>