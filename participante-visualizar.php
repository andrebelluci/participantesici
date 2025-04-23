<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';
require_once 'includes/header.php';

// Obter o ID do participante da URL
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM participantes WHERE id = ?");
$stmt->execute([$id]);
$pessoa = $stmt->fetch();

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

if (!$pessoa) {
    die("Participante não encontrado.");
}

// Paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itens_por_pagina = 10;
$offset = ($pagina - 1) * $itens_por_pagina;

// Filtro por Nome do Ritual
$filtro_nome = isset($_GET['filtro_nome']) ? trim($_GET['filtro_nome']) : '';

// Ordenação
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'data_ritual'; // Coluna padrão: data_ritual
$order_dir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'DESC'; // Direção padrão: DESC (mais novo primeiro)

// Consulta para contar o total de registros
$sql_count = "
    SELECT COUNT(*) AS total 
    FROM inscricoes i 
    JOIN rituais r ON i.ritual_id = r.id 
    WHERE i.participante_id = ?
";
$params_count = [$id];
if (!empty($filtro_nome)) {
    $sql_count .= " AND r.nome LIKE ?";
    $params_count[] = "%$filtro_nome%";
}
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params_count);
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Consulta para listar os rituais com paginação e ordenação
$sql_rituais = "
    SELECT r.*, i.presente, i.observacao 
    FROM inscricoes i 
    JOIN rituais r ON i.ritual_id = r.id 
    WHERE i.participante_id = ?
";
$params = [$id];
if (!empty($filtro_nome)) {
    $sql_rituais .= " AND r.nome LIKE ?";
    $params[] = "%$filtro_nome%";
}
$sql_rituais .= " ORDER BY $order_by $order_dir LIMIT $itens_por_pagina OFFSET $offset";
$stmt_rituais = $pdo->prepare($sql_rituais);
$stmt_rituais->execute($params);
$rituais = $stmt_rituais->fetchAll();
?>
<div class="page-title">
    <!-- Cabeçalho com foto, nome, CPF e data de nascimento -->
    <div class="participant-header">
        <img src="<?= htmlspecialchars($pessoa['foto']) ?>" alt="Foto do Participante" class="medium-image" onerror="this.src='assets/images/no-image.png';">
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
        <a href="participantes.php" class="btn voltar">Voltar</a>
        <button class="btn adicionar" onclick="document.getElementById('modal-adicionar').style.display='flex'">Adicionar ritual</button>
    </div>
