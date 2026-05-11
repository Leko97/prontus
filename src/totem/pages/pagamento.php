<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Pagamento — Prontus'; require_once __DIR__ . '/inc/head.php'; ?>
<body>

  <header class="totem-header">
    <a href="/src/totem/pages/carrinho.php" class="totem-logo">
      <div class="totem-logo-icon">🍽️</div>
      <div>
        <div class="totem-logo-name">Prontus</div>
        <div class="totem-logo-sub">Pagamento</div>
      </div>
    </a>
  </header>

  <div class="totem-container" style="max-width:760px;margin:0 auto">

    <div style="margin-bottom:var(--space-6)">
      <a href="/src/totem/pages/carrinho.php" class="btn-back">← Voltar ao carrinho</a>
    </div>

    <h1 style="font-size:var(--text-2xl);font-weight:var(--font-bold);margin-bottom:var(--space-6)">
      💳 Como deseja pagar?
    </h1>

    <!-- Total a pagar -->
    <div class="pagamento-total">
      <div class="pagamento-total-label">Total a pagar</div>
      <div class="pagamento-total-valor" id="pagamentoTotal">R$ 0,00</div>
    </div>

    <!-- Métodos de pagamento -->
    <div class="metodos-grid">
      <button class="metodo-btn" data-metodo="pix">
        <span class="metodo-icon">🟩</span>
        <span>PIX</span>
      </button>
      <button class="metodo-btn" data-metodo="cartao">
        <span class="metodo-icon">💳</span>
        <span>Cartão</span>
      </button>
      <button class="metodo-btn" data-metodo="dinheiro">
        <span class="metodo-icon">💵</span>
        <span>Dinheiro</span>
      </button>
    </div>

    <!-- Painéis de instrução -->
    <div id="instrucao-pix" class="instrucao-panel">
      <div class="instrucao-icon">🟩</div>
      <div class="qr-placeholder">📷</div>
      <p class="instrucao-text">Aponte a câmera do celular para o QR Code acima ou use a chave PIX <strong>prontus@exemplo.com</strong></p>
    </div>

    <div id="instrucao-cartao" class="instrucao-panel">
      <div class="instrucao-icon">💳</div>
      <p class="instrucao-text" style="font-size:var(--text-xl);margin-bottom:var(--space-4)">
        Aproxime ou insira o cartão na máquina ao lado
      </p>
      <p class="instrucao-text">Aceitamos débito, crédito e vale-refeição</p>
    </div>

    <div id="instrucao-dinheiro" class="instrucao-panel">
      <div class="instrucao-icon">💵</div>
      <p class="instrucao-text" style="font-size:var(--text-xl);margin-bottom:var(--space-4)">
        Dirija-se ao caixa com sua senha para realizar o pagamento
      </p>
      <p class="instrucao-text">O atendente irá efetuar o troco</p>
    </div>

    <!-- Erro -->
    <div id="alertErro" class="alert alert-danger hidden" style="margin-bottom:var(--space-5)">
      ❌ Erro ao processar o pedido. Tente novamente.
    </div>

    <!-- Confirmar -->
    <button class="btn btn-primary btn-full btn-lg" id="btnConfirmar" disabled>
      Confirmar pagamento
    </button>

  </div>

  <script type="module" src="/src/totem/assets/js/pagamento.js"></script>
</body>
</html>
