# Gestor Clima

Sistema web para gestão de locação de climatizadores (PHP/MySQL).

## Objetivo
Versão do sistema para desenvolvimento e CI.

## Começando o desenvolvimento

- Ajuste `config/config.php` localmente (não versionar credenciais sensíveis).
- Instale dependências:

```bash
composer install
```

- Inicie servidor para desenvolvimento (opcional):

```bash
php -S localhost:8000
```

## CI (GitHub Actions)

O repositório inclui um workflow em `.github/workflows/ci.yml` que prepara um banco temporário (MariaDB), instala dependências e executa verificações básicas (lint/smoke). Configure `config/config.php` localmente — não commitá-lo com credenciais reais.


## Visão geral

- Escopo: gestão de clientes, locações, controle de disponibilidade de climatizadores, geração de contratos e orçamentos em PDF, auditoria e relatórios.
- Stack: PHP 8.x, MySQL/MariaDB, Composer, mPDF, phpspreadsheet, JavaScript/HTML/CSS.
- Arquitetura: código organizado em `controllers/`, `models/`, `scripts/`, `app/Services/` e `views/`.

## Instruções locais (desenvolvimento)

1. Copie e edite `config/config.php` com suas credenciais locais.
2. Instale dependências:

```bash
composer install
```

3. Inicie servidor para desenvolvimento (opcional):

```bash
php -S localhost:8000
```


## Estrutura principal

- `app/Services/` — serviços auxiliares (ex.: geração de PDF).
- `controllers/` — controladores HTTP/ação.
- `models/` — modelos de domínio.
- `scripts/` — scripts utilitários e migrações.
- `config/` — configurações (NÃO comitar credenciais reais).


