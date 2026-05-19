import { getUsuarioById, criarUsuario, atualizarUsuario } from '/src/shared/js/api.js';
import { getParam } from '/src/shared/js/utils.js';
import { initAdminPage, showToast } from './admin.js';

await initAdminPage();

const usuarioId = getParam('id');
const isEdit    = !!usuarioId;

if (isEdit) {
  document.getElementById('pageTitle').textContent   = 'Editar Usuário';
  document.getElementById('formHeading').textContent = 'Editar Usuário';
  document.getElementById('btnSalvar').textContent   = 'Salvar usuário';
  document.getElementById('senhaLabel').innerHTML    = 'Nova senha <small style="color:var(--color-text-muted);font-weight:400">(opcional)</small>';
  document.getElementById('senha').placeholder       = 'Deixe em branco para manter a senha atual';
}

/* Pré-preencher no modo edição */
if (isEdit) {
  try {
    const u = await getUsuarioById(usuarioId);
    document.getElementById('nome').value   = u.nome;
    document.getElementById('email').value  = u.email;
    document.getElementById('perfil').value = u.perfil;
  } catch (err) {
    showToast('Erro ao carregar usuário.', 'error');
    console.error(err);
  }
}

/* Submit */
document.getElementById('formUsuario').addEventListener('submit', async (e) => {
  e.preventDefault();

  const alertErro = document.getElementById('alertErro');
  alertErro.classList.add('hidden');

  const nome   = document.getElementById('nome').value.trim();
  const email  = document.getElementById('email').value.trim();
  const perfil = document.getElementById('perfil').value;
  const senha  = document.getElementById('senha').value;

  const dados = { nome, email, perfil };
  if (!isEdit || senha) dados.senha = senha;

  const btn = document.getElementById('btnSalvar');
  btn.disabled = true;
  btn.textContent = 'Salvando…';

  try {
    if (isEdit) {
      await atualizarUsuario(usuarioId, dados);
    } else {
      await criarUsuario(dados);
    }
    showToast('Usuário salvo!');
    setTimeout(() => window.location.href = '/src/admin/pages/usuarios.php', 800);
  } catch (err) {
    const mensagem = err.message?.includes('409')
      ? 'E-mail já cadastrado.'
      : 'Erro ao salvar usuário. Verifique os dados e tente novamente.';
    alertErro.textContent = mensagem;
    alertErro.classList.remove('hidden');
    btn.disabled = false;
    btn.textContent = isEdit ? 'Salvar usuário' : 'Criar usuário';
    console.error(err);
  }
});
