import { criarPedido } from '/src/shared/js/api.js';
import { formatCurrency } from '/src/shared/js/utils.js';
import { getCarrinho, calcularTotal, limparCarrinho } from './carrinho.js';

let metodoPagamento = null;
let processando = false;

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
  if (!metodoPagamento || processando) return;
  processando = true;

  const btn = document.getElementById('btnConfirmar');
  btn.disabled = true;
  btn.textContent = 'Processando…';

  const alertEl = document.getElementById('alertErro');
  if (alertEl) alertEl.classList.add('hidden');

  try {
    const carrinho = getCarrinho();
    const payload  = {
      itens: carrinho.map(item => ({
        produtoId:  item.produtoId,
        quantidade: item.quantidade,
        extras:     (item.extras || [])
                      .filter(e => (e.quantidade || 1) > 0)
                      .map(e => ({ id: e.id, nome: e.nome, quantidade: e.quantidade || 1 })),
        remocoes:   item.remocoes || [],
        restricoes: item.restricoes || [],
      })),
      pagamento: metodoPagamento,
      total: calcularTotal(),
    };

    const resultado = await criarPedido(payload);

    if (!resultado?.senha) throw new Error('Resposta inválida: campo "senha" ausente');

    // Salva o resumo ANTES de limpar o carrinho (a tela de senha só lê o sessionStorage)
    const carrinhoFinal = getCarrinho();
    sessionStorage.setItem('prontus_ultimo_resumo', JSON.stringify({
      itens: carrinhoFinal.map(i => ({
        produto:    i.nome,
        quantidade: i.quantidade,
        extras:     (i.extras || []).map(e => e.nome),
      })),
      total: calcularTotal(),
    }));

    limparCarrinho();
    const params = new URLSearchParams({ senha: resultado.senha });
    if (resultado.id) params.set('pedido_id', resultado.id);
    window.location.href = `/src/totem/pages/senha.php?${params.toString()}`;
  } catch (err) {
    console.error(err);
    if (alertEl) alertEl.classList.remove('hidden');
    btn.disabled = false;
    btn.textContent = 'Confirmar pagamento';
    processando = false;
  }
}
