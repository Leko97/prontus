import { criarPedido } from '/src/shared/js/api.js';
import { formatCurrency } from '/src/shared/js/utils.js';
import { getCarrinho, calcularTotal, limparCarrinho } from './carrinho.js';

let metodoPagamento = null;

document.addEventListener('DOMContentLoaded', () => {
  /* Total */
  const total = calcularTotal();
  const totalEl = document.getElementById('pagamentoTotal');
  if (totalEl) totalEl.textContent = formatCurrency(total);

  if (total === 0) {
    window.location.href = '/src/totem/pages/carrinho.php';
    return;
  }

  initMetodos();

  document.getElementById('btnConfirmar')?.addEventListener('click', confirmarPagamento);
});

function initMetodos() {
  document.querySelectorAll('.metodo-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.metodo-btn').forEach(b => b.classList.remove('selecionado'));
      btn.classList.add('selecionado');

      metodoPagamento = btn.dataset.metodo;
      mostrarInstrucao(metodoPagamento);

      const btnConfirmar = document.getElementById('btnConfirmar');
      if (btnConfirmar) btnConfirmar.disabled = false;
    });
  });
}

function mostrarInstrucao(metodo) {
  document.querySelectorAll('.instrucao-panel').forEach(p => p.classList.remove('visible'));
  const painel = document.getElementById(`instrucao-${metodo}`);
  if (painel) painel.classList.add('visible');
}

async function confirmarPagamento() {
  if (!metodoPagamento) return;

  const btn = document.getElementById('btnConfirmar');
  btn.disabled = true;
  btn.textContent = 'Processando…';

  const alertEl = document.getElementById('alertErro');
  if (alertEl) alertEl.classList.add('hidden');

  try {
    const carrinho = getCarrinho();
    const payload  = {
      itens: carrinho.map(item => ({
        produto:    item.nome,
        quantidade: item.quantidade,
        extras:     (item.extras || []).filter(e => (e.quantidade || 1) > 0).map(e => e.nome),
        remocoes:   item.remocoes || [],
        restricoes: item.restricoes || [],
      })),
      metodoPagamento,
      total: calcularTotal(),
    };

    const resultado = await criarPedido(payload);

    limparCarrinho();
    window.location.href = `/src/totem/pages/senha.php?senha=${encodeURIComponent(resultado.senha)}`;
  } catch (err) {
    console.error(err);

    if (alertEl) alertEl.classList.remove('hidden');
    btn.disabled = false;
    btn.textContent = 'Confirmar pagamento';
  }
}
