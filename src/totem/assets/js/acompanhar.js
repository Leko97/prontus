import { getParam } from '/src/shared/js/utils.js';

const API_BASE = window.PRONTUS_API_URL || '/api';

const STATUS_LABELS = {
  recebido:   { label: 'Recebido',   icon: '🔔', cor: '#e67e22' },
  'em-preparo': { label: 'Em Preparo', icon: '🔥', cor: '#e74c3c' },
  pronto:     { label: 'Pronto para retirada!', icon: '✅', cor: '#27ae60' },
  finalizado: { label: 'Finalizado', icon: '🎉', cor: '#7f8c8d' },
};

let pollingId   = null;
let emAndamento = false;
let senhaAtual  = null;

document.addEventListener('DOMContentLoaded', () => {
  const inputSenha  = document.getElementById('inputSenha');
  const btnConsultar = document.getElementById('btnConsultar');

  /* Pré-preencher senha vinda da URL */
  const senhaParam = getParam('senha');
  if (senhaParam) {
    inputSenha.value = senhaParam.replace('#', '');
    consultar(senhaParam.replace('#', ''));
  }

  btnConsultar.addEventListener('click', () => {
    const val = inputSenha.value.trim();
    if (!val) return;
    consultar(val);
  });

  inputSenha.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') btnConsultar.click();
  });

  /* Parar polling ao sair da página */
  window.addEventListener('pagehide', pararPolling);
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) pararPolling();
    else if (senhaAtual) iniciarPolling(senhaAtual);
  });
});

async function consultar(senha) {
  senhaAtual = senha;
  pararPolling();
  await buscarStatus(senha);
  iniciarPolling(senha);
}

function iniciarPolling(senha) {
  pararPolling();
  pollingId = setInterval(() => buscarStatus(senha), 5000);
}

function pararPolling() {
  if (pollingId !== null) {
    clearInterval(pollingId);
    pollingId = null;
  }
}

async function buscarStatus(senha) {
  if (emAndamento) return;
  emAndamento = true;

  const area = document.getElementById('resultadoArea');

  try {
    const res = await fetch(`${API_BASE}/pedidos?senha=${encodeURIComponent(senha)}`);

    if (res.status === 404) {
      area.innerHTML = renderNaoEncontrado(senha);
      pararPolling();
      return;
    }

    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const pedido = await res.json();
    area.innerHTML = renderStatus(pedido);

    /* Parar polling em estados terminais */
    if (pedido.status === 'finalizado') pararPolling();
  } catch {
    area.innerHTML = `
      <div style="text-align:center;color:var(--color-text-muted);padding:24px">
        ⚠️ Erro ao consultar. Tente novamente.
      </div>`;
  } finally {
    emAndamento = false;
  }
}

function renderStatus(pedido) {
  const info = STATUS_LABELS[pedido.status] || STATUS_LABELS.recebido;
  const pronto = pedido.status === 'pronto';

  return `
    <div style="
      border-radius:16px;
      border:2px solid ${info.cor};
      background:${pronto ? '#f0fff4' : 'var(--color-surface)'};
      padding:24px;
      text-align:center;
    ">
      <div style="font-size:48px;margin-bottom:8px">${info.icon}</div>
      <div class="senha-badge" style="margin:0 auto 16px;display:inline-block">
        #${String(pedido.senha).replace('#', '')}
      </div>
      <div style="font-size:var(--text-xl);font-weight:700;color:${info.cor};margin-bottom:8px">
        ${info.label}
      </div>
      ${pronto ? `
        <div style="margin-top:12px;padding:12px;background:#d4edda;border-radius:10px;color:#155724;font-weight:600">
          Retire seu pedido no balcão
        </div>` : ''}
      <div style="margin-top:16px;font-size:var(--text-xs);color:var(--color-text-muted)">
        Atualiza automaticamente a cada 5 segundos
      </div>
    </div>
  `;
}

function renderNaoEncontrado(senha) {
  return `
    <div style="text-align:center;color:var(--color-text-muted);padding:32px">
      <div style="font-size:40px;margin-bottom:12px">🔍</div>
      <div style="font-weight:600;margin-bottom:8px">Senha #${senha} não encontrada</div>
      <div style="font-size:var(--text-sm)">Verifique o número e tente novamente.</div>
    </div>
  `;
}
