<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Relatórios — Prontus Admin'; require_once __DIR__ . '/inc/head.php'; ?>
<body>
  <div class="admin-layout">

    <aside id="sidebar" class="sidebar"></aside>

    <div class="admin-content">
      <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px">
          <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
            <span></span><span></span><span></span>
          </button>
          <h1 class="topbar-title">Relatórios</h1>
        </div>
        <div class="topbar-actions">
          <button class="btn btn-ghost btn-sm" id="btnExportarCSV">⬇ Exportar CSV</button>
        </div>
      </header>

      <main class="admin-main">

        <div class="page-header">
          <div class="page-header-left">
            <h1>Relatórios de Vendas</h1>
            <p>Analise o desempenho do estabelecimento por período</p>
          </div>
        </div>

        <!-- Filtros -->
        <div class="form-card" style="margin-bottom:var(--space-5)">
          <div style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap">
            <div class="form-group" style="margin:0;flex:1;min-width:140px">
              <label class="form-label" for="dataInicio">Data Início</label>
              <input type="date" id="dataInicio" class="form-control">
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:140px">
              <label class="form-label" for="dataFim">Data Fim</label>
              <input type="date" id="dataFim" class="form-control">
            </div>
            <button class="btn btn-primary" id="btnGerar" style="height:42px;white-space:nowrap">
              Gerar relatório
            </button>
          </div>
        </div>

        <!-- Estado vazio -->
        <div id="estadoVazio" style="text-align:center;padding:60px 20px;color:var(--color-text-muted)">
          <div style="font-size:48px;margin-bottom:16px">📊</div>
          <p>Selecione um período e clique em <strong>Gerar relatório</strong></p>
        </div>

        <!-- Conteúdo do relatório -->
        <div id="conteudoRelatorio" style="display:none">

          <!-- KPIs -->
          <div class="metrics-grid" style="margin-bottom:var(--space-5)">
            <div class="metric-card">
              <div class="metric-icon">📦</div>
              <div class="metric-label">Total de Pedidos</div>
              <div class="metric-value" id="kpiPedidos">—</div>
              <div class="metric-sub">No período</div>
            </div>
            <div class="metric-card success">
              <div class="metric-icon">💰</div>
              <div class="metric-label">Faturamento</div>
              <div class="metric-value" id="kpiFaturado">—</div>
              <div class="metric-sub">No período</div>
            </div>
            <div class="metric-card info">
              <div class="metric-icon">🎯</div>
              <div class="metric-label">Ticket Médio</div>
              <div class="metric-value" id="kpiTicket">—</div>
              <div class="metric-sub">Por pedido</div>
            </div>
            <div class="metric-card warning">
              <div class="metric-icon">⏱️</div>
              <div class="metric-label">Tempo Médio</div>
              <div class="metric-value" id="kpiTempo">—</div>
              <div class="metric-sub">Por pedido</div>
            </div>
          </div>

          <!-- Gráfico por dia -->
          <div class="chart-section" style="margin-bottom:var(--space-5)">
            <h2 class="chart-title">Faturamento por Dia</h2>
            <canvas id="chartCanvas" height="200"></canvas>
          </div>

          <!-- Formas de pagamento + Produtos mais vendidos -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5)">

            <div class="table-wrapper">
              <h2 style="font-size:var(--text-base);font-weight:var(--font-semibold);margin-bottom:var(--space-3)">
                Formas de Pagamento
              </h2>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Forma</th>
                    <th>Pedidos</th>
                    <th>%</th>
                  </tr>
                </thead>
                <tbody id="tbodyPagamento">
                  <tr><td colspan="3" class="table-empty">—</td></tr>
                </tbody>
              </table>
            </div>

            <div class="table-wrapper">
              <h2 style="font-size:var(--text-base);font-weight:var(--font-semibold);margin-bottom:var(--space-3)">
                Top 10 Produtos
              </h2>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Produto</th>
                    <th>Qtd</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody id="tbodyProdutos">
                  <tr><td colspan="3" class="table-empty">—</td></tr>
                </tbody>
              </table>
            </div>

          </div>

        </div><!-- /conteudoRelatorio -->

      </main>
    </div>
  </div>

  <script type="module" src="/src/admin/assets/js/relatorios.js"></script>
</body>
</html>
