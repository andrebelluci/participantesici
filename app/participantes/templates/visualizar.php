<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($pessoa)) {
  die("Pessoa não encontrado.");
}
?>

<div class="page-title">
    <!-- Cabeçalho com foto, nome, CPF e data de nascimento -->
    <div class="participant-header">
        <img src="<?= htmlspecialchars($pessoa['foto']) ?>" alt="Foto do Participante" class="medium-image"
      onerror="this.src='/participantesici/public_html/assets/images/no-image.png';">
    <div class="details">
      <h1>
        <?= htmlspecialchars($pessoa['nome_completo']) ?>
        <button class="btn ver-cadastro" onclick="abrirModalCadastro()">Ver cadastro</button>
      </h1>
      <p><strong>CPF:</strong> <?= formatarCPF(htmlspecialchars($pessoa['cpf'])) ?></p>
      <p><strong>Data de Nascimento:</strong>
        <?php
        // Formata a data para DD/MM/AAAA
        $nascimento = new DateTime($pessoa['nascimento']);
        echo $nascimento->format('d/m/Y');
        ?>
      </p>
    </div>
  </div>
  <!-- Botões Voltar e Adicionar Ritual -->
  <div class="actions">
    <a href="/participantesici/public_html/participantes" class="btn voltar">Voltar</a>
    <button class="btn adicionar" onclick="document.getElementById('modal-adicionar').style.display='flex'">Adicionar
      ritual</button>
  </div>
</div>
<div class="container">
  <!-- Filtro por Nome do Ritual -->
  <form method="GET" class="filters">
    <div class="filter-group">
      <label for="filtro_nome">Nome do Ritual:</label>
      <input type="text" name="filtro_nome" id="filtro_nome" placeholder="Filtrar por nome"
        value="<?= htmlspecialchars($_GET['filtro_nome'] ?? '') ?>">
    </div>
    <div class="filter-actions">
      <!-- Campo oculto para enviar o ID do participante -->
      <input type="hidden" name="id" value="<?= $id ?>">
      <button type="submit" class="filter-btn">Filtrar</button>
      <a href="/participantesici/public_html/participante/<?= $id ?>" class="filter-btn clear-btn">Limpar Filtro</a>
    </div>
  </form>

  <!-- Tabela de Rituais -->
  <h2>Rituais</h2>
  <table class="styled-table">
    <thead>
      <tr>
        <th class="col-foto-ritual">Foto</th>
        <th class="col-nome-ritual">
          <a href="?pagina=<?= $pagina ?>&id=<?= $id ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&order_by=nome&order_dir=<?= $order_by === 'nome' && $order_dir === 'ASC' ? 'DESC' : 'ASC' ?>"
            class="sortable-header">
            Nome do Ritual
            <?php if ($order_by === 'nome'): ?>
              <span class="order-icon"><?= $order_dir === 'ASC' ? '▼' : '▲' ?></span>
            <?php endif; ?>
          </a>
        </th>
        <th class="col-data-ritual">
          <a href="?pagina=<?= $pagina ?>&id=<?= $id ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&order_by=data_ritual&order_dir=<?= $order_by === 'data_ritual' && $order_dir === 'ASC' ? 'DESC' : 'ASC' ?>"
            class="sortable-header">
            Data do Ritual
            <?php if ($order_by === 'data_ritual'): ?>
              <span class="order-icon"><?= $order_dir === 'ASC' ? '▼' : '▲' ?></span>
            <?php endif; ?>
          </a>
        </th>
        <th class="col-observacao-ritual">Observação do Participante</th>
        <th class="col-presente">Presente?</th>
        <th class="col-acoes-ritual">Ações</th>
      </tr>
    </thead>
    <tbody id="tabela-rituais">
      <?php foreach ($rituais as $ritual): ?>
        <tr>
          <td class="col-foto-ritual">
            <img src="<?= htmlspecialchars($ritual['foto']) ?>" alt="Foto do Ritual" class="square-image clickable"
              onclick="openImageModal('<?= htmlspecialchars($ritual['foto']) ?>')"
              onerror="this.src='/participantesici/public_html/assets/images/no-image.png'; this.onclick=null; this.classList.remove('clickable');">
          </td>
          <td class="col-nome-ritual">
            <?= htmlspecialchars($ritual['nome']) ?>
          </td>
          <td class="col-data-ritual">
            <?php
            // Formata a data para DD/MM/AAAA
            $data_ritual = new DateTime($ritual['data_ritual']);
            echo $data_ritual->format('d/m/Y');
            ?>
          </td>
          <td class="col-observacao-ritual"><?= htmlspecialchars($ritual['observacao'] ?? '') ?></td>
          <td class="col-presente">
            <button class="presence-btn <?= $ritual['presente'] === 'Sim' ? 'active' : '' ?>"
              data-ritual-id="<?= $ritual['id'] ?>" data-current-status="<?= $ritual['presente'] ?>"
              onclick="togglePresenca(this)">
              <?= htmlspecialchars($ritual['presente']) ?>
            </button>
          </td>
          <td class="col-acoes-ritual">
            <a href="#" class="action-icon" title="Observação do participante neste ritual"
              onclick="abrirModalObservacao(<?= $ritual['id'] ?>)">
              <i class="fa-solid fa-comment-medical"></i>
            </a>
            <a href="#" class="action-icon" title="Detalhes da inscrição no ritual"
              onclick="abrirModalDetalhes(<?= $ritual['id'] ?>)">
              <i class="fa-solid fa-info-circle"></i>
            </a>
            <a href="participante-excluir-ritual.php?id=<?= $ritual['id'] ?>" class="action-icon danger"
              title="Remover participante do ritual"
              onclick="return confirm('Tem certeza que deseja remover este ritual do participante?')">
              <i class="fa-solid fa-trash"></i>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Paginação -->
  <div class="pagination">
    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
      <a href="?pagina=<?= $i ?>&id=<?= $id ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&order_by=<?= $order_by ?>&order_dir=<?= $order_dir ?>"
        class="<?= $pagina == $i ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
