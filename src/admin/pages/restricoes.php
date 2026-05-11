<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Restrições — Prontus Admin'; require_once __DIR__ . '/inc/head.php'; ?>
<body>
  <div class="admin-layout">

    <aside id="sidebar" class="sidebar"></aside>

    <div class="admin-content">
      <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px">
          <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
            <span></span><span></span><span></span>
          </button>
          <h1 class="topbar-title">Restrições Alimentares</h1>
        </div>
      </header>

      <main class="admin-main" style="max-width:640px">

        <div class="page-header">
          <div class="page-header-left">
            <h1>Restrições Alimentares</h1>
            <p>Configure as restrições disponíveis no sistema</p>
          </div>
        </div>

        <div id="alertMsg" class="alert hidden" style="margin-bottom:20px"></div>

        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Restrições configuradas</h2>
          </div>

          <div class="restricoes-list" id="restricoesList"></div>

          <!-- Nova restrição -->
          <div class="add-restricao-form">
            <div class="form-group">
              <label class="form-label" for="novaRestricaoNome">Nome da nova restrição</label>
              <input type="text" id="novaRestricaoNome" class="form-control"
                placeholder="Ex: Sem Glúten" style="height:44px">
            </div>
            <div class="form-group">
              <label class="form-label" for="novaRestricaoCor">Cor do badge</label>
              <select id="novaRestricaoCor" class="form-control" style="height:44px">
                <option value="warning">Amarelo (alerta)</option>
                <option value="success">Verde</option>
                <option value="danger">Vermelho</option>
                <option value="info">Azul</option>
                <option value="neutral">Cinza</option>
              </select>
            </div>
            <button class="btn btn-primary" id="btnAddRestricao" style="flex-shrink:0;height:44px">
              + Adicionar
            </button>
          </div>
        </div>

      </main>
    </div>
  </div>

  <script type="module">
    import { initAdminPage, showToast } from '/src/admin/assets/js/admin.js';

    await initAdminPage();

    const DEFAULTS = [
      { id: 'sem-gluten',   nome: 'Sem Glúten',   cor: 'warning', ativo: true },
      { id: 'vegetariano',  nome: 'Vegetariano',   cor: 'success', ativo: true },
      { id: 'vegano',       nome: 'Vegano',         cor: 'success', ativo: true },
      { id: 'sem-lactose',  nome: 'Sem Lactose',   cor: 'info',    ativo: true },
      { id: 'sem-amendoim', nome: 'Sem Amendoim',  cor: 'danger',  ativo: true },
    ];

    let restricoes = JSON.parse(localStorage.getItem('prontus_restricoes') || 'null') || DEFAULTS;

    function save() {
      localStorage.setItem('prontus_restricoes', JSON.stringify(restricoes));
    }

    function render() {
      const list = document.getElementById('restricoesList');
      list.innerHTML = restricoes.map((r, i) => `
        <div class="toggle-wrapper">
          <div class="toggle-label">
            <strong>
              <span class="badge badge-${r.cor}" style="margin-right:8px">${r.nome}</span>
              ${r.nome}
            </strong>
            <span>Identificador: <code>${r.id}</code></span>
          </div>
          <label class="toggle" title="${r.ativo ? 'Desativar' : 'Ativar'}">
            <input type="checkbox" ${r.ativo ? 'checked' : ''} data-index="${i}">
            <span class="toggle-slider"></span>
          </label>
        </div>
      `).join('');

      list.querySelectorAll('input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', () => {
          restricoes[Number(cb.dataset.index)].ativo = cb.checked;
          save();
          showToast(cb.checked ? 'Restrição ativada.' : 'Restrição desativada.');
        });
      });
    }

    document.getElementById('btnAddRestricao')?.addEventListener('click', () => {
      const nome = document.getElementById('novaRestricaoNome').value.trim();
      const cor  = document.getElementById('novaRestricaoCor').value;

      if (!nome) {
        showToast('Informe o nome da restrição.', 'error');
        return;
      }

      const id = nome.toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/\s+/g, '-');

      if (restricoes.find(r => r.id === id)) {
        showToast('Já existe uma restrição com esse nome.', 'error');
        return;
      }

      restricoes.push({ id, nome, cor, ativo: true });
      save();
      render();
      document.getElementById('novaRestricaoNome').value = '';
      showToast('Restrição adicionada.');
    });

    render();
  </script>
</body>
</html>
