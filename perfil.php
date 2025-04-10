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

<div class="container">
    <h1>Alterar Senha</h1>
    <div class="actions">
        <a href="home.php" class="btn">Voltar</a>
    </div>

    <?= $mensagem ?>

    <form method="POST">
        <label for="senha_atual">Senha Atual:</label>
        <input type="password" name="senha_atual" required>

        <label for="nova_senha">Nova Senha:</label>
        <input type="password" name="nova_senha" required>

        <label for="confirmar_senha">Confirmar Nova Senha:</label>
        <input type="password" name="confirmar_senha" required>

        <button type="submit">Alterar Senha</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>