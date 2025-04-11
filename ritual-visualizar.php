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
    <!-- Cabeçalho com foto, nome, data e padrinho/madrinha -->
    <div class="ritual-header">
        <img src="<?= htmlspecialchars($ritual['foto']) ?>" alt="Foto do Ritual" class="medium-image" onerror="this.src='assets/images/no-image.png';">
        <div class="details">
            <h1><?= htmlspecialchars($ritual['nome']) ?></h1>
            <p><strong>Data:</strong> <?= htmlspecialchars($ritual['data_ritual']) ?></p>
            <p><strong>Padrinho/Madrinha:</strong> <?= htmlspecialchars($ritual['padrinho_madrinha']) ?></p>
        </div>
    </div>

    <!-- Botões Voltar e Adicionar Participante -->
    <div class="actions">
        <a href="rituais.php" class="btn voltar">Voltar</a>
        <button class="btn adicionar" onclick="document.getElementById('modal-adicionar').style.display='block'">Adicionar Participante</button>
    </div>

    <!-- Filtro por Nome -->
    <form method="GET" class="filters">
        <div class="filter-group">
            <label for="filtro_nome">Nome:</label>
            <input type="text" name="filtro_nome" id="filtro_nome" placeholder="Filtrar por nome" value="<?= htmlspecialchars($_GET['filtro_nome'] ?? '') ?>">
        </div>
        <div class="filter-actions">
            <!-- Campo oculto para enviar o ID do ritual -->
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="filter-btn">Filtrar</button>
            <a href="ritual-visualizar.php?id=<?= $id ?>" class="filter-btn clear-btn">Limpar Filtro</a>
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
                        <img
                            src="<?= htmlspecialchars($participante['foto']) ?>"
                            alt="Foto do Participante"
                            class="square-image clickable"
                            onclick="openImageModal('<?= htmlspecialchars($participante['foto']) ?>')"
                            onerror="this.src='assets/images/no-image.png'; this.onclick=null; this.classList.remove('clickable');">
                    </td>
                    <td class="col-nome-participante"><?= htmlspecialchars($participante['nome_completo']) ?></td>
                    <td class="col-observacao"><?= htmlspecialchars($participante['observacao'] ?? '') ?></td>
                    <td class="col-presente">
                        <button
                            class="presence-btn <?= $participante['presente'] === 'Sim' ? 'active' : '' ?>"
                            data-participante-id="<?= $participante['id'] ?>"
                            data-current-status="<?= $participante['presente'] ?>"
                            onclick="togglePresenca(this)">
                            <?= htmlspecialchars($participante['presente']) ?>
                        </button>
                    </td>
                    <td class="col-acoes-participante">
                        <a href="#" class="action-icon" title="Detalhes da Inscrição" onclick="abrirModalDetalhes(<?= $participante['id'] ?>)">
                            <i class="fa-solid fa-info-circle"></i>
                        </a>
                        <a href="#" class="action-icon" title="Observação" onclick="abrirModalObservacao(<?= $participante['id'] ?>)">
                            <i class="fa-solid fa-comment-medical"></i>
                        </a>
                        <a href="participante-excluir.php?id=<?= $participante['id'] ?>" class="action-icon danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este participante?')">
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
            <h2>Adicionar Participante</h2>
            <form id="pesquisa-participante-form" onsubmit="return false;">
                <input type="hidden" name="ritual_id" value="<?= $id ?>">
                <label for="nome_pesquisa">Pesquisar Participante:</label>
                <input type="text" id="nome_pesquisa" name="nome_pesquisa" placeholder="Digite o nome">
                <button type="button" onclick="pesquisarParticipantes()">Pesquisar</button>
            </form>
            <!-- Área para exibir os resultados da pesquisa -->
            <div id="resultados-pesquisa" class="scrollable-list" style="display: none;">
                <h3>Resultados</h3>
                <ul id="lista-participantes"></ul>
                <!-- Botão para adicionar nova pessoa -->
                <button id="btn-adicionar-nova-pessoa" style="display: none;" onclick="adicionarNovaPessoa()">Adicionar Nova Pessoa</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalhes da Inscrição -->
<div id="modal-detalhes-inscricao" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <span class="close" onclick="fecharModalDetalhes()">&times;</span>
            <h2>Detalhes da Inscrição</h2>
            <form method="POST" action="salvar-detalhes-inscricao.php">
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
                <select name="doenca_psiquiatrica" required>
                    <option value="">Selecione...</option>
                    <option value="Sim">Sim</option>
                    <option value="Não">Não</option>
                </select>

                <!-- Nome da doença -->
                <label for="nome_doenca">Se sim, escreva o nome da doença:</label>
                <input type="text" name="nome_doenca" value="">

                <!-- Uso de medicação -->
                <label for="uso_medicao">Faz uso de alguma medicação?</label>
                <select name="uso_medicao" required>
                    <option value="">Selecione...</option>
                    <option value="Sim">Sim</option>
                    <option value="Não">Não</option>
                </select>

                <!-- Nome da medicação -->
                <label for="nome_medicao">Se sim, escreva o nome da medicação:</label>
                <input type="text" name="nome_medicao" value="">

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
            <h2>Adicionar Observação</h2>
            <form method="POST" action="salvar-observacao.php">
                <!-- Campo oculto para o ID da inscrição -->
                <input type="hidden" id="inscricao_id_observacao" name="inscricao_id">

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

