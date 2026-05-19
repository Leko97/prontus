import { getUsuarios, deletarUsuario } from '/src/shared/js/api.js';
import { initAdminPage, showToast, openModal, closeModal } from './admin.js';

await initAdminPage();

let todosUsuarios = [];
let usuarioParaExcluir = null;

/* -------- Carregar dados -------- */
async function load() {
  const tbody = document.getElementById('usuariosTbody');
  tbody.innerHTML = `<tr><td colspan="5" class="table-empty"><div class="spinner-center"><div class="spinner"></div></div></td></tr>`;

  try {
    todosUsuarios = await getUsuarios();
    renderTabela(todosUsuarios);
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="5" class="table-empty">Erro ao carregar usuários.</td></tr>`;
    console.error(err);
  }
}

function renderTabela(usuarios) {
  const tbody = document.getElementById('usuariosTbody');

  if (!usuarios.length) {
    tbody.innerHTML = `
      <tr><td colspan="5" class="table-empty">
        <div class="table-empty-icon">👥</div>
        Nenhum usuário encontrado.
      </td></tr>`;
    return;
  }

  tbody.innerHTML = usuarios.map(u => `
    <tr>
      <td><strong>${u.nome}</strong></td>
      <td>${u.email}</td>
      <td>
        <span class="badge ${u.perfil === 'admin' ? 'badge-warning' : 'badge-info'}">
          ${u.perfil === 'admin' ? 'Admin' : 'Cozinha'}
        </span>
      </td>
      <td>
        <span class="badge ${u.ativo ? 'badge-success' : 'badge-neutral'}">
          ${u.ativo ? 'Ativo' : 'Inativo'}
        </span>
      </td>
      <td>
        <div class="table-actions">
          <button class="btn-action btn-edit" title="Editar"
            onclick="location.href='/src/admin/pages/usuario-form.php?id=${u.id}'">✏️</button>
          <button class="btn-action btn-delete" title="Desativar"
            data-id="${u.id}" data-nome="${u.nome}">🗑️</button>
        </div>
      </td>
    </tr>
  `).join('');

  tbody.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', () => {
      usuarioParaExcluir = { id: Number(btn.dataset.id), nome: btn.dataset.nome };
      document.getElementById('deleteNome').textContent = usuarioParaExcluir.nome;
      openModal('modalExcluir');
    });
  });
}

/* -------- Busca -------- */
document.getElementById('busca')?.addEventListener('input', (e) => {
  const q = e.target.value.toLowerCase().trim();
  const filtrados = todosUsuarios.filter(u =>
    u.nome.toLowerCase().includes(q) ||
    u.email.toLowerCase().includes(q)
  );
  renderTabela(filtrados);
});

/* -------- Confirmação de exclusão -------- */
document.getElementById('btnConfirmDelete')?.addEventListener('click', async () => {
  if (!usuarioParaExcluir) return;

  const btn = document.getElementById('btnConfirmDelete');
  btn.disabled = true;
  btn.textContent = 'Desativando…';

  try {
    await deletarUsuario(usuarioParaExcluir.id);
    todosUsuarios = todosUsuarios.filter(u => u.id !== usuarioParaExcluir.id);
    renderTabela(todosUsuarios);
    closeModal('modalExcluir');
    showToast('Usuário desativado com sucesso.');
  } catch (err) {
    showToast('Erro ao desativar usuário.', 'error');
    console.error(err);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Desativar';
    usuarioParaExcluir = null;
  }
});

load();
