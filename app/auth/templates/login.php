<?php
session_start();
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']); // Limpa o erro após exibir
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participantes - ICI</title>
    <link rel="icon" type="image/x-icon" href="/participantesici/public_html/assets/images/favicon.ico">
    <link rel="stylesheet" href="/participantesici/public_html/assets/css/styles.css">
</head>

<body>
    <!-- Vídeo de Fundo -->
    <div class="video-background">
        <video autoplay muted loop id="bg-video">
            <source src="/participantesici/public_html/assets/videos/fogueira.mp4" type="video/mp4">
            Seu navegador não suporta vídeos.
        </video>
    </div>

    <!-- Conteúdo da Página -->
    <div class="login-container">
        <div class="logo-container">
            <img src="/participantesici/public_html/assets/images/logo.png" alt="Logo" class="logo">
        </div>
        <h2>Gestão de participantes</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="/participantesici/public_html/entrar">
            <input type="text" name="usuario" placeholder="Usuário" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <button class="login-button" type="submit">Entrar</button>
        </form>
    </div>
</body>

</html>