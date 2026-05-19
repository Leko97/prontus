CREATE TABLE configuracoes (
  chave         VARCHAR(100) PRIMARY KEY,
  valor         TEXT         NOT NULL,
  grupo         VARCHAR(50)  DEFAULT 'geral',
  atualizado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO configuracoes (chave, valor, grupo) VALUES
  ('nome_estabelecimento', 'Meu Restaurante',              'geral'),
  ('slogan',               'Peça, aguarde, saboreie',      'geral'),
  ('logo_url',             '',                             'geral'),
  ('cor_primaria',         '#FF6B35',                      'visual'),
  ('cor_secundaria',       '#2D3047',                      'visual'),
  ('pix_chave',            '',                             'pagamento'),
  ('pix_nome_recebedor',   '',                             'pagamento'),
  ('mensagem_display',     'Aguarde sua senha ser chamada!','display'),
  ('tempo_alerta_kds',     '10',                           'operacao'),
  ('totem_idle_segundos',  '60',                           'operacao');
