<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    $sobre_participante = $_POST['sobre_participante'];

    // Processamento do formulário (mantido igual)
    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        $foto_nome = uniqid() . '_' . basename($_FILES['foto']['name']);
        $foto_destino = 'uploads/' . $foto_nome;
        move_uploaded_file($_FILES['foto']['tmp_name'], $foto_destino);
        $foto = $foto_destino;
    }

    // Verifica se o e-mail é válido
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]{3,}@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        die("Erro: Por favor, digite um e-mail válido.");
    }

    // Verifica se o CPF já existe no banco de dados
    $stmt_check_cpf = $pdo->prepare("SELECT id FROM participantes WHERE cpf = ?");
    $stmt_check_cpf->execute([$cpf]);
    if ($stmt_check_cpf->rowCount() > 0) {
        die("<script>alert('Erro: Este CPF já está cadastrado.'); window.location.href = 'participante-novo.php';</script>");
    }

    $stmt = $pdo->prepare("
        INSERT INTO participantes (
            foto, nome_completo, nascimento, sexo, cpf, rg, passaporte, celular, email, como_soube, cep, endereco_rua, endereco_numero, endereco_complemento, cidade, estado, bairro, sobre_participante
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$foto, $nome_completo, $nascimento, $sexo, $cpf, $rg, $passaporte, $celular, $email, $como_soube, $cep, $endereco_rua, $endereco_numero, $endereco_complemento, $cidade, $estado, $bairro, $sobre_participante]);

    $novoParticipanteId = $pdo->lastInsertId();

    // Redireciona para a página original, se houver parâmetros de redirecionamento
    if (isset($_GET['redirect']) && isset($_GET['id'])) {
        $redirectUrl = $_GET['redirect'];
        $ritualId = $_GET['id'];

        // Insere o novo participante no ritual
        $stmt = $pdo->prepare("
            INSERT INTO inscricoes (ritual_id, participante_id) 
            VALUES (?, ?)
        ");
        $stmt->execute([$ritualId, $novoParticipanteId]);

        echo "<script>alert('Pessoa cadastrada e vinculada ao ritual com sucesso!');</script>";
        echo "<script>window.location.href = '$redirectUrl?id=$ritualId';</script>";
        exit;
    } else {
        echo "<script>alert('Pessoa cadastrada com sucesso!');</script>";
        echo "<script>window.location.href = 'participantes.php';</script>";
    }
}
?>

<div class="page-title">
    <h1>👤 Novo Participante</h1>
    <br>
    <div class="actions">
        <div class="left-actions">
            <a href="participantes.php" class="btn voltar">Voltar</a>
        </div>
        <div class="right-actions">
            <button type="submit" form="formulario-participante" class="btn salvar">Cadastrar</button>
        </div>
    </div>
