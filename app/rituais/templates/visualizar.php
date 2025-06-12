<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($ritual)) {
    die("Ritual não encontrado.");
}
?>

<div class="page-title">
    <div class="ritual-header">
        <img src="<?= htmlspecialchars($ritual['foto']) ?>" alt="Foto do Ritual" class="medium-image"
            onerror="this.src='/participantesici/public_html/assets/images/no-image.png';">
        <div class="details">
            <h1><?= htmlspecialchars($ritual['nome']) ?></h1>
            <p><strong>Data:</strong> <?= (new DateTime($ritual['data_ritual']))->format('d/m/Y') ?></p>
            <p><strong>Padrinho/Madrinha:</strong> <?= htmlspecialchars($ritual['padrinho_madrinha']) ?></p>
        </div>
    </div>

    <div class="actions">
        <a href="/participantesici/public_html/rituais" class="btn voltar">Voltar</a>
        <button class="btn adicionar" onclick="document.getElementById('modal-adicionar').style.display='flex'">Adicionar participante</button>
    </div>
</div>

<div class="container">

    <!-- Filtro por Nome -->
    <form method="GET" class="filters">
        <div class="filter-group">
            <label for="filtro_nome">Nome:</label>
            <input type="text" name="filtro_nome" id="filtro_nome" placeholder="Filtrar por nome"
                value="<?= htmlspecialchars($_GET['filtro_nome'] ?? '') ?>">
        </div>
        <div class="filter-actions">
            <!-- Campo oculto para enviar o ID do ritual -->
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="filter-btn">Filtrar</button>
            <a href="/participantesici/public_html/rituais/<?= $id ?>" class="filter-btn clear-btn">Limpar Filtro</a>
        </div>
    </form>

    <!-- Tabela de Participantes -->
    <h2>Participantes</h2>
    <table class="styled-table">
        <thead>
            <tr>
                <th class="col-foto-participante">Foto</th>
                <th class="col-nome-participante">
                    <a href="#" onclick="ordenarPor('nome')">Nome</a>
                </th>
                <th class="col-observacao">Observação</th>
                <th class="col-presente">
                    <a href="#" onclick="ordenarPor('presente')">Presente?</a>
                </th>
                <th class="col-acoes-participante">Ações</th>
            </tr>
        </thead>
        <tbody id="tabela-participantes">
            <?php
            // Aplicar filtro por nome
            $filtro_nome = isset($_GET['filtro_nome']) ? trim($_GET['filtro_nome']) : '';
            $sql_participantes = "
                SELECT p.*, i.presente, i.observacao
                FROM inscricoes i
                JOIN participantes p ON i.participante_id = p.id
                WHERE i.ritual_id = ?
            ";
            $params = [$id];
            if (!empty($filtro_nome)) {
                $sql_participantes .= " AND p.nome_completo LIKE ?";
                $params[] = "%$filtro_nome%";
            }
            $stmt_participantes = $pdo->prepare($sql_participantes);
            $stmt_participantes->execute($params);
            $participantes = $stmt_participantes->fetchAll();
            foreach ($participantes as $participante): ?>
                <tr>
                    <td class="col-foto-participante">
                        <img src="<?= htmlspecialchars($participante['foto']) ?>" alt="Foto do Participante"
                            class="square-image clickable"
                            onclick="openImageModal('<?= htmlspecialchars($participante['foto']) ?>')"
                            onerror="this.src='/participantesici/public_html/assets/images/no-image.png'; this.onclick=null; this.classList.remove('clickable');">
                    </td>
                    <td class="col-nome-participante">
                        <a
                            href="participantes?pagina=1&filtro_cpf=<?= urlencode(htmlspecialchars($participante['cpf'])) ?>&redirect=ritual-visualizar?id=<?= $id ?>">
                            <?= htmlspecialchars($participante['nome_completo']) ?>
                        </a>
                    </td>
                    <td class="col-observacao"><?= htmlspecialchars($participante['observacao'] ?? '') ?></td>
                    <td class="col-presente">
                        <button class="presence-btn <?= $participante['presente'] === 'Sim' ? 'active' : '' ?>"
                            data-participante-id="<?= $participante['id'] ?>"
                            data-current-status="<?= $participante['presente'] ?>" onclick="togglePresenca(this)">
                            <?= htmlspecialchars($participante['presente']) ?>
                        </button>
                    </td>
                    <td class="col-acoes-participante">
                        <a href="#" class="action-icon" title="Observação do participante neste ritual"
                            onclick="abrirModalObservacao(<?= $participante['id'] ?>)">
                            <i class="fa-solid fa-comment-medical"></i>
                        </a>
                        <a href="#" class="action-icon" title="Detalhes da inscrição do participante"
                            onclick="abrirModalDetalhes(<?= $participante['id'] ?>)">
                            <i class="fa-solid fa-info-circle"></i>
                        </a>
                        <a href="/participantesici/public_html/api/inscricoes/excluir-participacao?id=<?= $participante['id'] ?>" class="action-icon danger"
                            title="Remover participante do ritual"
                            onclick="return confirm('Tem certeza que deseja remover este participante do ritual?')">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Adicionar Participante -->
<div id="modal-adicionar" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <span class="close" onclick="fecharModalAdicionar()">&times;</span>
            <h2>Adicionar participante</h2>
            <form id="pesquisa-participante-form" onsubmit="return false;">
                <input type="hidden" name="ritual_id" value="<?= $id ?>">
                <label for="nome_pesquisa">Pesquisar:</label>
                <input type="text" id="nome_pesquisa" name="nome_pesquisa" placeholder="Digite o nome ou CPF"
                    oninput="aplicarMascaraCPF(this)">
                <div class="button-container">
                    <button type="button" id="pesquisar-btn" onclick="pesquisarParticipantes()">Pesquisar</button>
                    <button type="button" id="limpar-pesquisa-btn" onclick="limparPesquisa()"
                        style="display: none;">Limpar pesquisa</button>
                </div>
            </form>
            <!-- Área para exibir os resultados da pesquisa -->
            <div id="resultados-pesquisa" class="scrollable-list" style="display: none;">
                <h3>Resultados</h3>
                <ul id="lista-participantes"></ul>
                <!-- Botão para adicionar nova pessoa -->
                <button id="btn-adicionar-nova-pessoa" style="display: none;" onclick="adicionarNovaPessoa()">Adicionar
                    novo participante</button>
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

<script src="/participantesici/public_html/assets/js/ritual-visualizar.js"></script>
<script>
    const ritualId = <?= json_encode($id) ?>;
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>