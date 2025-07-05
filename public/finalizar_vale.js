$(document).on('click', '.selecionar-vale', function() {
  const idVale = $(this).data('id');

  $.getJSON('ajax/detalhes_vale.php', { id_vale: idVale }, function(res) {
    if (!res.success) {
      alert('Erro: ' + res.mensagem);
      return;
    }

    // Preencher campos do modal
    $('#id_vale_modal').val(res.id_vale);
    $('#total_atualizado_modal').val(res.valor_total);
    $('#total_pagar_texto').text(`MT ${res.valor_total.toFixed(2).replace('.', ',')}`);

    // Exibir saldo e parcelas pagas (você pode criar elementos para isso no modal)
    $('#saldo_texto').text(`Saldo: MT ${res.saldo.toFixed(2).replace('.', ',')}`);
    $('#parcelas_texto').text(`Parcelas pagas: ${res.parcelas_pagas} de 3`);

    // Ajustar campo valor pago - máximo o saldo
    $('#valor_pago').val('');
    $('#valor_pago').attr('max', res.saldo);

    $('#troco').val('0,00');
    $('#metodo_pagamento').val('');
    $('#numero_pagamento').val('').parent().addClass('d-none');

    // Se saldo zero, não pode pagar mais
    if (res.saldo <= 0) {
      alert('Este vale já está totalmente pago.');
      $('#modalFinalizarVale').modal('hide');
      return;
    }

    // Se já pagou 3 parcelas, bloqueia pagamento parcial (tem que pagar total)
    if (res.parcelas_pagas >= 3) {
      alert('Limite de 3 parcelas atingido. Pague o saldo total.');
      $('#valor_pago').attr('max', res.saldo);
    }

    $('#modalFinalizarVale').modal('show');
  });
});

// Mostrar campo número da transação apenas para alguns métodos de pagamento
$('#metodo_pagamento').change(function() {
  const metodo = $(this).val();
  if (['mpesa', 'emola', 'cartao'].includes(metodo)) {
    $('#numero_pagamento').parent().removeClass('d-none');
    $('#numero_pagamento').attr('required', true);
  } else {
    $('#numero_pagamento').parent().addClass('d-none');
    $('#numero_pagamento').removeAttr('required');
  }
});

// Calcular troco dinamicamente
$('#valor_pago').on('input', function() {
  const valorPago = parseFloat($(this).val()) || 0;
  const saldo = parseFloat($('#valor_pago').attr('max')) || 0;
  const troco = valorPago - saldo;
  $('#troco').val(troco > 0 ? troco.toFixed(2).replace('.', ',') : '0,00');
});

// Função para finalizar o pagamento via fetch/AJAX
function finalizarVale() {
  const idVale = $('#id_vale_modal').val();
  const valorPago = parseFloat($('#valor_pago').val());
  const metodoPagamento = $('#metodo_pagamento').val();
  const numeroTransacao = $('#numero_pagamento').val();

  if (!idVale || !valorPago || !metodoPagamento) {
    alert('Preencha todos os campos!');
    return;
  }

  const maxValor = parseFloat($('#valor_pago').attr('max'));
  if (valorPago > maxValor) {
    alert(`Valor pago não pode ser maior que o saldo (MT ${maxValor.toFixed(2).replace('.', ',')})`);
    return;
  }

  const dados = new URLSearchParams({
    id_vale: idVale,
    valor_pago: valorPago,
    metodo_pagamento: metodoPagamento,
    numero_transacao: numeroTransacao
  });

  fetch('ajax/finalizar_vale.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: dados.toString()
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert(data.mensagem);
      location.reload(); // recarrega a página para atualizar lista
    } else {
      alert('Erro: ' + data.mensagem);
    }
  })
  .catch(err => alert('Erro na requisição: ' + err));
}

// Linkar ao botão no modal:
$('#formFinalizarVale').on('submit', function(e) {
  e.preventDefault();
  finalizarVale();
});
