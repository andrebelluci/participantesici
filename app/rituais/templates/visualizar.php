<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($ritual)) {
    die("Ritual não encontrado.");
}
?>

<div class="max-w-screen-xl mx-auto px-4 py-6">
    <!-- Cabeçalho com foto, nome e informações do ritual -->
    <div class="bg-white p-6 rounded-lg shadow border border-gray-200 mb-6">
        <div class="flex flex-col md:flex-row items-center md:items-start gap-4 mb-4">
            <img src="<?= htmlspecialchars($ritual['foto']) ?>" alt="Foto do Ritual"
                class="w-24 h-24 md:w-32 md:h-32 rounded-lg object-cover border border-gray-300 cursor-pointer"
                onclick="openImageModal(this.src)"
                onerror="this.src='/assets/images/no-image.png'; this.onclick=null; this.classList.remove('cursor-pointer');">

            <div class="text-center md:text-left flex-1">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
                    <?= htmlspecialchars($ritual['nome']) ?>
                </h1>
                <div class="text-sm text-gray-600 space-y-1">
                    <p><span class="font-semibold">Data:</span>
                        <?= (new DateTime($ritual['data_ritual']))->format('d/m/Y') ?></p>
                    <p><span class="font-semibold">Padrinho/Madrinha:</span>
                        <?= htmlspecialchars($ritual['padrinho_madrinha']) ?></p>
                    <p><span class="font-semibold">Inscritos:</span>
                        <span class="bg-[#00bfff] text-black px-2 py-1 rounded md:text-lg font-medium">
                            <?= count($participantes) ?>
                        </span>
                    </p>
                </div>
            </div>

            <div class="flex gap-2">
                <a href="/ritual/<?= $id ?>/editar?redirect=/ritual/<?= $id ?>"
                    class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-4 py-2 rounded-lg flex items-center gap-2 transition">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Editar ritual</span>
                </a>
            </div>
        </div>

        <!-- Botões de ação -->
        <div class="flex items-center justify-between">
            <a href="/rituais"
                class="flex items-center text-gray-600 hover:text-[#00bfff] transition text-sm">
                <i class="fa-solid fa-arrow-left mr-2"></i> Voltar
            </a>

            <button onclick="abrirModalAdicionar()"
                class="bg-[#00bfff] text-black px-2 md:px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
                <i class="fa-solid fa-plus mr-2"></i> Adicionar participante
            </button>
        </div>
    </div>

    <!-- Título da seção -->
    <div class="flex flex-col sm:flex-row justify-between md:items-end gap-4">
        <h2 class="text-xl font-bold text-gray-800 md:mb-4 flex items-center gap-2">
            <i class="fa-solid fa-users text-blue-500"></i>Participantes do Ritual
        </h2>

        <div class="flex justify-end md:mb-4 gap-2">

            <?php if ($is_admin && $export_id && $export_type): ?>
                <!-- Botão de Exportação (só para admins) -->
                <div class="relative inline-block">
                    <button type="button" id="export-button" onclick="toggleExportDropdown(event)"
                        class="flex items-center justify-center bg-orange-100 text-orange-700 w-10 h-10 rounded hover:bg-orange-200 transition border border-orange-300"
                        title="Exportar relatório">
                        <i class="fa-solid fa-file-export text-lg"></i>
                    </button>

                    <div id="export-dropdown"
                        class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                        <div class="py-2">
                            <div class="px-4 py-2 text-sm font-medium text-gray-700 border-b border-gray-100">
                                Exportar como:
                            </div>
                            <button onclick="exportar<?= ucfirst($export_type) ?>(<?= $export_id ?>, 'pdf')"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <i class="fa-solid fa-file-pdf text-red-500"></i>
                                PDF
                            </button>
                            <button onclick="exportar<?= ucfirst($export_type) ?>(<?= $export_id ?>, 'excel')"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <i class="fa-solid fa-file-excel text-green-500"></i>
                                Excel
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <button type="button" id="view-toggle"
                class="hidden md:flex items-center justify-center bg-gray-100 text-gray-700 w-10 h-10 rounded hover:bg-gray-200 transition border border-gray-300"
                title="Alternar visualização">
                <i class="fa-solid fa-list text-lg"></i>
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="md:hidden flex items-center justify-between">
        <button type="button" onclick="document.getElementById('filtros').classList.toggle('hidden')"
            class="bg-gray-200 text-gray-700 px-4 py-2 rounded mb-4 flex items-center gap-2 text-sm shadow hover:bg-gray-300">
            <i class="fa-solid fa-search"></i> Filtrar
        </button>
        <a href="/ritual/<?= $id ?>"
            class="<?= empty($_GET['filtro_nome']) ? 'hidden' : '' ?> bg-red-600 text-white px-4 py-2 rounded mb-4 flex items-center gap-2 text-sm shadow hover:bg-red-300 transition">
            <i class="fa-solid fa-broom mr-1"></i> Limpar Filtro
        </a>
    </div>

    <div class="form-container mobile-compact">
        <form id="filtros" method="GET"
            class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-white p-3 rounded-lg shadow border border-gray-200 mb-6 <?= empty($_GET['filtro_nome']) ? 'hidden md:grid' : '' ?>">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="md:col-span-2">
                <label for="filtro_nome" class="block text-sm font-medium text-gray-700 mb-1">Nome do
                    Participante:</label>
                <input type="text" name="filtro_nome" id="filtro_nome" placeholder="Filtrar por nome do participante"
                    value="<?= htmlspecialchars($_GET['filtro_nome'] ?? '') ?>"
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
            </div>

            <div class="flex items-end gap-2 md:col-span-2">
                <button type="submit"
                    class="bg-[#00bfff] text-black px-4 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
                    <i class="fa-solid fa-search mr-1"></i> Buscar
                </button>
                <a href="/ritual/<?= $id ?>"
                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition font-semibold shadow">
                    <i class="fa-solid fa-broom mr-1"></i> Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Cards dos Participantes -->
    <div id="cards-view" class="grid gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($participantes as $participante): ?>
            <div class="bg-white p-4 rounded-lg shadow border border-gray-200 flex flex-col gap-3">
                <!-- Header do card com foto e info básica -->
                <div class="flex items-start gap-4">
                    <img src="<?= htmlspecialchars($participante['foto']) ?>" alt="Foto do Participante"
                        class="w-16 h-16 rounded-lg object-cover border border-gray-300 cursor-pointer"
                        onclick="openImageModal('<?= htmlspecialchars($participante['foto']) ?>')"
                        onerror="this.src='/assets/images/no-image.png'; this.onclick=null; this.classList.remove('cursor-pointer');">

                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-gray-800 text-lg mb-1 truncate">
                            <a href="/participantes?pagina=1&filtro_cpf=<?= urlencode(htmlspecialchars($participante['cpf'])) ?>&redirect=/ritual/<?= $id ?>"
                                class="hover:text-[#00bfff] transition">
                                <?= htmlspecialchars($participante['nome_completo']) ?>
                            </a>
                        </h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p><span class="font-semibold">CPF:</span>
                                <?php
                                $cpf = preg_replace('/[^0-9]/', '', $participante['cpf']);
                                echo substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                                ?>
                            </p>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold">Presente:</span>
                                <button
                                    class="presence-btn <?= $participante['presente'] === 'Sim' ? 'active bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' ?> px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 min-w-[80px] flex items-center justify-center gap-2"
                                    data-participante-id="<?= $participante['id'] ?>"
                                    data-current-status="<?= $participante['presente'] ?>" onclick="togglePresenca(this)">

                                    <?php if ($participante['presente'] === 'Sim'): ?>
                                        <i class="fa-solid fa-check"></i>
                                        <span>Sim</span>
                                    <?php else: ?>
                                        <i class="fa-solid fa-xmark"></i>
                                        <span>Não</span>
                                    <?php endif; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção de Observação no card -->
                <div class="border-t pt-3">
                    <p class="text-sm text-gray-600 mb-2 font-semibold">Observação:</p>

                    <?php if (!empty($participante['observacao'])): ?>
                        <!-- Tem observação: mostra preview de 2 linhas + tag -->
                        <div class="space-y-2">
                            <!-- Preview da observação (1 linha) -->
                            <div class="text-sm text-gray-700 line-clamp-1 leading-relaxed">
                                <?= htmlspecialchars($participante['observacao']) ?>
                            </div>

                            <!-- Tag para abrir modal -->
                            <div class="flex items-center justify-between">
                                <button onclick="abrirModalObservacao(<?= $participante['id'] ?>)"
                                    class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-xs font-medium transition-colors">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                    Ver observação completa
                                </button>
                                <span class="text-xs text-gray-500">
                                    (<?= mb_strlen($participante['observacao']) ?> caracteres)
                                </span>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Não tem observação: mostra mensagem e botão para adicionar -->
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-400 italic">Nenhuma observação registrada</p>
                            <button onclick="abrirModalObservacao(<?= $participante['id'] ?>)"
                                class="inline-flex items-center gap-1 px-3 py-1 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-xs font-medium transition-colors">
                                <i class="fa-solid fa-plus text-xs"></i>
                                Adicionar observação
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Ações do card -->
                <div class="flex justify-center gap-6 md:justify-end md:gap-2 mt-2 text-sm border-t pt-3">
                    <!-- Botão Observação -->
                    <button onclick="abrirModalObservacao(<?= $participante['id'] ?>)"
                        class="relative bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded flex items-center gap-1"
                        title="Observação do participante neste ritual">
                        <div class="flex flex-col items-center sm:flex-row sm:gap-1">
                            <i class="fa-solid fa-file-lines text-lg"></i>
                            <span class="block sm:hidden text-xs mt-1">Observação</span>
                        </div>

                        <!-- Bolinha vermelha se não tem observação -->
                        <?php if (empty($participante['observacao'])): ?>
                            <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border border-white"></span>
                        <?php endif; ?>
                    </button>

                    <!-- Botão Detalhes -->
                    <button onclick="abrirModalDetalhes(<?= $participante['id'] ?>)"
                        class="relative bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1 rounded flex items-center gap-1"
                        title="Detalhes da inscrição no ritual">
                        <div class="flex flex-col items-center sm:flex-row sm:gap-1">
                            <i class="fa-solid fa-pencil text-lg"></i>
                            <span class="block sm:hidden text-xs mt-1">Inscrição</span>
                        </div>

                        <!-- Bolinha vermelha se não tem detalhes preenchidos -->
                        <?php
                        $temDetalhes = !empty($participante['salvo_em']);
                        ?>
                        <?php if (!$temDetalhes): ?>
                            <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border border-white"></span>
                        <?php endif; ?>
                    </button>

                    <!-- Botão Desvincular -->
                    <button
                        onclick="abrirConfirmacaoExcluir('/api/inscricoes/excluir-participacao?participante_id=<?= $participante['id'] ?>&ritual_id=<?= $ritual['id'] ?>&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>')"
                        class="bg-orange-100 hover:bg-orange-200 text-orange-700 px-3 py-1 rounded flex items-center gap-1"
                        title="Desvincular participante do ritual">
                        <div class="flex flex-col items-center sm:flex-row sm:gap-1">
                            <i class="fa-solid fa-link-slash text-lg"></i>
                            <span class="block sm:hidden text-xs mt-1">Desvincular</span>
                        </div>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Mensagem quando não há participantes -->
        <?php if (empty($participantes)): ?>
            <div class="col-span-full text-center py-8">
                <?php if (!isset($_GET['filtro_nome']) || empty($_GET['filtro_nome'])): ?>
                    <!-- Ritual sem participantes -->
                    <div class="text-gray-400 mb-4">
                        <i class="fa-solid fa-user-group text-4xl"></i>
                    </div>
                    <p class="text-gray-500 text-lg mb-2">Nenhum participante encontrado</p>
                    <p class="text-gray-400 text-sm">Este ritual ainda não possui participantes inscritos.</p>
                    <button onclick="abrirModalAdicionar()"
                        class="mt-4 bg-[#00bfff] text-black px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
                        <i class="fa-solid fa-plus mr-2"></i> Adicionar primeiro participante
                    </button>
                <?php else: ?>
                    <!-- Filtro não retornou resultados -->
                    <div class="text-orange-400 mb-4">
                        <i class="fa-solid fa-search text-4xl"></i>
                    </div>
                    <p class="text-gray-500 text-lg mb-2">Nenhum participante encontrado com esse nome.</p>
                    <p class="text-gray-400 text-sm">Pesquise novamente, ou adicione o participante pelo botão abaixo.</p>
                    <div class="mt-4 flex flex-col sm:flex-row gap-3 justify-center">
                        <a href="/ritual/<?= $id ?>"
                            class="inline-flex items-center gap-2 bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 transition font-semibold shadow">
                            <i class="fa-solid fa-list"></i> Ver todos os participantes
                        </a>
                        <button onclick="abrirModalAdicionar()"
                            class="inline-flex items-center gap-2 bg-[#00bfff] text-black px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
                            <i class="fa-solid fa-plus"></i> Adicionar participante
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Table View para Participantes do Ritual (adicionar APÓS os cards) -->
    <div id="table-view" class="hidden bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Foto</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Participante</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">CPF</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Presente</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Observação</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($participantes)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <div class="text-center py-8">
                                    <div class="text-gray-400 mb-4">
                                        <i class="fa-solid fa-users text-4xl"></i>
                                    </div>
                                    <p class="text-gray-500 text-lg mb-2">Nenhum participante encontrado</p>
                                    <p class="text-gray-400 text-sm">Este ritual ainda não possui participantes inscritos.
                                    </p>
                                    <button onclick="abrirModalAdicionar()"
                                        class="mt-4 bg-[#00bfff] text-black px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
                                        <i class="fa-solid fa-plus mr-2"></i> Adicionar primeiro participante
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($participantes as $participante): ?>
                            <tr class="hover:bg-gray-50">
                                <!-- Foto -->
                                <td class="px-4 py-3">
                                    <img src="<?= htmlspecialchars($participante['foto']) ?>" alt="Foto do Participante"
                                        class="w-12 h-12 rounded-lg object-cover border border-gray-300 cursor-pointer"
                                        onclick="openImageModal('<?= htmlspecialchars($participante['foto']) ?>')"
                                        onerror="this.src='/assets/images/no-image.png'; this.onclick=null; this.classList.remove('cursor-pointer');">
                                </td>

                                <!-- Nome do Participante -->
                                <td class="px-4 py-3">
                                    <div class="font-bold text-gray-800 text-sm">
                                        <a href="/participantes?pagina=1&filtro_cpf=<?= urlencode(htmlspecialchars($participante['cpf'])) ?>&redirect=/ritual/<?= $id ?>"
                                            class="hover:text-[#00bfff] transition">
                                            <?= htmlspecialchars($participante['nome_completo']) ?>
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?= (new DateTime($participante['nascimento']))->format('d/m/Y') ?>
                                    </div>
                                </td>

                                <!-- CPF -->
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <?php
                                    $cpf = preg_replace('/[^0-9]/', '', $participante['cpf']);
                                    echo substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                                    ?>
                                </td>

                                <!-- Presente -->
                                <td class="px-4 py-3 text-center">
                                    <button
                                        class="presence-btn <?= $participante['presente'] === 'Sim' ? 'active bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' ?> px-3 py-1 rounded-lg text-sm font-medium transition-all duration-200 min-w-[70px] flex items-center justify-center gap-1"
                                        data-participante-id="<?= $participante['id'] ?>"
                                        data-current-status="<?= $participante['presente'] ?>" onclick="togglePresenca(this)">
                                        <?php if ($participante['presente'] === 'Sim'): ?>
                                            <i class="fa-solid fa-check"></i>
                                            <span>Sim</span>
                                        <?php else: ?>
                                            <i class="fa-solid fa-xmark"></i>
                                            <span>Não</span>
                                        <?php endif; ?>
                                    </button>
                                </td>

                                <!-- Observação -->
                                <td class="px-4 py-3">
                                    <?php if (!empty($participante['observacao'])): ?>
                                        <!-- Tem observação: mostra preview de 2 linhas + tag -->
                                        <!-- Preview da observação (1 linha) -->
                                        <div class="text-sm text-gray-700 leading-relaxed">
                                            <a href="javascript:void(0);" onclick="abrirModalObservacao(<?= $participante['id'] ?>)"
                                                class="text-blue-600 hover:underline cursor-pointer font-semibold">
                                                <?= htmlspecialchars(mb_strimwidth($participante['observacao'], 0, 30, '...')) ?>
                                            </a>
                                        </div>
                                        <!-- Tag para abrir modal -->
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-500">
                                                (<?= mb_strlen($participante['observacao']) ?> caracteres)
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <!-- Não tem observação: mostra mensagem e botão para adicionar -->
                                        <div class="flex items-center justify-between">
                                            <button onclick="abrirModalObservacao(<?= $participante['id'] ?>)"
                                                class="inline-flex items-center gap-1 px-3 py-1 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-xs font-medium transition-colors">
                                                <i class="fa-solid fa-plus text-xs"></i>
                                                Adicionar observação
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Ações -->
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center gap-1">
                                        <!-- Botão Observação -->
                                        <button onclick="abrirModalObservacao(<?= $participante['id'] ?>)"
                                            class="relative bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded flex items-center gap-1"
                                            title="Observação do participante neste ritual">
                                            <i class="fa-solid fa-file-lines"></i>
                                            <!-- Bolinha vermelha se não tem observação -->
                                            <?php if (empty($participante['observacao'])): ?>
                                                <span
                                                    class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                                            <?php endif; ?>
                                        </button>

                                        <!-- Botão Detalhes -->
                                        <button onclick="abrirModalDetalhes(<?= $participante['id'] ?>)"
                                            class="relative bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1 rounded flex items-center gap-1"
                                            title="Detalhes da inscrição do participante">
                                            <i class="fa-solid fa-pencil"></i>
                                            <!-- Bolinha vermelha se detalhes estão vazios -->
                                            <?php
                                            $temDetalhes = !empty($participante['salvo_em']);
                                            ?>
                                            <?php if (!$temDetalhes): ?>
                                                <span
                                                    class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                                            <?php endif; ?>
                                        </button>

                                        <!-- Botão Remover -->
                                        <button
                                            onclick="openConfirmModal('Tem certeza que deseja remover este participante do ritual?', () => { window.location.href = '/api/inscricoes/excluir-participacao?ritual_id=<?= $id ?>&participante_id=<?= $participante['id'] ?>&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>' })"
                                            class="bg-orange-100 hover:bg-orange-200 text-orange-700 px-3 py-1 rounded flex items-center gap-1"
                                            title="Remover participante do ritual">
                                            <i class="fa-solid fa-link-slash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Info da listagem -->
    <div class="mt-4 text-sm text-gray-600">
        <?php if (!empty($participantes)): ?>
            <p>
                Mostrando <?= count($participantes) ?> de <?= $total_registros ?> participante(s) do ritual
                <?= $ritual['nome'] ?>
                <?php if (!empty($filtros)): ?>
                    (filtrado)
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Paginação (mesmo padrão do listar.php) -->
    <div class="flex justify-center mt-6 flex-wrap gap-2">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?pagina=<?= $i ?>&id=<?= $id ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&order_by=<?= $order_by ?>&order_dir=<?= $order_dir ?>"
                class="px-4 py-2 rounded border transition
               <?= $pagina == $i ? 'bg-[#00bfff] text-black font-semibold shadow' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>

