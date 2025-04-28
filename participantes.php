<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
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
$filtro_cpf = isset($_GET['filtro_cpf']) ? $_GET['filtro_cpf'] : '';
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : ''; // Novo filtro para mobile

// Ordenação
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'nome_completo'; // Coluna padrão: nome_completo
$order_dir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'ASC'; // Direção padrão: ASC

$where = "";
$params = [];
if (!empty($filtro_nome)) {
    $where .= " AND nome_completo LIKE ?";
    $params[] = "%$filtro_nome%";
}
if (!empty($filtro_cpf)) {
    $where .= " AND cpf LIKE ?";
    $params[] = "%$filtro_cpf%";
}
if (!empty($filtro)) {
    // Para o filtro mobile, busca tanto por nome quanto por CPF
    $where .= " AND (nome_completo LIKE ? OR cpf LIKE ?)";
    $params[] = "%$filtro%";
    $params[] = "%$filtro%";
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
            <a href="participante-novo" class="btn novo-participante">Novo participante</a>
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
            <label for="filtro_cpf">CPF:</label>
            <input type="text" name="filtro_cpf" id="filtro_cpf" placeholder="___.___.___-__" value="<?= htmlspecialchars($filtro_cpf) ?>" oninput="mascaraCPF(this)">
        </div>
        <div class="filter-actions">
            <button type="submit" class="filter-btn">Filtrar</button>
            <a href="participantes" class="filter-btn clear-btn">Limpar filtro</a>
        </div>
    </form>
    <!-- Filtro para Mobile -->
    <form method="GET" class="filters-mobile">
        <div class="search-container">
            <!-- Input único para pesquisa por Nome ou CPF -->
            <input
                type="text"
                name="filtro"
                id="filtro"
                placeholder="Pesquisar por nome ou CPF..."
                value="<?= htmlspecialchars($filtro ?? '') ?>"
                oninput="aplicarMascaraCPF(this)"
                autocomplete="off">
            <!-- Botão Pesquisar -->
            <button type="submit" class="search-btn">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
            <!-- Botão Limpar (inicialmente oculto) -->
            <button type="button" class="clear-btn" onclick="limparPesquisa()" style="display: none;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </form>

    <!-- Lista de Participantes (Desktop) -->
    <table class="styled-table">
        <thead>
            <tr>
                <th class="col-foto-pessoa">Foto</th>
                <th class="col-nome-pessoa">
                    <a href="?pagina=<?= $pagina ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&order_by=nome_completo&order_dir=<?= $order_by === 'nome_completo' && $order_dir === 'ASC' ? 'DESC' : 'ASC' ?>" class="sortable-header">
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
                    <a href="?pagina=<?= $pagina ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&order_by=rituais_participados&order_dir=<?= $order_by === 'rituais_participados' && $order_dir === 'ASC' ? 'DESC' : 'ASC' ?>" class="sortable-header">
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
                        <img
                            src="<?= htmlspecialchars($pessoa['foto']) ?>"
                            alt="Foto"
                            class="square-image clickable"
                            onclick="openImageModal('<?= htmlspecialchars($pessoa['foto']) ?>')"
                            onerror="this.src='assets/images/no-image.png'; this.onclick=null; this.classList.remove('clickable');">
                    </td>
                    <td class="col-nome-pessoa">
                        <a href="participante-visualizar?id=<?= $pessoa['id'] ?>" title="Gerenciar rituais"><?= htmlspecialchars($pessoa['nome_completo']) ?></a>
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
                        <a href="participante-visualizar?id=<?= $pessoa['id'] ?>" class="action-icon" title="Gerenciar rituais">
                            <i class="fa-solid fa-list-check"></i>
                        </a>
                        <a href="participante-editar?id=<?= $pessoa['id'] ?>" class="action-icon" title="Editar dados do participante">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <a href="participante-excluir?id=<?= $pessoa['id'] ?>" class="action-icon danger" title="Excluir participante" onclick="return confirm('Tem certeza que deseja remover este participante permanentemente e desvincular de todos os rituais?')">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="table-responsive-cards-wrapper">
        <div class="table-responsive-cards">
            <?php foreach ($pessoas as $pessoa): ?>
                <div class="card">
                    <!-- Primeira linha: Foto e Nome -->
                    <div class="card-header">
                        <img
                            src="<?= htmlspecialchars($pessoa['foto']) ?>"
                            alt="Foto"
                            class="small-preview"
                            onclick="openImageModal('<?= htmlspecialchars($pessoa['foto']) ?>')"
                            onerror="this.src='assets/images/no-image.png'; this.onclick=null; this.classList.remove('clickable');">
                        <span class="nome-completo"><?= htmlspecialchars($pessoa['nome_completo']) ?></span>
                    </div>

                    <!-- Segunda linha: Data de Nascimento -->
                    <div class="card-row">
                        <strong>Data de Nascimento:</strong>
                        <span>
                            <?php
                            // Formata a data para DD/MM/AAAA
                            $nascimento = new DateTime($pessoa['nascimento']);
                            echo $nascimento->format('d/m/Y');
                            ?>
                        </span>
                    </div>

                    <!-- Terceira linha: CPF -->
                    <div class="card-row">
                        <strong>CPF:</strong>
                        <span><?= formatarCPF(htmlspecialchars($pessoa['cpf'])) ?></span>
                    </div>

                    <!-- Quarta linha: Rituais Participados -->
                    <div class="card-row">
                        <strong>Rituais Participados:</strong>
                        <span><?= htmlspecialchars($pessoa['rituais_participados']) ?></span>
                    </div>

                    <!-- Ícones de Ação (Botões) -->
                    <div class="card-actions">
                        <a href="participante-visualizar?id=<?= $pessoa['id'] ?>" class="action-icon-mobile" title="Gerenciar rituais">
                            <i class="fa-solid fa-list-check"></i>
                        </a>
                        <a href="participante-editar?id=<?= $pessoa['id'] ?>" class="action-icon-mobile" title="Editar dados do participante">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <a href="participante-excluir?id=<?= $pessoa['id'] ?>" class="action-icon-mobile danger" title="Excluir participante" onclick="return confirm('Tem certeza que deseja remover este participante permanentemente e desvincular de todos os rituais?')">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

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

    <div class="page-bottom">
        <div class="actions">
            <div class="left-actions">
                <?php
                // Verifica se há um parâmetro 'redirect' na URL
                $redirect = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'home';
                ?>
                <a href="<?= $redirect ?>" class="btn voltar">Voltar</a>
            </div>
            <div class="right-actions">
                <a href="participante-novo" class="btn novo-participante">Novo participante</a>
            </div>
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

        // Função para aplicar máscara no CPF
        function mascaraCPF(input) {
            let valor = input.value.replace(/\D/g, ''); // Remove tudo que não é número
            if (valor.length > 11) valor = valor.slice(0, 11); // Limita a 11 dígitos
            valor = valor.replace(/(\d{3})(\d)/, '$1.$2'); // Adiciona o primeiro ponto
            valor = valor.replace(/(\d{3})(\d)/, '$1.$2'); // Adiciona o segundo ponto
            valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2'); // Adiciona o traço
            input.value = valor;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const cpfInput = document.getElementById('filtro_cpf');
            if (cpfInput && cpfInput.value) {
                // Reaplica a máscara ao valor preenchido
                cpfInput.value = cpfInput.value.replace(/\D/g, ''); // Remove máscara temporariamente
                mascaraCPF(cpfInput); // Reaplica a máscara
            }
        });

        // Função para remover máscara antes de enviar o formulário
        document.querySelector('form.filters-mobile').addEventListener('submit', function(event) {
            const filtroInput = document.getElementById('filtro');
            if (filtroInput) {
                // Verifica se o valor parece ser um CPF (contém apenas números após a remoção de máscara)
                const valorSemMascara = filtroInput.value.replace(/\D/g, '');
                if (/^\d+$/.test(valorSemMascara)) {
                    filtroInput.value = valorSemMascara; // Remove a máscara
                }
            }
        });

        // Máscara para CPF
        function aplicarMascaraCPF(input) {
            let valor = input.value;

            // Verifica se o valor contém apenas números
            if (/^\d+$/.test(valor.replace(/\D/g, ''))) {
                // Aplica a máscara de CPF
                valor = valor.replace(/\D/g, ''); // Remove tudo que não é número
                if (valor.length > 11) valor = valor.slice(0, 11); // Limita a 11 dígitos
                valor = valor.replace(/(\d{3})(\d)/, '$1.$2'); // Adiciona o primeiro ponto
                valor = valor.replace(/(\d{3})(\d)/, '$1.$2'); // Adiciona o segundo ponto
                valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2'); // Adiciona o traço
                input.value = valor;
            } else {
                // Não aplica a máscara se o valor contiver letras
                input.value = valor;
            }
        }

        // Função para remover máscara antes de enviar o formulário
        document.querySelector('form.filters').addEventListener('submit', function(event) {
            const cpfInput = document.getElementById('filtro_cpf');
            if (cpfInput) {
                cpfInput.value = cpfInput.value.replace(/\D/g, ''); // Remove tudo que não é número
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            const filtroInput = document.getElementById('filtro');
            const clearBtn = document.querySelector('.filters-mobile .clear-btn');

            // Mostra ou oculta o botão "Limpar" com base no valor do input
            filtroInput.addEventListener('input', function() {
                if (filtroInput.value.trim() !== '') {
                    clearBtn.style.display = 'inline-block'; // Mostra o botão "X"
                } else {
                    clearBtn.style.display = 'none'; // Oculta o botão "X"
                }
            });

            // Função para limpar a pesquisa
            window.limparPesquisa = function() {
                filtroInput.value = ''; // Limpa o campo de pesquisa
                clearBtn.style.display = 'none'; // Oculta o botão "X"

                // Recarrega a página para mostrar todos os itens novamente
                location.href = location.pathname; // Redireciona para a mesma página sem parâmetros de pesquisa
            };

            // Verifica se há um valor pré-existente no campo de pesquisa ao carregar a página
            if (filtroInput.value.trim() !== '') {
                clearBtn.style.display = 'inline-block'; // Mantém o botão "X" visível se houver um valor
            }
        });
    </script>

    <?php require_once 'includes/footer.php'; ?>