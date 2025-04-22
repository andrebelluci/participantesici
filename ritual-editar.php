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

<div class="page-title">
    <h1>🪵 Editar Ritual</h1>
    <br>
    <div class="actions">
        <div class="left-actions">
            <a href="rituais.php" class="btn voltar">Voltar</a>
        </div>
        <div class="right-actions">
            <button type="submit" form="formulario-ritual" class="btn salvar">Salvar alterações</button>
        </div>
    </div>
</div>
<div class="container">
    <form method="POST" enctype="multipart/form-data" class="styled-form" id="formulario-ritual">
        <div class="form-columns">
            <!-- Coluna 1: Dados do Ritual -->
            <div class="form-column">
                <h3>🍃Dados do Ritual</h3>
                <label for="foto">Foto do Ritual:</label>
                <div class="foto-preview-container">
                    <input type="file" name="foto" id="foto-input" accept="image/*" style="display: none;">
                    <button type="button" id="adicionar-imagem-btn" class="btn adicionar-imagem">Adicionar Imagem</button>
                    <div id="preview-container" style="<?= $ritual['foto'] ? 'display: block;' : 'display: none;' ?>">
                        <div class="image-and-button">
                            <img id="preview-image" src="<?= htmlspecialchars($ritual['foto'] ?? '') ?>" alt="Preview" class="small-preview">
                            <button type="button" id="excluir-imagem-btn" class="btn excluir-imagem">Excluir Imagem</button>
                        </div>
                    </div>
                </div>
                <br>
                <div class="horizontal-fields">
                    <div class="field-group">
                        <label for="nome">Nome do Ritual:</label>
                        <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($ritual['nome']) ?>" required>
                    </div>

                    <div class="field-group">
                        <label for="data_ritual">Data do Ritual:</label>
                        <input type="date" name="data_ritual" id="data_ritual" value="<?= htmlspecialchars($ritual['data_ritual']) ?>" required>
                    </div>
                    <div class="field-group">
                        <label for="padrinho_madrinha">Padrinho ou Madrinha:</label>
                        <select name="padrinho_madrinha" id="padrinho_madrinha" required>
                            <option value="Dirceu" <?= $ritual['padrinho_madrinha'] == 'Dirceu' ? 'selected' : '' ?>>Dirceu</option>
                            <option value="Gabriela" <?= $ritual['padrinho_madrinha'] == 'Gabriela' ? 'selected' : '' ?>>Gabriela</option>
                            <option value="Dirceu e Gabriela" <?= $ritual['padrinho_madrinha'] == 'Dirceu e Gabriela' ? 'selected' : '' ?>>Dirceu e Gabriela</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Modal de Ampliação de Imagem -->
    <div id="modal-image" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <span class="close" onclick="closeImageModal()">&times;</span>
                <img id="modal-image-content" class="modal-image" alt="Imagem Ampliada">
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
    // Função para abrir a modal de imagem
    function openImageModal(imageSrc) {
        const modal = document.getElementById('modal-image');
        const modalImage = document.getElementById('modal-image-content');
        modalImage.src = imageSrc; // Define a imagem ampliada
        modal.style.display = 'flex'; // Exibe a modal
    }

    // Função para fechar a modal de imagem
    function closeImageModal() {
        const modal = document.getElementById('modal-image');
        modal.style.display = 'none'; // Oculta a modal
    }

    // Preview da imagem
    const fileInput = document.getElementById('foto-input');
    const adicionarImagemBtn = document.getElementById('adicionar-imagem-btn');
    const previewContainer = document.getElementById('preview-container');
    const previewImage = document.getElementById('preview-image');
    const excluirImagemBtn = document.getElementById('excluir-imagem-btn');

    // Verifica se já há uma imagem carregada
    if (previewImage.src && previewImage.src !== '#') {
        previewContainer.style.display = 'block';
        adicionarImagemBtn.style.display = 'none';
    }

    adicionarImagemBtn.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImage.src = e.target.result;
                previewContainer.style.display = 'block';
                adicionarImagemBtn.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    excluirImagemBtn.addEventListener('click', () => {
        previewImage.src = '#';
        previewContainer.style.display = 'none';
        adicionarImagemBtn.style.display = 'inline-block';
        fileInput.value = '';
    });

    // Abrir modal ao clicar na imagem de preview
    previewImage.addEventListener('click', () => {
        openImageModal(previewImage.src);
    });

    document.addEventListener("DOMContentLoaded", function() {
        const modals = document.querySelectorAll(".modal");

        modals.forEach(modal => {
            modal.addEventListener("click", function(event) {
                // Verifica se o clique foi fora do .modal-content
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            });
        });
    });
</script>