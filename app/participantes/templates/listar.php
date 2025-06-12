<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-title">
    <div class="mobile-actions">
        <div class="left-actions">
       <?php
      // Verifica se há um parâmetro 'redirect' na URL
      $redirect = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'home';
      ?>
      <a href="<?= $redirect ?>" class="btn-mobile voltar"><i class="fa-solid fa-chevron-left"></i></a>

    </div>
  </div>
  <h1>👥 Participantes</h1>
  <br>
  <div class="actions">
    <div class="left-actions">
      <?php
      // Verifica se há um parâmetro 'redirect' na URL
      $redirect = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'home';
      ?>
      <a href="<?= $redirect ?>" class="btn voltar">Voltar</a>
    </div>
    <div class="right-actions">
      <a href="/participantesici/public_html/participante/novo" class="btn novo-participante">Novo participante</a>
    </div>
  </div>
</div>
<div class="container">
  <!-- Filtros -->
  <form method="GET" class="filters">
    <div class="filter-group">
      <label for="filtro_nome">Nome:</label>
      <input type="text" name="filtro_nome" id="filtro_nome" placeholder="Filtrar por nome"
        value="<?= htmlspecialchars($filtro_nome) ?>">
    </div>
    <div class="filter-group">
      <label for="filtro_cpf">CPF:</label>
      <input type="text" name="filtro_cpf" id="filtro_cpf" placeholder="___.___.___-__"
        value="<?= htmlspecialchars($filtro_cpf) ?>" oninput="mascaraCPF(this)">
    </div>
    <div class="filter-actions">
      <button type="submit" class="filter-btn">Filtrar</button>
      <a href="/participantesici/public_html/participantes" class="filter-btn clear-btn">Limpar filtro</a>
    </div>
  </form>

  <table class="styled-table">
    <thead>
      <tr>
        <th class="col-foto-pessoa">Foto</th>
        <th class="col-nome-pessoa">
          <a href="?pagina=<?= $pagina ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&order_by=nome_completo&order_dir=<?= $order_by === 'nome_completo' && $order_dir === 'ASC' ? 'DESC' : 'ASC' ?>"
            class="sortable-header">
            Nome Completo
            <?php if ($order_by === 'nome_completo'): ?>
              <span class="order-icon"><?= $order_dir === 'ASC' ? '▼' : '▲' ?></span>
            <?php endif; ?>
          </a>
        </th>
        <th class="col-nascimento">Nascimento</th>
        <th class="col-cpf">CPF</th>
        <th class="col-celular">Celular</th>
        <th class="col-rituais-participados">
          <a href="?pagina=<?= $pagina ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&order_by=rituais_participados&order_dir=<?= $order_by === 'rituais_participados' && $order_dir === 'ASC' ? 'DESC' : 'ASC' ?>"
            class="sortable-header">
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
            <img src="<?= htmlspecialchars($pessoa['foto']) ?>" alt="Foto" class="square-image clickable"
              onclick="openImageModal('<?= htmlspecialchars($pessoa['foto']) ?>')"
              onerror="this.src='/participantesici/public_html/assets/images/no-image.png'; this.onclick=null; this.classList.remove('clickable');">
          </td>
          <td class="col-nome-pessoa">
            <a href="/participantesici/public_html/participante/<?= $pessoa['id'] ?>"
              title="Gerenciar rituais"><?= htmlspecialchars($pessoa['nome_completo']) ?></a>
          </td>
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
            <a href="/participantesici/public_html/participante/<?= $pessoa['id'] ?>" class="action-icon" title="Gerenciar rituais">
              <i class="fa-solid fa-list-check"></i>
            </a>
            <a href="/participantesici/public_html/participante/<?= $pessoa['id'] ?>/editar" class="action-icon"
              title="Editar dados do participante">
              <i class="fa-solid fa-pen-to-square"></i>
            </a>
            <a href="/participantesici/public_html/participante/<?= $pessoa['id'] ?>/excluir" class="action-icon danger" title="Excluir participante"
              onclick="return confirm('Tem certeza que deseja remover este participante permanentemente e desvincular de todos os rituais?')">
              <i class="fa-solid fa-trash"></i>
            </a>
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
      <a href="?pagina=<?= $i ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&data_inicio=<?= htmlspecialchars($data_inicio) ?>&data_fim=<?= htmlspecialchars($data_fim) ?>"
        class="<?= $pagina == $i ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
</div>

<script src="/participantesici/public_html/assets/js/participantes.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>