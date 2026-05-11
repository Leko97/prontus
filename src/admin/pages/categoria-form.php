<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Categoria — Prontus Admin'; require_once __DIR__ . '/inc/head.php'; ?>
<body>
  <div class="admin-layout">

    <aside id="sidebar" class="sidebar"></aside>

    <div class="admin-content">
      <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px">
          <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
            <span></span><span></span><span></span>
          </button>
          <h1 class="topbar-title" id="pageTitle">Nova Categoria</h1>
        </div>
        <div class="topbar-actions">
          <a href="/src/admin/pages/categorias.php" class="btn btn-ghost btn-sm">← Voltar</a>
        </div>
      </header>

      <main class="admin-main form-page">

        <div class="page-header">
          <div class="page-header-left">
            <h1 id="formHeading">Nova Categoria</h1>
            <p>Preencha as informações da categoria</p>
          </div>
        </div>

        <form id="formCategoria" novalidate>
          <input type="hidden" id="categoriaId">

          <div class="form-card" style="margin-bottom:var(--space-6)">
            <h3 class="form-section-title">Dados da categoria</h3>

            <div class="form-group">
              <label class="form-label" for="nome">Nome <span class="required">*</span></label>
              <input type="text" id="nome" class="form-control" placeholder="Ex: Lanches" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="icone">Ícone (emoji)</label>
              <input type="text" id="icone" class="form-control" placeholder="🍔" maxlength="4"
                style="font-size:1.5rem;text-align:center;width:80px">
              <span class="form-hint">Um emoji que representa a categoria</span>
            </div>
          </div>

          <div style="display:flex;gap:12px">
            <button type="submit" class="btn btn-primary" id="btnSalvar">Salvar categoria</button>
            <a href="/src/admin/pages/categorias.php" class="btn btn-ghost">Cancelar</a>
          </div>
        </form>

      </main>
    </div>
  </div>

  <script type="module">
    import { getCategorias, salvarCategoria } from '/src/shared/js/api.js';
    import { getParam } from '/src/shared/js/utils.js';
    import { initAdminPage, showToast } from '/src/admin/assets/js/admin.js';

    await initAdminPage();

    const categoriaId = getParam('id');
    const isEdit = !!categoriaId;

    if (isEdit) {
      document.getElementById('pageTitle').textContent   = 'Editar Categoria';
      document.getElementById('formHeading').textContent = 'Editar Categoria';

      try {
        const cats = await getCategorias();
        const cat  = cats.find(c => c.id === Number(categoriaId));
        if (cat) {
          document.getElementById('categoriaId').value = cat.id;
          document.getElementById('nome').value        = cat.nome;
          document.getElementById('icone').value       = cat.icone;
        }
      } catch (err) {
        showToast('Erro ao carregar categoria.', 'error');
        console.error(err);
      }
    }

    document.getElementById('formCategoria').addEventListener('submit', async (e) => {
      e.preventDefault();

      const nome  = document.getElementById('nome').value.trim();
      const icone = document.getElementById('icone').value.trim() || '🍽️';

      if (!nome) {
        showToast('Informe o nome da categoria.', 'error');
        return;
      }

      const btn = document.getElementById('btnSalvar');
      btn.disabled = true;
      btn.textContent = 'Salvando…';

      const dados = { nome, icone };
      if (isEdit) dados.id = Number(categoriaId);

      try {
        await salvarCategoria(dados);
        showToast(isEdit ? 'Categoria atualizada!' : 'Categoria criada!');
        setTimeout(() => window.location.href = '/src/admin/pages/categorias.php', 800);
      } catch (err) {
        showToast('Erro ao salvar categoria.', 'error');
        btn.disabled = false;
        btn.textContent = 'Salvar categoria';
        console.error(err);
      }
    });
  </script>
</body>
</html>
