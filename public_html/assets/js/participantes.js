// Reaplica máscara CPF se já houver valor preenchido
const cpfInput = document.getElementById('filtro_cpf');
if (cpfInput?.value) {
  cpfInput.value = cpfInput.value.replace(/\D/g, '');
  mascaraCPF(cpfInput);
}

// Remover máscara no submit
const form = document.querySelector('form.filters');
if (form) {
  form.addEventListener('submit', function () {
    const cpfInput = document.getElementById('filtro_cpf');
    if (cpfInput) {
      cpfInput.value = cpfInput.value.replace(/\D/g, '');
    }
  });
}

// Máscara CPF
function mascaraCPF(input) {
  let valor = input.value.replace(/\D/g, '');
  if (valor.length > 11) valor = valor.slice(0, 11);
  valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
  valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
  valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  input.value = valor;
}

// Fecha os filtros no mobile após envio
document.addEventListener("DOMContentLoaded", () => {
  const filtros = document.getElementById("filtros");

  // Fecha os filtros no mobile após envio
  document.querySelector('form#filtros')?.addEventListener('submit', () => {
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

// Confirmação excluir participante
function abrirConfirmacaoExcluir(url) {
  openConfirmModal('Esta ação irá excluir permanentemente este participante!', () => {
    window.location.href = url;
  });
}
