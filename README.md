# Gestor Clima

Sistema web para gestão de locação de climatizadores.

## Objetivo
Versão do sistema para desenvolvimento.

## Começando o desenvolvimento

- Copie e edite `config/config.example.php` para criar `config/config.php` (NÃO comitar credenciais reais).

Exemplo rápido:

```bash
cp config/config.example.php config/config.php
# ou (Windows PowerShell)
Copy-Item config\\config.example.php config\\config.php
```

Edite `config/config.php` e atualize as constantes de conexão ao banco de dados (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).

Para preparar um banco local de desenvolvimento, importe o dump SQL incluído:

```bash
# com MySQL client (substitua usuário conforme necessário):
mysql -u root -p gestorclima_db < database/gestorclima_db.sql
```

Este arquivo contém um esquema mínimo com dados fictícios seguros para desenvolvimento. 

- Instale dependências:

```bash
composer install
```

- Inicie servidor para desenvolvimento (opcional):

```bash
php -S localhost:8000
```


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


