# FormSecure

Formulário de cadastro web com validação completa no cliente e no servidor, proteção contra SQL Injection, hash bcrypt de senhas, tabela de registros e trilha de auditoria completa.

---

## Sumário

1. [Requisitos](#requisitos)
2. [Estrutura do Projeto](#estrutura-do-projeto)
3. [Configuração com XAMPP](#configuração-com-xampp)
4. [Banco de Dados com MySQL Workbench](#banco-de-dados-com-mysql-workbench)
5. [Executando a Aplicação](#executando-a-aplicação)
6. [Segurança](#segurança)
7. [Git](#git)
8. [Solução de Problemas](#solução-de-problemas)

---

## Requisitos

| Ferramenta      | Versão  | Download                                          |
|-----------------|---------|---------------------------------------------------|
| XAMPP           | 8.2+    | https://www.apachefriends.org                     |
| MySQL Workbench | 8.0+    | https://dev.mysql.com/downloads/workbench         |
| Git             | Qualquer| https://git-scm.com                               |
| Navegador       | Chrome / Edge / Firefox atualizado | —            |

---

## Estrutura do Projeto

```
PWEB/
├── backend/
│   ├── db.php          # Conexão PDO (singleton)
│   ├── validate.php    # Funções de validação puras
│   ├── submit.php      # Endpoint POST — validação, hash, persistência
│   └── listar.php      # Endpoint GET — lista usuários sem senha
├── database/
│   └── schema.sql      # Definição do banco e das tabelas
├── frontend/
│   └── index.html      # SPA completa (HTML + CSS + JS)
├── .gitignore
└── README.md
```

---

## Configuração com XAMPP

### Passo 1 — Instalar e iniciar o XAMPP

1. Baixe o XAMPP em https://www.apachefriends.org e execute o instalador.
2. Abra o **Painel de Controle do XAMPP**.
3. Inicie o módulo **Apache**.
4. Inicie o módulo **MySQL**.

Ambos os indicadores de status devem ficar verdes antes de continuar.

### Passo 2 — Copiar o projeto para o htdocs

1. Abra o Explorador de Arquivos do Windows.
2. Navegue até `C:\xampp\htdocs\`.
3. Copie ou clone a pasta `PWEB` inteira para dentro de `htdocs`.

O caminho final deve ser:

```
C:\xampp\htdocs\PWEB\
```

---

## Banco de Dados com MySQL Workbench

### Passo 1 — Conectar ao servidor MySQL local

1. Abra o **MySQL Workbench**.
2. Na tela inicial, clique no ícone `+` ao lado de **MySQL Connections**.
3. Preencha os campos:

   | Campo           | Valor       |
   |-----------------|-------------|
   | Connection Name | XAMPP Local |
   | Hostname        | 127.0.0.1   |
   | Port            | 3306        |
   | Username        | root        |
   | Password        | (deixe em branco — padrão do XAMPP) |

4. Clique em **Test Connection** — deve ser bem-sucedido.
5. Clique em **OK** e abra a conexão.

### Passo 2 — Executar o schema

1. No Workbench, clique em **File > Open SQL Script**.
2. Navegue até `C:\xampp\htdocs\PWEB\database\schema.sql` e abra.
3. Clique no ícone de raio (**Execute**) ou pressione `Ctrl + Shift + Enter`.
4. O banco `formsecure`, a tabela `users` e a tabela `submission_logs` serão criados.

Verifique expandindo o painel **Schemas** à esquerda e confirmando que `formsecure` aparece.

### Passo 3 — Credenciais do banco

Abra `backend/db.php` e ajuste as constantes se o seu MySQL usar usuário ou senha diferente do padrão XAMPP:

```php
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NOME',    'formsecure');
define('DB_USUARIO', 'root');
define('DB_SENHA',   '');
```

---

## Executando a Aplicação

1. Confirme que Apache e MySQL estão em execução no Painel de Controle do XAMPP.
2. Abra o navegador e acesse:

```
http://localhost/PWEB/frontend/index.html
```

3. Preencha o formulário. A validação ocorre no cliente primeiro; o backend revalida todos os campos na submissão.
4. Após o cadastro, uma tabela com todos os usuários registrados (sem a senha) é exibida automaticamente.

### Verificação rápida do backend

Acesse diretamente no navegador:

```
http://localhost/PWEB/backend/submit.php
```

Deve retornar:

```json
{"sucesso":false,"erros":{"servidor":"Método não permitido. Use POST."}}
```

Se aparecer erro 404, a pasta não está no lugar correto dentro de `htdocs`.

---

## Segurança

### SQL Injection

Todas as queries usam PDO com prepared statements nativos (`PDO::ATTR_EMULATE_PREPARES = false`). Nenhuma entrada do usuário é interpolada diretamente em strings SQL.

### Armazenamento de Senha

Senhas são armazenadas com `password_hash()` usando `PASSWORD_BCRYPT` com fator de custo 12. O texto original nunca é armazenado ou registrado em nenhum lugar.

### Sanitização de Entrada

- Null bytes são removidos de todas as entradas antes de qualquer processamento.
- Senhas não passam por trim (espaços são válidos em senhas).
- Toda saída exibida ao usuário passa por `htmlspecialchars`.

### Validação Backend — Regras por campo

| Campo    | Regras                                                                       |
|----------|------------------------------------------------------------------------------|
| nome     | 2 a 100 caracteres, apenas letras, espaços, hífens e apóstrofos             |
| email    | RFC-compliant via `filter_var`, máximo 254 caracteres, único por registro   |
| senha    | 8 a 72 caracteres, maiúscula, minúscula, número e caractere especial        |
| mensagem | 1 a 250 caracteres                                                           |

### Trilha de Auditoria

Cada submissão — com sucesso ou falha — é registrada na tabela `submission_logs` com IP, resultado e motivo da falha quando aplicável.

### Cabeçalhos HTTP

`submit.php` e `listar.php` definem:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`

---

## Git

### Inicializar repositório

```bash
cd C:\xampp\htdocs\PWEB
git init
git add .
git commit -m "Commit inicial"
```

### Conectar a um repositório remoto

```bash
git remote add origin https://github.com/seu-usuario/PWEB.git
git branch -M main
git push -u origin main
```

### Nunca commitar credenciais reais

O arquivo `backend/db.php` contém as credenciais padrão do XAMPP. Antes de enviar para um repositório público, substitua as constantes por variáveis de ambiente ou adicione o arquivo ao `.gitignore`.

---

## Solução de Problemas

| Sintoma                                    | Causa provável                        | Solução                                                        |
|--------------------------------------------|---------------------------------------|----------------------------------------------------------------|
| Página em branco no index.html             | Apache não está em execução           | Inicie o Apache no Painel de Controle do XAMPP                 |
| Erro "Banco de dados indisponível"         | MySQL parado ou credenciais incorretas| Inicie o MySQL; verifique as constantes em `backend/db.php`    |
| Erro 404 ao acessar submit.php             | Pasta no lugar errado                 | Confirme que a pasta PWEB está dentro de `C:\xampp\htdocs\`    |
| Erro 405 ao acessar submit.php no browser  | Acesso via GET em vez de POST         | Normal — o endpoint só aceita POST vindo do formulário         |
| "E-mail já cadastrado"                     | Submissão duplicada                   | Use um endereço de e-mail diferente                            |
| Importação do schema falha no Workbench    | Schema ativo incorreto                | O script já executa `USE formsecure;` — execute novamente      |
| Porta 3306 em uso                          | Outra instância MySQL rodando         | Encerre a outra instância ou altere a porta nas configurações do XAMPP |
