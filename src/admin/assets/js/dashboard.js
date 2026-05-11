import { getMetricas, getPedidos } from '/src/shared/js/api.js';
import { formatCurrency, formatTime } from '/src/shared/js/utils.js';
import { initAdminPage } from './admin.js';

await initAdminPage();

/* -------- Métricas -------- */
async function loadMetricas() {
  try {
    const m = await getMetricas();

    document.getElementById('metTotal').textContent    = m.total_pedidos;
    document.getElementById('metTempo').textContent    = `${m.tempo_medio_minutos} min`;
    document.getElementById('metVolume').textContent   = formatCurrency(m.volume_vendas);
    document.getElementById('metAbertos').textContent  = m.pedidos_em_aberto;

    renderChart(m.pedidos_por_hora);
  } catch (err) {
    console.error('Erro ao carregar métricas:', err);
  }
}

/* -------- Gráfico de barras (canvas puro) -------- */
function renderChart(data) {
  const canvas = document.getElementById('chartCanvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const dpr = window.devicePixelRatio || 1;
  const W = canvas.offsetWidth;
  const H = 200;

  canvas.width  = W * dpr;
  canvas.height = H * dpr;
  canvas.style.height = H + 'px';
  ctx.scale(dpr, dpr);

  const max    = Math.max(...data, 1);
  const pad    = { top: 16, right: 8, bottom: 32, left: 32 };
  const chartW = W - pad.left - pad.right;
  const chartH = H - pad.top - pad.bottom;
  const barW   = (chartW / data.length) * 0.65;
  const gap    = (chartW / data.length) * 0.35;

  ctx.clearRect(0, 0, W, H);

  /* Grade horizontal */
  ctx.strokeStyle = '#e9ecef';
  ctx.lineWidth = 1;
  for (let i = 0; i <= 4; i++) {
    const y = pad.top + (chartH / 4) * i;
    ctx.beginPath();
    ctx.moveTo(pad.left, y);
    ctx.lineTo(W - pad.right, y);
    ctx.stroke();
  }

  /* Barras */
  data.forEach((val, i) => {
    const barH = (val / max) * chartH;
    const x    = pad.left + i * (barW + gap);
    const y    = pad.top + chartH - barH;

    const grad = ctx.createLinearGradient(0, y, 0, y + barH);
    grad.addColorStop(0, '#FF6B35');
    grad.addColorStop(1, '#ffaa80');

    ctx.fillStyle = val > 0 ? grad : '#f0f0f0';
    ctx.beginPath();
    ctx.roundRect(x, y, barW, barH, [4, 4, 0, 0]);
    ctx.fill();

    /* Label hora */
    if (i % 3 === 0) {
      ctx.fillStyle = '#6C757D';
      ctx.font = '10px Inter, system-ui, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(`${String(i).padStart(2, '0')}h`, x + barW / 2, H - 8);
    }
  });

  /* Eixo Y */
  ctx.fillStyle = '#6C757D';
  ctx.font = '10px Inter, system-ui, sans-serif';
  ctx.textAlign = 'right';
  for (let i = 0; i <= 4; i++) {
    const val = Math.round((max / 4) * (4 - i));
    const y   = pad.top + (chartH / 4) * i;
    ctx.fillText(val, pad.left - 6, y + 4);
  }
}

/* -------- Últimos pedidos -------- */
async function loadUltimosPedidos() {
  const tbody = document.getElementById('recentTbody');
  if (!tbody) return;

  try {
    const pedidos = await getPedidos();
    const ultimos = pedidos.slice(0, 5);

    if (!ultimos.length) {
      tbody.innerHTML = `<tr><td colspan="4" class="table-empty">Nenhum pedido ainda.</td></tr>`;
      return;
    }

    const statusClass = { recebido: 'info', 'em-preparo': 'warning', pronto: 'success', finalizado: 'neutral' };
    const statusLabel = { recebido: 'Recebido', 'em-preparo': 'Em Preparo', pronto: 'Pronto', finalizado: 'Finalizado' };

    tbody.innerHTML = ultimos.map(p => `
      <tr>
        <td><strong>${p.senha}</strong></td>
        <td>${p.itens.map(i => `${i.quantidade}× ${i.produto}`).join(', ')}</td>
        <td><span class="badge badge-${statusClass[p.status] || 'neutral'}">${statusLabel[p.status] || p.status}</span></td>
        <td style="color:var(--color-text-muted)">${formatTime(p.horario)}</td>
      </tr>
    `).join('');
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4" class="table-empty">Erro ao carregar pedidos.</td></tr>`;
    console.error(err);
  }
}

loadMetricas();
loadUltimosPedidos();
