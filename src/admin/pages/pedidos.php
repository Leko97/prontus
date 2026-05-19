<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Pedidos — Prontus Admin'; require_once __DIR__ . '/inc/head.php'; ?>
<body>
  <div class="admin-layout">

    <aside id="sidebar" class="sidebar"></aside>

    <div class="admin-content">
      <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px">
          <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
            <span></span><span></span><span></span>
          </button>
          <h1 class="topbar-title">Pedidos</h1>
        </div>
      </header>

      <main class="admin-main">

        <div class="page-header">
          <div class="page-header-left">
            <h1>Pedidos</h1>
            <p>Histórico de pedidos realizados</p>
          </div>
        </div>

        <div class="filtros-bar">
          <input type="date" id="filtroDataInicio">
          <input type="date" id="filtroDataFim">
          <select id="filtroStatus">
            <option value="">Todos os status</option>
            <option value="recebido">Recebido</option>
            <option value="em-preparo">Em Preparo</option>
            <option value="pronto">Pronto</option>
            <option value="finalizado">Finalizado</option>
          </select>
          <input type="text" id="filtroBusca" placeholder="Buscar senha…">
          <button class="btn btn-primary btn-sm" id="btnFiltrar">Filtrar</button>
        </div>

        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Senha</th>
                <th>Data/Hora</th>
                <th>Status</th>
                <th>Pagamento</th>
                <th>Total</th>
                <th>Itens</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody id="pedidosTbody">
              <tr><td colspan="7" class="table-empty">
                <div class="spinner-center"><div class="spinner"></div></div>
              </td></tr>
            </tbody>
          </table>
        </div>

      </main>
    </div>
  </div>

  <!-- Modal de detalhes -->
  <div id="modalDetalhes" class="modal-overlay hidden">
    <div class="modal" style="max-width:560px">
      <div class="modal-header">
        <h2 class="modal-title" id="modalDetalhesTitulo">Pedido</h2>
        <button class="modal-close" data-close-modal>✕</button>
      </div>
      <div class="modal-body" id="modalDetalhesCorpo" style="padding:var(--space-4) 0"></div>
      <div class="modal-footer" id="modalDetalhesRodape"></div>
    </div>
  </div>

  <script type="module" src="/src/admin/assets/js/pedidos-admin.js"></script>
</body>
</html>
