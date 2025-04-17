<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';
require_once 'includes/header.php';

// Obtém o ID da pessoa a ser editada
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID da pessoa não especificado.");
}

// Consulta os dados da pessoa no banco de dados
$stmt = $pdo->prepare("SELECT * FROM participantes WHERE id = ?");
$stmt->execute([$id]);
$pessoa = $stmt->fetch();

if (!$pessoa) {
    die("Pessoa não encontrada.");
}

// Processamento do formulário de edição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Processa a foto (mantém a atual se nenhuma nova for enviada)
    $foto = $pessoa['foto'];
    if (!empty($_FILES['foto']['name'])) {
        $foto_nome = uniqid() . '_' . basename($_FILES['foto']['name']);
        $foto_destino = 'uploads/' . $foto_nome;
        move_uploaded_file($_FILES['foto']['tmp_name'], $foto_destino);
        $foto = $foto_destino;
    }

    // Captura os dados do formulário
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

    // Atualiza os dados no banco de dados
    $stmt_update = $pdo->prepare("
        UPDATE participantes SET
            foto = ?, nome_completo = ?, nascimento = ?, sexo = ?, cpf = ?, rg = ?, passaporte = ?,
            celular = ?, email = ?, como_soube = ?, cep = ?, endereco_rua = ?, endereco_numero = ?,
            endereco_complemento = ?, cidade = ?, estado = ?, bairro = ?
        WHERE id = ?
    ");
    $stmt_update->execute([
        $foto,
        $nome_completo,
        $nascimento,
        $sexo,
        $cpf,
        $rg,
        $passaporte,
        $celular,
        $email,
        $como_soube,
        $cep,
        $endereco_rua,
        $endereco_numero,
        $endereco_complemento,
        $cidade,
        $estado,
        $bairro,
        $id
    ]);

    echo "<script>alert('Pessoa atualizada com sucesso!');</script>";
    echo "<script>window.location.href = 'participantes.php';</script>";
}
?>

