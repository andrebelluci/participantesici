<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-title">
  <h1>🔥Rituais</h1>
  <br>
  <div class="actions">
    <div class="left-actions">
      <a href="/participantesici/public_html/home" class="btn voltar">Voltar</a>
    </div>
    <div class="right-actions">
      <a href="/participantesici/public_html/ritual/novo" class="btn novo-ritual">Novo ritual</a>
    </div>
  </div>
</div>

<div class="container">
  <!-- Filtros -->
  <form method="GET" class="filters">
    <div class="filter-group">
      <label for="filtro_nome">Nome:</label>
      <input type="text" name="filtro_nome" id="filtro_nome" placeholder="Filtrar por nome"
        value="<?= htmlspecialchars($_GET['filtro_nome'] ?? '') ?>">
    </div>
    <div class="filter-group">
      <label for="data_inicio">Data início:</label>
      <input type="date" name="data_inicio" id="data_inicio"
        value="<?= htmlspecialchars($_GET['data_inicio'] ?? '') ?>">
    </div>
    <div class="filter-group">
      <label for="data_fim">Data fim:</label>
      <input type="date" name="data_fim" id="data_fim" value="<?= htmlspecialchars($_GET['data_fim'] ?? '') ?>">
    </div>
    <div class="filter-actions">
      <button type="submit" class="filter-btn">Filtrar</button>
      <a href="/participantesici/public_html/rituais" class="filter-btn clear-btn">Limpar filtro</a>
    </div>
  </form>

  <!-- Lista de Rituais -->
  <table class="styled-table">
    <thead>
      <tr>
        <th class="col-foto">Foto</th>
        <th class="col-nome">
          <a href="?<?= http_build_query(array_merge($_GET, ['order_by' => 'nome', 'order_dir' => ($_GET['order_by'] ?? '') === 'nome' && ($_GET['order_dir'] ?? '') === 'ASC' ? 'DESC' : 'ASC'])) ?>"
            class="sortable-header">
            Nome
            <?php if (($_GET['order_by'] ?? '') === 'nome'): ?>
              <span class="order-icon"><?= ($_GET['order_dir'] ?? '') === 'ASC' ? '▼' : '▲' ?></span>
            <?php endif; ?>
          </a>
        </th>
        <th class="col-data">
          <a href="?<?= http_build_query(array_merge($_GET, ['order_by' => 'data_ritual', 'order_dir' => ($_GET['order_by'] ?? '') === 'data_ritual' && ($_GET['order_dir'] ?? '') === 'ASC' ? 'DESC' : 'ASC'])) ?>"
            class="sortable-header">
            Data
            <?php if (($_GET['order_by'] ?? '') === 'data_ritual'): ?>
              <span class="order-icon"><?= ($_GET['order_dir'] ?? '') === 'ASC' ? '▼' : '▲' ?></span>
            <?php endif; ?>
          </a>
        </th>
        <th class="col-padrinho">Padrinho/Madrinha</th>
        <th class="col-inscritos">Inscritos</th>
        <th class="col-acoes">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rituais as $ritual): ?>
        <tr>
          <td class="col-foto">
            <img src="<?= htmlspecialchars($ritual['foto']) ?>" alt="Foto" class="square-image clickable"
              onclick="openImageModal('<?= htmlspecialchars($ritual['foto']) ?>')"
              onerror="this.src='/participantesici/public_html/assets/images/no-image.png'; this.onclick=null; this.classList.remove('clickable');"
              title="Ver foto">
          </td>
          <td class="col-nome">
            <a href="/participantesici/public_html/ritual/<?= $ritual['id'] ?>"
              title="Gerenciar participantes"><?= htmlspecialchars($ritual['nome']) ?></a>
          </td>
          <td class="col-data">
            <?= (new DateTime($ritual['data_ritual']))->format('d/m/Y') ?>
          </td>
          <td class="col-padrinho"><?= htmlspecialchars($ritual['padrinho_madrinha']) ?></td>
          <td class="col-inscritos"><?= htmlspecialchars($ritual['inscritos']) ?></td>
          <td class="col-acoes">
            <a href="/participantesici/public_html/ritual/<?= $ritual['id'] ?>" class="action-icon"
              title="Gerenciar participantes"><i class="fa-solid fa-circle-user"></i></a>
            <a href="/participantesici/public_html/ritual/<?= $ritual['id'] ?>/editar" class="action-icon"
              title="Editar dados do ritual"><i class="fa-solid fa-pen-to-square"></i></a>
            <a href="/participantesici/public_html/ritual/<?= $ritual['id'] ?>/excluir" class="action-icon danger"
              title="Excluir ritual"
              onclick="return confirm('ATENÇÃO: Esta ação irá excluir permanentemente este ritual e todos os participantes associados. Deseja continuar?')">
              <i class="fa-solid fa-trash"></i>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Modal de Imagem -->
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
      <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"
        class="<?= ($_GET['pagina'] ?? 1) == $i ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
</div>

<script src="/participantesici/public_html/assets/js/rituais.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
$senha_atual = hash('sha256', $_POST['senha_atual']);