import { getAdicionais, getCategorias, getProdutos, salvarAdicional } from '/src/shared/js/api.js';
import { formatCurrency } from '/src/shared/js/utils.js';
import { initAdminPage, showToast, openModal, closeModal } from './admin.js';

await initAdminPage();

let todosAdicionais = [];
let todosProdutos   = [];

/* -------- Carregar -------- */
async function load() {
  const tbody = document.getElementById('adicionaisTbody');
  tbody.innerHTML = `<tr><td colspan="4" class="table-empty"><div class="spinner-center"><div class="spinner"></div></div></td></tr>`;

  try {
    [todosAdicionais, todosProdutos] = await Promise.all([getAdicionais(), getProdutos()]);
    renderTabela(todosAdicionais);
    populateSelect();
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4" class="table-empty">Erro ao carregar adicionais.</td></tr>`;
    console.error(err);
  }
}

function populateSelect() {
  const sel = document.getElementById('adicionalProduto');
  if (!sel) return;
  sel.innerHTML = `<option value="">Selecione um produto…</option>` +
    todosProdutos.map(p => `<option value="${p.id}">${p.nome}</option>`).join('');
}

function produtoNome(id) {
  return todosProdutos.find(p => p.id === Number(id))?.nome ?? '—';
}

function renderTabela(adicionais) {
  const tbody = document.getElementById('adicionaisTbody');

  if (!adicionais.length) {
    tbody.innerHTML = `
      <tr><td colspan="4" class="table-empty">
        <div class="table-empty-icon">➕</div>
        Nenhum adicional cadastrado.
      </td></tr>`;
    return;
  }

  tbody.innerHTML = adicionais.map(a => `
    <tr>
      <td><strong>${a.nome}</strong></td>
      <td>${formatCurrency(a.preco)}</td>
      <td>${produtoNome(a.produtoId)}</td>
      <td>
        <div class="table-actions">
          <button class="btn-action btn-edit" title="Editar"
            data-id="${a.id}" data-nome="${a.nome}"
            data-preco="${a.preco}" data-produto="${a.produtoId}">✏️</button>
        </div>
      </td>
    </tr>
  `).join('');

  tbody.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('adicionalId').value      = btn.dataset.id;
      document.getElementById('adicionalNome').value    = btn.dataset.nome;
      document.getElementById('adicionalPreco').value   = btn.dataset.preco;
      document.getElementById('adicionalProduto').value = btn.dataset.produto;
      document.getElementById('modalTitle').textContent = 'Editar Adicional';
      openModal('modalForm');
    });
  });
}

/* -------- Novo adicional -------- */
document.getElementById('btnNovo')?.addEventListener('click', () => {
  document.getElementById('formAdicional').reset();
  document.getElementById('adicionalId').value = '';
  document.getElementById('modalTitle').textContent = 'Novo Adicional';
  openModal('modalForm');
});

/* -------- Salvar -------- */
document.getElementById('formAdicional')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const id       = document.getElementById('adicionalId').value;
  const nome     = document.getElementById('adicionalNome').value.trim();
  const preco    = parseFloat(document.getElementById('adicionalPreco').value);
  const produtoId = Number(document.getElementById('adicionalProduto').value);

  if (!nome || isNaN(preco)) return;

  const btn = e.submitter;
  btn.disabled = true;
  btn.textContent = 'Salvando…';

  try {
    const dados = { nome, preco, produtoId };
    if (id) dados.id = Number(id);

    const salvo = await salvarAdicional(dados);
    salvo.produtoId = produtoId;

    if (id) {
      const idx = todosAdicionais.findIndex(a => a.id === Number(id));
      if (idx !== -1) todosAdicionais[idx] = salvo;
    } else {
      todosAdicionais.push(salvo);
    }

    renderTabela(todosAdicionais);
    closeModal('modalForm');
    showToast(id ? 'Adicional atualizado.' : 'Adicional criado.');
  } catch (err) {
    showToast('Erro ao salvar adicional.', 'error');
    console.error(err);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Salvar';
  }
});

/* -------- Busca -------- */
document.getElementById('busca')?.addEventListener('input', (e) => {
  const q = e.target.value.toLowerCase().trim();
  renderTabela(todosAdicionais.filter(a =>
    a.nome.toLowerCase().includes(q) || produtoNome(a.produtoId).toLowerCase().includes(q)
  ));
});

load();