<div class="container">
    <h1>👤 Editar Pessoa</h1>
    <br>
    <div class="actions">
        <a href="participantes.php" class="btn voltar">Voltar</a>
    </div>
    <form method="POST" enctype="multipart/form-data" class="styled-form" id="formulario-pessoa">
        <div class="form-columns">
            <!-- Coluna 1: Dados Pessoais -->
            <div class="form-column">
                <h3>Dados Pessoais</h3>
                <label for="foto">Foto:</label>
                <div class="foto-preview-container">
                    <input type="file" name="foto" id="foto-input" accept="image/*" style="display: none;">
                    <button type="button" id="adicionar-imagem-btn" class="btn adicionar-imagem">Adicionar Imagem</button>
                    <div id="preview-container" style="<?= $pessoa['foto'] ? 'display: block;' : 'display: none;' ?>">
                        <div class="image-and-button">
                            <img id="preview-image" src="<?= htmlspecialchars($pessoa['foto']) ?>" alt="Preview" class="small-preview" onerror="this.src='assets/images/no-image.png';">
                            <button type="button" id="excluir-imagem-btn" class="btn excluir-imagem">Excluir Imagem</button>
                        </div>
                    </div>
                </div>
                <br>
                <label for="nome_completo">Nome Completo:</label>
                <input type="text" name="nome_completo" id="nome_completo" value="<?= htmlspecialchars($pessoa['nome_completo']) ?>" required>
                <label for="nascimento">Data de Nascimento:</label>
                <input type="date" name="nascimento" id="nascimento" value="<?= htmlspecialchars($pessoa['nascimento']) ?>" required>
                <label for="sexo">Sexo:</label>
                <select name="sexo" id="sexo" required>
                    <option value="M" <?= $pessoa['sexo'] === 'M' ? 'selected' : '' ?>>Masculino</option>
                    <option value="F" <?= $pessoa['sexo'] === 'F' ? 'selected' : '' ?>>Feminino</option>
                </select>
                <label for="cpf">CPF:</label>
                <input type="text" name="cpf" id="cpf" placeholder="___.___.___-__" value="<?= htmlspecialchars($pessoa['cpf']) ?>" required oninput="mascaraCPF(this)">
                <label for="rg">RG:</label>
                <input type="text" name="rg" id="rg" value="<?= htmlspecialchars($pessoa['rg']) ?>">
                <label for="passaporte">Passaporte:</label>
                <input type="text" name="passaporte" id="passaporte" value="<?= htmlspecialchars($pessoa['passaporte']) ?>">
                <label for="celular">Celular:</label>
                <input type="text" name="celular" id="celular" placeholder="(__) _____-____" value="<?= htmlspecialchars($pessoa['celular']) ?>" required oninput="mascaraCelular(this)">
                <label for="email">E-mail:</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($pessoa['email']) ?>">
            </div>
            <!-- Coluna 2: Endereço -->
            <div class="form-column">
                <h3>Endereço</h3>
                <label for="cep">CEP:</label>
                <div class="cep-group">
                    <input type="text" name="cep" id="cep" placeholder="_____ - ___" value="<?= htmlspecialchars($pessoa['cep']) ?>" required oninput="mascaraCEP(this)">
                    <button type="button" id="buscar-cep-btn" class="btn buscar-cep">Buscar CEP</button>
                </div>
                <label for="endereco_rua">Rua:</label>
                <input type="text" name="endereco_rua" id="endereco_rua" value="<?= htmlspecialchars($pessoa['endereco_rua']) ?>" required>
                <label for="endereco_numero">Número:</label>
                <input type="text" name="endereco_numero" id="endereco_numero" value="<?= htmlspecialchars($pessoa['endereco_numero']) ?>" required>
                <label for="endereco_complemento">Complemento:</label>
                <input type="text" name="endereco_complemento" id="endereco_complemento" value="<?= htmlspecialchars($pessoa['endereco_complemento']) ?>">
                <label for="cidade">Cidade:</label>
                <input type="text" name="cidade" id="cidade" value="<?= htmlspecialchars($pessoa['cidade']) ?>" required>
                <label for="estado">Estado:</label>
                <input type="text" name="estado" id="estado" value="<?= htmlspecialchars($pessoa['estado']) ?>" required>
                <label for="bairro">Bairro:</label>
                <input type="text" name="bairro" id="bairro" value="<?= htmlspecialchars($pessoa['bairro']) ?>" required>
                <br>
                <h3>Informações adicionais</h3>
                <label for="como_soube">Como soube do Instituto Céu Interior?</label>
                <textarea name="como_soube" id="como_soube"><?= htmlspecialchars($pessoa['como_soube']) ?></textarea>
            </div>
        </div>
        <button type="submit" class="btn salvar">Salvar Alterações</button>
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

    // Máscara para CPF
    function mascaraCPF(input) {
        let valor = input.value.replace(/\D/g, ''); // Remove tudo que não é número
        if (valor.length > 11) valor = valor.slice(0, 11); // Limita a 11 dígitos
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2'); // Adiciona o primeiro ponto
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2'); // Adiciona o segundo ponto
        valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2'); // Adiciona o traço
        input.value = valor;
    }

    // Máscara para Celular
    function mascaraCelular(input) {
        let valor = input.value.replace(/\D/g, ''); // Remove tudo que não é número
        if (valor.length > 11) valor = valor.slice(0, 11); // Limita a 11 dígitos
        valor = valor.replace(/^(\d{2})(\d)/g, '($1) $2'); // Adiciona os parênteses
        valor = valor.replace(/(\d{5})(\d)/, '$1-$2'); // Adiciona o hífen
        input.value = valor;
    }

    // Máscara para CEP
    function mascaraCEP(input) {
        let valor = input.value.replace(/\D/g, ''); // Remove tudo que não é número
        if (valor.length > 8) valor = valor.slice(0, 8); // Limita a 8 dígitos
        valor = valor.replace(/(\d{5})(\d)/, '$1-$2'); // Adiciona o hífen
        input.value = valor;
    }

    // Função para remover máscaras antes de enviar o formulário
    document.getElementById('formulario-pessoa').addEventListener('submit', function(event) {
        // Remove máscara do CPF
        const cpfInput = document.getElementById('cpf');
        cpfInput.value = cpfInput.value.replace(/\D/g, ''); // Remove tudo que não é número

        // Remove máscara do Celular
        const celularInput = document.getElementById('celular');
        celularInput.value = celularInput.value.replace(/\D/g, ''); // Remove tudo que não é número

        // Remove máscara do CEP
        const cepInput = document.getElementById('cep');
        cepInput.value = cepInput.value.replace(/\D/g, ''); // Remove tudo que não é número
    });
</script>