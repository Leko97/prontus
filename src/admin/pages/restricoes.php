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
                <option value="#F39C12">Amarelo (alerta)</option>
                <option value="#27AE60">Verde</option>
                <option value="#E74C3C">Vermelho</option>
                <option value="#3498DB">Azul</option>
                <option value="#95A5A6">Cinza</option>
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
    import { getRestricoesAdmin, criarRestricao, atualizarRestricao } from '/src/shared/js/api.js';
    import { escapeHtml } from '/src/shared/js/utils.js';

    await initAdminPage();

    let restricoes = [];

    async function carregar() {
      const list = document.getElementById('restricoesList');
      try {
        restricoes = await getRestricoesAdmin();
        render();
      } catch (err) {
        console.error(err);
        list.innerHTML = '<p style="color:var(--color-text-muted)">Erro ao carregar restrições. Recarregue a página.</p>';
      }
    }

    function render() {
      const list = document.getElementById('restricoesList');
      list.innerHTML = restricoes.map(r => `
        <div class="toggle-wrapper">
          <div class="toggle-label">
            <strong>
              <span class="badge" style="margin-right:8px;background:${escapeHtml(r.cor)};color:#fff">${escapeHtml(r.nome)}</span>
              ${escapeHtml(r.nome)}
            </strong>
            <span>Identificador: <code>${escapeHtml(r.slug)}</code></span>
          </div>
          <label class="toggle" title="${r.ativo ? 'Desativar' : 'Ativar'}">
            <input type="checkbox" ${r.ativo ? 'checked' : ''} data-slug="${escapeHtml(r.slug)}">
            <span class="toggle-slider"></span>
          </label>
        </div>
      `).join('');
    }

    document.getElementById('restricoesList').addEventListener('change', async (ev) => {
      const cb = ev.target;
      if (cb.type !== 'checkbox') return;

      const slug = cb.dataset.slug;
      const ativo = cb.checked;
      cb.disabled = true;
      try {
        const atualizada = await atualizarRestricao(slug, { ativo });
        const idx = restricoes.findIndex(r => r.slug === slug);
        if (idx !== -1) restricoes[idx] = atualizada;
        showToast(ativo ? 'Restrição ativada.' : 'Restrição desativada.');
      } catch (err) {
        console.error(err);
        cb.checked = !ativo;
        showToast('Erro ao salvar. Tente novamente.', 'error');
      } finally {
        cb.disabled = false;
      }
    });

    document.getElementById('btnAddRestricao')?.addEventListener('click', async () => {
      const nomeInput = document.getElementById('novaRestricaoNome');
      const nome = nomeInput.value.trim();
      const cor  = document.getElementById('novaRestricaoCor').value;

      if (!nome) {
        showToast('Informe o nome da restrição.', 'error');
        return;
      }

      const btn = document.getElementById('btnAddRestricao');
      btn.disabled = true;
      try {
        const nova = await criarRestricao({ nome, cor });
        restricoes.push(nova);
        render();
        nomeInput.value = '';
        showToast('Restrição adicionada.');
      } catch (err) {
        console.error(err);
        showToast(
          String(err.message).includes('409')
            ? 'Já existe uma restrição com esse nome.'
            : 'Erro ao salvar. Tente novamente.',
          'error'
        );
      } finally {
        btn.disabled = false;
      }
    });

    await carregar();
  </script>
</body>
</html>
