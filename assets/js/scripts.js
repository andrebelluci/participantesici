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