<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';
require_once 'includes/header.php';

// Paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itens_por_pagina = 10;
$offset = ($pagina - 1) * $itens_por_pagina;

// Consulta para contar o total de registros
$stmt_count = $pdo->query("SELECT COUNT(*) AS total FROM participantes");
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Consulta para listar as pessoas com o número de rituais em que estiveram presentes
$sql = "
    SELECT p.*, COUNT(i.id) AS rituais_participados
    FROM participantes p
    LEFT JOIN inscricoes i ON p.id = i.participante_id AND i.presente = 'Sim'
    GROUP BY p.id
    ORDER BY p.nome_completo
    LIMIT $itens_por_pagina OFFSET $offset
";
$stmt = $pdo->query($sql);
$pessoas = $stmt->fetchAll();
?>

<div class="container">
    <h1>👥 Pessoas</h1>
    <div class="actions">
        <a href="home.php" class="btn">Voltar</a>
        <a href="pessoa-novo.php" class="btn">Nova Pessoa</a>
    </div>

    <!-- Lista de Pessoas -->
    <table class="table">
        <thead>
            <tr>
                <th>Foto</th>
                <th>Nome Completo</th>
                <th>Nascimento</th>
                <th>CPF</th>
                <th>Celular</th>
                <th>Rituais Participados</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pessoas as $pessoa): ?>
                <tr>
                    <td><img src="<?= htmlspecialchars($pessoa['foto']) ?>" alt="Foto" class="small-image"></td>
                    <td><?= htmlspecialchars($pessoa['nome_completo']) ?></td>
                    <td><?= htmlspecialchars($pessoa['nascimento']) ?></td>
                    <td><?= htmlspecialchars($pessoa['cpf']) ?></td>
                    <td><?= htmlspecialchars($pessoa['celular']) ?></td>
                    <td><?= htmlspecialchars($pessoa['rituais_participados']) ?></td>
                    <td>
                        <button class="btn" onclick="document.getElementById('modal-endereco-<?= $pessoa['id'] ?>').style.display='block'">Endereço</button>
                        <button class="btn" onclick="document.getElementById('modal-rituais-<?= $pessoa['id'] ?>').style.display='block'">Rituais</button>
                        <a href="pessoa-editar.php?id=<?= $pessoa['id'] ?>" class="btn">Editar</a>
                        <a href="pessoa-excluir.php?id=<?= $pessoa['id'] ?>" class="btn danger" onclick="return confirm('Tem certeza?')">Excluir</a>
                    </td>
                </tr>

                <!-- Modal Endereço -->
                <div id="modal-endereco-<?= $pessoa['id'] ?>" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="document.getElementById('modal-endereco-<?= $pessoa['id'] ?>').style.display='none'">&times;</span>
                        <h2>Endereço de <?= htmlspecialchars($pessoa['nome_completo']) ?></h2>
                        <form method="POST" action="atualizar-endereco.php">
                            <input type="hidden" name="id" value="<?= $pessoa['id'] ?>">
                            <label for="cep">CEP:</label>
                            <input type="text" name="cep" id="cep" value="<?= htmlspecialchars($pessoa['cep']) ?>" required>
                            <label for="endereco_rua">Rua:</label>
                            <input type="text" name="endereco_rua" value="<?= htmlspecialchars($pessoa['endereco_rua']) ?>" required>
                            <label for="endereco_numero">Número:</label>
                            <input type="text" name="endereco_numero" value="<?= htmlspecialchars($pessoa['endereco_numero']) ?>" required>
                            <label for="endereco_complemento">Complemento:</label>
                            <input type="text" name="endereco_complemento" value="<?= htmlspecialchars($pessoa['endereco_complemento']) ?>">
                            <label for="cidade">Cidade:</label>
                            <input type="text" name="cidade" value="<?= htmlspecialchars($pessoa['cidade']) ?>" required>
                            <label for="estado">Estado:</label>
                            <input type="text" name="estado" value="<?= htmlspecialchars($pessoa['estado']) ?>" required>
                            <label for="bairro">Bairro:</label>
                            <input type="text" name="bairro" value="<?= htmlspecialchars($pessoa['bairro']) ?>" required>
                            <button type="submit">Salvar</button>
                        </form>
                    </div>
                </div>

                <!-- Modal Rituais -->
                <div id="modal-rituais-<?= $pessoa['id'] ?>" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="document.getElementById('modal-rituais-<?= $pessoa['id'] ?>').style.display='none'">&times;</span>
                        <h2>Rituais de <?= htmlspecialchars($pessoa['nome_completo']) ?></h2>
                        <ul>
                            <?php
                            $stmt_rituais = $pdo->prepare("SELECT r.nome, r.data_ritual, i.presente, i.observacao FROM inscricoes i JOIN rituais r ON i.ritual_id = r.id WHERE i.participante_id = ?");
                            $stmt_rituais->execute([$pessoa['id']]);
                            $rituais_pessoa = $stmt_rituais->fetchAll();
                            foreach ($rituais_pessoa as $ritual): ?>
                                <li>
                                    <strong><?= htmlspecialchars($ritual['nome']) ?></strong> (<?= htmlspecialchars($ritual['data_ritual']) ?>)
                                    <p>Presente: <?= htmlspecialchars($ritual['presente']) ?></p>
                                    <p>Observação: <?= htmlspecialchars($ritual['observacao']) ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Paginação -->
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?pagina=<?= $i ?>" class="<?= $pagina == $i ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>