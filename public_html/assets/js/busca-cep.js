document.addEventListener('DOMContentLoaded', function () {
  const cepInput = document.getElementById('cep');
  const buscarCepBtn = document.getElementById('buscar-cep-btn');
  let searchTimeout;
  let lastSearchedCep = '';

  // Função para buscar o CEP
  function buscarCep() {
    const cep = cepInput.value.replace(/\D/g, '');

    // Evita buscar o mesmo CEP novamente
    if (cep === lastSearchedCep) return;

    if (cep.length === 8) {
      lastSearchedCep = cep; // Salva o CEP buscado

      // Feedback visual durante o carregamento
      buscarCepBtn.textContent = 'Buscando...';
      buscarCepBtn.disabled = true;

      fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
          if (!data.erro) {
            // Preenche os campos
            document.getElementById('endereco_rua').value = data.logradouro || '';
            document.getElementById('bairro').value = data.bairro || '';
            document.getElementById('cidade').value = data.localidade || '';
            document.getElementById('estado').value = data.uf || '';

            // Foca no primeiro campo não preenchido
            focusFirstEmptyField();

            showToast('CEP encontrado com sucesso!', 'success');
          } else {
            showToast('CEP não encontrado. Por favor, verifique o número digitado.');
            lastSearchedCep = ''; // Limpa para permitir nova tentativa
          }
        })
        .catch(() => {
          showToast('Erro ao buscar o CEP. Tente novamente mais tarde.');
          lastSearchedCep = ''; // Limpa para permitir nova tentativa
        })
        .finally(() => {
          // Restaura o botão
          buscarCepBtn.textContent = 'Buscar CEP';
          buscarCepBtn.disabled = false;
        });
    } else {
      showToast('Por favor, insira um CEP válido com 8 dígitos.');
    }
  }

  // Função para focar no primeiro campo de endereço vazio
  function focusFirstEmptyField() {
    const addressFields = [
      'endereco_numero', 'endereco_rua', 'endereco_complemento',
      'bairro', 'cidade', 'estado'
    ];

    for (const fieldId of addressFields) {
      const field = document.getElementById(fieldId);
      if (field && (!field.value || field.value.trim() === '')) {
        field.focus();
        field.classList.add('ring-2', 'ring-blue-300');
        setTimeout(() => {
          field.classList.remove('ring-2', 'ring-blue-300');
        }, 1500);
        break;
      }
    }
  }

  // Evento de blur com debounce
  cepInput.addEventListener('blur', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(buscarCep, 300); // Aguarda 300ms
  });

  // Evento de clique no botão
  buscarCepBtn.addEventListener('click', () => {
    clearTimeout(searchTimeout); // Cancela busca pendente
    buscarCep();
  });

  // Limpa o CEP salvo quando o usuário modifica o campo
  cepInput.addEventListener('input', () => {
    lastSearchedCep = '';
  });
});