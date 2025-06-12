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
  fetch(`/participantesici/public_html/api/inscricoes/buscar-id?participante_id=${participanteId}&ritual_id=${ritualId}`)
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
      fetch(`/participantesici/public_html/api/inscricoes/detalhes-inscricao?id=${inscricaoId}`)
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
  document.getElementById('modal-detalhes-inscricao').style.display = 'flex';
}
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById('form-detalhes-inscricao');

  form.addEventListener('submit', function (event) {
    event.preventDefault(); // Impede o envio tradicional do formulário

    // Captura os dados do formulário
    const formData = new FormData(form);

    // Envia os dados via AJAX
    fetch('/participantesici/public_html/api/inscricoes/buscar-id', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert("Detalhes salvos com sucesso!");
          // Fecha o modal
          document.getElementById('modal-detalhes-inscricao').style.display = 'none';
          // Atualiza a tabela (opcional)
          location.reload();
        } else {
          alert("Erro ao salvar detalhes: " + data.error);
        }
      })
      .catch(error => {
        console.error('Erro ao enviar requisição:', error);
        alert("Erro ao salvar detalhes. Por favor, tente novamente.");
      });
  });
});

// Função para fechar o modal de detalhes da inscrição
function fecharModalDetalhes() {
  document.getElementById('modal-detalhes-inscricao').style.display = 'none';
}

// Função para abrir o modal de observação
function abrirModalObservacao(participanteId) {
  // Busca o ID da inscrição via AJAX
  fetch(`/participantesici/public_html/api/inscricoes/buscar-id?participante_id=${participanteId}&ritual_id=${ritualId}`)
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
      fetch(`/participantesici/public_html/api/inscricoes/detalhes-inscricao?id=${inscricaoId}`)
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
      document.getElementById('modal-observacao').style.display = 'flex';
    })
    .catch(error => console.error('Erro ao buscar ID da inscrição:', error));
}
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById('form-observacao');

  form.addEventListener('submit', function (event) {
    event.preventDefault(); // Impede o envio tradicional do formulário

    // Captura os dados do formulário
    const formData = new FormData(form);
    const observacao = formData.get('observacao');

    // Verifica se a observação está vazia
    if (!observacao.trim()) {
      alert("A observação não pode estar vazia.");
      return;
    }

    // Envia os dados via AJAX
    fetch('/participantesici/public_html/api/inscricoes/salvar-observacao', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert("Observação salva com sucesso!");
          // Fecha o modal
          document.getElementById('modal-observacao').style.display = 'none';
          // Atualiza a tabela (opcional)
          location.reload();
        } else {
          alert("Erro ao salvar observação: " + data.error);
        }
      })
      .catch(error => {
        console.error('Erro ao enviar requisição:', error);
        alert("Erro ao salvar observação. Por favor, tente novamente.");
      });
  });
});

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
  fetch(`/participantesici/public_html/api/inscricoes/buscar-id?participante_id=${participanteId}&ritual_id=${ritualId}`)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        alert(data.error);
        return;
      }

      const inscricaoId = data.inscricao_id;

      // Envia a requisição AJAX para atualizar o status no banco de dados
      fetch(`/participantesici/public_html/api/inscricoes/atualizar-presenca`, {
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
    alert("Digite um nome ou CPF para pesquisar.");
    return;
  }
  // Mostra o botão "Limpar Pesquisa"
  const limparPesquisaBtn = document.getElementById('limpar-pesquisa-btn');
  limparPesquisaBtn.style.display = 'inline-block';
  // Exibe os resultados (simulação)
  const resultadosPesquisa = document.getElementById('resultados-pesquisa');
  resultadosPesquisa.style.display = 'block';
  // Limpa a lista de resultados
  const listaParticipantes = document.getElementById('lista-participantes');
  listaParticipantes.innerHTML = '';
  // Exibe a área de resultados
  document.getElementById('resultados-pesquisa').style.display = 'block';
  // Envia a requisição AJAX para buscar os participantes
  fetch(`/participantesici/public_html/api/ritual/buscar-participante?nome=${encodeURIComponent(nomePesquisa)}`)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        alert(data.error);
        return;
      }

      if (data.length === 0) {
        // Se nenhum participante for encontrado, exibe o botão "Adicionar Novo Participante"
        listaParticipantes.innerHTML = `
              <li>Nenhum participante encontrado.</li>
              <li>
                  <button class="add-new-btn" onclick="adicionarNovaPessoa()">Adicionar Novo Participante</button>
              </li>
          `;
        return;
      }

      // Preenche a lista com os participantes encontrados
      data.forEach(participante => {
        const li = document.createElement('li');
        li.innerHTML = `
              <img src="${participante.foto || '/participantesici/public_html/assets/images/no-image.png'}" alt="Foto">
              <span>${participante.nome_completo}</span>
              <button class="add-btn" onclick="adicionarParticipante(${participante.id})">Adicionar</button>
          `;
        listaParticipantes.appendChild(li);
      });
      const li = document.createElement('ul');
      li.innerHTML = `
          <br>
              <h3>Não encontrou o participante?</h3><br>
              <button class="add-new-btn" onclick="adicionarNovaPessoa()">Adicionar Novo Participante</button>
          `;
      listaParticipantes.appendChild(li);
    })
    .catch(error => console.error('Erro ao buscar participantes:', error));
}

