<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'];
    $senha = hash('sha256', $_POST['senha']);

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND senha = ?");
    $stmt->execute([$usuario, $senha]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nome'] = $user['nome'];
        header("Location: home");
        exit;
    } else {
        echo "<script>alert('Usuário ou senha inválidos!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participantes - ICI</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>
    <!-- Vídeo de Fundo -->
    <div class="video-background">
        <video autoplay muted loop id="bg-video">
            <source src="assets/videos/fogueira.mp4" type="video/mp4">
            Seu navegador não suporta vídeos.
        </video>
    </div>

    <!-- Conteúdo da Página -->
    <div class="login-container">
        <div class="logo-container">
            <img src="assets/images/logo.png" alt="Logo" class="logo">
        </div>
        <h2>Gestão de participantes</h2>
        <form class="login-form" method="POST">
            <input type="text" name="usuario" placeholder="Usuário" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <button class="login-button" type="submit">Entrar</button>
        </form>
    </div>
</body>

</html>