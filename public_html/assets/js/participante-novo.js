// Função para abrir a imagem ampliada
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

// Preview da imagem
const fileInput = document.getElementById('foto-input');
const adicionarImagemBtn = document.getElementById('adicionar-imagem-btn');
const previewContainer = document.getElementById('preview-container');
const previewImage = document.getElementById('preview-image');
const excluirImagemBtn = document.getElementById('excluir-imagem-btn');

adicionarImagemBtn.addEventListener('click', () => {
  fileInput.click();
});

fileInput.addEventListener('change', () => {
  const file = fileInput.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      previewImage.src = e.target.result;
      previewContainer.style.display = 'block';
      adicionarImagemBtn.style.display = 'none';
    };
    reader.readAsDataURL(file);
  }
});

excluirImagemBtn.addEventListener('click', () => {
  previewImage.src = '#';
  previewContainer.style.display = 'none';
  adicionarImagemBtn.style.display = 'inline-block';
  fileInput.value = '';
});

// Abrir modal ao clicar na imagem de preview
previewImage.addEventListener('click', () => {
  openImageModal(previewImage.src);
});

// Máscara para CPF
function mascaraCPF(input) {
  let valor = input.value.replace(/\D/g, ''); // Remove tudo que não é número
  if (valor.length > 11) valor = valor.slice(0, 11); // Limita a 11 dígitos
  valor = valor.replace(/(\d{3})(\d)/, '$1.$2'); // Adiciona o primeiro ponto
  valor = valor.replace(/(\d{3})(\d)/, '$1.$2'); // Adiciona o segundo ponto
  valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2'); // Adiciona o traço
  input.value = valor;
}

// Máscara para Celular
function mascaraCelular(input) {
  let valor = input.value.replace(/\D/g, ''); // Remove tudo que não é número
  if (valor.length > 11) valor = valor.slice(0, 11); // Limita a 11 dígitos
  valor = valor.replace(/^(\d{2})(\d)/g, '($1) $2'); // Adiciona os parênteses
  valor = valor.replace(/(\d{5})(\d)/, '$1-$2'); // Adiciona o hífen
  input.value = valor;
}

// Máscara para CEP
function mascaraCEP(input) {
  let valor = input.value.replace(/\D/g, ''); // Remove tudo que não é número
  if (valor.length > 8) valor = valor.slice(0, 8); // Limita a 8 dígitos
  valor = valor.replace(/(\d{5})(\d)/, '$1-$2'); // Adiciona o hífen
  input.value = valor;
}

// Função para remover máscaras antes de enviar o formulário
document.getElementById('formulario-participante').addEventListener('submit', function (event) {
  // Remove máscara do CPF
  const cpfInput = document.getElementById('cpf');
  cpfInput.value = cpfInput.value.replace(/\D/g, ''); // Remove tudo que não é número

  // Remove máscara do Celular
  const celularInput = document.getElementById('celular');
  celularInput.value = celularInput.value.replace(/\D/g, ''); // Remove tudo que não é número

  // Remove máscara do CEP
  const cepInput = document.getElementById('cep');
  cepInput.value = cepInput.value.replace(/\D/g, ''); // Remove tudo que não é número

  // Função para validar o e-mail
  function validarEmail(email) {
    const regex = /^[a-zA-Z0-9._%+-]{3,}@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return regex.test(email);
  }

  // Validar o formulário antes de enviar
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const emailInput = document.getElementById('email');

    form.addEventListener('submit', function (event) {
      const emailValue = emailInput.value.trim();

      if (!validarEmail(emailValue)) {
        event.preventDefault(); // Impede o envio do formulário
        alert('Por favor, digite um e-mail válido.');
        emailInput.focus();
      }
    });
  });
});

document.getElementById('cpf').addEventListener('blur', function () {
  const cpfInput = this.value.replace(/\D/g, ''); // Remove máscara
  if (cpfInput.length !== 11) return; // Ignora CPFs incompletos

  fetch('/participantesici/public_html/api/participante/verifica-cpf?cpf=' + cpfInput)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        alert(data.error); // Exibe mensagem de erro (ex.: "CPF inválido")
        document.getElementById('cpf').value = ''; // Limpa o campo
        document.getElementById('cpf').focus(); // Foca novamente no campo
      } else if (data.exists) {
        alert('Este CPF já está cadastrado.');
        document.getElementById('cpf').value = ''; // Limpa o campo
        document.getElementById('cpf').focus(); // Foca novamente no campo
      }
    })
    .catch(error => console.error('Erro ao verificar CPF:', error));
});

document.addEventListener('DOMContentLoaded', function () {
  const cepInput = document.getElementById('cep');
  const buscarCepBtn = document.getElementById('buscar-cep-btn');

  // Função para buscar o CEP
  function buscarCep() {
    const cep = cepInput.value.replace(/\D/g, ''); // Remove caracteres não numéricos
    if (cep.length === 8) {
      fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
          if (!data.erro) {
            document.getElementById('endereco_rua').value = data.logradouro || '';
            document.getElementById('bairro').value = data.bairro || '';
            document.getElementById('cidade').value = data.localidade || '';
            document.getElementById('estado').value = data.uf || '';
          } else {
            alert('CEP não encontrado. Por favor, verifique o número digitado.');
          }
        })
        .catch(() => alert('Erro ao buscar o CEP. Tente novamente mais tarde.'));
    } else {
      alert('Por favor, insira um CEP válido com 8 dígitos.');
    }
  }

  // Evento de perda de foco (blur)
  cepInput.addEventListener('blur', buscarCep);

  // Evento de clique no botão "Buscar CEP"
  buscarCepBtn.addEventListener('click', buscarCep);
});