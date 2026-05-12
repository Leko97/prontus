CREATE TABLE categorias (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nome      VARCHAR(100) NOT NULL,
  icone     VARCHAR(10)  DEFAULT '',
  ativo     TINYINT      NOT NULL DEFAULT 1,
  criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE restricoes (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  slug  VARCHAR(50)  NOT NULL UNIQUE,
  nome  VARCHAR(100) NOT NULL,
  cor   VARCHAR(7)   DEFAULT '#F39C12',
  ativo TINYINT      NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE produtos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT           NOT NULL,
  nome         VARCHAR(200)  NOT NULL,
  descricao    TEXT,
  preco        DECIMAL(10,2) NOT NULL,
  imagem       VARCHAR(500)  DEFAULT NULL,
  ativo        TINYINT       NOT NULL DEFAULT 1,
  criado_em    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_categoria (categoria_id),
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE produto_restricoes (
  produto_id     INT         NOT NULL,
  restricao_slug VARCHAR(50) NOT NULL,
  PRIMARY KEY (produto_id, restricao_slug),
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE adicionais (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  produto_id INT           NOT NULL,
  nome       VARCHAR(100)  NOT NULL,
  preco      DECIMAL(10,2) NOT NULL DEFAULT 0,
  ativo      TINYINT(1)    NOT NULL DEFAULT 1,
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE remocoes (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  produto_id INT          NOT NULL,
  nome       VARCHAR(100) NOT NULL,
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedidos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  senha        VARCHAR(10)   NOT NULL,
  status       ENUM('recebido','em-preparo','pronto','finalizado') NOT NULL DEFAULT 'recebido',
  pagamento    VARCHAR(20)   DEFAULT NULL,
  total        DECIMAL(10,2) NOT NULL DEFAULT 0,
  horario      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  preparado_em DATETIME      DEFAULT NULL,
  INDEX idx_status  (status),
  INDEX idx_horario (horario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedido_itens (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id      INT           NOT NULL,
  produto_id     INT           DEFAULT NULL,
  produto_nome   VARCHAR(200)  NOT NULL,
  preco_unitario DECIMAL(10,2) NOT NULL,
  quantidade     INT           NOT NULL DEFAULT 1,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedido_item_extras (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  pedido_item_id INT          NOT NULL,
  nome           VARCHAR(100) NOT NULL,
  FOREIGN KEY (pedido_item_id) REFERENCES pedido_itens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedido_item_remocoes (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  pedido_item_id INT          NOT NULL,
  nome           VARCHAR(100) NOT NULL,
  FOREIGN KEY (pedido_item_id) REFERENCES pedido_itens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedido_item_restricoes (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  pedido_item_id INT         NOT NULL,
  restricao_slug VARCHAR(50) NOT NULL,
  FOREIGN KEY (pedido_item_id) REFERENCES pedido_itens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(100) NOT NULL,
  email      VARCHAR(200) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  perfil     ENUM('admin','cozinha') NOT NULL DEFAULT 'cozinha',
  ativo      TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
