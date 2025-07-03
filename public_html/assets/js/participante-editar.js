// participante-editar.js - Versão simplificada

document.addEventListener('DOMContentLoaded', function () {
  // CPF original do participante (vem do PHP)
  const cpfOriginalElement = document.querySelector('input[name="cpf"]');
  const cpfOriginal = cpfOriginalElement?.value.replace(/\D/g, '') || '';

  const cpfInput = document.getElementById('cpf');

  cpfInput?.addEventListener('blur', function () {
    const cpfValue = this.value.replace(/\D/g, '');

    // ✅ Ignora se CPF estiver vazio ou for igual ao original
    if (!cpfValue || cpfValue === cpfOriginal) return;

    // ✅ Validação de tamanho
    if (cpfValue.length < 11) {
      showToast('CPF deve ter 11 dígitos', 'error');
      this.focus();
      return;
    }

    fetch(`/api/participante/verifica-cpf?cpf=${cpfValue}`)
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          showToast(data.error, 'error');
          this.value = cpfOriginalElement.defaultValue;
          mascaraCPF(this);
          this.focus();
        } else if (data.exists) {
          showToast('Este CPF já está cadastrado.', 'error');
          this.value = cpfOriginalElement.defaultValue;
          mascaraCPF(this);
          this.focus();
        } else {
          showToast('CPF válido!', 'success');
        }
      })
      .catch(error => {
        console.error('Erro ao verificar CPF:', error);
        showToast('Erro ao validar CPF. Tente novamente.', 'error');
      });
  });
});