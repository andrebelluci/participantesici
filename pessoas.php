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

// Filtros
$filtro_nome = isset($_GET['filtro_nome']) ? $_GET['filtro_nome'] : '';

// Ordenação
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'nome_completo'; // Coluna padrão: nome_completo
$order_dir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'ASC'; // Direção padrão: ASC

$where = "";
$params = [];
if (!empty($filtro_nome)) {
    $where .= " AND nome_completo LIKE ?";
    $params[] = "%$filtro_nome%";
}
if (!empty($data_inicio) && !empty($data_fim)) {
    $where .= " AND nascimento BETWEEN ? AND ?";
    $params[] = $data_inicio;
    $params[] = $data_fim;
}

// Consulta para contar o total de registros
$stmt_count = $pdo->prepare("SELECT COUNT(*) AS total FROM participantes WHERE 1=1 $where");
$stmt_count->execute($params);
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Consulta para listar as pessoas com o número de rituais em que estiveram presentes
$sql = "
    SELECT p.*, COUNT(i.id) AS rituais_participados
    FROM participantes p
    LEFT JOIN inscricoes i ON p.id = i.participante_id AND i.presente = 'Sim'
    WHERE 1=1 $where
    GROUP BY p.id
    ORDER BY $order_by $order_dir
    LIMIT $itens_por_pagina OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pessoas = $stmt->fetchAll();

function formatarCPF($cpf)
{
    // Remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    // Aplica a máscara ###.###.###-##
    return substr($cpf, 0, 3) . '.' .
        substr($cpf, 3, 3) . '.' .
        substr($cpf, 6, 3) . '-' .
        substr($cpf, 9, 2);
}

// Função para formatar telefone
function formatarTelefone($telefone)
{
    // Remove caracteres não numéricos
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    // Verifica o tamanho do número (fixo ou celular)
    if (strlen($telefone) === 10) { // Telefone fixo: (##) ####-####
        return '(' . substr($telefone, 0, 2) . ') ' .
            substr($telefone, 2, 4) . '-' .
            substr($telefone, 6, 4);
    } elseif (strlen($telefone) === 11) { // Celular: (##) #####-####
        return '(' . substr($telefone, 0, 2) . ') ' .
            substr($telefone, 2, 5) . '-' .
            substr($telefone, 7, 4);
    } else {
        return $telefone; // Retorna o valor original caso o formato seja inválido
    }
}
?>

<div class="container">
    <h1>👥 Pessoas</h1>
    <br>
    <div class="actions">
        <div class="left-actions">
            <a href="home.php" class="btn voltar">Voltar</a>
        </div>
        <div class="right-actions">
            <a href="pessoa-novo.php" class="btn novo-participante">Nova Pessoa</a>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" class="filters">
        <div class="filter-group">
            <label for="filtro_nome">Nome:</label>
            <input type="text" name="filtro_nome" id="filtro_nome" placeholder="Filtrar por nome" value="<?= htmlspecialchars($filtro_nome) ?>">
        </div>
        <div class="filter-actions">
            <button type="submit" class="filter-btn">Filtrar</button>
            <a href="pessoas.php" class="filter-btn clear-btn">Limpar Filtro</a>
        </div>
    </form>

    <!-- Lista de Pessoas -->
    <table class="styled-table">
        <thead>
            <tr>
                <th class="col-foto-pessoa">Foto</th>
                <th class="col-nome-pessoa">
                    <a href="?pagina=<?= $pagina ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&data_inicio=<?= htmlspecialchars($data_inicio) ?>&data_fim=<?= htmlspecialchars($data_fim) ?>&order_by=nome_completo&order_dir=<?= $order_by === 'nome_completo' && $order_dir === 'ASC' ? 'DESC' : 'ASC' ?>" class="sortable-header">
                        Nome Completo
                        <?php if ($order_by === 'nome_completo'): ?>
                            <span class="order-icon"><?= $order_dir === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th class="col-nascimento">
                    <a href="?pagina=<?= $pagina ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&data_inicio=<?= htmlspecialchars($data_inicio) ?>&data_fim=<?= htmlspecialchars($data_fim) ?>&order_by=nascimento&order_dir=<?= $order_by === 'nascimento' && $order_dir === 'ASC' ? 'DESC' : 'ASC' ?>" class="sortable-header">
                        Nascimento
                        <?php if ($order_by === 'nascimento'): ?>
                            <span class="order-icon"><?= $order_dir === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th class="col-cpf">CPF</th>
                <th class="col-celular">Celular</th>
                <th class="col-rituais-participados">
                    <a href="?pagina=<?= $pagina ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&data_inicio=<?= htmlspecialchars($data_inicio) ?>&data_fim=<?= htmlspecialchars($data_fim) ?>&order_by=rituais_participados&order_dir=<?= $order_by === 'rituais_participados' && $order_dir === 'ASC' ? 'DESC' : 'ASC' ?>" class="sortable-header">
                        Rituais Participados
                        <?php if ($order_by === 'rituais_participados'): ?>
                            <span class="order-icon"><?= $order_dir === 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th class="col-acoes-pessoa">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pessoas as $pessoa): ?>
                <tr>
                    <td class="col-foto-pessoa">
                        <img src="<?= htmlspecialchars($pessoa['foto']) ?>" alt="Foto" class="square-image" onerror="this.src='assets/images/no-image.png';">
                    </td>
                    <td class="col-nome-pessoa"><?= htmlspecialchars($pessoa['nome_completo']) ?></td>
                    <td class="col-nascimento">
                        <?php
                        // Formata a data para DD/MM/AAAA
                        $nascimento = new DateTime($pessoa['nascimento']);
                        echo $nascimento->format('d/m/Y');
                        ?>
                    </td>
                    <td class="col-cpf"><?= formatarCPF(htmlspecialchars($pessoa['cpf'])) ?></td>
                    <td class="col-celular"><?= formatarTelefone(htmlspecialchars($pessoa['celular'])) ?></td>
                    <td class="col-rituais-participados"><?= htmlspecialchars($pessoa['rituais_participados']) ?></td>
                    <td class="col-acoes-pessoa">
                        <button class="action-icon" title="Ver Endereço" onclick="document.getElementById('modal-endereco-<?= $pessoa['id'] ?>').style.display='block'">
                            <i class="fa-solid fa-map-marker-alt"></i>
                        </button>
                        <button class="action-icon" title="Ver Rituais" onclick="document.getElementById('modal-rituais-<?= $pessoa['id'] ?>').style.display='block'">
                            <i class="fa-solid fa-calendar-alt"></i>
                        </button>
                        <a href="pessoa-editar.php?id=<?= $pessoa['id'] ?>" class="action-icon" title="Editar">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <a href="pessoa-excluir.php?id=<?= $pessoa['id'] ?>" class="action-icon danger" title="Excluir" onclick="return confirm('Tem certeza?')">
                            <i class="fa-solid fa-trash"></i>
                        </a>
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
            <a href="?pagina=<?= $i ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&data_inicio=<?= htmlspecialchars($data_inicio) ?>&data_fim=<?= htmlspecialchars($data_fim) ?>" class="<?= $pagina == $i ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>