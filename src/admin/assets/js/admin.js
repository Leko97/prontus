import { checkAuth, logout } from '/src/shared/js/api.js';

/* -------- Proteção de rota -------- */
export async function protegerRota() {
  try {
    const { logado } = await checkAuth();
    if (!logado) redirectLogin();
  } catch {
    redirectLogin();
  }
}

function redirectLogin() {
  window.location.href = '/src/admin/pages/login.html';
}

/* -------- Sidebar -------- */
const NAV_LINKS = [
  { href: '/src/admin/pages/dashboard.html',    icon: '📊', label: 'Dashboard' },
  { href: '/src/admin/pages/produtos.html',     icon: '🍔', label: 'Produtos' },
  { href: '/src/admin/pages/categorias.html',   icon: '🗂️',  label: 'Categorias' },
  { href: '/src/admin/pages/adicionais.html',   icon: '➕', label: 'Adicionais' },
  { href: '/src/admin/pages/restricoes.html',   icon: '⚠️',  label: 'Restrições' },
];

export function renderSidebar(containerSelector = '#sidebar') {
  const sidebar = document.querySelector(containerSelector);
  if (!sidebar) return;

  const currentPath = window.location.pathname;

  sidebar.innerHTML = `
    <a href="/src/admin/pages/dashboard.html" class="sidebar-logo">
      <div class="sidebar-logo-icon">🍽️</div>
      <div>
        <span class="sidebar-logo-text">Prontus</span>
        <span class="sidebar-logo-sub">Painel Admin</span>
      </div>
    </a>

    <nav class="sidebar-nav">
      <div class="sidebar-section-title">Menu</div>
      ${NAV_LINKS.map(({ href, icon, label }) => `
        <a href="${href}" class="sidebar-link ${currentPath.endsWith(href.split('/').pop()) ? 'active' : ''}">
          <span class="nav-icon">${icon}</span>
          ${label}
        </a>
      `).join('')}
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-avatar" id="sidebarAvatar">A</div>
        <div>
          <div class="sidebar-user-name" id="sidebarUserName">Admin</div>
          <div class="sidebar-user-role">Administrador</div>
        </div>
      </div>
      <button class="btn-logout" id="btnLogout">
        🚪 Sair
      </button>
    </div>
  `;

  /* Logout */
  document.getElementById('btnLogout')?.addEventListener('click', async () => {
    try {
      await logout();
    } finally {
      redirectLogin();
    }
  });

  /* Preencher usuário */
  checkAuth().then(({ usuario }) => {
    if (!usuario) return;
    const nameEl = document.getElementById('sidebarUserName');
    const avatarEl = document.getElementById('sidebarAvatar');
    if (nameEl) nameEl.textContent = usuario.nome;
    if (avatarEl) avatarEl.textContent = usuario.nome.charAt(0).toUpperCase();
  }).catch(() => {});
}

/* -------- Toggle mobile -------- */
export function initSidebarToggle() {
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (!toggle || !sidebar) return;

  toggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
  });

  document.addEventListener('click', (e) => {
    if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });
}

/* -------- Modal utilitário -------- */
export function openModal(id) {
  document.getElementById(id)?.classList.remove('hidden');
}

export function closeModal(id) {
  document.getElementById(id)?.classList.add('hidden');
}

export function initModalClose() {
  document.querySelectorAll('[data-close-modal]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = btn.closest('.modal-overlay');
      if (modal) modal.classList.add('hidden');
    });
  });

  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) overlay.classList.add('hidden');
    });
  });
}

/* -------- Toast de feedback -------- */
let toastTimer;

export function showToast(message, type = 'success') {
  let toast = document.getElementById('adminToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'adminToast';
    toast.style.cssText = `
      position:fixed;bottom:24px;right:24px;z-index:9999;
      padding:12px 20px;border-radius:10px;font-size:14px;font-weight:500;
      box-shadow:0 4px 16px rgba(0,0,0,0.15);display:flex;align-items:center;gap:8px;
      transition:opacity 0.3s ease,transform 0.3s ease;
      transform:translateY(8px);opacity:0;
    `;
    document.body.appendChild(toast);
  }

  const colors = {
    success: { bg: '#d4edda', color: '#155724' },
    error:   { bg: '#f8d7da', color: '#721c24' },
    info:    { bg: '#cce5ff', color: '#004085' },
  };

  const { bg, color } = colors[type] || colors.success;
  const icons = { success: '✅', error: '❌', info: 'ℹ️' };

  toast.style.background = bg;
  toast.style.color = color;
  toast.innerHTML = `${icons[type] || ''} ${message}`;

  clearTimeout(toastTimer);
  setTimeout(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
  }, 10);

  toastTimer = setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(8px)';
  }, 3000);
}

/* -------- Init geral -------- */
export async function initAdminPage() {
  renderSidebar();
  initSidebarToggle();
  initModalClose();
  await protegerRota();
}
