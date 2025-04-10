<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/header.php';
?>

<div class="container">
    <h1>Bem-vindo, <?php echo $_SESSION['nome']; ?>!</h1>
    <div class="cards">
        <a href="rituais.php" class="card">Rituais</a>
        <a href="pessoas.php" class="card">Pessoas</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>