</div>
<div class="container">
    <!-- Filtro por Nome do Ritual -->
    <form method="GET" class="filters">
        <div class="filter-group">
            <label for="filtro_nome">Nome do Ritual:</label>
            <input type="text" name="filtro_nome" id="filtro_nome" placeholder="Filtrar por nome" value="<?= htmlspecialchars($_GET['filtro_nome'] ?? '') ?>">
        </div>
        <div class="filter-actions">
            <!-- Campo oculto para enviar o ID do participante -->
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="filter-btn">Filtrar</button>
            <a href="participante-visualizar.php?id=<?= $id ?>" class="filter-btn clear-btn">Limpar Filtro</a>
        </div>
    </form>
    <!-- Tabela de Rituais -->
    <h2>Rituais</h2>
    <table class="styled-table">
        <thead>
            <tr>
                <th class="col-foto-ritual">Foto</th>
                <th class="col-nome-ritual">
                    <a href="?pagina=<?= $pagina ?>&id=<?= $id ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&order_by=nome&order_dir=<?= $order_by === 'nome' && $order_dir === 'ASC' ? 'DESC' : 'ASC' ?>" class="sortable-header">
                        Nome do Ritual
                        <?php if ($order_by === 'nome'): ?>
                            <span class="order-icon"><?= $order_dir === 'ASC' ? '▼' : '▲' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th class="col-data-ritual">
                    <a href="?pagina=<?= $pagina ?>&id=<?= $id ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&order_by=data_ritual&order_dir=<?= $order_by === 'data_ritual' && $order_dir === 'ASC' ? 'DESC' : 'ASC' ?>" class="sortable-header">
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
                        <img
                            src="<?= htmlspecialchars($ritual['foto']) ?>"
                            alt="Foto do Ritual"
                            class="square-image clickable"
                            onclick="openImageModal('<?= htmlspecialchars($ritual['foto']) ?>')"
                            onerror="this.src='assets/images/no-image.png'; this.onclick=null; this.classList.remove('clickable');">
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
                        <button
                            class="presence-btn <?= $ritual['presente'] === 'Sim' ? 'active' : '' ?>"
                            data-ritual-id="<?= $ritual['id'] ?>"
                            data-current-status="<?= $ritual['presente'] ?>"
                            onclick="togglePresenca(this)">
                            <?= htmlspecialchars($ritual['presente']) ?>
                        </button>
                    </td>
                    <td class="col-acoes-ritual">
                        <a href="#" class="action-icon" title="Observação do participante neste ritual" onclick="abrirModalObservacao(<?= $ritual['id'] ?>)">
                            <i class="fa-solid fa-comment-medical"></i>
                        </a>
                        <a href="#" class="action-icon" title="Detalhes da inscrição no ritual" onclick="abrirModalDetalhes(<?= $ritual['id'] ?>)">
                            <i class="fa-solid fa-info-circle"></i>
                        </a>
                        <a href="participante-excluir-ritual.php?id=<?= $ritual['id'] ?>" class="action-icon danger" title="Remover participante do ritual" onclick="return confirm('Tem certeza que deseja remover este ritual do participante?')">
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
            <a href="?pagina=<?= $i ?>&id=<?= $id ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&order_by=<?= $order_by ?>&order_dir=<?= $order_dir ?>" class="<?= $pagina == $i ? 'active' : '' ?>"><?= $i ?></a>
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
                <input
                    type="text"
                    id="nome_pesquisa"
                    name="nome_pesquisa"
                    placeholder="Digite o nome do ritual"
                    oninput="aplicarMascaraRitual(this)">
                <div class="button-container">
                    <button type="button" id="pesquisar-btn" onclick="pesquisarRituais()">Pesquisar</button>
                    <button type="button" id="limpar-pesquisa-btn" onclick="limparPesquisa()" style="display: none;">Limpar pesquisa</button>
                </div>
            </form>
            <!-- Área para exibir os resultados da pesquisa -->
            <div id="resultados-pesquisa" class="scrollable-list" style="display: none;">
                <h3>Resultados</h3>
                <ul id="lista-rituais"></ul>
                <!-- Botão para adicionar novo ritual -->
                <button id="btn-adicionar-novo-ritual" style="display: none;" onclick="adicionarNovoRitual()">Adicionar novo ritual</button>
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
                    <li><strong>Sexo:</strong> <?= htmlspecialchars($pessoa['sexo'] === 'M' ? 'Masculino' : 'Feminino') ?></li>
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
                    <li><strong>Como soube do Instituto Céu Interior?</strong> <?= htmlspecialchars($pessoa['como_soube']) ?></li>
                    <li><strong>Sobre o Participante:</strong> <?= htmlspecialchars($pessoa['sobre_participante']) ?></li>
                    <br>
                    <a href="participante-editar.php?id=<?= $pessoa['id'] ?>&redirect=participante-visualizar.php" class="action-icon" title="Editar dados do participante">
                        <i class="fa-solid fa-pen-to-square"></i>
                        Editar dados do participante
                    </a>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    // Função para abrir o modal de detalhes da inscrição
    function abrirModalDetalhes(ritualId) {
        // Limpa todos os campos do formulário
        document.getElementById('id').value = '';
        document.querySelector('select[name="primeira_vez_instituto"]').value = '';
        document.querySelector('select[name="primeira_vez_ayahuasca"]').value = '';
        document.querySelector('select[name="doenca_psiquiatrica"]').value = '';
        document.querySelector('input[name="nome_doenca"]').value = '';
        document.querySelector('select[name="uso_medicao"]').value = '';
        document.querySelector('input[name="nome_medicao"]').value = '';
        document.querySelector('textarea[name="mensagem"]').value = '';

        // Busca o ID da inscrição via AJAX
        fetch(`buscar-id-inscricao.php?participante_id=<?= $id ?>&ritual_id=${ritualId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                const inscricaoId = data.inscricao_id;

                // Preenche o ID da inscrição no formulário
                document.getElementById('id').value = inscricaoId;

                // Busca os detalhes da inscrição
                fetch(`carregar-detalhes-inscricao.php?id=${inscricaoId}`)
                    .then(response => response.json())
                    .then(detalhes => {
                        if (detalhes.error) {
                            alert(detalhes.error);
                            return;
                        }

                        // Preenche os campos do formulário com os dados retornados
                        document.querySelector('select[name="primeira_vez_instituto"]').value = detalhes.primeira_vez_instituto || '';
                        document.querySelector('select[name="primeira_vez_ayahuasca"]').value = detalhes.primeira_vez_ayahuasca || '';
                        document.querySelector('select[name="doenca_psiquiatrica"]').value = detalhes.doenca_psiquiatrica || '';
                        document.querySelector('input[name="nome_doenca"]').value = detalhes.nome_doenca || '';
                        document.querySelector('select[name="uso_medicao"]').value = detalhes.uso_medicao || '';
                        document.querySelector('input[name="nome_medicao"]').value = detalhes.nome_medicao || '';
                        document.querySelector('textarea[name="mensagem"]').value = detalhes.mensagem || '';

                        // Preenche a data de salvamento (se existir)
                        const salvoEm = detalhes.salvo_em ?
                            new Date(detalhes.salvo_em).toLocaleDateString('pt-BR') : 'Nunca salvo';
                        document.getElementById('salvo_em').value = salvoEm;
                    })
                    .catch(error => console.error('Erro ao carregar detalhes:', error));
            })
            .catch(error => console.error('Erro ao buscar ID da inscrição:', error));

        // Exibe a modal
        document.getElementById('modal-detalhes-inscricao').style.display = 'flex';
    }
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById('form-detalhes-inscricao');

        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Impede o envio tradicional do formulário

            // Captura os dados do formulário
            const formData = new FormData(form);
            const inscricaoId = formData.get('id');

            // Envia os dados via AJAX
            fetch('salvar-detalhes-inscricao.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Detalhes salvos com sucesso!");
                        // Fecha o modal
                        document.getElementById('modal-detalhes-inscricao').style.display = 'none';
                        // Atualiza a tabela (opcional)
                        location.reload();
                    } else {
                        alert("Erro ao salvar detalhes: " + data.error);
                    }
                })
                .catch(error => {
                    console.error('Erro ao enviar requisição:', error);
                    alert("Erro ao salvar detalhes. Por favor, tente novamente.");
                });
        });
    });
    // Função para fechar o modal de detalhes da inscrição
    function fecharModalDetalhes() {
        document.getElementById('modal-detalhes-inscricao').style.display = 'none';
    }
    // Função para abrir o modal de observação
    function abrirModalObservacao(ritualId) {
        // Busca o ID da inscrição via AJAX
        fetch(`buscar-id-inscricao.php?participante_id=<?= $id ?>&ritual_id=${ritualId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                const inscricaoId = data.inscricao_id;
                // Preenche o ID da inscrição no formulário
                document.getElementById('inscricao_id_observacao').value = inscricaoId;
                // Busca os detalhes da inscrição
                fetch(`carregar-detalhes-inscricao.php?id=${inscricaoId}`)
                    .then(response => response.json())
                    .then(detalhes => {
                        if (detalhes.error) {
                            alert(detalhes.error);
                            return;
                        }
                        // Preenche o campo de observação com o valor salvo no banco
                        document.querySelector('textarea[name="observacao"]').value = detalhes.observacao || '';
                        // Preenche a data de salvamento (se existir)
                        const obsSalvoEm = detalhes.obs_salvo_em ?
                            new Date(detalhes.obs_salvo_em).toLocaleDateString('pt-BR') // Formato "DD/MM/YYYY"
                            :
                            'Nunca salvo';
                        document.getElementById('obs_salvo_em').value = obsSalvoEm;
                    })
                    .catch(error => console.error('Erro ao carregar detalhes:', error));
                // Exibe a modal
                document.getElementById('modal-observacao').style.display = 'flex';
            })
            .catch(error => console.error('Erro ao buscar ID da inscrição:', error));
    }

    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById('form-observacao');

        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Impede o envio tradicional do formulário

            // Captura os dados do formulário
            const formData = new FormData(form);
            const inscricaoId = formData.get('inscricao_id');
            const observacao = formData.get('observacao');

            // Verifica se a observação está vazia
            if (!observacao.trim()) {
                alert("A observação não pode estar vazia.");
                return;
            }

            // Envia os dados via AJAX
            fetch('salvar-observacao.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Observação salva com sucesso!");
                        // Fecha o modal
                        document.getElementById('modal-observacao').style.display = 'none';
                        // Atualiza a tabela (opcional)
                        location.reload();
                    } else {
                        alert("Erro ao salvar observação: " + data.error);
                    }
                })
                .catch(error => {
                    console.error('Erro ao enviar requisição:', error);
                    alert("Erro ao salvar observação. Por favor, tente novamente.");
                });
        });
    });
    // Função para fechar o modal de observação
    function fecharModalObservacao() {
        document.getElementById('modal-observacao').style.display = 'none';
    }

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
    // Função para alternar a presença (Sim/Não)
    function togglePresenca(button) {
        const ritualId = button.getAttribute('data-ritual-id'); // ID do ritual
        const currentStatus = button.getAttribute('data-current-status'); // Status atual (Sim/Não)
        const newStatus = currentStatus === 'Sim' ? 'Não' : 'Sim'; // Alterna entre Sim/Não
        // Busca o ID da inscrição via AJAX
        fetch(`buscar-id-inscricao.php?participante_id=<?= $id ?>&ritual_id=${ritualId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                const inscricaoId = data.inscricao_id;
                // Envia a requisição AJAX para atualizar o status no banco de dados
                fetch(`atualizar-presenca.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            inscricao_id: inscricaoId,
                            novo_status: newStatus
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Atualiza o botão visualmente
                            button.textContent = newStatus;
                            button.classList.toggle('active'); // Alterna a classe CSS
                            button.setAttribute('data-current-status', newStatus); // Atualiza o atributo
                        } else {
                            alert('Erro ao atualizar presença: ' + data.error);
                        }
                    })
                    .catch(error => console.error('Erro ao atualizar presença:', error));
            })
            .catch(error => console.error('Erro ao buscar ID da inscrição:', error));
    }
    // Função para pesquisar rituais
    function pesquisarRituais() {
        const nomePesquisa = document.getElementById('nome_pesquisa').value.trim();
        if (!nomePesquisa) {
            alert("Digite um nome para pesquisar.");
            return;
        }
        // Mostra o botão "Limpar Pesquisa"
        const limparPesquisaBtn = document.getElementById('limpar-pesquisa-btn');
        limparPesquisaBtn.style.display = 'inline-block';
        // Exibe os resultados (simulação)
        const resultadosPesquisa = document.getElementById('resultados-pesquisa');
        resultadosPesquisa.style.display = 'block';
        // Limpa a lista de resultados
        const listaRituais = document.getElementById('lista-rituais');
        listaRituais.innerHTML = '';
        // Exibe a área de resultados
        document.getElementById('resultados-pesquisa').style.display = 'block';
        // Envia a requisição AJAX para buscar os rituais
        fetch(`ritual-buscar.php?nome=${encodeURIComponent(nomePesquisa)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                if (data.length === 0) {
                    // Se nenhum ritual for encontrado, exibe o botão "Adicionar Novo Ritual"
                    listaRituais.innerHTML = `
                    <li>Nenhum ritual encontrado.</li>
                    <li>
                        <button class="add-new-btn" onclick="adicionarNovoRitual()">Adicionar Novo Ritual</button>
                    </li>
                `;
                    return;
                }
                // Preenche a lista com os rituais encontrados
                data.forEach(ritual => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                    <img src="${ritual.foto || 'assets/images/no-image.png'}" alt="Foto">
                    <span>${ritual.nome}</span>
                    <button class="add-btn" onclick="adicionarRitual(${ritual.id})">Adicionar</button>
                `;
                    listaRituais.appendChild(li);
                });
                const li = document.createElement('ul');
                li.innerHTML = `
                <br>
                    <h3>Não encontrou o ritual?</h3><br>
                    <button class="add-new-btn" onclick="adicionarNovoRitual()">Adicionar Novo Ritual</button>                  
                `;
                listaRituais.appendChild(li);
            })
            .catch(error => console.error('Erro ao buscar rituais:', error));
    }
    // Função para limpar a pesquisa
    function limparPesquisa() {
        // Limpa o campo de pesquisa
        const nomePesquisa = document.getElementById('nome_pesquisa');
        nomePesquisa.value = '';
        // Remove os resultados da lista
        const listaRituais = document.getElementById('lista-rituais');
        listaRituais.innerHTML = '';
        // Oculta a área de resultados
        const resultadosPesquisa = document.getElementById('resultados-pesquisa');
        resultadosPesquisa.style.display = 'none';
        // Oculta o botão "Limpar Pesquisa"
        const limparPesquisaBtn = document.getElementById('limpar-pesquisa-btn');
        limparPesquisaBtn.style.display = 'none';
    }
    // Função para capturar o evento de pressionar Enter no campo de pesquisa
    document.getElementById('pesquisa-ritual-form').addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault(); // Impede o envio do formulário
            pesquisarRituais(); // Chama a função de pesquisa
        }
    });
    // Função para redirecionar para a página de cadastro de novo ritual
    function adicionarNovoRitual() {
        const participanteId = document.querySelector('#modal-adicionar input[name="participante_id"]').value;
        window.location.href = `ritual-novo.php?redirect=participante-visualizar.php&id=${participanteId}`;
    }

    // Função para adicionar um ritual ao participante
    function adicionarRitual(ritualId) {
        const participanteId = document.querySelector('#modal-adicionar input[name="participante_id"]').value;
        // Envia a requisição AJAX para adicionar o ritual ao participante
        fetch('ritual-adicionar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ritual_id: ritualId,
                    participante_id: participanteId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Ritual adicionado com sucesso!');
                    location.reload(); // Recarrega a página para atualizar a lista de rituais
                } else {
                    alert('Erro ao adicionar ritual: ' + data.error);
                }
            })
            .catch(error => console.error('Erro ao adicionar ritual:', error));
    }
    // Função para fechar o modal
    function fecharModalAdicionar() {
        document.getElementById('modal-adicionar').style.display = 'none';
        document.getElementById('resultados-pesquisa').style.display = 'none';
        document.getElementById('lista-rituais').innerHTML = '';
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Função para habilitar/desabilitar o campo "Nome da doença"
        function toggleNomeDoenca() {
            const doencaPsiquiatrica = document.getElementById("doenca_psiquiatrica");
            const nomeDoenca = document.getElementById("nome_doenca");
            if (doencaPsiquiatrica.value === "Sim") {
                nomeDoenca.disabled = false;
                nomeDoenca.required = true; // Torna o campo obrigatório se "Sim" for selecionado
            } else {
                nomeDoenca.disabled = true;
                nomeDoenca.required = false; // Remove a obrigatoriedade
                nomeDoenca.value = ""; // Limpa o valor do campo
            }
        }
        // Função para habilitar/desabilitar o campo "Nome da medicação"
        function toggleNomeMedicacao() {
            const usoMedicacao = document.getElementById("uso_medicao");
            const nomeMedicacao = document.getElementById("nome_medicao");
            if (usoMedicacao.value === "Sim") {
                nomeMedicacao.disabled = false;
                nomeMedicacao.required = true; // Torna o campo obrigatório se "Sim" for selecionado
            } else {
                nomeMedicacao.disabled = true;
                nomeMedicacao.required = false; // Remove a obrigatoriedade
                nomeMedicacao.value = ""; // Limpa o valor do campo
            }
        }
        // Monitorar mudanças no campo "Possui doença psiquiátrica diagnosticada?"
        document.getElementById("doenca_psiquiatrica").addEventListener("change", toggleNomeDoenca);
        // Monitorar mudanças no campo "Faz uso de alguma medicação?"
        document.getElementById("uso_medicao").addEventListener("change", toggleNomeMedicacao);
        // Executar as funções ao carregar a página para garantir o estado inicial correto
        toggleNomeDoenca();
        toggleNomeMedicacao();
    });
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

    // Função para abrir a modal de cadastro
    function abrirModalCadastro() {
        document.getElementById('modal-cadastro').style.display = 'flex';
    }

    // Função para fechar a modal de cadastro
    function fecharModalCadastro() {
        document.getElementById('modal-cadastro').style.display = 'none';
    }

    // Fechar modal ao clicar fora do conteúdo
    document.addEventListener("DOMContentLoaded", function() {
        const modals = document.querySelectorAll(".modal");
        modals.forEach(modal => {
            modal.addEventListener("click", function(event) {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            });
        });
    });
</script>
<?php require_once 'includes/footer.php'; ?>