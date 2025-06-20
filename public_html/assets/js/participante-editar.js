// participante-editar.js - Funcionalidades específicas da página de editar participante

document.addEventListener('DOMContentLoaded', function () {
  // CPF original do participante (vem do PHP)
  const cpfOriginalElement = document.querySelector('input[name="cpf"]');
  const cpfOriginal = cpfOriginalElement?.value.replace(/\D/g, '') || '';

  // Validação de CPF (ignorando o CPF original do participante)
  const cpfInput = document.getElementById('cpf');

  cpfInput?.addEventListener('blur', function () {
    const cpfValue = this.value.replace(/\D/g, '');

    // Ignora se CPF estiver vazio, incompleto ou for igual ao original
    if (cpfValue.length !== 11 || cpfValue === cpfOriginal) return;

    fetch('/participantesici/public_html/api/participante/verifica-cpf?cpf=' + cpfValue)
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          showToast(data.error);
          // Restaura o CPF original
          document.getElementById('cpf').value = cpfOriginalElement.defaultValue;
          mascaraCPF(document.getElementById('cpf'));
          document.getElementById('cpf').focus();
        } else if (data.exists) {
          showToast('Este CPF já está cadastrado.');
          // Restaura o CPF original
          document.getElementById('cpf').value = cpfOriginalElement.defaultValue;
          mascaraCPF(document.getElementById('cpf'));
          document.getElementById('cpf').focus();
        }
      })
      .catch(error => console.error('Erro ao verificar CPF:', error));
  });
});