// Função para limpar a pesquisa
function limparPesquisa() {
  // Limpa o campo de pesquisa
  const nomePesquisa = document.getElementById('nome_pesquisa');
  nomePesquisa.value = '';

  // Remove os resultados da lista
  const listaParticipantes = document.getElementById('lista-participantes');
  listaParticipantes.innerHTML = '';

  // Oculta a área de resultados
  const resultadosPesquisa = document.getElementById('resultados-pesquisa');
  resultadosPesquisa.style.display = 'none';

  // Oculta o botão "Limpar Pesquisa"
  const limparPesquisaBtn = document.getElementById('limpar-pesquisa-btn');
  limparPesquisaBtn.style.display = 'none';
}

// Função para capturar o evento de pressionar Enter no campo de pesquisa
document.getElementById('pesquisa-participante-form').addEventListener('keypress', function (event) {
  if (event.key === 'Enter') {
    event.preventDefault(); // Impede o envio do formulário
    pesquisarParticipantes(); // Chama a função de pesquisa
  }
});

// Função para redirecionar para a página de cadastro de nova pessoa
function adicionarNovaPessoa() {
  const ritualId = document.querySelector('#modal-adicionar input[name="ritual_id"]').value;
  window.location.href = `participante-novo?redirect=ritual-visualizar&id=${ritualId}`;
}

// Função para adicionar um participante ao ritual
function adicionarParticipante(participanteId) {
  const ritualId = document.querySelector('#modal-adicionar input[name="ritual_id"]').value;

  // Envia a requisição AJAX para adicionar o participante ao ritual
  fetch('/participantesici/public_html/api/ritual/adicionar-participante', {
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

document.addEventListener("DOMContentLoaded", function () {
  // Função para habilitar/desabilitar o campo "Nome da doença"
  function toggleNomeDoenca() {
    const doencaPsiquiatrica = document.getElementById("doenca_psiquiatrica");
    const nomeDoenca = document.getElementById("nome_doenca");

    if (doencaPsiquiatrica.value === "Sim") {
      nomeDoenca.disabled = false;
      nomeDoenca.required = true; // Torna o campo obrigatório se "Sim" for selecionado
    } else {
      nomeDoenca.disabled = true;
      nomeDoenca.required = false; // Remove a obrigatoriedade
      nomeDoenca.value = ""; // Limpa o valor do campo
    }
  }

  // Função para habilitar/desabilitar o campo "Nome da medicação"
  function toggleNomeMedicacao() {
    const usoMedicacao = document.getElementById("uso_medicao");
    const nomeMedicacao = document.getElementById("nome_medicao");

    if (usoMedicacao.value === "Sim") {
      nomeMedicacao.disabled = false;
      nomeMedicacao.required = true; // Torna o campo obrigatório se "Sim" for selecionado
    } else {
      nomeMedicacao.disabled = true;
      nomeMedicacao.required = false; // Remove a obrigatoriedade
      nomeMedicacao.value = ""; // Limpa o valor do campo
    }
  }

  // Monitorar mudanças no campo "Possui doença psiquiátrica diagnosticada?"
  document.getElementById("doenca_psiquiatrica").addEventListener("change", toggleNomeDoenca);

  // Monitorar mudanças no campo "Faz uso de alguma medicação?"
  document.getElementById("uso_medicao").addEventListener("change", toggleNomeMedicacao);

  // Executar as funções ao carregar a página para garantir o estado inicial correto
  toggleNomeDoenca();
  toggleNomeMedicacao();
});

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