<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Dados do formulário
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

    // Inserir no banco
    $stmt = $pdo->prepare("INSERT INTO participantes (foto, nome_completo, nascimento, sexo, cpf, rg, passaporte, celular, email, como_soube, cep, endereco_rua, endereco_numero, endereco_complemento, cidade, estado, bairro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$foto, $nome_completo, $nascimento, $sexo, $cpf, $rg, $passaporte, $celular, $email, $como_soube, $cep, $endereco_rua, $endereco_numero, $endereco_complemento, $cidade, $estado, $bairro]);

    echo "<script>alert('Pessoa cadastrada com sucesso!');</script>";
    echo "<script>window.location.href = 'pessoas.php';</script>";
}
?>

<div class="container">
    <h1>Nova Pessoa</h1>
    <div class="actions">
        <a href="pessoas.php" class="btn">Voltar</a>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <label for="foto">Foto:</label>
        <input type="file" name="foto" accept="image/*">

        <label for="nome_completo">Nome Completo:</label>
        <input type="text" name="nome_completo" required>

        <label for="nascimento">Data de Nascimento:</label>
        <input type="date" name="nascimento" required>

        <label for="sexo">Sexo:</label>
        <select name="sexo" required>
            <option value="M">Masculino</option>
            <option value="F">Feminino</option>
        </select>

        <label for="cpf">CPF:</label>
        <input type="text" name="cpf" required>

        <label for="rg">RG:</label>
        <input type="text" name="rg">

        <label for="passaporte">Passaporte:</label>
        <input type="text" name="passaporte">

        <label for="celular">Celular:</label>
        <input type="text" name="celular" required>

        <label for="email">E-mail:</label>
        <input type="email" name="email">

        <label for="como_soube">Como soube do Instituto Céu Interior?</label>
        <textarea name="como_soube"></textarea>

        <label for="cep">CEP:</label>
        <input type="text" name="cep" id="cep" required>
        <button type="button" id="buscar-cep-btn" class="btn">Buscar CEP</button>

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

        <button type="submit">Cadastrar</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>

<script src="assets/js/scripts.js" defer></script>