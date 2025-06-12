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

document.addEventListener("DOMContentLoaded", function () {
  const modals = document.querySelectorAll(".modal");

  modals.forEach(modal => {
    modal.addEventListener("click", function (event) {
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

document.addEventListener('DOMContentLoaded', function () {
  const cpfInput = document.getElementById('filtro_cpf');
  if (cpfInput && cpfInput.value) {
    // Reaplica a máscara ao valor preenchido
    cpfInput.value = cpfInput.value.replace(/\D/g, ''); // Remove máscara temporariamente
    mascaraCPF(cpfInput); // Reaplica a máscara
  }
});

// Função para remover máscara antes de enviar o formulário
document.querySelector('form.filters').addEventListener('submit', function (event) {
  const cpfInput = document.getElementById('filtro_cpf');
  if (cpfInput) {
    cpfInput.value = cpfInput.value.replace(/\D/g, ''); // Remove tudo que não é número
  }
});
