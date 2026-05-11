import { getCategorias, getProdutos, salvarCategoria, deletarCategoria } from '/src/shared/js/api.js';
import { initAdminPage, showToast, openModal, closeModal } from './admin.js';

await initAdminPage();

let todasCategorias = [];
let todosProdutos = [];
let categoriaParaExcluir = null;

/* -------- Carregar -------- */
async function load() {
  const tbody = document.getElementById('categoriasTbody');
  tbody.innerHTML = `<tr><td colspan="4" class="table-empty"><div class="spinner-center"><div class="spinner"></div></div></td></tr>`;

  try {
    [todasCategorias, todosProdutos] = await Promise.all([getCategorias(), getProdutos()]);
    renderTabela(todasCategorias);
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4" class="table-empty">Erro ao carregar categorias.</td></tr>`;
    console.error(err);
  }
}

function qtdProdutos(categoriaId) {
  return todosProdutos.filter(p => p.categoriaId === categoriaId).length;
}

function renderTabela(categorias) {
  const tbody = document.getElementById('categoriasTbody');

  if (!categorias.length) {
    tbody.innerHTML = `
      <tr><td colspan="4" class="table-empty">
        <div class="table-empty-icon">🗂️</div>
        Nenhuma categoria encontrada.
      </td></tr>`;
    return;
  }

  tbody.innerHTML = categorias.map(c => `
    <tr>
      <td><strong>${c.nome}</strong></td>
      <td style="font-size:1.4rem">${c.icone}</td>
      <td>${qtdProdutos(c.id)} produto(s)</td>
      <td>
        <div class="table-actions">
          <button class="btn-action btn-edit" title="Editar"
            data-id="${c.id}" data-nome="${c.nome}" data-icone="${c.icone}">✏️</button>
          <button class="btn-action btn-delete" title="Excluir"
            data-id="${c.id}" data-nome="${c.nome}">🗑️</button>
        </div>
      </td>
    </tr>
  `).join('');

  /* Editar */
  tbody.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('editId').value    = btn.dataset.id;
      document.getElementById('editNome').value  = btn.dataset.nome;
      document.getElementById('editIcone').value = btn.dataset.icone;
      document.getElementById('modalEditTitle').textContent = 'Editar Categoria';
      openModal('modalForm');
    });
  });

  /* Excluir */
  tbody.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', () => {
      categoriaParaExcluir = { id: Number(btn.dataset.id), nome: btn.dataset.nome };
      document.getElementById('deleteNome').textContent = categoriaParaExcluir.nome;
      openModal('modalExcluir');
    });
  });
}

/* -------- Nova categoria -------- */
document.getElementById('btnNova')?.addEventListener('click', () => {
  document.getElementById('editId').value    = '';
  document.getElementById('editNome').value  = '';
  document.getElementById('editIcone').value = '';
  document.getElementById('modalEditTitle').textContent = 'Nova Categoria';
  openModal('modalForm');
});

/* -------- Salvar -------- */
document.getElementById('formCategoria')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const id    = document.getElementById('editId').value;
  const nome  = document.getElementById('editNome').value.trim();
  const icone = document.getElementById('editIcone').value.trim();

  if (!nome) return;

  const btn = e.submitter;
  btn.disabled = true;
  btn.textContent = 'Salvando…';

  try {
    const dados = { nome, icone: icone || '🍽️' };
    if (id) dados.id = Number(id);

    const salva = await salvarCategoria(dados);

    if (id) {
      const idx = todasCategorias.findIndex(c => c.id === Number(id));
      if (idx !== -1) todasCategorias[idx] = salva;
    } else {
      todasCategorias.push(salva);
    }

    renderTabela(todasCategorias);
    closeModal('modalForm');
    showToast(id ? 'Categoria atualizada.' : 'Categoria criada.');
  } catch (err) {
    showToast('Erro ao salvar categoria.', 'error');
    console.error(err);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Salvar';
  }
});

/* -------- Confirmar exclusão -------- */
document.getElementById('btnConfirmDelete')?.addEventListener('click', async () => {
  if (!categoriaParaExcluir) return;

  const btn = document.getElementById('btnConfirmDelete');
  btn.disabled = true;
  btn.textContent = 'Excluindo…';

  try {
    await deletarCategoria(categoriaParaExcluir.id);
    todasCategorias = todasCategorias.filter(c => c.id !== categoriaParaExcluir.id);
    renderTabela(todasCategorias);
    closeModal('modalExcluir');
    showToast('Categoria excluída.');
  } catch (err) {
    showToast('Erro ao excluir categoria.', 'error');
    console.error(err);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Excluir';
    categoriaParaExcluir = null;
  }
});

/* -------- Busca -------- */
document.getElementById('busca')?.addEventListener('input', (e) => {
  const q = e.target.value.toLowerCase().trim();
  renderTabela(todasCategorias.filter(c => c.nome.toLowerCase().includes(q)));
});

load();
