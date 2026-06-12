import { atualizarStatusPedido, checkAuth, logout } from '/src/shared/js/api.js';
import { formatTime, formatElapsed, minutesAgo, proximoStatus, btnAvancarLabel, restricaoLabel } from '/src/shared/js/utils.js';
import { iniciarPolling } from './polling.js';

const COLUNAS = ['recebido', 'em-preparo', 'pronto'];
let pedidosLocal      = [];
let pedidosFinalizados = [];
let idsConhecidos = new Set();

/* -------- Som -------- */
let audioCtx = null;
let somAtivo = localStorage.getItem('kds_som') !== 'false';

function tocarAlerta() {
  if (!somAtivo) return;
  try {
    audioCtx = audioCtx || new AudioContext();
    const osc  = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.frequency.value = 880;
    gain.gain.setValueAtTime(0.4, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
    osc.start();
    osc.stop(audioCtx.currentTime + 0.4);
  } catch (e) {
    console.warn('[kds] Erro ao tocar alerta:', e);
  }
}

function initSomToggle() {
  const btn = document.getElementById('btnSomToggle');
  if (!btn) return;
  btn.textContent = somAtivo ? '🔔 Som: ON' : '🔕 Som: OFF';
  btn.addEventListener('click', () => {
    somAtivo = !somAtivo;
    localStorage.setItem('kds_som', somAtivo ? 'true' : 'false');
    btn.textContent = somAtivo ? '🔔 Som: ON' : '🔕 Som: OFF';
    /* Inicializa o AudioContext na primeira interação do usuário */
    if (somAtivo && !audioCtx) {
      audioCtx = new AudioContext();
    }
  });
}

/* -------- Init -------- */
document.addEventListener('DOMContentLoaded', async () => {
  try {
    const { logado } = await checkAuth();
    if (!logado) { window.location.href = '/src/kds/pages/login.php'; return; }
  } catch {
    window.location.href = '/src/kds/pages/login.php';
    return;
  }

  atualizarRelogio();
  setInterval(atualizarRelogio, 1000);
  setInterval(atualizarTempos, 30000);

  initSomToggle();
  iniciarPolling(onNovosDados, 2000, () => setConexao(false));

  document.getElementById('btnLogout')?.addEventListener('click', async () => {
    try {
      await logout();
    } finally {
      window.location.href = '/src/kds/pages/login.php';
    }
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

  const novosRecebidos = pedidos.filter(
    p => p.status === 'recebido' && !idsConhecidos.has(p.id)
  );
  if (novosRecebidos.length > 0) tocarAlerta();

  const pedidosMapeados = pedidos.map(p => ({
    ...p,
    status: statusLocal[p.id] ?? p.status,
  }));

  pedidosFinalizados = pedidosMapeados
    .filter(p => p.status === 'finalizado')
    .sort((a, b) => new Date(b.horario) - new Date(a.horario))
    .slice(0, 10);

  pedidosLocal = pedidosMapeados.filter(p => p.status !== 'finalizado');

  idsConhecidos = new Set(pedidos.map(p => p.id));

  renderTudo();
  renderFinalizados();
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
  const btnLabel = btnAvancarLabel(pedido.status);

  /* Restrições consolidadas de todos os itens */
  const todasRestricoes = [...new Set(
    (pedido.itens || []).flatMap(i => i.restricoes || [])
  )];

  const itensHtml = (pedido.itens || []).map(item => `
    <div class="pedido-item">
      <div class="pedido-item-nome">
        <span class="item-qtd">${item.quantidade}×</span>
        <span class="item-produto">${item.produto}</span>
      </div>
      ${item.extras?.length
        ? `<div class="pedido-item-extras">${item.extras.map(e => `+ ${e.nome}${e.quantidade > 1 ? ` ×${e.quantidade}` : ''}`).join('<br>')}</div>`
        : ''}
      ${item.remocoes?.length
        ? `<div class="pedido-item-remocoes">Sem ${item.remocoes.join(', ')}</div>`
        : ''}
    </div>
  `).join('');

  const restricoesHtml = todasRestricoes.map(r =>
    `<span class="tag-restricao">${restricaoLabel(r)}</span>`
  ).join('');

  return `
    <div class="pedido-card ${atrasado ? 'atrasado' : ''}" data-id="${pedido.id}">
      <div class="pedido-card-top">
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

      <button class="btn-avancar ${pedido.status}"
        data-id="${pedido.id}"
        data-status="${pedido.status}">
        ${btnLabel}
      </button>
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

function renderFinalizados() {
  const body  = document.getElementById('finalizadosBody');
  const count = document.getElementById('countFinalizado');
  if (!body) return;

  if (count) count.textContent = pedidosFinalizados.length;

  if (!pedidosFinalizados.length) {
    body.innerHTML = `
      <div class="kds-col-empty">
        <div class="kds-col-empty-icon">🗂️</div>
        <div>Nenhum finalizado</div>
      </div>`;
    return;
  }

  body.innerHTML = pedidosFinalizados.map(p => `
    <div class="pedido-card" data-id="${p.id}">
      <div class="pedido-card-top">
        <span class="pedido-senha">${p.senha}</span>
        <div class="pedido-meta">
          <span class="pedido-hora">${formatTime(p.horario)}</span>
        </div>
      </div>
      <div class="pedido-card-body">
        <div class="pedido-itens">
          ${(p.itens || []).map(i => `
            <div class="pedido-item">
              <div class="pedido-item-nome">
                <span class="item-qtd">${i.quantidade}×</span>
                <span class="item-produto">${i.produto}</span>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    </div>
  `).join('');
}

function atualizarTotalBadge() {
  const badge = document.getElementById('totalBadge');
  if (badge) badge.textContent = `${pedidosLocal.length} pedido(s) ativo(s)`;

  const badgeFin = document.getElementById('totalFinalizadosBadge');
  if (badgeFin) {
    badgeFin.textContent = `${pedidosFinalizados.length} finalizado(s) hoje`;
    badgeFin.style.display = pedidosFinalizados.length > 0 ? '' : 'none';
  }
}