</div>

<!-- Modal Adicionar Ritual -->
<div id="modal-adicionar" class="modal">
  <div class="modal-dialog">
    <div class="modal-content">
      <span class="close" onclick="fecharModalAdicionar()">&times;</span>
      <h2>Adicionar ritual</h2>
      <form id="pesquisa-ritual-form" onsubmit="return false;">
        <input type="hidden" name="participante_id" value="<?= $id ?>">
        <label for="nome_pesquisa">Pesquisar:</label>
        <input type="text" id="nome_pesquisa" name="nome_pesquisa" placeholder="Digite o nome do ritual"
          oninput="aplicarMascaraRitual(this)">
        <div class="button-container">
          <button type="button" id="pesquisar-btn" onclick="pesquisarRituais()">Pesquisar</button>
          <button type="button" id="limpar-pesquisa-btn" onclick="limparPesquisa()" style="display: none;">Limpar
            pesquisa</button>
        </div>
      </form>
      <!-- Área para exibir os resultados da pesquisa -->
      <div id="resultados-pesquisa" class="scrollable-list" style="display: none;">
        <h3>Resultados</h3>
        <ul id="lista-rituais"></ul>
        <!-- Botão para adicionar novo ritual -->
        <button id="btn-adicionar-novo-ritual" style="display: none;" onclick="adicionarNovoRitual()">Adicionar
          novo ritual</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Detalhes da Inscrição -->
<div id="modal-detalhes-inscricao" class="modal">
  <div class="modal-dialog">
    <div class="modal-content">
      <span class="close" onclick="fecharModalDetalhes()">&times;</span>
      <h2>Detalhes da inscrição</h2>
      <form id="form-detalhes-inscricao" method="POST">
        <!-- Campo oculto para o ID da inscrição -->
        <input type="hidden" id="id" name="id" value="">
        <!-- Primeira vez no Instituto -->
        <label for="primeira_vez_instituto">Primeira vez no Instituto?</label>
        <select name="primeira_vez_instituto" required>
          <option value="">Selecione...</option>
          <option value="Sim">Sim</option>
          <option value="Não">Não</option>
        </select>
        <!-- Primeira vez consagrando Ayahuasca -->
        <label for="primeira_vez_ayahuasca">Primeira vez consagrando Ayahuasca?</label>
        <select name="primeira_vez_ayahuasca" required>
          <option value="">Selecione...</option>
          <option value="Sim">Sim</option>
          <option value="Não">Não</option>
        </select>
        <!-- Doença psiquiátrica diagnosticada -->
        <label for="doenca_psiquiatrica">Possui doença psiquiátrica diagnosticada?</label>
        <select name="doenca_psiquiatrica" id="doenca_psiquiatrica" required>
          <option value="">Selecione...</option>
          <option value="Sim">Sim</option>
          <option value="Não">Não</option>
        </select>
        <!-- Nome da doença -->
        <label for="nome_doenca">Se sim, escreva o nome da doença:</label>
        <input type="text" name="nome_doenca" id="nome_doenca" value="" disabled>
        <!-- Uso de medicação -->
        <label for="uso_medicao">Faz uso de alguma medicação?</label>
        <select name="uso_medicao" id="uso_medicao" required>
          <option value="">Selecione...</option>
          <option value="Sim">Sim</option>
          <option value="Não">Não</option>
        </select>
        <!-- Nome da medicação -->
        <label for="nome_medicao">Se sim, escreva o nome da medicação:</label>
        <input type="text" name="nome_medicao" id="nome_medicao" value="" disabled>
        <!-- Mensagem do participante -->
        <label for="mensagem">Mensagem do participante:</label>
        <textarea name="mensagem"></textarea>
        <!-- Data de Salvamento -->
        <label for="salvo_em">Salvo em:</label>
        <input type="text" id="salvo_em" name="salvo_em" readonly value="">
        <!-- Botão de envio -->
        <button type="submit">Salvar</button>
      </form>
    </div>
  </div>
