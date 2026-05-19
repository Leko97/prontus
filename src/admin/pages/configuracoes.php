<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Configurações — Prontus Admin'; require_once __DIR__ . '/inc/head.php'; ?>
<body>
  <div class="admin-layout">

    <aside id="sidebar" class="sidebar"></aside>

    <div class="admin-content">
      <header class="admin-topbar">
        <div style="display:flex;align-items:center;gap:12px">
          <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
            <span></span><span></span><span></span>
          </button>
          <h1 class="topbar-title">Configurações</h1>
        </div>
        <div class="topbar-actions">
          <button class="btn btn-primary btn-sm" id="btnSalvarTop">Salvar</button>
        </div>
      </header>

      <main class="admin-main form-page">

        <div class="page-header">
          <div class="page-header-left">
            <h1>Configurações do Sistema</h1>
            <p>Personalize o estabelecimento, aparência e operação</p>
          </div>
        </div>

        <form id="formConfig" novalidate>

          <!-- Identidade -->
          <div class="form-card" style="margin-bottom:var(--space-5)">
            <h3 class="form-section-title">Identidade</h3>
            <div class="form-group">
              <label class="form-label" for="nome_estabelecimento">Nome do Estabelecimento</label>
              <input type="text" id="nome_estabelecimento" name="nome_estabelecimento" class="form-control" placeholder="Ex: Burguer House">
            </div>
            <div class="form-group">
              <label class="form-label" for="slogan">Slogan</label>
              <input type="text" id="slogan" name="slogan" class="form-control" placeholder="Ex: Peça, aguarde, saboreie">
            </div>
            <div class="form-group">
              <label class="form-label" for="logo_url">URL do Logo</label>
              <input type="url" id="logo_url" name="logo_url" class="form-control" placeholder="https://...">
              <div style="margin-top:12px">
                <img id="logoPreview" src="" alt="Preview do logo"
                  style="max-height:80px;max-width:200px;border-radius:8px;display:none;background:#f0f0f0;padding:4px">
              </div>
            </div>
          </div>

          <!-- Aparência -->
          <div class="form-card" style="margin-bottom:var(--space-5)">
            <h3 class="form-section-title">Aparência</h3>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="cor_primaria">Cor Primária</label>
                <div style="display:flex;gap:8px;align-items:center">
                  <input type="color" id="cor_primaria" name="cor_primaria" value="#FF6B35"
                    style="width:48px;height:40px;border:1px solid var(--color-border);border-radius:8px;cursor:pointer;padding:2px">
                  <input type="text" id="cor_primaria_hex" class="form-control" value="#FF6B35"
                    placeholder="#FF6B35" style="flex:1;font-family:monospace" maxlength="7">
                </div>
              </div>
              <div class="form-group">
                <label class="form-label" for="cor_secundaria">Cor Secundária</label>
                <div style="display:flex;gap:8px;align-items:center">
                  <input type="color" id="cor_secundaria" name="cor_secundaria" value="#2D3047"
                    style="width:48px;height:40px;border:1px solid var(--color-border);border-radius:8px;cursor:pointer;padding:2px">
                  <input type="text" id="cor_secundaria_hex" class="form-control" value="#2D3047"
                    placeholder="#2D3047" style="flex:1;font-family:monospace" maxlength="7">
                </div>
              </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:8px">
              <div id="previewPrimaria"
                style="flex:1;height:48px;border-radius:8px;background:#FF6B35;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:500">
                Cor Primária
              </div>
              <div id="previewSecundaria"
                style="flex:1;height:48px;border-radius:8px;background:#2D3047;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:500">
                Cor Secundária
              </div>
            </div>
          </div>

          <!-- Pagamento PIX -->
          <div class="form-card" style="margin-bottom:var(--space-5)">
            <h3 class="form-section-title">Pagamento PIX</h3>
            <div class="form-group">
              <label class="form-label" for="pix_chave">Chave PIX</label>
              <input type="text" id="pix_chave" name="pix_chave" class="form-control"
                placeholder="CPF, CNPJ, e-mail, telefone ou chave aleatória">
            </div>
            <div class="form-group">
              <label class="form-label" for="pix_nome_recebedor">Nome do Recebedor</label>
              <input type="text" id="pix_nome_recebedor" name="pix_nome_recebedor" class="form-control"
                placeholder="Nome que aparece no QR Code">
            </div>
          </div>

          <!-- Operação -->
          <div class="form-card" style="margin-bottom:var(--space-5)">
            <h3 class="form-section-title">Operação</h3>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="tempo_alerta_kds">Alerta KDS após (minutos)</label>
                <input type="number" id="tempo_alerta_kds" name="tempo_alerta_kds" class="form-control"
                  min="1" max="60" value="10" placeholder="10">
                <small class="form-hint">Pedido fica destacado em vermelho após este tempo em preparo</small>
              </div>
              <div class="form-group">
                <label class="form-label" for="totem_idle_segundos">Totem vai para idle após (segundos)</label>
                <input type="number" id="totem_idle_segundos" name="totem_idle_segundos" class="form-control"
                  min="10" max="600" value="60" placeholder="60">
              </div>
            </div>
          </div>

          <!-- Display -->
          <div class="form-card" style="margin-bottom:var(--space-6)">
            <h3 class="form-section-title">Tela Display</h3>
            <div class="form-group">
              <label class="form-label" for="mensagem_display">Mensagem de Rodapé</label>
              <textarea id="mensagem_display" name="mensagem_display" class="form-control"
                rows="2" placeholder="Aguarde sua senha ser chamada!"></textarea>
            </div>
          </div>

          <div style="display:flex;gap:12px">
            <button type="submit" class="btn btn-primary" id="btnSalvar">Salvar configurações</button>
          </div>

        </form>
      </main>
    </div>
  </div>

  <script type="module" src="/src/admin/assets/js/configuracoes.js"></script>
</body>
</html>
