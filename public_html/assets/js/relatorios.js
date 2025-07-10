/**
 * Sistema de Relatórios
 * Funções para interação com botões de exportação gerados pelo PHP
 */

// Função para mostrar/esconder o dropdown de exportação
function toggleExportDropdown(event) {
  event.stopPropagation();
  const dropdown = document.getElementById('export-dropdown');
  if (dropdown) {
    dropdown.classList.toggle('hidden');
  }
}

// Função para exportar participante
function exportarParticipante(id, formato) {
  if (!id) {
    alert('ID do participante não encontrado');
    return;
  }

  // Mostrar loading
  showExportLoading();

  // Construir URL de exportação
  const url = `/participante/${id}/relatorio/${formato}`;

  // Criar link temporário para download
  const link = document.createElement('a');
  link.href = url;
  link.download = '';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  // Esconder dropdown e loading
  hideExportDropdown();
  setTimeout(hideExportLoading, 2000);
}

// Função para exportar ritual
function exportarRitual(id, formato) {
  if (!id) {
    alert('ID do ritual não encontrado');
    return;
  }

  // Mostrar loading
  showExportLoading();

  // Construir URL de exportação
  const url = `/ritual/${id}/relatorio/${formato}`;

  // Criar link temporário para download
  const link = document.createElement('a');
  link.href = url;
  link.download = '';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  // Esconder dropdown e loading
  hideExportDropdown();
  setTimeout(hideExportLoading, 2000);
}

// Função para esconder dropdown
function hideExportDropdown() {
  const dropdown = document.getElementById('export-dropdown');
  if (dropdown) {
    dropdown.classList.add('hidden');
  }
}

// Função para mostrar loading
function showExportLoading() {
  const button = document.getElementById('export-button');
  if (!button) return;

  const originalContent = button.innerHTML;

  button.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-lg"></i>';
  button.disabled = true;
  button.dataset.originalContent = originalContent;
}

// Função para esconder loading
function hideExportLoading() {
  const button = document.getElementById('export-button');
  if (button && button.dataset.originalContent) {
    button.innerHTML = button.dataset.originalContent;
    button.disabled = false;
    delete button.dataset.originalContent;
  }
}

// Event listener para fechar dropdown ao clicar fora
document.addEventListener('click', function (event) {
  const dropdown = document.getElementById('export-dropdown');
  const button = document.getElementById('export-button');

  if (dropdown && button && !button.contains(event.target) && !dropdown.contains(event.target)) {
    hideExportDropdown();
  }
});

// Event listener para tecla ESC
document.addEventListener('keydown', function (event) {
  if (event.key === 'Escape') {
    hideExportDropdown();
  }
});