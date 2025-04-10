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
?>

<div class="container">
    <h1><?= htmlspecialchars($ritual['nome']) ?></h1>
    <div class="actions">
        <a href="rituais.php" class="btn">Voltar</a>
    </div>

    <div class="ritual-details">
        <img src="<?= htmlspecialchars($ritual['foto']) ?>" alt="Foto do Ritual" class="medium-image">
        <p><strong>Data:</strong> <?= htmlspecialchars($ritual['data_ritual']) ?></p>
        <p><strong>Padrinho/Madrinha:</strong> <?= htmlspecialchars($ritual['padrinho_madrinha']) ?></p>
    </div>

    <h2>Participantes</h2>
    <button class="btn" onclick="document.getElementById('modal-adicionar').style.display='block'">Adicionar Participante</button>

    <table class="table">
        <thead>
            <tr>
                <th>Foto</th>
                <th>Nome</th>
                <th>Observação</th>
                <th>Presente?</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt_participantes = $pdo->prepare("SELECT p.*, i.presente FROM inscricoes i JOIN participantes p ON i.participante_id = p.id WHERE i.ritual_id = ?");
            $stmt_participantes->execute([$id]);
            $participantes = $stmt_participantes->fetchAll();
            foreach ($participantes as $participante): ?>
                <tr>
                    <td><img src="<?= htmlspecialchars($participante['foto']) ?>" alt="Foto" class="small-image"></td>
                    <td><?= htmlspecialchars($participante['nome_completo']) ?></td>
                    <td><?= htmlspecialchars($participante['observacao']) ?></td>
                    <td><?= htmlspecialchars($participante['presente']) ?></td>
                    <td>
                        <a href="#" class="btn">Detalhes da Inscrição</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Adicionar Participante -->
<div id="modal-adicionar" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('modal-adicionar').style.display='none'">&times;</span>
        <h2>Adicionar Participante</h2>
        <form method="POST" action="participante-adicionar.php">
            <input type="hidden" name="ritual_id" value="<?= $id ?>">
            <label for="nome">Pesquisar Participante:</label>
            <input type="text" name="nome" placeholder="Digite o nome">
            <button type="submit">Pesquisar</button>
        </form>
    </div>
</div>

<!-- Modal Detalhes da Inscrição -->
<div id="modal-detalhes-inscricao-<?= $participante['id'] ?>" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('modal-detalhes-inscricao-<?= $participante['id'] ?>').style.display='none'">&times;</span>
        <h2>Detalhes da Inscrição</h2>
        <form method="POST" action="salvar-detalhes-inscricao.php">
            <input type="hidden" name="inscricao_id" value="<?= $inscricao['id'] ?>">
            <label for="primeira_vez_instituto">Primeira vez no Instituto?</label>
            <select name="primeira_vez_instituto" required>
                <option value="Sim">Sim</option>
                <option value="Não">Não</option>
            </select>

            <label for="primeira_vez_ayahuasca">Primeira vez consagrando Ayahuasca?</label>
            <select name="primeira_vez_ayahuasca" required>
                <option value="Sim">Sim</option>
                <option value="Não">Não</option>
            </select>

            <label for="doenca_psiquiatrica">Possui doença psiquiátrica diagnosticada?</label>
            <select name="doenca_psiquiatrica" required>
                <option value="Sim">Sim</option>
                <option value="Não">Não</option>
            </select>

            <label for="nome_doenca">Se sim, escreva o nome da doença:</label>
            <input type="text" name="nome_doenca">

            <label for="uso_medicao">Faz uso de alguma medicação?</label>
            <select name="uso_medicao" required>
                <option value="Sim">Sim</option>
                <option value="Não">Não</option>
            </select>

            <label for="nome_medicao">Se sim, escreva o nome da medicação:</label>
            <input type="text" name="nome_medicao">

            <label for="mensagem">Mensagem do participante:</label>
            <textarea name="mensagem"></textarea>

            <button type="submit">Salvar</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>