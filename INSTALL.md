# 🚀 CHECKLIST DE INSTALAÇÃO - GESTOR CLIMA

## ✅ PRÉ-REQUISITOS

- [ ] Servidor web (Apache/Nginx) instalado
- [ ] PHP 7.4+ instalado
- [ ] MySQL 8.0+ instalado
- [ ] Extensões PHP necessárias:
  - [ ] PDO
  - [ ] PDO_MySQL
  - [ ] mbstring
  - [ ] json

## 📦 INSTALAÇÃO PASSO A PASSO

### 1️⃣ Upload dos Arquivos
- [ ] Fazer upload de todos os arquivos via FTP ou cPanel File Manager
- [ ] Verificar se a estrutura de pastas está correta

### 2️⃣ Banco de Dados
- [ ] Criar banco de dados MySQL (gestorclima_db)
- [ ] Acessar phpMyAdmin
- [ ] Importar arquivo `database/schema.sql`
- [ ] Verificar se todas as tabelas foram criadas:
  - [ ] clientes
  - [ ] climatizadores
  - [ ] locacoes
- [ ] Verificar se as views foram criadas
- [ ] Verificar se os triggers foram criados

### 3️⃣ Configuração
- [ ] Editar `config/config.php`
- [ ] Configurar DB_HOST (geralmente 'localhost')
- [ ] Configurar DB_NAME (nome do banco criado)
- [ ] Configurar DB_USER (usuário do MySQL)
- [ ] Configurar DB_PASS (senha do MySQL)
- [ ] Ajustar APP_URL se necessário

### 4️⃣ Ajustes de JavaScript
- [ ] Abrir `assets/js/app.js`
- [ ] Verificar/ajustar a constante API_BASE:
  ```javascript
  const API_BASE = window.location.origin + '/gestorclimaa/controllers/';
  ```
- [ ] Se instalou em subpasta diferente, ajustar o caminho

### 5️⃣ Permissões (cPanel/Linux)
- [ ] Ajustar permissões das pastas:
  ```
  Pastas: 755
  Arquivos: 644
  ```
- [ ] Se criar pasta de logs/uploads, dar permissão 755

### 6️⃣ Testes

#### Dashboard
- [ ] Acessar: `http://seusite.com/gestorclimaa/`
- [ ] Verificar se carrega sem erros
- [ ] Verificar se os cards de estatísticas aparecem
- [ ] Verificar se o menu lateral funciona
- [ ] Testar menu mobile (F12 > modo responsivo)

#### Módulo de Clientes
- [ ] Acessar página de clientes
- [ ] Testar cadastro de novo cliente
- [ ] Testar edição de cliente
- [ ] Testar busca de cliente
- [ ] Testar exclusão de cliente
- [ ] Verificar validações de formulário

#### Módulo de Climatizadores
- [ ] Acessar página de climatizadores
- [ ] Cadastrar novo climatizador
- [ ] Editar climatizador
- [ ] Testar busca
- [ ] Verificar se status funciona corretamente

#### Módulo de Locações
- [ ] Acessar página de locações
- [ ] Criar nova locação
- [ ] Verificar se cálculo de dias está correto
- [ ] Verificar se valor total é calculado
- [ ] Verificar se status do climatizador muda para "Locado"
- [ ] Finalizar uma locação
- [ ] Verificar se climatizador volta para "Disponível"
- [ ] Testar filtros (Ativas, Finalizadas)

### 7️⃣ Responsividade
- [ ] Testar em celular real ou modo responsivo do navegador
- [ ] Verificar menu hamburguer
- [ ] Verificar se formulários se adaptam
- [ ] Verificar tabelas com scroll horizontal
- [ ] Testar em diferentes resoluções

### 8️⃣ Performance
- [ ] Verificar velocidade de carregamento
- [ ] Testar com dados de exemplo
- [ ] Verificar console do navegador (F12) por erros JavaScript
- [ ] Verificar Network tab por requisições falhadas

### 9️⃣ Segurança
- [ ] Verificar se arquivo .htaccess está funcionando
- [ ] Testar se SQL Injection é prevenido
- [ ] Verificar se validações funcionam
- [ ] Testar inputs com caracteres especiais
- [ ] Em produção: desabilitar display_errors em config.php

### 🔟 Dados de Exemplo (Opcional)
- [ ] O schema.sql já inclui dados de exemplo
- [ ] Para limpar: executar DELETE nas tabelas na ordem:
  1. locacoes
  2. climatizadores
  3. clientes

## 🐛 TROUBLESHOOTING

### Página em branco?
- Verificar logs de erro do PHP
- Habilitar display_errors temporariamente
- Verificar se todas as extensões PHP estão instaladas

### Erro "Connection failed"?
- Verificar credenciais do banco em config.php
- Verificar se MySQL está rodando
- Verificar se usuário tem permissões no banco

### JavaScript não funciona?
- Abrir Console do navegador (F12)
- Verificar erros JavaScript
- Verificar se API_BASE está correto em app.js
- Verificar se jQuery/FontAwesome carregaram

### Estilo não carrega?
- Verificar caminhos dos arquivos CSS
- Verificar se arquivos existem na pasta assets/css/
- Limpar cache do navegador

### API retorna erro 404?
- Verificar caminho dos controllers
- Verificar se .htaccess está ativo
- Verificar se mod_rewrite está habilitado

## 📞 SUPORTE

Logs importantes:
- PHP errors: `/logs/php-errors.log` (se configurado)
- Apache errors: `/var/log/apache2/error.log` (Linux)
- MySQL errors: verificar no phpMyAdmin

## ✅ SISTEMA PRONTO!

Se todos os itens estão marcados, seu sistema está funcionando perfeitamente! 🎉

---

**Última atualização**: Outubro 2025
**Versão**: 1.0.0
