import { getPedidosHistorico } from '/src/shared/js/api.js';
import { formatCurrency, formatDateTime, statusLabel } from '/src/shared/js/utils.js';
import { initAdminPage, openModal, closeModal } from './admin.js';

await initAdminPage();

/* -------- Defaults de data (últimos 30 dias) -------- */
const hoje = new Date();
const ha30dias = new Date();
ha30dias.setDate(hoje.getDate() - 30);

function toDateInput(date) {
  return date.toISOString().slice(0, 10);
}

document.getElementById('filtroDataInicio').value = toDateInput(ha30dias);
document.getElementById('filtroDataFim').value     = toDateInput(hoje);

/* -------- Badges -------- */
function badgeStatus(status) {
  const map = {
    'recebido':   'badge-info',
    'em-preparo': 'badge-warning',
    'pronto':     'badge-success',
    'finalizado': 'badge-neutral',
  };
  return map[status] ?? 'badge-neutral';
}

/* -------- Carregar dados -------- */
let todosPedidos = [];

async function load(params = {}) {
  const tbody = document.getElementById('pedidosTbody');
  tbody.innerHTML = `<tr><td colspan="7" class="table-empty"><div class="spinner-center"><div class="spinner"></div></div></td></tr>`;

  try {
    todosPedidos = await getPedidosHistorico(params);
    renderTabela(todosPedidos);
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="7" class="table-empty">Erro ao carregar pedidos.</td></tr>`;
    console.error(err);
  }
}

function renderTabela(pedidos) {
  const tbody = document.getElementById('pedidosTbody');

  if (!pedidos.length) {
    tbody.innerHTML = `
      <tr><td colspan="7" class="table-empty">
        <div class="table-empty-icon">📋</div>
        Nenhum pedido encontrado.
      </td></tr>`;
    return;
  }

  tbody.innerHTML = pedidos.map(p => `
    <tr>
      <td><strong>${p.senha}</strong></td>
      <td>${formatDateTime(p.horario)}</td>
      <td><span class="badge ${badgeStatus(p.status)}">${statusLabel(p.status)}</span></td>
      <td>${p.pagamento ?? '—'}</td>
      <td>${formatCurrency(p.total)}</td>
      <td>${p.itens.length} item(s)</td>
      <td>
        <button class="btn-action" title="Ver detalhes" data-id="${p.id}">👁</button>
      </td>
    </tr>
  `).join('');

  tbody.querySelectorAll('[data-id]').forEach(btn => {
    btn.addEventListener('click', () => {
      const pedido = todosPedidos.find(p => p.id === Number(btn.dataset.id));
      if (pedido) abrirDetalhes(pedido);
    });
  });
}

/* -------- Filtrar -------- */
document.getElementById('btnFiltrar')?.addEventListener('click', () => {
  const params = {};
  const dataInicio = document.getElementById('filtroDataInicio').value;
  const dataFim    = document.getElementById('filtroDataFim').value;
  const status     = document.getElementById('filtroStatus').value;
  const senha      = document.getElementById('filtroBusca').value.trim();

  if (dataInicio) params.data_inicio = dataInicio;
  if (dataFim)    params.data_fim    = dataFim;
  if (status)     params.status      = status;
  if (senha)      params.senha       = senha;

  load(params);
});

/* -------- Modal de detalhes -------- */
function abrirDetalhes(pedido) {
  document.getElementById('modalDetalhesTitulo').textContent = `Pedido ${pedido.senha}`;

  const itensHtml = pedido.itens.map(item => {
    const extras = item.extras?.length
      ? `<div style="font-size:var(--text-sm);color:var(--color-success)">+ ${item.extras.map(e => `${e.nome}${e.quantidade > 1 ? ` (×${e.quantidade})` : ''}`).join(', ')}</div>`
      : '';
    const remocoes = item.remocoes?.length
      ? `<div style="font-size:var(--text-sm);color:var(--color-danger)">− ${item.remocoes.join(', ')}</div>`
      : '';
    const restricoes = item.restricoes?.length
      ? `<div style="font-size:var(--text-sm);margin-top:2px">${item.restricoes.map(r => `<span class="tag-restricao ${r}" style="font-size:11px">${r}</span>`).join(' ')}</div>`
      : '';
    return `
      <div style="display:flex;justify-content:space-between;padding:var(--space-2) 0;border-bottom:1px solid var(--color-border)">
        <div>
          <strong>${item.quantidade}× ${item.produto}</strong>
          ${extras}${remocoes}${restricoes}
        </div>
      </div>`;
  }).join('');

  document.getElementById('modalDetalhesCorpo').innerHTML = itensHtml || '<p>Sem itens.</p>';

  let tempoPrep = '—';
  if (pedido.preparado_em && pedido.horario) {
    const diff = Math.round((new Date(pedido.preparado_em) - new Date(pedido.horario)) / 60000);
    tempoPrep = diff >= 0 ? `${diff} min` : '—';
  }

  document.getElementById('modalDetalhesRodape').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);width:100%;font-size:var(--text-sm)">
      <div><strong>Total:</strong> ${formatCurrency(pedido.total)}</div>
      <div><strong>Status:</strong> <span class="badge ${badgeStatus(pedido.status)}">${statusLabel(pedido.status)}</span></div>
      <div><strong>Pagamento:</strong> ${pedido.pagamento ?? '—'}</div>
      <div><strong>Horário:</strong> ${formatDateTime(pedido.horario)}</div>
      <div><strong>Tempo de preparo:</strong> ${tempoPrep}</div>
    </div>
    <button class="btn btn-ghost" data-close-modal style="margin-top:var(--space-3)">Fechar</button>
  `;

  openModal('modalDetalhes');
}

/* Carregar com defaults */
load({
  data_inicio: toDateInput(ha30dias),
  data_fim:    toDateInput(hoje),
});