</div>



<!-- Botão Voltar ao Topo -->
<button id="scroll-to-top"
    class="fixed bottom-12 right-4 bg-[#00bfff] md:hover:bg-yellow-400 text-black p-3 rounded-full shadow-lg transform transition-all duration-300 ease-in-out opacity-0 invisible translate-y-4 z-50">
    <i class="fa-solid fa-chevron-up md:text-lg"></i>
</button>

<!-- Modal Adicionar Participante -->
<div id="modal-adicionar" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative mx-4 max-h-[90vh] overflow-y-auto">
        <button onclick="fecharModalAdicionar()"
            class="absolute top-2 right-2 text-red-600 hover:text-red-800 text-lg z-10">
            <i class="fa-solid fa-window-close"></i>
        </button>

        <h2 class="text-xl font-bold mb-4 text-gray-800">Adicionar participante</h2>

        <!-- Área do filtro - inicialmente aberta -->
        <div id="area-filtro-ritual" class="mb-4">
            <!-- Botão toggle para mostrar/esconder filtro (inicialmente escondido) -->
            <div id="botao-toggle-filtro" class="hidden mb-4 flex items-center justify-between">
                <button type="button" onclick="toggleFiltroRitual()"
                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded flex items-center gap-2 text-sm shadow hover:bg-gray-300 transition">
                    <i class="fa-solid fa-search"></i> Filtrar
                </button>
                <button type="button" id="limpar-filtro-btn" onclick="limparFiltroCompleto()" style="display: none;"
                    class="bg-red-600 text-white px-4 py-2 rounded flex items-center gap-2 text-sm shadow hover:bg-red-700 transition">
                    <i class="fa-solid fa-broom mr-1"></i> Limpar Filtro
                </button>
            </div>

            <!-- Formulário de pesquisa - inicialmente visível -->
            <div class="form-container mobile-compact">
                <form id="pesquisa-participante-form" onsubmit="return false;" class="space-y-4">
                    <input type="hidden" name="ritual_id" value="<?= $id ?>">

                    <div>
                        <input type="text" id="nome_pesquisa" name="nome_pesquisa"
                            placeholder="Digite o nome ou CPF (com ou sem pontos)" oninput="aplicarMascaraCPF(this)"
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fa-solid fa-info-circle mr-1"></i>
                            Aceita nome (mín. 3 caracteres) ou CPF (exatos 11 dígitos)
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" id="pesquisar-btn" onclick="pesquisarParticipantes()"
                            class="bg-[#00bfff] text-black px-4 py-2 rounded hover:bg-yellow-400 transition font-semibold">
                            <i class="fa-solid fa-search mr-1"></i> Pesquisar
                        </button>
                        <button type="button" id="limpar-pesquisa-btn" onclick="limparPesquisa()" style="display: none;"
                            class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition font-semibold">
                            Limpar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Área de resultados -->
        <div id="resultados-pesquisa" class="mt-4" style="display: none;">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Resultados</h3>
            <div class="max-h-60 overflow-y-auto border border-gray-200 rounded">
                <ul id="lista-participantes" class="divide-y divide-gray-200"></ul>
            </div>

            <!-- Seção "Não encontrou o participante?" -->
            <div class="mt-4 pt-4 border-t border-gray-200 text-center">
                <h4 class="text-md font-medium text-gray-700 mb-3">Não encontrou o participante?</h4>
                <button onclick="adicionarNovaPessoa()"
                    class="w-full bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 transition font-semibold flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus"></i>
                    Adicionar novo participante
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalhes da Inscrição -->
<div id="modal-detalhes-inscricao"
    class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative mx-4 max-h-[90vh] overflow-y-auto">
        <button onclick="fecharModalDetalhes()"
            class="absolute top-2 right-2 text-red-600 hover:text-red-800 text-lg z-10">
            <i class="fa-solid fa-window-close"></i>
        </button>

        <h2 class="text-xl font-bold mb-4 text-gray-800">Detalhes da inscrição</h2>

        <div class="form-container mobile-compact">
            <form id="form-detalhes-inscricao" method="POST" class="space-y-4" novalidate>
                <input type="hidden" id="id" name="id" value="">

                <div>
                    <label for="primeira_vez_instituto" class="block text-sm font-medium text-gray-700 mb-1">Primeira
                        vez no
                        Instituto?</label>
                    <select name="primeira_vez_instituto" required
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Selecione...</option>
                        <option value="Sim">Sim</option>
                        <option value="Não">Não</option>
                    </select>
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>

                <div>
                    <label for="primeira_vez_ayahuasca" class="block text-sm font-medium text-gray-700 mb-1">Primeira
                        vez
                        consagrando Ayahuasca?</label>
                    <select name="primeira_vez_ayahuasca" required
                        class="w-full border border-gray-300 rounded px-3 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <option value="">Selecione...</option>
                        <option value="Sim">Sim</option>
                        <option value="Não">Não</option>
                    </select>
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>

                <div>
                    <label for="doenca_psiquiatrica" class="block text-sm font-medium text-gray-700 mb-1">Possui doença
                        psiquiátrica diagnosticada?</label>
                    <select name="doenca_psiquiatrica" id="doenca_psiquiatrica" required
                        class="w-full border border-gray-300 rounded px-3 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <option value="">Selecione...</option>
                        <option value="Sim">Sim</option>
                        <option value="Não">Não</option>
                    </select>
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>

                <div>
                    <label for="nome_doenca" class="block text-sm font-medium text-gray-700 mb-1">Se sim, escreva o nome
                        da
                        doença:</label>
                    <input type="text" name="nome_doenca" id="nome_doenca" value="" disabled
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 text-sm">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório quando doença psiquiátrica for "Sim"
                    </p>
                </div>

                <div>
                    <label for="uso_medicao" class="block text-sm font-medium text-gray-700 mb-1">Faz uso de alguma
                        medicação?</label>
                    <select name="uso_medicao" id="uso_medicao" required
                        class="w-full border border-gray-300 rounded px-3 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <option value="">Selecione...</option>
                        <option value="Sim">Sim</option>
                        <option value="Não">Não</option>
                    </select>
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório</p>
                </div>

                <div>
                    <label for="nome_medicao" class="block text-sm font-medium text-gray-700 mb-1">Se sim, escreva o
                        nome da
                        medicação:</label>
                    <input type="text" name="nome_medicao" id="nome_medicao" value="" disabled
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 text-sm">
                    <p class="text-red-500 text-sm mt-1 hidden">Campo obrigatório quando uso de medicação for "Sim"</p>
                </div>

                <div>
                    <label for="mensagem" class="block text-sm font-medium text-gray-700 mb-1">Mensagem do
                        participante:</label>
                    <textarea name="mensagem" rows="3"
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"></textarea>
                </div>

                <div>
                    <label for="salvo_em" class="block text-sm font-medium text-gray-700 mb-1">Salvo em:</label>
                    <input type="text" id="salvo_em" name="salvo_em" readonly value=""
                        class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 text-sm">
                </div>

                <button type="submit"
                    class="w-full bg-[#00bfff] text-black py-2 rounded hover:bg-yellow-400 transition font-semibold">
                    <i class="fa-solid fa-save mr-1"></i>
                    Salvar
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Observação -->
<div id="modal-observacao" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative mx-4">
        <button onclick="fecharModalObservacao()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
            <i class="fas fa-times text-xl"></i>
        </button>

        <h2 class="text-xl font-bold mb-4 text-gray-800">Adicionar observação</h2>

        <div class="form-container mobile-compact">
            <form id="form-observacao" method="POST" class="space-y-4">
                <input type="hidden" id="inscricao_id_observacao" name="inscricao_id" value="">

                <div>
                    <label for="observacao" class="block text-sm font-medium text-gray-700 mb-1">Observação:</label>
                    <textarea name="observacao" required rows="6"
                        placeholder="Digite sua observação sobre este participante..."
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        Descreva qualquer informação relevante sobre a participação neste ritual
                    </p>
                </div>

                <div>
                    <label for="obs_salvo_em" class="block text-sm font-medium text-gray-700 mb-1">Salvo em:</label>
                    <input type="text" id="obs_salvo_em" name="obs_salvo_em" readonly value=""
                        class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 text-sm">
                </div>

                <button type="submit"
                    class="w-full bg-[#00bfff] text-black py-2 rounded hover:bg-yellow-400 transition font-semibold flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus"></i>
                    Salvar observação
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Visualização de Imagem -->
<div id="modal-image" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg overflow-hidden shadow-lg relative max-w-sm w-full mx-4">
        <button onclick="closeImageModal()" class="absolute top-2 right-2 text-red-600 hover:text-red-800 text-lg z-10">
            <i class="fa-solid fa-window-close"></i>
        </button>
        <img id="modal-image-content" class="w-full h-auto object-contain max-h-[80vh]" alt="Imagem Ampliada">
    </div>
</div>

<!-- Modal de Confirmação Genérico -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative mx-4">
        <h2 class="text-xl font-bold mb-4 text-red-600" id="confirmModalTitle"><i
                class="fa-solid fa-exclamation-triangle mr-2"></i>ATENÇÃO!</h2>
        <p class="text-gray-700 mb-6" id="confirmModalText">Tem certeza que deseja remover este participante do ritual?
        </p>
        <div class="flex justify-end gap-3">
            <button id="confirmModalBtn"
                class="px-4 py-2 bg-[#00bfff] text-black rounded hover:bg-yellow-400 transition font-semibold">
                Confirmar
            </button>
            <button onclick="closeConfirmModal()"
                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-900 transition font-semibold">
                Cancelar
            </button>
        </div>
    </div>
</div>

<script>
    const ritualId = <?= json_encode($id) ?>;
</script>

<style>
    .line-clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<script src="/assets/js/ritual-visualizar.js"></script>
<script src="/assets/js/modal.js"></script>
<script src="/assets/js/relatorios.js"></script>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>