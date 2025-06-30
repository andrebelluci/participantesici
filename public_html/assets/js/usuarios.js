// Confirmação para excluir usuário
function abrirConfirmacaoExcluir(url) {
  openConfirmModal('Esta ação irá excluir permanentemente este usuário do sistema!', () => {
    window.location.href = url;
  });
}

// Fecha os filtros no mobile após envio
document.addEventListener("DOMContentLoaded", () => {
  const filtros = document.getElementById("filtro_nome");

  // Fecha os filtros no mobile após envio
  document.querySelector('form')?.addEventListener('submit', () => {
    if (window.innerWidth < 768 && filtros) {
      filtros.classList.add('hidden');
      localStorage.setItem('fechouFiltrosMobile', 'true');
    }
  });

  // No carregamento da página, oculta os filtros se o flag estiver setado
  if (window.innerWidth < 768 && localStorage.getItem('fechouFiltrosMobile') === 'true') {
    filtros?.classList.add('hidden');
    localStorage.removeItem('fechouFiltrosMobile');
  }
});