<script>
    // Função para abrir o modal de detalhes da inscrição
    function abrirModalDetalhes(participanteId) {
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
        fetch(`buscar-id-inscricao.php?participante_id=${participanteId}&ritual_id=<?= $id ?>`)
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
                            new Date(detalhes.salvo_em).toLocaleDateString('pt-BR') // Formato "DD/MM/YYYY"
                            :
                            'Nunca salvo';
                        document.getElementById('salvo_em').value = salvoEm;
                    })
                    .catch(error => console.error('Erro ao carregar detalhes:', error));
            })
            .catch(error => console.error('Erro ao buscar ID da inscrição:', error));

        // Exibe a modal
        document.getElementById('modal-detalhes-inscricao').style.display = 'block';
    }

    // Função para fechar o modal de detalhes da inscrição
    function fecharModalDetalhes() {
        document.getElementById('modal-detalhes-inscricao').style.display = 'none';
    }

    // Função para abrir o modal de observação
    function abrirModalObservacao(participanteId) {
        // Busca o ID da inscrição via AJAX
        fetch(`buscar-id-inscricao.php?participante_id=${participanteId}&ritual_id=<?= $id ?>`)
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
                document.getElementById('modal-observacao').style.display = 'block';
            })
            .catch(error => console.error('Erro ao buscar ID da inscrição:', error));
    }

    // Função para fechar o modal de observação
    function fecharModalObservacao() {
        document.getElementById('modal-observacao').style.display = 'none';
    }

    // Função para ordenar a tabela (simulação)
    function ordenarPor(coluna) {
        alert(`Ordenar por ${coluna}`);
        // Implementar lógica de ordenação aqui (pode ser via JavaScript ou PHP)
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
        const participanteId = button.getAttribute('data-participante-id'); // ID do participante
        const currentStatus = button.getAttribute('data-current-status'); // Status atual (Sim/Não)
        const newStatus = currentStatus === 'Sim' ? 'Não' : 'Sim'; // Alterna entre Sim/Não

        // Busca o ID da inscrição via AJAX
        fetch(`buscar-id-inscricao.php?participante_id=${participanteId}&ritual_id=<?= $id ?>`)
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

    // Função para pesquisar participantes
    function pesquisarParticipantes() {
        const nomePesquisa = document.getElementById('nome_pesquisa').value.trim();
        if (!nomePesquisa) {
            alert("Digite um nome para pesquisar.");
            return;
        }

        // Limpa a lista de resultados
        const listaParticipantes = document.getElementById('lista-participantes');
        listaParticipantes.innerHTML = '';

        // Exibe a área de resultados
        document.getElementById('resultados-pesquisa').style.display = 'block';

        // Envia a requisição AJAX para buscar os participantes
        fetch(`participante-buscar.php?nome=${encodeURIComponent(nomePesquisa)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                if (data.length === 0) {
                    // Se nenhum participante for encontrado, exibe o botão "Adicionar Nova Pessoa"
                    listaParticipantes.innerHTML = `
                    <li>Nenhum participante encontrado.</li>
                    <li>
                        <button class="add-new-btn" onclick="adicionarNovaPessoa()">Adicionar Nova Pessoa</button>
                    </li>
                `;
                    return;
                }

                // Preenche a lista com os participantes encontrados
                data.forEach(participante => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                    <img src="${participante.foto || 'assets/images/no-image.png'}" alt="Foto">
                    <span>${participante.nome_completo}</span>
                    <button class="add-btn" onclick="adicionarParticipante(${participante.id})">Adicionar</button>
                `;
                    listaParticipantes.appendChild(li);
                });
            })
            .catch(error => console.error('Erro ao buscar participantes:', error));
    }

    // Função para capturar o evento de pressionar Enter no campo de pesquisa
    document.getElementById('pesquisa-participante-form').addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault(); // Impede o envio do formulário
            pesquisarParticipantes(); // Chama a função de pesquisa
        }
    });

    // Função para redirecionar para a página de cadastro de nova pessoa
    function adicionarNovaPessoa() {
        const ritualId = document.querySelector('#modal-adicionar input[name="ritual_id"]').value;
        window.location.href = `pessoa-novo.php?redirect=ritual-visualizar.php&id=${ritualId}`;
    }

    // Função para adicionar um participante ao ritual
    function adicionarParticipante(participanteId) {
        const ritualId = document.querySelector('#modal-adicionar input[name="ritual_id"]').value;

        // Envia a requisição AJAX para adicionar o participante ao ritual
        fetch('participante-adicionar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    participante_id: participanteId,
                    ritual_id: ritualId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Participante adicionado com sucesso!');
                    location.reload(); // Recarrega a página para atualizar a lista de participantes
                } else {
                    alert('Erro ao adicionar participante: ' + data.error);
                }
            })
            .catch(error => console.error('Erro ao adicionar participante:', error));
    }

    // Função para fechar o modal
    function fecharModalAdicionar() {
        document.getElementById('modal-adicionar').style.display = 'none';
        document.getElementById('resultados-pesquisa').style.display = 'none';
        document.getElementById('lista-participantes').innerHTML = '';
    }
</script>

<?php require_once 'includes/footer.php'; ?>