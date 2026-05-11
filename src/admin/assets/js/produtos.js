import { getProdutos, getCategorias, deletarProduto } from '/src/shared/js/api.js';
import { formatCurrency, restricaoLabel } from '/src/shared/js/utils.js';
import { initAdminPage, showToast, openModal, closeModal } from './admin.js';

await initAdminPage();

let todosProdutos = [];
let todasCategorias = [];
let produtoParaExcluir = null;

/* -------- Carregar dados -------- */
async function load() {
  const tbody = document.getElementById('produtosTbody');
  tbody.innerHTML = `<tr><td colspan="5" class="table-empty"><div class="spinner-center"><div class="spinner"></div></div></td></tr>`;

  try {
    [todosProdutos, todasCategorias] = await Promise.all([getProdutos(), getCategorias()]);
    renderTabela(todosProdutos);
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="5" class="table-empty">Erro ao carregar produtos.</td></tr>`;
    console.error(err);
  }
}

function categoriaNome(id) {
  return todasCategorias.find(c => c.id === id)?.nome ?? '—';
}

function renderTabela(produtos) {
  const tbody = document.getElementById('produtosTbody');

  if (!produtos.length) {
    tbody.innerHTML = `
      <tr><td colspan="5" class="table-empty">
        <div class="table-empty-icon">🍽️</div>
        Nenhum produto encontrado.
      </td></tr>`;
    return;
  }

  tbody.innerHTML = produtos.map(p => `
    <tr>
      <td><strong>${p.nome}</strong></td>
      <td>${categoriaNome(p.categoriaId)}</td>
      <td>${formatCurrency(p.preco)}</td>
      <td>
        ${(p.restricoes || []).map(r => `<span class="tag-restricao ${r}">${restricaoLabel(r)}</span>`).join(' ') || '<span style="color:var(--color-text-muted)">—</span>'}
      </td>
      <td>
        <div class="table-actions">
          <button class="btn-action btn-edit" title="Editar"
            onclick="location.href='/src/admin/pages/produto-form.php?id=${p.id}'">✏️</button>
          <button class="btn-action btn-delete" title="Excluir"
            data-id="${p.id}" data-nome="${p.nome}">🗑️</button>
        </div>
      </td>
    </tr>
  `).join('');

  /* Delegação para botões excluir */
  tbody.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', () => {
      produtoParaExcluir = { id: Number(btn.dataset.id), nome: btn.dataset.nome };
      document.getElementById('deleteNome').textContent = produtoParaExcluir.nome;
      openModal('modalExcluir');
    });
  });
}

/* -------- Busca -------- */
document.getElementById('busca')?.addEventListener('input', (e) => {
  const q = e.target.value.toLowerCase().trim();
  const filtrados = todosProdutos.filter(p =>
    p.nome.toLowerCase().includes(q) ||
    categoriaNome(p.categoriaId).toLowerCase().includes(q)
  );
  renderTabela(filtrados);
});

/* -------- Confirmação de exclusão -------- */
document.getElementById('btnConfirmDelete')?.addEventListener('click', async () => {
  if (!produtoParaExcluir) return;

  const btn = document.getElementById('btnConfirmDelete');
  btn.disabled = true;
  btn.textContent = 'Excluindo…';

  try {
    await deletarProduto(produtoParaExcluir.id);
    todosProdutos = todosProdutos.filter(p => p.id !== produtoParaExcluir.id);
    renderTabela(todosProdutos);
    closeModal('modalExcluir');
    showToast('Produto excluído com sucesso.');
  } catch (err) {
    showToast('Erro ao excluir produto.', 'error');
    console.error(err);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Excluir';
    produtoParaExcluir = null;
  }
});

load();
