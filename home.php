<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/header.php';
?>
<div class="page-title">
    <h1>Bem-vindo, <?php echo $_SESSION['nome']; ?>!</h1>
</div>
<div class="container">

    <div class="cards-home">
        <a href="participantes" class="card-home">Participantes</a>
        <a href="rituais" class="card-home">Rituais</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>