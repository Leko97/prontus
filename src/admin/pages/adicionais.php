<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Adicionais — Prontus Admin'; require_once __DIR__ . '/inc/head.php'; ?>
<body>
  <div class="admin-layout">

    <aside id="sidebar" class="sidebar"></aside>

    <div class="admin-content">
      <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px">
          <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
            <span></span><span></span><span></span>
          </button>
          <h1 class="topbar-title">Adicionais</h1>
        </div>
        <div class="topbar-actions">
          <button class="btn btn-primary btn-sm" id="btnNovo">+ Novo Adicional</button>
        </div>
      </header>

      <main class="admin-main">

        <div class="page-header">
          <div class="page-header-left">
            <h1>Gerenciar Adicionais</h1>
            <p>Extras e personalizações disponíveis nos produtos</p>
          </div>
          <button class="btn btn-primary" id="btnNovoTop">+ Novo Adicional</button>
        </div>

        <div class="search-bar">
          <div class="search-input-wrapper">
            <span class="search-icon">🔍</span>
            <input type="text" id="busca" placeholder="Buscar adicional ou produto…">
          </div>
        </div>

        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Preço</th>
                <th>Produto</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody id="adicionaisTbody">
              <tr><td colspan="4" class="table-empty">
                <div class="spinner-center"><div class="spinner"></div></div>
              </td></tr>
            </tbody>
          </table>
        </div>

      </main>
    </div>
  </div>

  <!-- Modal inline -->
  <div id="modalForm" class="modal-overlay hidden">
    <div class="modal">
      <div class="modal-header">
        <h2 class="modal-title" id="modalTitle">Novo Adicional</h2>
        <button class="modal-close" data-close-modal>✕</button>
      </div>
      <form id="formAdicional" novalidate>
        <input type="hidden" id="adicionalId">
        <div class="form-group">
          <label class="form-label" for="adicionalNome">Nome <span class="required">*</span></label>
          <input type="text" id="adicionalNome" class="form-control" placeholder="Ex: Bacon extra" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="adicionalPreco">Preço (R$) <span class="required">*</span></label>
          <input type="number" id="adicionalPreco" class="form-control" step="0.01" min="0" placeholder="0,00" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="adicionalProduto">Produto associado</label>
          <select id="adicionalProduto" class="form-control">
            <option value="">Selecione um produto…</option>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" data-close-modal>Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <script type="module" src="/src/admin/assets/js/adicionais.js"></script>
  <script>
    document.getElementById('btnNovoTop')?.addEventListener('click', () => {
      document.getElementById('btnNovo').click();
    });
  </script>
</body>
</html>
