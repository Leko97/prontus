<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Produto — Prontus Admin'; require_once __DIR__ . '/inc/head.php'; ?>
<body>
  <div class="admin-layout">

    <aside id="sidebar" class="sidebar"></aside>

    <div class="admin-content">
      <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px">
          <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
            <span></span><span></span><span></span>
          </button>
          <h1 class="topbar-title" id="pageTitle">Novo Produto</h1>
        </div>
        <div class="topbar-actions">
          <a href="/src/admin/pages/produtos.php" class="btn btn-ghost btn-sm">← Voltar</a>
        </div>
      </header>

      <main class="admin-main form-page">

        <div class="page-header">
          <div class="page-header-left">
            <h1 id="formHeading">Novo Produto</h1>
            <p>Preencha as informações do produto</p>
          </div>
        </div>

        <div id="alertMsg" class="alert hidden" style="margin-bottom:20px"></div>

        <form id="formProduto" novalidate>
          <div class="form-card" style="margin-bottom:var(--space-5)">
            <h3 class="form-section-title">Informações básicas</h3>

            <div class="form-group">
              <label class="form-label" for="nome">Nome <span class="required">*</span></label>
              <input type="text" id="nome" class="form-control" placeholder="Ex: X-Burguer Clássico" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="descricao">Descrição</label>
              <textarea id="descricao" class="form-control" placeholder="Descreva os ingredientes e preparo…"></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="preco">Preço (R$) <span class="required">*</span></label>
                <input type="number" id="preco" class="form-control" step="0.01" min="0" placeholder="0,00" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="categoria">Categoria <span class="required">*</span></label>
                <select id="categoria" class="form-control" required>
                  <option value="">Selecione…</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Restrições -->
          <div class="form-card" style="margin-bottom:var(--space-5)">
            <h3 class="form-section-title">Restrições alimentares</h3>
            <div class="checkbox-group" id="restricoesGroup">
              <label class="checkbox-item">
                <input type="checkbox" name="restricoes" value="sem-gluten"> Sem Glúten
              </label>
              <label class="checkbox-item">
                <input type="checkbox" name="restricoes" value="vegetariano"> Vegetariano
              </label>
              <label class="checkbox-item">
                <input type="checkbox" name="restricoes" value="vegano"> Vegano
              </label>
              <label class="checkbox-item">
                <input type="checkbox" name="restricoes" value="sem-lactose"> Sem Lactose
              </label>
              <label class="checkbox-item">
                <input type="checkbox" name="restricoes" value="sem-amendoim"> Sem Amendoim
              </label>
            </div>
          </div>

          <!-- Extras -->
          <div class="form-card" style="margin-bottom:var(--space-5)">
            <h3 class="form-section-title">Extras disponíveis</h3>
            <div class="dynamic-list" id="extrasList"></div>
            <button type="button" class="btn btn-ghost btn-sm" id="btnAddExtra">+ Adicionar extra</button>
          </div>

          <!-- Remoções -->
          <div class="form-card" style="margin-bottom:var(--space-6)">
            <h3 class="form-section-title">Ingredientes removíveis</h3>
            <div class="dynamic-list" id="remocoesList"></div>
            <button type="button" class="btn btn-ghost btn-sm" id="btnAddRemocao">+ Adicionar ingrediente</button>
          </div>

          <div style="display:flex;gap:12px">
            <button type="submit" class="btn btn-primary" id="btnSalvar">Salvar produto</button>
            <a href="/src/admin/pages/produtos.php" class="btn btn-ghost">Cancelar</a>
          </div>
        </form>

      </main>
    </div>
  </div>

  <script type="module">
    import { getProdutoById, getCategorias, salvarProduto } from '/src/shared/js/api.js';
    import { getParam } from '/src/shared/js/utils.js';
    import { initAdminPage, showToast } from '/src/admin/assets/js/admin.js';

    await initAdminPage();

    const produtoId = getParam('id');
    const isEdit    = !!produtoId;

    if (isEdit) {
      document.getElementById('pageTitle').textContent   = 'Editar Produto';
      document.getElementById('formHeading').textContent = 'Editar Produto';
    }

    /* Categorias */
    const cats = await getCategorias();
    const sel  = document.getElementById('categoria');
    cats.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.id;
      opt.textContent = `${c.icone} ${c.nome}`;
      sel.appendChild(opt);
    });

    /* Restrições — sincroniza visual */
    document.querySelectorAll('.checkbox-item input').forEach(cb => {
      cb.addEventListener('change', () => {
        cb.closest('.checkbox-item').classList.toggle('checked', cb.checked);
      });
    });

    /* Listas dinâmicas */
    function createExtraRow(nome = '', preco = '') {
      const div = document.createElement('div');
      div.className = 'dynamic-item';
      div.innerHTML = `
        <input type="text" class="form-control extra-nome" placeholder="Nome do extra" value="${nome}" style="flex:2">
        <input type="number" class="form-control extra-preco" placeholder="Preço" value="${preco}" step="0.01" min="0" style="flex:1">
        <button type="button" class="btn-remove-item" title="Remover">✕</button>
      `;
      div.querySelector('.btn-remove-item').addEventListener('click', () => div.remove());
      return div;
    }

    function createRemocaoRow(valor = '') {
      const div = document.createElement('div');
      div.className = 'dynamic-item';
      div.innerHTML = `
        <input type="text" class="form-control remocao-item" placeholder="Ex: Cebola" value="${valor}">
        <button type="button" class="btn-remove-item" title="Remover">✕</button>
      `;
      div.querySelector('.btn-remove-item').addEventListener('click', () => div.remove());
      return div;
    }

    document.getElementById('btnAddExtra').addEventListener('click', () => {
      document.getElementById('extrasList').appendChild(createExtraRow());
    });

    document.getElementById('btnAddRemocao').addEventListener('click', () => {
      document.getElementById('remocoesList').appendChild(createRemocaoRow());
    });

    /* Pré-preencher se edição */
    if (isEdit) {
      try {
        const p = await getProdutoById(produtoId);

        document.getElementById('nome').value      = p.nome;
        document.getElementById('descricao').value = p.descricao || '';
        document.getElementById('preco').value     = p.preco;
        document.getElementById('categoria').value = p.categoriaId;

        (p.restricoes || []).forEach(r => {
          const cb = document.querySelector(`input[value="${r}"]`);
          if (cb) { cb.checked = true; cb.closest('.checkbox-item').classList.add('checked'); }
        });

        (p.extras || []).forEach(e => {
          document.getElementById('extrasList').appendChild(createExtraRow(e.nome, e.preco));
        });

        (p.remocoes || []).forEach(r => {
          document.getElementById('remocoesList').appendChild(createRemocaoRow(r));
        });
      } catch (err) {
        showToast('Erro ao carregar produto.', 'error');
        console.error(err);
      }
    }

    /* Submit */
    document.getElementById('formProduto').addEventListener('submit', async (e) => {
      e.preventDefault();

      const nome     = document.getElementById('nome').value.trim();
      const descricao = document.getElementById('descricao').value.trim();
      const preco    = parseFloat(document.getElementById('preco').value);
      const categoriaId = Number(document.getElementById('categoria').value);

      if (!nome || isNaN(preco) || !categoriaId) {
        showToast('Preencha todos os campos obrigatórios.', 'error');
        return;
      }

      const restricoes = [...document.querySelectorAll('input[name="restricoes"]:checked')]
        .map(cb => cb.value);

      const extras = [...document.querySelectorAll('#extrasList .dynamic-item')].map((div, i) => ({
        id: i + 1,
        nome:  div.querySelector('.extra-nome').value.trim(),
        preco: parseFloat(div.querySelector('.extra-preco').value) || 0,
      })).filter(e => e.nome);

      const remocoes = [...document.querySelectorAll('.remocao-item')]
        .map(el => el.value.trim()).filter(Boolean);

      const dados = { nome, descricao, preco, categoriaId, restricoes, extras, remocoes };
      if (isEdit) dados.id = Number(produtoId);

      const btn = document.getElementById('btnSalvar');
      btn.disabled = true;
      btn.textContent = 'Salvando…';

      try {
        await salvarProduto(dados);
        showToast(isEdit ? 'Produto atualizado!' : 'Produto criado!');
        setTimeout(() => window.location.href = '/src/admin/pages/produtos.php', 800);
      } catch (err) {
        showToast('Erro ao salvar produto.', 'error');
        btn.disabled = false;
        btn.textContent = 'Salvar produto';
        console.error(err);
      }
    });
  </script>
</body>
</html>
