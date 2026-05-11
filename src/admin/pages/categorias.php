<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Categorias — Prontus Admin'; require_once __DIR__ . '/inc/head.php'; ?>
<body>
  <div class="admin-layout">

    <aside id="sidebar" class="sidebar"></aside>

    <div class="admin-content">
      <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px">
          <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
            <span></span><span></span><span></span>
          </button>
          <h1 class="topbar-title">Categorias</h1>
        </div>
        <div class="topbar-actions">
          <button class="btn btn-primary btn-sm" id="btnNova">+ Nova Categoria</button>
        </div>
      </header>

      <main class="admin-main">

        <div class="page-header">
          <div class="page-header-left">
            <h1>Gerenciar Categorias</h1>
            <p>Organize os produtos por categoria</p>
          </div>
          <button class="btn btn-primary" id="btnNovaTop">+ Nova Categoria</button>
        </div>

        <div class="search-bar">
          <div class="search-input-wrapper">
            <span class="search-icon">🔍</span>
            <input type="text" id="busca" placeholder="Buscar categoria…">
          </div>
        </div>

        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Ícone</th>
                <th>Qtd. de Produtos</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody id="categoriasTbody">
              <tr><td colspan="4" class="table-empty">
                <div class="spinner-center"><div class="spinner"></div></div>
              </td></tr>
            </tbody>
          </table>
        </div>
      </main>
    </div>
  </div>

  <!-- Modal Form -->
  <div id="modalForm" class="modal-overlay hidden">
    <div class="modal">
      <div class="modal-header">
        <h2 class="modal-title" id="modalEditTitle">Nova Categoria</h2>
        <button class="modal-close" data-close-modal>✕</button>
      </div>
      <form id="formCategoria" novalidate>
        <input type="hidden" id="editId">
        <div class="form-group">
          <label class="form-label" for="editNome">Nome <span class="required">*</span></label>
          <input type="text" id="editNome" class="form-control" placeholder="Ex: Lanches" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="editIcone">Ícone (emoji)</label>
          <input type="text" id="editIcone" class="form-control" placeholder="🍔" maxlength="4">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" data-close-modal>Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Exclusão -->
  <div id="modalExcluir" class="modal-overlay hidden">
    <div class="modal">
      <div class="modal-header">
        <h2 class="modal-title">Confirmar exclusão</h2>
        <button class="modal-close" data-close-modal>✕</button>
      </div>
      <p>Tem certeza que deseja excluir a categoria <strong id="deleteNome"></strong>?</p>
      <div class="modal-footer">
        <button class="btn btn-ghost" data-close-modal>Cancelar</button>
        <button class="btn btn-danger" id="btnConfirmDelete">Excluir</button>
      </div>
    </div>
  </div>

  <script type="module" src="/src/admin/assets/js/categorias.js"></script>
  <script>
    /* Sincronizar botões Nova da topbar e do page-header */
    document.getElementById('btnNovaTop')?.addEventListener('click', () => {
      document.getElementById('btnNova').click();
    });
  </script>
</body>
</html>
