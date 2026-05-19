<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Usuários — Prontus Admin'; require_once __DIR__ . '/inc/head.php'; ?>
<body>
  <div class="admin-layout">

    <aside id="sidebar" class="sidebar"></aside>

    <div class="admin-content">
      <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px">
          <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
            <span></span><span></span><span></span>
          </button>
          <h1 class="topbar-title">Usuários</h1>
        </div>
        <div class="topbar-actions">
          <a href="/src/admin/pages/usuario-form.php" class="btn btn-primary btn-sm">
            + Novo Usuário
          </a>
        </div>
      </header>

      <main class="admin-main">

        <div class="page-header">
          <div class="page-header-left">
            <h1>Usuários</h1>
            <p>Gerencie os acessos ao sistema</p>
          </div>
          <a href="/src/admin/pages/usuario-form.php" class="btn btn-primary">+ Novo Usuário</a>
        </div>

        <div class="search-bar">
          <div class="search-input-wrapper">
            <span class="search-icon">🔍</span>
            <input type="text" id="busca" placeholder="Buscar por nome ou e-mail…">
          </div>
        </div>

        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Perfil</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody id="usuariosTbody">
              <tr><td colspan="5" class="table-empty">
                <div class="spinner-center"><div class="spinner"></div></div>
              </td></tr>
            </tbody>
          </table>
        </div>

      </main>
    </div>
  </div>

  <!-- Modal de exclusão -->
  <div id="modalExcluir" class="modal-overlay hidden">
    <div class="modal">
      <div class="modal-header">
        <h2 class="modal-title">Confirmar exclusão</h2>
        <button class="modal-close" data-close-modal>✕</button>
      </div>
      <p>Tem certeza que deseja desativar o usuário <strong id="deleteNome"></strong>?</p>
      <div class="modal-footer">
        <button class="btn btn-ghost" data-close-modal>Cancelar</button>
        <button class="btn btn-danger" id="btnConfirmDelete">Desativar</button>
      </div>
    </div>
  </div>

  <script type="module" src="/src/admin/assets/js/usuarios.js"></script>
</body>
</html>
