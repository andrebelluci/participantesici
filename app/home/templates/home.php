<?php
session_start();
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-title">
    <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome']); ?>!</h1>
</div>
<div class="container">
    <div class="cards-home">
        <a href="/participantesici/public_html/participantes" class="card-home">Participantes</a>
        <a href="/participantesici/public_html/rituais" class="card-home">Rituais</a>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>