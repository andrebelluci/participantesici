<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $data_ritual = $_POST['data_ritual'];
    $padrinho_madrinha = $_POST['padrinho_madrinha'];

    // Upload da foto
    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        $foto_nome = uniqid() . '_' . basename($_FILES['foto']['name']);
        $foto_destino = 'uploads/' . $foto_nome;
        move_uploaded_file($_FILES['foto']['tmp_name'], $foto_destino);
        $foto = $foto_destino;
    }

    $stmt = $pdo->prepare("INSERT INTO rituais (nome, data_ritual, foto, padrinho_madrinha) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nome, $data_ritual, $foto, $padrinho_madrinha]);

    echo "<script>alert('Ritual criado com sucesso!');</script>";
    echo "<script>window.location.href = 'rituais.php';</script>";
}
?>

<div class="container">
    <h1>Novo Ritual</h1>
    <div class="actions">
        <a href="rituais.php" class="btn">Voltar</a>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <label for="nome">Nome do Ritual:</label>
        <input type="text" name="nome" required>

        <label for="data_ritual">Data do Ritual:</label>
        <input type="date" name="data_ritual" required>

        <label for="foto">Foto do Ritual:</label>
        <input type="file" name="foto" accept="image/*">

        <label for="padrinho_madrinha">Padrinho ou Madrinha:</label>
        <select name="padrinho_madrinha" required>
            <option value="Dirceu">Dirceu</option>
            <option value="Gabriela">Gabriela</option>
        </select>

        <button type="submit">Criar Ritual</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>