</div>

<!-- Modal Adicionar Observação -->
<div id="modal-observacao" class="modal">
  <div class="modal-dialog">
    <div class="modal-content">
      <span class="close" onclick="fecharModalObservacao()">&times;</span>
      <h2>Adicionar observação</h2>
      <form id="form-observacao" method="POST">
        <!-- Campo oculto para o ID da inscrição -->
        <input type="hidden" id="inscricao_id_observacao" name="inscricao_id" value="">
        <!-- Campo de Observação -->
        <label for="observacao">Observação:</label>
        <textarea name="observacao" required></textarea>
        <!-- Data de Salvamento -->
        <label for="obs_salvo_em">Salvo em:</label>
        <input type="text" id="obs_salvo_em" name="obs_salvo_em" readonly value="">
        <!-- Botão de envio -->
        <button type="submit">Salvar</button>
      </form>
    </div>
  </div>
</div>

<!-- Modal de Visualização de Imagem -->
<div id="modal-image" class="modal">
  <div class="modal-dialog">
    <div class="modal-content">
      <span class="close" onclick="closeImageModal()">&times;</span>
      <img id="modal-image-content" class="modal-image" alt="Imagem Ampliada">
    </div>
  </div>
</div>

<!-- Modal Ver Cadastro -->
<div id="modal-cadastro" class="modal">
  <div class="modal-dialog">
    <div class="modal-content">
      <span class="close" onclick="fecharModalCadastro()">&times;</span>
      <h2>Participante: <?= htmlspecialchars($pessoa['nome_completo']) ?></h2>
      <div class="modal-body">
        <ul class="styled-list">
          <!-- Dados Pessoais -->
          <h3><strong>ℹ️ Dados Pessoais</strong></h3>
          <li><strong>Nome Completo:</strong> <?= htmlspecialchars($pessoa['nome_completo']) ?></li>
          <li><strong>CPF:</strong> <?= formatarCPF(htmlspecialchars($pessoa['cpf'])) ?></li>
          <li><strong>Data de Nascimento:</strong>
            <?php
            // Formata a data para DD/MM/AAAA
            $nascimento = new DateTime($pessoa['nascimento']);
            echo $nascimento->format('d/m/Y');
            ?>
          </li>
          <li><strong>Sexo:</strong>
            <?= htmlspecialchars($pessoa['sexo'] === 'M' ? 'Masculino' : 'Feminino') ?></li>
          <li><strong>RG:</strong> <?= htmlspecialchars($pessoa['rg']) ?></li>
          <li><strong>Passaporte:</strong> <?= htmlspecialchars($pessoa['passaporte']) ?></li>
          <li><strong>Celular:</strong> <?= htmlspecialchars($pessoa['celular']) ?></li>
          <li><strong>E-mail:</strong> <?= htmlspecialchars($pessoa['email']) ?></li>
          <li><strong>Como soube do Instituto:</strong> <?= htmlspecialchars($pessoa['como_soube']) ?></li>

          <!-- Endereço -->
          <h3><strong>📍 Endereço</strong></h3>
          <li><strong>CEP:</strong> <?= htmlspecialchars($pessoa['cep']) ?></li>
          <li><strong>Rua:</strong> <?= htmlspecialchars($pessoa['endereco_rua']) ?></li>
          <li><strong>Número:</strong> <?= htmlspecialchars($pessoa['endereco_numero']) ?></li>
          <li><strong>Complemento:</strong> <?= htmlspecialchars($pessoa['endereco_complemento']) ?></li>
          <li><strong>Bairro:</strong> <?= htmlspecialchars($pessoa['bairro']) ?></li>
          <li><strong>Cidade:</strong> <?= htmlspecialchars($pessoa['cidade']) ?></li>
          <li><strong>Estado:</strong> <?= htmlspecialchars($pessoa['estado']) ?></li>

          <!-- Informações Adicionais -->
          <h3><strong>➕ Informações Adicionais</strong></h3>
          <li><strong>Como soube do Instituto Céu Interior?</strong>
            <?= htmlspecialchars($pessoa['como_soube']) ?></li>
          <li><strong>Sobre o Participante:</strong> <?= htmlspecialchars($pessoa['sobre_participante']) ?>
          </li>
          <br>
          <a href="/participantesici/public_html/participante?id=<?= $pessoa['id'] ?>/editar&redirect=/participantesici/public_html/participante/" class="action-icon"
            title="Editar dados do participante">
            <i class="fa-solid fa-pen-to-square"></i>
            Editar dados do participante
          </a>
        </ul>
      </div>
    </div>
  </div>
</div>

<script src="/participantesici/public_html/assets/js/participante-visualizar.js"></script>
<script>
    const pessoaId = <?= json_encode($id) ?>;
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>