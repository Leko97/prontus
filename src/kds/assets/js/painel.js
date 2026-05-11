import { atualizarStatusPedido } from '/src/shared/js/api.js';
import { formatTime, formatElapsed, minutesAgo, proximoStatus, btnAvancarLabel, restricaoLabel } from '/src/shared/js/utils.js';
import { iniciarPolling } from './polling.js';

const COLUNAS = ['recebido', 'em-preparo', 'pronto'];

/* Estado local de pedidos para simular avanço de status no modo mock */
let pedidosLocal = [];

/* -------- Init -------- */
document.addEventListener('DOMContentLoaded', () => {
  atualizarRelogio();
  setInterval(atualizarRelogio, 1000);
  setInterval(atualizarTempos, 30000); // atualiza "há X min" a cada 30s

  iniciarPolling(onNovosDados, 2000);

  document.getElementById('btnLogout')?.addEventListener('click', () => {
    window.location.href = '/src/kds/pages/login.html';
  });
});

/* -------- Relógio -------- */
function atualizarRelogio() {
  const el = document.getElementById('kdsClock');
  if (el) el.textContent = new Date().toLocaleTimeString('pt-BR', {
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  });
}

/* -------- Callback do polling -------- */
function onNovosDados(pedidos) {
  /* Mescla com estado local: respeita mudanças de status feitas localmente */
  const statusLocal = {};
  pedidosLocal.forEach(p => { statusLocal[p.id] = p.status; });

  pedidosLocal = pedidos.map(p => ({
    ...p,
    status: statusLocal[p.id] ?? p.status,
  })).filter(p => p.status !== 'finalizado');

  renderTudo();
  atualizarTotalBadge();
  setConexao(true);
}

function setConexao(online) {
  const dot = document.getElementById('connDot');
  if (dot) dot.classList.toggle('offline', !online);
}

/* -------- Render completo -------- */
function renderTudo() {
  COLUNAS.forEach(status => {
    const colBody = document.querySelector(`.kds-col[data-status="${status}"] .kds-col-body`);
    const colCount = document.querySelector(`.kds-col[data-status="${status}"] .kds-col-count`);
    if (!colBody) return;

    const pedidosCol = pedidosLocal.filter(p => p.status === status);

    if (colCount) colCount.textContent = pedidosCol.length;

    if (!pedidosCol.length) {
      colBody.innerHTML = `
        <div class="kds-col-empty">
          <div class="kds-col-empty-icon">✓</div>
          <div>Nenhum pedido</div>
        </div>`;
      return;
    }

    /* Ordena mais antigo primeiro */
    const ordenados = [...pedidosCol].sort(
      (a, b) => new Date(a.horario) - new Date(b.horario)
    );

    colBody.innerHTML = ordenados.map(p => renderCard(p)).join('');

    /* Botões de avançar status */
    colBody.querySelectorAll('.btn-avancar').forEach(btn => {
      btn.addEventListener('click', () => avancarStatus(Number(btn.dataset.id), btn.dataset.status));
    });
  });
}

/* -------- Render de um card -------- */
function renderCard(pedido) {
  const mins     = minutesAgo(pedido.horario);
  const elapsed  = formatElapsed(pedido.horario);
  const atrasado = pedido.status === 'em-preparo' && mins >= 10;
  const proximo  = proximoStatus(pedido.status);
  const btnLabel = btnAvancarLabel(pedido.status);

  /* Restrições consolidadas de todos os itens */
  const todasRestricoes = [...new Set(
    (pedido.itens || []).flatMap(i => i.restricoes || [])
  )];

  const itensHtml = (pedido.itens || []).map(item => `
    <div class="pedido-item">
      <div class="pedido-item-nome">${item.quantidade}× ${item.produto}</div>
      ${item.extras?.length
        ? `<div class="pedido-item-extras">+ ${item.extras.join(', ')}</div>`
        : ''}
      ${item.remocoes?.length
        ? `<div class="pedido-item-remocoes">✕ Sem: ${item.remocoes.join(', ')}</div>`
        : ''}
    </div>
  `).join('');

  const restricoesHtml = todasRestricoes.map(r =>
    `<span class="tag-restricao">${restricaoLabel(r)}</span>`
  ).join('');

  return `
    <div class="pedido-card ${atrasado ? 'atrasado' : ''}" data-id="${pedido.id}">
      <div class="pedido-card-header">
        <span class="pedido-senha">${pedido.senha}</span>
        <div class="pedido-meta">
          <span class="pedido-hora">${formatTime(pedido.horario)}</span>
          <span class="pedido-elapsed ${mins >= 10 ? 'urgente' : ''}">${elapsed}</span>
        </div>
      </div>

      <div class="pedido-card-body">
        ${restricoesHtml ? `<div class="pedido-restricoes">${restricoesHtml}</div>` : ''}
        <div class="pedido-itens">${itensHtml}</div>
      </div>

      <div class="pedido-card-footer">
        <button class="btn-avancar ${pedido.status}"
          data-id="${pedido.id}"
          data-status="${pedido.status}"
          ${!proximo && pedido.status !== 'pronto' ? 'disabled' : ''}>
          ${btnLabel}
        </button>
      </div>
    </div>
  `;
}

/* -------- Avançar status -------- */
async function avancarStatus(id, statusAtual) {
  const btn = document.querySelector(`.btn-avancar[data-id="${id}"]`);
  if (btn) { btn.disabled = true; btn.textContent = '…'; }

  const proximo = proximoStatus(statusAtual);

  /* Atualiza estado local imediatamente (otimista) */
  const idx = pedidosLocal.findIndex(p => p.id === id);
  if (idx !== -1) {
    if (proximo === null) {
      pedidosLocal.splice(idx, 1); // "Pronto → Finalizado" remove da tela
    } else {
      pedidosLocal[idx] = { ...pedidosLocal[idx], status: proximo };
    }
  }

  renderTudo();
  atualizarTotalBadge();

  /* Chama API em background (sem bloquear) */
  try {
    await atualizarStatusPedido(id, proximo ?? 'finalizado');
  } catch (err) {
    console.warn('[kds] Erro ao atualizar status:', err.message);
  }
}

/* -------- Atualizar tempos decorridos -------- */
function atualizarTempos() {
  document.querySelectorAll('.pedido-elapsed').forEach(el => {
    const card = el.closest('.pedido-card');
    const p    = pedidosLocal.find(x => x.id === Number(card?.dataset.id));
    if (p) {
      const mins = minutesAgo(p.horario);
      el.textContent = formatElapsed(p.horario);
      el.classList.toggle('urgente', mins >= 10);
      card.classList.toggle('atrasado', p.status === 'em-preparo' && mins >= 10);
    }
  });
}

function atualizarTotalBadge() {
  const badge = document.getElementById('totalBadge');
  if (badge) badge.textContent = `${pedidosLocal.length} pedido(s) ativo(s)`;
}
