<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Combos — Prontus Admin'; require_once __DIR__ . '/inc/head.php'; ?>
<body>
  <div class="admin-layout">

    <aside id="sidebar" class="sidebar"></aside>

    <div class="admin-content">
      <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px">
          <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
            <span></span><span></span><span></span>
          </button>
          <h1 class="topbar-title">Combos</h1>
        </div>
        <div class="topbar-actions">
          <button class="btn btn-primary btn-sm" id="btnNovo">+ Novo Combo</button>
        </div>
      </header>

      <main class="admin-main">

        <div class="page-header">
          <div class="page-header-left">
            <h1>Combos e Promoções</h1>
            <p>Crie combinações de produtos com preço especial</p>
          </div>
          <button class="btn btn-primary" id="btnNovoTop">+ Novo Combo</button>
        </div>

        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Preço</th>
                <th>Itens</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody id="combosTbody">
              <tr><td colspan="5" class="table-empty">
                <div class="spinner-center"><div class="spinner"></div></div>
              </td></tr>
            </tbody>
          </table>
        </div>

      </main>
    </div>
  </div>

  <!-- Modal criar/editar combo -->
  <div id="modalForm" class="modal-overlay hidden">
    <div class="modal" style="max-width:560px">
      <div class="modal-header">
        <h2 class="modal-title" id="modalTitle">Novo Combo</h2>
        <button class="modal-close" data-close-modal>✕</button>
      </div>
      <form id="formCombo" novalidate>
        <input type="hidden" id="comboId">

        <div class="form-group">
          <label class="form-label" for="comboNome">Nome <span class="required">*</span></label>
          <input type="text" id="comboNome" class="form-control" placeholder="Ex: Combo Executivo" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="comboDescricao">Descrição</label>
          <input type="text" id="comboDescricao" class="form-control" placeholder="Ex: Lanche + Batata + Bebida">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="comboPreco">Preço (R$) <span class="required">*</span></label>
            <input type="number" id="comboPreco" class="form-control" step="0.01" min="0" placeholder="0,00" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="comboImagem">URL da Imagem</label>
            <input type="url" id="comboImagem" class="form-control" placeholder="https://...">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Itens do Combo</label>
          <div id="itensCombo" style="display:flex;flex-direction:column;gap:8px;margin-bottom:8px"></div>
          <button type="button" class="btn btn-ghost btn-sm" id="btnAdicionarItem">+ Adicionar item</button>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" data-close-modal>Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar Combo</button>
        </div>
      </form>
    </div>
  </div>

  <script type="module" src="/src/admin/assets/js/combos.js"></script>
  <script>
    document.getElementById('btnNovoTop')?.addEventListener('click', () => {
      document.getElementById('btnNovo').click();
    });
  </script>
</body>
</html>
