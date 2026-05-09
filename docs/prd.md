# PRD – Prontus v1

## Nome do Projeto
Prontus

## Visão Geral
O Prontus é um sistema web para restaurantes que organiza pedidos feitos via totem de autoatendimento e os distribui digitalmente para a cozinha, eliminando comandas em papel e reduzindo gargalos em filas.

## Objetivos da v1
- Organizar o fluxo de pedidos entre totem e cozinha
- Reduzir erros operacionais causados por comandas físicas
- Permitir controle de status em tempo real
- Validar interesse de donos de restaurantes na solução
- Medir impacto na velocidade de atendimento

## Personas

**Gestor Operacional** — Precisa organizar filas, reduzir erros, acelerar entregas e acompanhar desempenho da cozinha em tempo real.

**Operador de Cozinha** — Precisa visualizar pedidos organizados por ordem e status, com destaque para restrições alimentares e personalizações.

**Cliente Final** — Quer fazer pedido rapidamente, personalizar ingredientes, pagar e acompanhar sua senha até ser chamado.

## Funcionalidades Essenciais (MVP)

### Totem de Autoatendimento
- Visualização do cardápio por categorias
- Personalização dinâmica de itens
- Filtros de restrição alimentar
- Carrinho com resumo do pedido
- Pagamento multimodal (PIX, cartão, dinheiro)
- Geração automática de senha
- Check-in de fidelidade

### Painel KDS (Cozinha)
- Lista de pedidos em tempo real
- Alertas de restrição alimentar
- Status: Recebido → Em preparo → Pronto → Finalizado
- Ordenação automática por horário

### Tela Pública de Chamadas
- Senhas em preparo e prontas
- Atualização automática
- Modo tela cheia para TV

### Painel de Gestão
- CRUD de produtos, categorias, preços e adicionais
- Configuração de restrições alimentares
- Métricas: total de pedidos, tempo médio, volume de vendas

## Requisitos Não-Funcionais
- Plataforma web responsiva
- Atualização em até 2 segundos
- Interface com botões grandes (uso operacional)
- Capacidade: até 500 pedidos/dia na v1
- Backend PHP
- Backup automático diário
- Controle de acesso por perfil (Administrador / Cozinha)

## Fora do Escopo (v1)
- App mobile nativo
- Integração com delivery externo
- Relatórios avançados
- Integração com impressoras fiscais ou ERPs
- Múltiplas unidades

## Indicadores de Sucesso
- 3+ restaurantes ativos por 30 dias
- Redução de erros relatada por 2+ clientes
- Redução de 15%+ no tempo médio de preparo
- 200+ pedidos processados na validação inicial
