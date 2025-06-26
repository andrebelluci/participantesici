// participante-novo.js - Funcionalidades específicas da página de novo participante

document.addEventListener('DOMContentLoaded', function () {
  // Validação de CPF para novo participante
  const cpfInput = document.getElementById('cpf');

  cpfInput?.addEventListener('blur', function () {
    const cpfValue = this.value.replace(/\D/g, '');

    // ✅ Ignora se CPF estiver vazio
    if (!cpfValue) return;

    // ✅ Valida CPF com menos de 11 dígitos
    if (cpfValue.length < 11) {
      showToast('CPF deve ter 11 dígitos', 'error');
      this.classList.add('border-red-500');
      this.focus();
      return;
    }

    // ✅ Valida CPF com mais de 11 dígitos
    if (cpfValue.length > 11) {
      showToast('CPF deve ter exatamente 11 dígitos', 'error');
      this.classList.add('border-red-500');
      return;
    }

    // ✅ Remove borda de erro antes da validação
    this.classList.remove('border-red-500');

    // ✅ Chama API para validar CPF
    fetch(`/participantesici/public_html/api/participante/verifica-cpf?cpf=${cpfValue}`)
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          showToast(data.error, 'error');
          document.getElementById('cpf').value = '';
          document.getElementById('cpf').classList.add('border-red-500');
          document.getElementById('cpf').focus();
        } else if (data.exists) {
          showToast('Este CPF já está cadastrado.', 'error');
          document.getElementById('cpf').value = '';
          document.getElementById('cpf').classList.add('border-red-500');
          document.getElementById('cpf').focus();
        } else {
          // ✅ CPF válido e disponível
          showToast('CPF válido!', 'success');
          document.getElementById('cpf').classList.remove('border-red-500');
          document.getElementById('cpf').classList.add('border-green-500');

          // Remove a borda verde após 2 segundos
          setTimeout(() => {
            document.getElementById('cpf').classList.remove('border-green-500');
          }, 2000);
        }
      })
      .catch(error => {
        console.error('Erro ao verificar CPF:', error);
        showToast('Erro ao validar CPF. Tente novamente.', 'error');
        document.getElementById('cpf').classList.add('border-red-500');
      });
  });

  // ✅ Validação em tempo real durante digitação
  cpfInput?.addEventListener('input', function () {
    const cpfValue = this.value.replace(/\D/g, '');

    // Remove bordas de erro/sucesso quando começar a digitar
    this.classList.remove('border-red-500', 'border-green-500');

    // Feedback visual imediato
    if (cpfValue.length > 11) {
      this.classList.add('border-red-500');
      showToast('CPF não pode ter mais de 11 dígitos', 'warning');
    } else if (cpfValue.length === 11) {
      // Remove borda vermelha quando completar 11 dígitos
      this.classList.remove('border-red-500');
    }
  });

  // ✅ Previne cola de texto muito longo
  cpfInput?.addEventListener('paste', function (e) {
    setTimeout(() => {
      const cpfValue = this.value.replace(/\D/g, '');
      if (cpfValue.length > 11) {
        // Corta para 11 dígitos e aplica máscara
        this.value = cpfValue.substring(0, 11);
        mascaraCPF(this);
        showToast('CPF foi limitado a 11 dígitos', 'warning');
      }
    }, 10);
  });
});