<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Processamento do formulário (mantido igual)
    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        $foto_nome = uniqid() . '_' . basename($_FILES['foto']['name']);
        $foto_destino = 'uploads/' . $foto_nome;
        move_uploaded_file($_FILES['foto']['tmp_name'], $foto_destino);
        $foto = $foto_destino;
    }

    $nome_completo = $_POST['nome_completo'];
    $nascimento = $_POST['nascimento'];
    $sexo = $_POST['sexo'];
    $cpf = $_POST['cpf'];
    $rg = $_POST['rg'];
    $passaporte = $_POST['passaporte'];
    $celular = $_POST['celular'];
    $email = $_POST['email'];
    $como_soube = $_POST['como_soube'];
    $cep = $_POST['cep'];
    $endereco_rua = $_POST['endereco_rua'];
    $endereco_numero = $_POST['endereco_numero'];
    $endereco_complemento = $_POST['endereco_complemento'];
    $cidade = $_POST['cidade'];
    $estado = $_POST['estado'];
    $bairro = $_POST['bairro'];

    $stmt = $pdo->prepare("
        INSERT INTO participantes (
            foto, nome_completo, nascimento, sexo, cpf, rg, passaporte, celular, email, como_soube, cep, endereco_rua, endereco_numero, endereco_complemento, cidade, estado, bairro
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$foto, $nome_completo, $nascimento, $sexo, $cpf, $rg, $passaporte, $celular, $email, $como_soube, $cep, $endereco_rua, $endereco_numero, $endereco_complemento, $cidade, $estado, $bairro]);

    echo "<script>alert('Pessoa cadastrada com sucesso!');</script>";
    echo "<script>window.location.href = 'pessoas.php';</script>";
}
?>

<div class="container">
    <h1>👥 Nova Pessoa</h1>
    <br>
    <div class="actions">
        <a href="pessoas.php" class="btn voltar">Voltar</a>
    </div>

    <form method="POST" enctype="multipart/form-data" class="styled-form">
        <div class="form-columns">
            <!-- Coluna 1: Dados Pessoais -->
            <div class="form-column">
                <h3>Dados Pessoais</h3>
                <label for="foto">Foto:</label>
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

                <label for="nome_completo">Nome Completo:</label>
                <input type="text" name="nome_completo" id="nome_completo" required>

                <label for="nascimento">Data de Nascimento:</label>
                <input type="date" name="nascimento" id="nascimento" required>

                <label for="sexo">Sexo:</label>
                <select name="sexo" id="sexo" required>
                    <option value="M">Masculino</option>
                    <option value="F">Feminino</option>
                </select>

                <label for="cpf">CPF:</label>
                <input type="text" name="cpf" id="cpf" required>

                <label for="rg">RG:</label>
                <input type="text" name="rg" id="rg">

                <label for="passaporte">Passaporte:</label>
                <input type="text" name="passaporte" id="passaporte">

                <label for="celular">Celular:</label>
                <input type="text" name="celular" id="celular" required>

                <label for="email">E-mail:</label>
                <input type="email" name="email" id="email">
            </div>

            <!-- Coluna 2: Endereço -->
            <div class="form-column">
                <h3>Endereço</h3>
                <label for="cep">CEP:</label>
                <div class="cep-group">
                    <input type="text" name="cep" id="cep" required>
                    <button type="button" id="buscar-cep-btn" class="btn buscar-cep">Buscar CEP</button>
                </div>

                <label for="endereco_rua">Rua:</label>
                <input type="text" name="endereco_rua" id="endereco_rua" required>

                <label for="endereco_numero">Número:</label>
                <input type="text" name="endereco_numero" id="endereco_numero" required>

                <label for="endereco_complemento">Complemento:</label>
                <input type="text" name="endereco_complemento" id="endereco_complemento">

                <label for="cidade">Cidade:</label>
                <input type="text" name="cidade" id="cidade" required>

                <label for="estado">Estado:</label>
                <input type="text" name="estado" id="estado" required>

                <label for="bairro">Bairro:</label>
                <input type="text" name="bairro" id="bairro" required>

                <br>
                <h3>Informações adicionais</h3>
                <label for="como_soube">Como soube do Instituto Céu Interior?</label>
                <textarea name="como_soube" id="como_soube"></textarea>
            </div>
        </div>

        <button type="submit" class="btn salvar">Cadastrar</button>
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