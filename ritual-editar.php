<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';
require_once 'includes/header.php';

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM rituais WHERE id = ?");
$stmt->execute([$id]);
$ritual = $stmt->fetch();

if (!$ritual) {
    die("Ritual não encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $data_ritual = $_POST['data_ritual'];
    $padrinho_madrinha = $_POST['padrinho_madrinha'];

    // Upload da foto (opcional)
    $foto = $ritual['foto']; // Mantém a foto atual se nenhuma nova for enviada
    if (!empty($_FILES['foto']['name'])) {
        $foto_nome = uniqid() . '_' . basename($_FILES['foto']['name']);
        $foto_destino = 'uploads/' . $foto_nome;
        move_uploaded_file($_FILES['foto']['tmp_name'], $foto_destino);
        $foto = $foto_destino;
    }

    // Atualizar no banco de dados
    $stmt_update = $pdo->prepare("UPDATE rituais SET nome = ?, data_ritual = ?, foto = ?, padrinho_madrinha = ? WHERE id = ?");
    $stmt_update->execute([$nome, $data_ritual, $foto, $padrinho_madrinha, $id]);

    echo "<script>alert('Ritual atualizado com sucesso!');</script>";
    echo "<script>window.location.href = 'rituais.php';</script>";
}
?>

<div class="container">
    <h1>Editar Ritual</h1>
    <div class="actions">
        <a href="rituais.php" class="btn">Voltar</a>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <label for="nome">Nome do Ritual:</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($ritual['nome']) ?>" required>

        <label for="data_ritual">Data do Ritual:</label>
        <input type="date" name="data_ritual" value="<?= htmlspecialchars($ritual['data_ritual']) ?>" required>

        <label for="foto">Foto do Ritual:</label>
        <?php if ($ritual['foto']): ?>
            <img src="<?= htmlspecialchars($ritual['foto']) ?>" alt="Foto Atual" class="small-image">
        <?php endif; ?>
        <input type="file" name="foto" accept="image/*">

        <label for="padrinho_madrinha">Padrinho ou Madrinha:</label>
        <select name="padrinho_madrinha" required>
            <option value="Dirceu" <?= $ritual['padrinho_madrinha'] == 'Dirceu' ? 'selected' : '' ?>>Dirceu</option>
            <option value="Gabriela" <?= $ritual['padrinho_madrinha'] == 'Gabriela' ? 'selected' : '' ?>>Gabriela</option>
        </select>

        <button type="submit">Salvar Alterações</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>