</div>
<div class="container">
    <form method="POST" enctype="multipart/form-data" class="styled-form" id="formulario-participante">

        <!-- Dados Pessoais -->
        <div class="form-section">
            <h3>ℹ️Dados Pessoais</h3>
            <label for="foto">Foto do participante:</label>
            <div class="form-line">
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
            </div>
            <br>

            <div class="form-line">
                <div class="field-group" style="flex: 50%;">
                    <label for="nome_completo">Nome Completo:</label>
                    <input type="text" name="nome_completo" id="nome_completo" required>
                </div>
                <div class="field-group" style="flex: 25%;">
                    <label for="nascimento">Data de Nascimento:</label>
                    <input type="date" name="nascimento" id="nascimento" required>
                </div>
                <div class="field-group" style="flex: 25%;">
                    <label for="sexo">Sexo:</label>
                    <select name="sexo" id="sexo" required>
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                    </select>
                </div>
            </div>

            <div class="form-line">
                <div class="field-group" style="flex: 10%;">
                    <label for="cpf">CPF:</label>
                    <input type="text" name="cpf" id="cpf" placeholder="___.___.___-__" required oninput="mascaraCPF(this)">
                </div>
                <div class="field-group" style="flex: 10%;">
                    <label for="rg">RG:</label>
                    <input type="text" name="rg" id="rg">
                </div>
                <div class="field-group" style="flex: 10%;">
                    <label for="passaporte">Passaporte:</label>
                    <input type="text" name="passaporte" id="passaporte">
                </div>
                <div class="field-group" style="flex: 10%;">
                    <label for="celular">Celular:</label>
                    <input type="text" name="celular" id="celular" placeholder="(__) _____-____" required oninput="mascaraCelular(this)">
                </div>
                <div class="field-group" style="flex: 60%;">
                    <label for="email">E-mail:</label>
                    <input type="email" name="email" id="email" required>
                </div>
            </div>
        </div>

        <!-- Endereço -->
        <div class="form-section">
            <h3>📍Endereço</h3>
            <div class="form-line">
                <!-- Grupo do CEP e Botão Buscar CEP -->
                <div class="field-group" style="flex: 10%;">
                    <label for="cep">CEP:</label>
                    <input type="text" name="cep" id="cep" placeholder="_____ - ___" required oninput="mascaraCEP(this)">
                </div>
                <button type="button" id="buscar-cep-btn" class="btn buscar-cep" style="flex: 7%;">Buscar CEP</button>

                <!-- Grupo da Rua, Número e Complemento -->
                <div class="field-group" style="flex: 1%;"></div>
                <div class="field-group" style="flex: 40%;">
                    <label for="endereco_rua">Rua:</label>
                    <input type="text" name="endereco_rua" id="endereco_rua" required>
                </div>
                <div class="field-group" style="flex: 10%;">
                    <label for="endereco_numero">Número:</label>
                    <input type="text" name="endereco_numero" id="endereco_numero" required>
                </div>
                <div class="field-group" style="flex: 40%;">
                    <label for="endereco_complemento">Complemento:</label>
                    <input type="text" name="endereco_complemento" id="endereco_complemento">
                </div>
            </div>

            <!-- Bairro, Cidade e Estado -->
            <div class="form-line">
                <div class="field-group" style="flex: 30%;">
                    <label for="bairro">Bairro:</label>
                    <input type="text" name="bairro" id="bairro" required>
                </div>
                <div class="field-group" style="flex: 40%;">
                    <label for="cidade">Cidade:</label>
                    <input type="text" name="cidade" id="cidade" required>
                </div>
                <div class="field-group" style="flex: 30%;">
                    <label for="estado">Estado:</label>
                    <input type="text" name="estado" id="estado" required>
                </div>
            </div>
        </div>

        <!-- Informações Adicionais -->
        <div class="form-section">
            <h3>➕Informações Adicionais</h3>
            <div class="form-line">
                <div class="field-group" style="flex: 40%;">
                    <label for="como_soube">Como soube do Instituto Céu Interior:</label>
                    <input type="text" name="como_soube" id="como_soube" required>
                </div>
                <div class="field-group" style="flex: 60%;">
                    <label for="sobre_participante">Sobre o Participante:</label>
                    <textarea name="sobre_participante" id="sobre_participante"></textarea>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal de Ampliação de Imagem -->
<div id="modal-image" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <span class="close" onclick="closeImageModal()">&times;</span>
            <img id="modal-image-content" class="modal-image" alt="Imagem Ampliada">
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
    // Função para abrir a imagem ampliada
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
    document.getElementById('formulario-participante').addEventListener('submit', function(event) {
        // Remove máscara do CPF
        const cpfInput = document.getElementById('cpf');
        cpfInput.value = cpfInput.value.replace(/\D/g, ''); // Remove tudo que não é número

        // Remove máscara do Celular
        const celularInput = document.getElementById('celular');
        celularInput.value = celularInput.value.replace(/\D/g, ''); // Remove tudo que não é número

        // Remove máscara do CEP
        const cepInput = document.getElementById('cep');
        cepInput.value = cepInput.value.replace(/\D/g, ''); // Remove tudo que não é número

        // Função para validar o e-mail
        function validarEmail(email) {
            const regex = /^[a-zA-Z0-9._%+-]{3,}@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            return regex.test(email);
        }

        // Validar o formulário antes de enviar
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const emailInput = document.getElementById('email');

            form.addEventListener('submit', function(event) {
                const emailValue = emailInput.value.trim();

                if (!validarEmail(emailValue)) {
                    event.preventDefault(); // Impede o envio do formulário
                    alert('Por favor, digite um e-mail válido.');
                    emailInput.focus();
                }
            });
        });
    });

    document.getElementById('cpf').addEventListener('blur', function() {
        const cpfInput = this.value.replace(/\D/g, ''); // Remove máscara
        if (cpfInput.length !== 11) return; // Ignora CPFs incompletos

        fetch('verificar-cpf.php?cpf=' + cpfInput)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error); // Exibe mensagem de erro (ex.: "CPF inválido")
                    document.getElementById('cpf').value = ''; // Limpa o campo
                    document.getElementById('cpf').focus(); // Foca novamente no campo
                } else if (data.exists) {
                    alert('Este CPF já está cadastrado.');
                    document.getElementById('cpf').value = ''; // Limpa o campo
                    document.getElementById('cpf').focus(); // Foca novamente no campo
                }
            })
            .catch(error => console.error('Erro ao verificar CPF:', error));
    });
</script>
<script src="assets/js/buscaCep.js"></script>