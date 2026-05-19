import { getConfiguracoes } from '/src/shared/js/api.js';

document.addEventListener('DOMContentLoaded', async () => {
  /* Carregar configurações */
  try {
    const config = await getConfiguracoes();

    if (config.nome_estabelecimento) {
      document.getElementById('idleNome').textContent = config.nome_estabelecimento;
    }
    if (config.slogan) {
      document.getElementById('idleSlogan').textContent = config.slogan;
    }
    if (config.logo_url) {
      const logo  = document.getElementById('idleLogo');
      const emoji = document.getElementById('idleEmoji');
      logo.src = config.logo_url;
      logo.style.display = 'block';
      if (emoji) emoji.style.display = 'none';
    }
    if (config.cor_primaria) {
      document.querySelector('.idle-screen').style.background = config.cor_primaria;
    }
  } catch (err) {
    console.warn('[idle] Erro ao carregar configurações:', err.message);
  }

  /* Toque/clique redireciona para o cardápio */
  document.getElementById('idleScreen').addEventListener('click', () => {
    window.location.href = '/src/totem/pages/index.php';
  });
});
