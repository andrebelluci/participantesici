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
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

// Ordenação
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'data_ritual'; // Coluna padrão: data_ritual
$order_dir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'DESC'; // Direção padrão: DESC

$where = "";
$params = [];
if (!empty($filtro_nome)) {
    $where .= " AND nome LIKE ?";
    $params[] = "%$filtro_nome%";
}
if (!empty($data_inicio) && !empty($data_fim)) {
    $where .= " AND data_ritual BETWEEN ? AND ?";
    $params[] = $data_inicio;
    $params[] = $data_fim;
}

// Consulta para contar o total de registros
$stmt_count = $pdo->prepare("SELECT COUNT(*) AS total FROM rituais WHERE 1=1 $where");
$stmt_count->execute($params);
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Consulta para listar os rituais com o número de inscritos
$sql = "
    SELECT r.*, COUNT(i.id) AS inscritos
    FROM rituais r
    LEFT JOIN inscricoes i ON r.id = i.ritual_id
    WHERE 1=1 $where
    GROUP BY r.id
    ORDER BY $order_by $order_dir
    LIMIT $itens_por_pagina OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rituais = $stmt->fetchAll();
?>

<div class="page-title">
    <h1>🔥Rituais</h1>
    <br>
    <div class="actions">
        <div class="left-actions">
            <a href="home.php" class="btn voltar">Voltar</a>
        </div>
        <div class="right-actions">
            <a href="ritual-novo.php" class="btn novo-ritual">Novo ritual</a>
        </div>
    </div>
</div>
<div class="container">
    <!-- Filtros -->
    <form method="GET" class="filters">
        <div class="filter-group">
            <label for="filtro_nome">Nome:</label>
            <input type="text" name="filtro_nome" id="filtro_nome" placeholder="Filtrar por nome" value="<?= htmlspecialchars($filtro_nome) ?>">
        </div>
        <div class="filter-group">
            <label for="data_inicio">Data início:</label>
            <input type="date" name="data_inicio" id="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>">
        </div>
        <div class="filter-group">
            <label for="data_fim">Data fim:</label>
            <input type="date" name="data_fim" id="data_fim" value="<?= htmlspecialchars($data_fim) ?>">
        </div>
        <div class="filter-actions">
            <button type="submit" class="filter-btn">Filtrar</button>
            <a href="rituais.php" class="filter-btn clear-btn">Limpar filtro</a>
        </div>
    </form>

    <!-- Lista de Rituais -->
    <table class="styled-table">
        <thead>
            <tr>
                <th class="col-foto">Foto</th>
                <th class="col-nome">
                    <a href="?pagina=<?= $pagina ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&data_inicio=<?= htmlspecialchars($data_inicio) ?>&data_fim=<?= htmlspecialchars($data_fim) ?>&order_by=nome&order_dir=<?= $order_by === 'nome' && $order_dir === 'ASC' ? 'DESC' : 'ASC' ?>" class="sortable-header">
                        Nome
                        <?php if ($order_by === 'nome'): ?>
                            <span class="order-icon"><?= $order_dir === 'ASC' ? '▼' : '▲' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th class="col-data">
                    <a href="?pagina=<?= $pagina ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&data_inicio=<?= htmlspecialchars($data_inicio) ?>&data_fim=<?= htmlspecialchars($data_fim) ?>&order_by=data_ritual&order_dir=<?= $order_by === 'data_ritual' && $order_dir === 'ASC' ? 'DESC' : 'ASC' ?>" class="sortable-header">
                        Data
                        <?php if ($order_by === 'data_ritual'): ?>
                            <span class="order-icon"><?= $order_dir === 'ASC' ? '▼' : '▲' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th class="col-padrinho">Padrinho/Madrinha</th>
                <th class="col-inscritos">
                    <a href="?pagina=<?= $pagina ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&data_inicio=<?= htmlspecialchars($data_inicio) ?>&data_fim=<?= htmlspecialchars($data_fim) ?>&order_by=inscritos&order_dir=<?= $order_by === 'inscritos' && $order_dir === 'ASC' ? 'DESC' : 'ASC' ?>" class="sortable-header">
                        Inscritos
                        <?php if ($order_by === 'inscritos'): ?>
                            <span class="order-icon"><?= $order_dir === 'ASC' ? '▼' : '▲' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th class="col-acoes">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rituais as $ritual): ?>
                <tr>
                    <td class="col-foto">
                        <img
                            src="<?= htmlspecialchars($ritual['foto']) ?>"
                            alt="Foto"
                            class="square-image clickable"
                            onclick="openImageModal('<?= htmlspecialchars($ritual['foto']) ?>')"
                            onerror="this.src='assets/images/no-image.png'; this.onclick=null; this.classList.remove('clickable');"
                            title="Ver foto">
                    </td>
                    <td class="col-nome">
                        <a href="ritual-visualizar.php?id=<?= $ritual['id'] ?>" title="Gerenciar participantes"><?= htmlspecialchars($ritual['nome']) ?></a>
                    </td>
                    <td class="col-data">
                        <?php
                        // Formata a data para DD/MM/AAAA
                        $data_ritual = new DateTime($ritual['data_ritual']);
                        echo $data_ritual->format('d/m/Y');
                        ?>
                    </td>
                    <td class="col-padrinho"><?= htmlspecialchars($ritual['padrinho_madrinha']) ?></td>
                    <td class="col-inscritos"><?= htmlspecialchars($ritual['inscritos']) ?></td>
                    <td class="col-acoes">
                        <a href="ritual-visualizar.php?id=<?= $ritual['id'] ?>" class="action-icon" title="Gerenciar participantes"><i class="fa-solid fa-circle-user"></i></a>
                        <a href="ritual-editar.php?id=<?= $ritual['id'] ?>" class="action-icon" title="Editar dados do ritual"><i class="fa-solid fa-pen-to-square"></i></a>
                        <a href="ritual-excluir.php?id=<?= $ritual['id'] ?>" class="action-icon danger" title="Excluir ritual" onclick="return confirm('ATENÇÃO: Esta ação irá excluir permanentemente este ritual e todos os participantes associados. Deseja continuar?')"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Modal de Ampliação de Imagem -->
    <div id="modal-image" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <span class="close" onclick="closeImageModal()">&times;</span>
                <img id="modal-image-content" class="modal-image" alt="Imagem Ampliada">
            </div>
        </div>
    </div>

    <!-- Paginação -->
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?pagina=<?= $i ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&data_inicio=<?= htmlspecialchars($data_inicio) ?>&data_fim=<?= htmlspecialchars($data_fim) ?>" class="<?= $pagina == $i ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

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

<?php require_once 'includes/footer.php'; ?>