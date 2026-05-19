import { getConfiguracoes, salvarConfiguracoes } from '/src/shared/js/api.js';
import { initAdminPage, showToast } from './admin.js';

await initAdminPage();

/* -------- Carregar configurações -------- */
let configAtual = {};

async function carregarConfiguracoes() {
  try {
    configAtual = await getConfiguracoes();

    const campos = [
      'nome_estabelecimento', 'slogan', 'logo_url',
      'pix_chave', 'pix_nome_recebedor',
      'mensagem_display', 'tempo_alerta_kds', 'totem_idle_segundos',
    ];
    campos.forEach(campo => {
      const el = document.getElementById(campo);
      if (el && configAtual[campo] !== undefined) {
        el.value = configAtual[campo];
      }
    });

    /* Cores */
    aplicarCor('primaria', configAtual.cor_primaria || '#FF6B35');
    aplicarCor('secundaria', configAtual.cor_secundaria || '#2D3047');

    /* Logo preview */
    atualizarLogoPreview(configAtual.logo_url || '');

  } catch (err) {
    showToast('Erro ao carregar configurações.', 'error');
    console.error(err);
  }
}

/* -------- Cores -------- */
function aplicarCor(tipo, hex) {
  const picker  = document.getElementById(`cor_${tipo}`);
  const hexInput = document.getElementById(`cor_${tipo}_hex`);
  const preview  = document.getElementById(`preview${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);

  if (picker)  picker.value = hex;
  if (hexInput) hexInput.value = hex;
  if (preview)  preview.style.background = hex;
}

function initColorPicker(tipo) {
  const picker   = document.getElementById(`cor_${tipo}`);
  const hexInput = document.getElementById(`cor_${tipo}_hex`);
  const preview  = document.getElementById(`preview${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);

  picker?.addEventListener('input', () => {
    const hex = picker.value;
    if (hexInput) hexInput.value = hex;
    if (preview)  preview.style.background = hex;
  });

  hexInput?.addEventListener('input', () => {
    const hex = hexInput.value;
    if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
      if (picker)  picker.value = hex;
      if (preview) preview.style.background = hex;
    }
  });
}

/* -------- Logo -------- */
function atualizarLogoPreview(url) {
  const img = document.getElementById('logoPreview');
  if (!img) return;
  if (url) {
    img.src = url;
    img.style.display = 'block';
  } else {
    img.src = '';
    img.style.display = 'none';
  }
}

document.getElementById('logo_url')?.addEventListener('input', (e) => {
  atualizarLogoPreview(e.target.value);
});

/* -------- Submit -------- */
document.getElementById('formConfig')?.addEventListener('submit', salvar);
document.getElementById('btnSalvarTop')?.addEventListener('click', () => {
  document.getElementById('formConfig').requestSubmit();
});

async function salvar(e) {
  e.preventDefault();

  const dados = {
    nome_estabelecimento: document.getElementById('nome_estabelecimento').value.trim(),
    slogan:               document.getElementById('slogan').value.trim(),
    logo_url:             document.getElementById('logo_url').value.trim(),
    cor_primaria:         document.getElementById('cor_primaria_hex').value.trim(),
    cor_secundaria:       document.getElementById('cor_secundaria_hex').value.trim(),
    pix_chave:            document.getElementById('pix_chave').value.trim(),
    pix_nome_recebedor:   document.getElementById('pix_nome_recebedor').value.trim(),
    mensagem_display:     document.getElementById('mensagem_display').value.trim(),
    tempo_alerta_kds:     document.getElementById('tempo_alerta_kds').value,
    totem_idle_segundos:  document.getElementById('totem_idle_segundos').value,
  };

  const btn = document.getElementById('btnSalvar');
  btn.disabled = true;
  btn.textContent = 'Salvando…';

  try {
    configAtual = await salvarConfiguracoes(dados);
    showToast('Configurações salvas com sucesso!');
  } catch (err) {
    showToast('Erro ao salvar configurações.', 'error');
    console.error(err);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Salvar configurações';
  }
}

/* -------- Init -------- */
initColorPicker('primaria');
initColorPicker('secundaria');
await carregarConfiguracoes();
