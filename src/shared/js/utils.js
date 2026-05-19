/**
 * Formata número para moeda brasileira: R$ 00,00
 */
export function formatCurrency(value) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(value ?? 0);
}

/**
 * Formata string ISO 8601 para DD/MM/AAAA HH:MM
 */
export function formatDateTime(isoString) {
  if (!isoString) return '—';
  return new Date(isoString).toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

/**
 * Formata string ISO 8601 para HH:MM
 */
export function formatTime(isoString) {
  if (!isoString) return '--:--';
  const date = new Date(isoString);
  return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

/**
 * Retorna quantos minutos atrás foi o timestamp ISO
 */
export function minutesAgo(isoString) {
  if (!isoString) return 0;
  const diff = Date.now() - new Date(isoString).getTime();
  return Math.floor(diff / 60000);
}

/**
 * Formata minutosAgo em texto legível: "há X min", "há X h"
 */
export function formatElapsed(isoString) {
  const mins = minutesAgo(isoString);
  if (mins < 1)  return 'agora';
  if (mins < 60) return `há ${mins} min`;
  const h = Math.floor(mins / 60);
  return `há ${h}h`;
}

/**
 * Gera número de senha aleatório de 3 dígitos: "#042"
 */
export function generateSenha() {
  const n = Math.floor(Math.random() * 900) + 100;
  return `#${n}`;
}

/**
 * Retorna a hora atual formatada HH:MM:SS
 */
export function currentTime() {
  return new Date().toLocaleTimeString('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}

/**
 * Retorna a hora atual formatada HH:MM
 */
export function currentTimeShort() {
  return new Date().toLocaleTimeString('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
  });
}

/**
 * Lê query param da URL
 */
export function getParam(name) {
  return new URLSearchParams(window.location.search).get(name);
}

/**
 * Mapeia slug de restrição para label legível
 */
const RESTRICAO_LABELS = {
  'sem-gluten':   'Sem Glúten',
  'vegetariano':  'Vegetariano',
  'vegano':       'Vegano',
  'sem-lactose':  'Sem Lactose',
  'sem-amendoim': 'Sem Amendoim',
};

export function restricaoLabel(slug) {
  return RESTRICAO_LABELS[slug] ?? slug;
}

/**
 * Cria elemento HTML a partir de template string
 */
export function html(strings, ...values) {
  return strings.reduce((acc, str, i) => acc + str + (values[i] ?? ''), '');
}

/**
 * Próximo status no fluxo do KDS
 */
const STATUS_FLOW = {
  'recebido':   'em-preparo',
  'em-preparo': 'pronto',
  'pronto':     null,
};

export function proximoStatus(status) {
  return STATUS_FLOW[status] ?? null;
}

export function statusLabel(status) {
  const labels = {
    'recebido':   'Recebido',
    'em-preparo': 'Em Preparo',
    'pronto':     'Pronto',
    'finalizado': 'Finalizado',
  };
  return labels[status] ?? status;
}

export function btnAvancarLabel(status) {
  const labels = {
    'recebido':   'Iniciar preparo',
    'em-preparo': 'Marcar como pronto',
    'pronto':     'Finalizar',
  };
  return labels[status] ?? 'Avançar';
}
