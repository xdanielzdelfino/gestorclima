-- phpMyAdmin SQL Dump (cleaned DEFINER)
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 30/03/2026 às 15:46
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `gestorclima_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(200) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefone` varchar(20) NOT NULL,
  `cpf_cnpj` varchar(18) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cep` varchar(9) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `email`, `telefone`, `cpf_cnpj`, `endereco`, `cidade`, `estado`, `cep`, `observacoes`, `data_cadastro`, `data_atualizacao`, `ativo`) VALUES
(1, 'Empresa Exemplo LTDA', 'contato@exemplo.com', '(11) 99999-0001', '12.345.678/0001-90', 'Rua Exemplo, 123', 'São Paulo', 'SP', '01000-000', 'Cliente fictício para testes', '2026-03-27 19:48:17', '2026-03-27 19:48:17', 1),
(2, 'Evento Demo', 'eventos@demo.com', '(21) 98888-0002', '123.456.789-00', 'Av. Demo, 500', 'Rio de Janeiro', 'RJ', '20000-000', 'Cliente para locações de eventos', '2026-03-27 19:48:17', '2026-03-27 19:48:17', 1),
(3, 'Particular Teste', 'pessoa@teste.com', '(85) 97777-0003', '987.654.321-00', 'Rua Teste, 45', 'Fortaleza', 'CE', '60000-000', '', '2026-03-27 19:48:17', '2026-03-27 19:48:17', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `climatizadores`
--

CREATE TABLE `climatizadores` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `capacidade` varchar(50) DEFAULT NULL COMMENT 'Ex: 10.000 BTU, 12.000 BTU',
  `tipo` enum('Portatil','Split','Janela','Central') DEFAULT 'Portatil',
  `descricao` text DEFAULT NULL,
  `valor_diaria` decimal(10,2) NOT NULL,
  `status` enum('Disponivel','Locado','Manutencao','Inativo') DEFAULT 'Disponivel',
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `estoque` int(11) DEFAULT 0,
  `desconto_maximo` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `climatizadores`
--

INSERT INTO `climatizadores` (`id`, `codigo`, `modelo`, `marca`, `capacidade`, `tipo`, `descricao`, `valor_diaria`, `status`, `data_cadastro`, `data_atualizacao`, `estoque`, `desconto_maximo`) VALUES
(1, 'CLIM-001', 'Portátil 12000 BTU', 'Electrolux', '12.000 BTU', 'Portatil', 'Climatizador portátil para até 20m²', 45.00, 'Disponivel', '2026-03-27 19:48:17', '2026-03-27 19:48:17', 5, 10.00),
(2, 'CLIM-002', 'Split 18000 BTU', 'Samsung', '18.000 BTU', 'Split', 'Split inverter econômico', 80.00, 'Disponivel', '2026-03-27 19:48:17', '2026-03-27 19:48:17', 3, 5.00),
(3, 'CLIM-003', 'Portátil 10000 BTU', 'Midea', '10.000 BTU', 'Portatil', 'Compacto e leve', 35.00, 'Disponivel', '2026-03-27 19:48:17', '2026-03-27 19:48:17', 8, 0.00),
(4, 'CLIM-004', 'Industrial 30000/H', 'Rotoplast', '30.000/H', 'Portatil', 'Alto fluxo para grandes eventos', 500.00, 'Disponivel', '2026-03-27 19:48:17', '2026-03-27 19:48:17', 2, 5.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `locacoes`
--

CREATE TABLE `locacoes` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `climatizador_id` int(11) NOT NULL,
  `data_inicio` datetime DEFAULT NULL,
  `data_fim` datetime DEFAULT NULL,
  `data_devolucao_real` datetime DEFAULT NULL COMMENT 'Data real de devolução',
  `valor_diaria` decimal(10,2) NOT NULL,
  `quantidade_dias` int(11) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `valor_pago` decimal(10,2) DEFAULT 0.00,
  `status` enum('Reserva','Ativa','Confirmada','Cancelada','Finalizada') NOT NULL DEFAULT 'Reserva',
  `observacoes` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `quantidade_climatizadores` int(11) DEFAULT 1,
  `desconto` decimal(5,2) DEFAULT 0.00,
  `aplicar_desconto` tinyint(1) NOT NULL DEFAULT 0,
  `local_evento` varchar(255) DEFAULT NULL,
  `despesas_acessorias` decimal(10,2) NOT NULL DEFAULT 0.00,
  `responsavel` varchar(255) DEFAULT NULL COMMENT 'Nome do responsável pela locação (campo opcional)',
  `despesas_acessorias_tipo` varchar(255) DEFAULT NULL COMMENT 'Rótulo/descrição da despesa acessória selecionada',
  `climatizadores_json` text DEFAULT NULL COMMENT 'JSON de itens (climatizadores) da locação',
  `climatizadores` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `locacoes`
--

INSERT INTO `locacoes` (`id`, `cliente_id`, `climatizador_id`, `data_inicio`, `data_fim`, `data_devolucao_real`, `valor_diaria`, `quantidade_dias`, `valor_total`, `valor_pago`, `status`, `observacoes`, `data_criacao`, `data_atualizacao`, `quantidade_climatizadores`, `desconto`, `aplicar_desconto`, `local_evento`, `despesas_acessorias`, `responsavel`, `despesas_acessorias_tipo`, `climatizadores_json`, `climatizadores`) VALUES
(1, 1, 1, '2026-03-28 16:48:17', '2026-03-28 16:48:17', NULL, 45.00, 1, 200.00, 0.00, 'Reserva', 'Reserva de teste', '2026-03-27 19:48:17', '2026-03-27 19:48:17', 2, 0.00, 0, 'Centro de Convenções', 50.00, 'Fulano Teste', 'Transporte', NULL, NULL),
(2, 2, 4, '2026-04-03 16:48:17', '2026-04-03 16:48:17', NULL, 500.00, 1, 1500.00, 0.00, 'Reserva', 'Evento demonstração', '2026-03-27 19:48:17', '2026-03-27 19:48:17', 3, 0.00, 0, 'Praça Principal', 300.00, 'Empresa Demo', 'Instalação', NULL, NULL);

--
-- Acionadores `locacoes`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_locacao` AFTER INSERT ON `locacoes` FOR EACH ROW BEGIN
            IF NEW.status IN ('Ativa','Confirmada') THEN
                UPDATE climatizadores 
                SET status = 'Locado' 
                WHERE id = NEW.climatizador_id;
            END IF;
        END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_update_locacao` AFTER UPDATE ON `locacoes` FOR EACH ROW BEGIN
            IF NEW.status IN ('Finalizada', 'Cancelada') AND OLD.status IN ('Ativa','Confirmada') THEN
                UPDATE climatizadores 
                SET status = 'Disponivel' 
                WHERE id = NEW.climatizador_id;
            END IF;
        END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_acesso`
