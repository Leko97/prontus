import { getRelatorio, getRelatorioCSVUrl } from '/src/shared/js/api.js';
import { formatCurrency } from '/src/shared/js/utils.js';
import { initAdminPage, showToast } from './admin.js';

await initAdminPage();

/* -------- Defaults: últimos 7 dias -------- */
function formatDate(d) {
  return d.toISOString().slice(0, 10);
}

const hoje = new Date();
const seteDiasAtras = new Date(hoje);
seteDiasAtras.setDate(seteDiasAtras.getDate() - 6);

document.getElementById('dataInicio').value = formatDate(seteDiasAtras);
document.getElementById('dataFim').value    = formatDate(hoje);

/* -------- Gerar relatório -------- */
document.getElementById('btnGerar').addEventListener('click', gerarRelatorio);

async function gerarRelatorio() {
  const dataInicio = document.getElementById('dataInicio').value;
  const dataFim    = document.getElementById('dataFim').value;

  if (!dataInicio || !dataFim) {
    showToast('Selecione as datas de início e fim.', 'error');
    return;
  }

  const btn = document.getElementById('btnGerar');
  btn.disabled = true;
  btn.textContent = 'Carregando…';

  try {
    const dados = await getRelatorio({ data_inicio: dataInicio, data_fim: dataFim });
    renderRelatorio(dados, dataInicio, dataFim);
    document.getElementById('estadoVazio').style.display = 'none';
    document.getElementById('conteudoRelatorio').style.display = 'block';
  } catch (err) {
    showToast('Erro ao gerar relatório.', 'error');
    console.error(err);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Gerar relatório';
  }
}

/* -------- Render -------- */
function renderRelatorio(dados, dataInicio, dataFim) {
  const { resumo, por_dia, por_forma_pagamento, produtos_mais_vendidos, pedidos_por_hora } = dados;

  /* KPIs */
  document.getElementById('kpiPedidos').textContent  = resumo.total_pedidos;
  document.getElementById('kpiFaturado').textContent = formatCurrency(resumo.total_faturado);
  document.getElementById('kpiTicket').textContent   = formatCurrency(resumo.ticket_medio);
  document.getElementById('kpiTempo').textContent    = `${resumo.tempo_medio_minutos} min`;

  /* Gráfico por dia */
  renderChart(por_dia);

  /* Formas de pagamento */
  const tbodyPag = document.getElementById('tbodyPagamento');
  if (por_forma_pagamento.length) {
    tbodyPag.innerHTML = por_forma_pagamento.map(p => `
      <tr>
        <td>${p.pagamento || '—'}</td>
        <td>${p.total}</td>
        <td>${p.percentual}%</td>
      </tr>
    `).join('');
  } else {
    tbodyPag.innerHTML = '<tr><td colspan="3" class="table-empty">Sem dados</td></tr>';
  }

  /* Top produtos */
  const tbodyProd = document.getElementById('tbodyProdutos');
  if (produtos_mais_vendidos.length) {
    tbodyProd.innerHTML = produtos_mais_vendidos.map(p => `
      <tr>
        <td>${p.produto_nome}</td>
        <td>${p.quantidade}</td>
        <td>${formatCurrency(p.total_faturado)}</td>
      </tr>
    `).join('');
  } else {
    tbodyProd.innerHTML = '<tr><td colspan="3" class="table-empty">Sem dados</td></tr>';
  }
}

/* -------- Gráfico (canvas puro) -------- */
function renderChart(porDia) {
  const canvas = document.getElementById('chartCanvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const dpr = window.devicePixelRatio || 1;
  const W   = canvas.offsetWidth;
  const H   = 200;

  canvas.width       = W * dpr;
  canvas.height      = H * dpr;
  canvas.style.height = H + 'px';
  ctx.scale(dpr, dpr);

  if (!porDia.length) {
    ctx.clearRect(0, 0, W, H);
    ctx.fillStyle = '#6C757D';
    ctx.font = '14px Inter, system-ui';
    ctx.textAlign = 'center';
    ctx.fillText('Sem dados no período', W / 2, H / 2);
    return;
  }

  const valores = porDia.map(d => parseFloat(d.faturado) || 0);
  const max     = Math.max(...valores, 1);
  const pad     = { top: 16, right: 8, bottom: 36, left: 52 };
  const chartW  = W - pad.left - pad.right;
  const chartH  = H - pad.top - pad.bottom;
  const barW    = (chartW / porDia.length) * 0.65;
  const gap     = (chartW / porDia.length) * 0.35;

  ctx.clearRect(0, 0, W, H);

  /* Grade */
  ctx.strokeStyle = '#e9ecef';
  ctx.lineWidth   = 1;
  for (let i = 0; i <= 4; i++) {
    const y = pad.top + (chartH / 4) * i;
    ctx.beginPath();
    ctx.moveTo(pad.left, y);
    ctx.lineTo(W - pad.right, y);
    ctx.stroke();

    const val = (max / 4) * (4 - i);
    ctx.fillStyle = '#6C757D';
    ctx.font = '10px Inter, system-ui';
    ctx.textAlign = 'right';
    ctx.fillText(formatCurrency(val), pad.left - 6, y + 4);
  }

  /* Barras */
  porDia.forEach((d, i) => {
    const val  = parseFloat(d.faturado) || 0;
    const barH = (val / max) * chartH;
    const x    = pad.left + i * (barW + gap);
    const y    = pad.top + chartH - barH;

    const grad = ctx.createLinearGradient(0, y, 0, y + barH);
    grad.addColorStop(0, '#FF6B35');
    grad.addColorStop(1, '#ffaa80');

    ctx.fillStyle = val > 0 ? grad : '#f0f0f0';
    ctx.beginPath();
    ctx.roundRect(x, y, barW, barH || 1, [4, 4, 0, 0]);
    ctx.fill();

    /* Label data */
    ctx.fillStyle = '#6C757D';
    ctx.font = '9px Inter, system-ui';
    ctx.textAlign = 'center';
    const label = d.data ? d.data.slice(5) : '';
    ctx.fillText(label, x + barW / 2, H - pad.bottom + 14);
  });
}

/* -------- Exportar CSV -------- */
document.getElementById('btnExportarCSV').addEventListener('click', () => {
  const dataInicio = document.getElementById('dataInicio').value;
  const dataFim    = document.getElementById('dataFim').value;

  if (!dataInicio || !dataFim) {
    showToast('Selecione as datas antes de exportar.', 'error');
    return;
  }

  const url = getRelatorioCSVUrl({ data_inicio: dataInicio, data_fim: dataFim });
  window.open(url, '_blank');
});
