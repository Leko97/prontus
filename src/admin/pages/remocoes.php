<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Remoções — Prontus Admin'; require_once __DIR__ . '/inc/head.php'; ?>
<body>
  <div class="admin-layout">

    <aside id="sidebar" class="sidebar"></aside>

    <div class="admin-content">
      <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px">
          <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
            <span></span><span></span><span></span>
          </button>
          <h1 class="topbar-title">Ingredientes Removíveis</h1>
        </div>
      </header>

      <main class="admin-main">

        <div class="page-header">
          <div class="page-header-left">
            <h1>Gerenciar Remoções</h1>
            <p>Configure quais ingredientes o cliente pode remover de cada produto</p>
          </div>
        </div>

        <!-- Seletor de produto -->
        <div class="form-card" style="margin-bottom:var(--space-5)">
          <h3 class="form-section-title">Selecionar Produto</h3>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label" for="selectProduto">Produto</label>
            <select id="selectProduto" class="form-control">
              <option value="">— Selecione um produto —</option>
            </select>
          </div>
        </div>

        <!-- Lista de remoções -->
        <div class="form-card" id="cardRemocoes" style="display:none">
          <h3 class="form-section-title" id="tituloRemocoes">Ingredientes removíveis</h3>

          <div id="listaRemocoes" style="margin-bottom:var(--space-4)">
            <p style="color:var(--color-text-muted)">Selecione um produto para ver as remoções.</p>
          </div>

          <div style="display:flex;gap:8px;align-items:center">
            <input type="text" id="inputNovaRemocao" class="form-control"
              placeholder="Ex: Cebola, Alface, Maionese…" style="flex:1">
            <button type="button" class="btn btn-primary" id="btnAddRemocao">+ Adicionar</button>
          </div>
        </div>

      </main>
    </div>
  </div>

  <script type="module" src="/src/admin/assets/js/remocoes.js"></script>
</body>
</html>
