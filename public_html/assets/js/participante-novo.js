// participante-novo.js - Funcionalidades específicas da página de novo participante

document.addEventListener('DOMContentLoaded', function () {
  // Validação de CPF (sem verificar CPF existente)
  const cpfInput = document.getElementById('cpf');

  cpfInput?.addEventListener('blur', function () {
    const cpfValue = this.value.replace(/\D/g, '');
    if (cpfValue.length !== 11) return; // Ignora CPFs incompletos

    fetch('/participantesici/public_html/api/participante/verifica-cpf?cpf=' + cpfValue)
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          showToast(data.error);
          document.getElementById('cpf').value = '';
          document.getElementById('cpf').focus();
        } else if (data.exists) {
          showToast('Este CPF já está cadastrado.');
          document.getElementById('cpf').value = '';
          document.getElementById('cpf').focus();
        }
      })
      .catch(error => console.error('Erro ao verificar CPF:', error));
  });
});