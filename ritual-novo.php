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
    <h1>🪵 Novo Ritual</h1>
    <br>
    <div class="actions">
        <a href="rituais.php" class="btn voltar">Voltar</a>
    </div>

    <form method="POST" enctype="multipart/form-data" class="styled-form">
        <div class="form-columns">
            <!-- Coluna 1: Dados do Ritual -->
            <div class="form-column">
                <h3>Dados do Ritual</h3>
                <label for="nome">Nome do Ritual:</label>
                <input type="text" name="nome" id="nome" required>

                <label for="data_ritual">Data do Ritual:</label>
                <input type="date" name="data_ritual" id="data_ritual" required>

                <label for="foto">Foto do Ritual:</label>
                <div class="foto-preview-container">
                    <input type="file" name="foto" id="foto-input" accept="image/*" style="display: none;">
                    <button type="button" id="adicionar-imagem-btn" class="btn adicionar-imagem">Adicionar Imagem</button>
                    <div id="preview-container" style="display: none;">
                        <div class="image-and-button">
                            <img id="preview-image" src="#" alt="Preview" class="small-preview">
                            <button type="button" id="excluir-imagem-btn" class="btn excluir-imagem">Excluir Imagem</button>
                        </div>
                    </div>
                </div>
                <br>

                <label for="padrinho_madrinha">Padrinho ou Madrinha:</label>
                <select name="padrinho_madrinha" id="padrinho_madrinha" required>
                    <option value="Dirceu">Dirceu</option>
                    <option value="Gabriela">Gabriela</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn salvar">Criar Ritual</button>
    </form>

    <!-- Modal de Ampliação de Imagem -->
    <div id="image-modal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <span class="close" onclick="closeImageModal()">&times;</span>
                <img id="expanded-image" class="modal-image">
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
    // Função para abrir a imagem ampliada
    function openImageModal(imageSrc) {
        const modal = document.getElementById('image-modal');
        const modalImg = document.getElementById('expanded-image');
        modal.style.display = 'block';
        modalImg.src = imageSrc;
    }

    // Função para fechar a imagem ampliada
    function closeImageModal() {
        const modal = document.getElementById('image-modal');
        modal.style.display = 'none';
    }

    // Preview da imagem
    const fileInput = document.getElementById('foto-input');
    const adicionarImagemBtn = document.getElementById('adicionar-imagem-btn');
    const previewContainer = document.getElementById('preview-container');
    const previewImage = document.getElementById('preview-image');
    const excluirImagemBtn = document.getElementById('excluir-imagem-btn');

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
</script>