--

CREATE TABLE `logs_acesso` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `acao` varchar(50) NOT NULL COMMENT 'login, logout, tentativa_falha',
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de acessos ao sistema';

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL COMMENT 'Senha criptografada com password_hash()',
  `nivel` enum('admin','operador','visualizador') NOT NULL DEFAULT 'operador' COMMENT 'Nível de acesso do usuário',
  `ativo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Ativo, 0 = Inativo',
  `ultimo_acesso` datetime DEFAULT NULL COMMENT 'Data e hora do último login',
  `token_recuperacao` varchar(100) DEFAULT NULL COMMENT 'Token para recuperação de senha',
  `token_expiracao` datetime DEFAULT NULL COMMENT 'Data de expiração do token',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de usuários do sistema';

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `nivel`, `ativo`, `ultimo_acesso`, `token_recuperacao`, `token_expiracao`, `criado_em`, `atualizado_em`) VALUES
(1, 'Administrador Dev', 'admin@admin.com', '$2y$12$8nhStyefTmgaUyk/5ucrSeG1.hMC4/OSMxQqbjJV1iFnGSAGq3kD2', 'admin', 1, '2026-03-30 10:32:51', NULL, NULL, '2026-03-27 19:48:17', '2026-03-30 13:32:51');

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_dashboard_resumo`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_dashboard_resumo` (
`total_clientes` bigint(21)
,`total_climatizadores` bigint(21)
,`climatizadores_disponiveis` bigint(21)
,`climatizadores_locados` bigint(21)
,`locacoes_ativas` bigint(21)
,`receita_ativa` decimal(32,2)
,`receita_mes_atual` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_locacoes_completas`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_locacoes_completas` (
`id` int(11)
,`data_inicio` datetime
,`data_fim` datetime
,`data_devolucao_real` datetime
,`quantidade_dias` int(11)
,`valor_total` decimal(10,2)
,`valor_pago` decimal(10,2)
,`status` enum('Reserva','Ativa','Confirmada','Cancelada','Finalizada')
,`cliente_nome` varchar(200)
,`cliente_telefone` varchar(20)
,`cliente_email` varchar(150)
,`climatizador_codigo` varchar(50)
,`climatizador_modelo` varchar(100)
,`climatizador_marca` varchar(100)
,`climatizador_capacidade` varchar(50)
,`data_criacao` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_usuarios_resumo`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_usuarios_resumo` (
`nivel` enum('admin','operador','visualizador')
,`total` bigint(21)
,`ativos` decimal(22,0)
,`inativos` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Estrutura para view `vw_dashboard_resumo`
--
DROP TABLE IF EXISTS `vw_dashboard_resumo`;

CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `vw_dashboard_resumo`  AS SELECT (select count(0) from `clientes` where `clientes`.`ativo` = 1) AS `total_clientes`, (select count(0) from `climatizadores` where `climatizadores`.`status` <> 'Inativo') AS `total_climatizadores`, (select count(0) from `climatizadores` where `climatizadores`.`status` = 'Disponivel') AS `climatizadores_disponiveis`, (select count(0) from `climatizadores` where `climatizadores`.`status` = 'Locado') AS `climatizadores_locados`, (select count(0) from `locacoes` where `locacoes`.`status` = 'Ativa') AS `locacoes_ativas`, (select coalesce(sum(`locacoes`.`valor_total`),0) from `locacoes` where `locacoes`.`status` = 'Ativa') AS `receita_ativa`, (select coalesce(sum(`locacoes`.`valor_total`),0) from `locacoes` where `locacoes`.`status` = 'Finalizada' and month(`locacoes`.`data_criacao`) = month(curdate())) AS `receita_mes_atual` ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_locacoes_completas`
--
DROP TABLE IF EXISTS `vw_locacoes_completas`;

CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `vw_locacoes_completas`  AS SELECT `l`.`id` AS `id`, `l`.`data_inicio` AS `data_inicio`, `l`.`data_fim` AS `data_fim`, `l`.`data_devolucao_real` AS `data_devolucao_real`, `l`.`quantidade_dias` AS `quantidade_dias`, `l`.`valor_total` AS `valor_total`, `l`.`valor_pago` AS `valor_pago`, `l`.`status` AS `status`, `c`.`nome` AS `cliente_nome`, `c`.`telefone` AS `cliente_telefone`, `c`.`email` AS `cliente_email`, `cl`.`codigo` AS `climatizador_codigo`, `cl`.`modelo` AS `climatizador_modelo`, `cl`.`marca` AS `climatizador_marca`, `cl`.`capacidade` AS `climatizador_capacidade`, `l`.`data_criacao` AS `data_criacao` FROM ((`locacoes` `l` join `clientes` `c` on(`l`.`cliente_id` = `c`.`id`)) join `climatizadores` `cl` on(`l`.`climatizador_id` = `cl`.`id`)) ORDER BY `l`.`data_criacao` DESC ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_usuarios_resumo`
--
DROP TABLE IF EXISTS `vw_usuarios_resumo`;

CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `vw_usuarios_resumo`  AS SELECT `usuarios`.`nivel` AS `nivel`, count(0) AS `total`, sum(case when `usuarios`.`ativo` = 1 then 1 else 0 end) AS `ativos`, sum(case when `usuarios`.`ativo` = 0 then 1 else 0 end) AS `inativos` FROM `usuarios` GROUP BY `usuarios`.`nivel` ;

-- --------------------------------------------------------

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nome` (`nome`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_cpf_cnpj` (`cpf_cnpj`);

--
-- Índices de tabela `climatizadores`
--
ALTER TABLE `climatizadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_codigo` (`codigo`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_modelo` (`modelo`);

--
-- Índices de tabela `locacoes`
--
ALTER TABLE `locacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cliente` (`cliente_id`),
  ADD KEY `idx_climatizador` (`climatizador_id`),
  ADD KEY `idx_data_inicio` (`data_inicio`),
  ADD KEY `idx_data_fim` (`data_fim`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `logs_acesso`
--
ALTER TABLE `logs_acesso`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_acao` (`acao`),
  ADD KEY `idx_criado_em` (`criado_em`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_ativo` (`ativo`),
  ADD KEY `idx_nivel` (`nivel`);

-- --------------------------------------------------------

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `climatizadores`
--
ALTER TABLE `climatizadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `locacoes`
--
ALTER TABLE `locacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `logs_acesso`
--
ALTER TABLE `logs_acesso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `locacoes`
--
ALTER TABLE `locacoes`
  ADD CONSTRAINT `locacoes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `locacoes_ibfk_2` FOREIGN KEY (`climatizador_id`) REFERENCES `climatizadores` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `logs_acesso`
--
ALTER TABLE `logs_acesso`
  ADD CONSTRAINT `logs_acesso_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
