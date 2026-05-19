<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Usuário — Prontus Admin'; require_once __DIR__ . '/inc/head.php'; ?>
<body>
  <div class="admin-layout">

    <aside id="sidebar" class="sidebar"></aside>

    <div class="admin-content">
      <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px">
          <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
            <span></span><span></span><span></span>
          </button>
          <h1 class="topbar-title" id="pageTitle">Novo Usuário</h1>
        </div>
        <div class="topbar-actions">
          <a href="/src/admin/pages/usuarios.php" class="btn btn-ghost btn-sm">← Cancelar</a>
        </div>
      </header>

      <main class="admin-main form-page">

        <div class="page-header">
          <div class="page-header-left">
            <h1 id="formHeading">Novo Usuário</h1>
            <p>Preencha as informações do usuário</p>
          </div>
        </div>

        <div id="alertErro" class="alert alert-danger hidden" style="margin-bottom:20px"></div>

        <form id="formUsuario" novalidate>
          <div class="form-card" style="margin-bottom:var(--space-6)">
            <h3 class="form-section-title">Dados do usuário</h3>

            <div class="form-group">
              <label class="form-label" for="nome">Nome <span class="required">*</span></label>
              <input type="text" id="nome" class="form-control" placeholder="Nome completo" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="email">E-mail <span class="required">*</span></label>
              <input type="email" id="email" class="form-control" placeholder="email@exemplo.com" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="senha" id="senhaLabel">Senha <span class="required">*</span></label>
              <input type="password" id="senha" class="form-control" placeholder="Mínimo 6 caracteres">
            </div>

            <div class="form-group">
              <label class="form-label" for="perfil">Perfil <span class="required">*</span></label>
              <select id="perfil" class="form-control" required>
                <option value="cozinha">Cozinha</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>

          <div style="display:flex;gap:12px">
            <button type="submit" class="btn btn-primary" id="btnSalvar">Criar usuário</button>
            <a href="/src/admin/pages/usuarios.php" class="btn btn-ghost">Cancelar</a>
          </div>
        </form>

      </main>
    </div>
  </div>

  <script type="module" src="/src/admin/assets/js/usuario-form.js"></script>
</body>
</html>
