import { getSenhas } from '/src/shared/js/api.js';
import { currentTimeShort } from '/src/shared/js/utils.js';

let estadoAnterior = null;
let intervaloPolling = null;

/* -------- Init -------- */
document.addEventListener('DOMContentLoaded', () => {
  atualizarRelogio();
  setInterval(atualizarRelogio, 1000);

  iniciarPolling();

  document.getElementById('btnFullscreen')?.addEventListener('click', () => {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(() => {});
    } else {
      document.exitFullscreen().catch(() => {});
    }
  });
});

/* -------- Relógio -------- */
function atualizarRelogio() {
  const el = document.getElementById('displayClock');
  if (el) el.textContent = currentTimeShort();
}

/* -------- Polling -------- */
function iniciarPolling() {
  let emAndamento = false;

  async function tick() {
    if (emAndamento) return;
    emAndamento = true;
    try {
      const senhas = await getSenhas();
      setConexao(true);

      const hash = JSON.stringify(senhas);
      if (hash !== estadoAnterior) {
        const anterior = estadoAnterior ? JSON.parse(estadoAnterior) : null;
        estadoAnterior = hash;
        renderSenhas(senhas, anterior);
      }
    } catch (err) {
      console.warn('[display] Erro no polling:', err.message);
      setConexao(false);
    } finally {
      emAndamento = false;
    }
  }

  tick();
  intervaloPolling = setInterval(tick, 2000);
}

function setConexao(online) {
  const dot = document.getElementById('connDot');
  if (dot) dot.classList.toggle('offline', !online);
}

/* -------- Render -------- */
function renderSenhas(senhas, anterior) {
  renderColuna('emPreparoList', senhas.em_preparo || [], anterior?.em_preparo || [], false);
  renderColuna('prontasList',   senhas.prontas    || [], anterior?.prontas    || [], true);
}

function renderColuna(containerId, senhas, anteriores, eColunaProntas) {
  const container = document.getElementById(containerId);
  if (!container) return;

  if (!senhas.length) {
    container.innerHTML = `
      <div class="display-col-empty">
        <div class="display-col-empty-icon">${eColunaProntas ? '✅' : '⏳'}</div>
        <div class="display-col-empty-text">
          ${eColunaProntas ? 'Nenhuma senha pronta' : 'Nenhum pedido em preparo'}
        </div>
      </div>`;
    return;
  }

  const novas = senhas.filter(s => !anteriores.includes(s));

  container.innerHTML = senhas.map(senha => {
    const isNova = novas.includes(senha);
    const classe = eColunaProntas && isNova ? 'senha-pill chamada' : 'senha-pill';
    return `<div class="${classe}">${senha}</div>`;
  }).join('');
}
