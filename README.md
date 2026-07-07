# Gestao de Recurso Setorial

Sistema web em PHP e MySQL para controle de recursos, estoque e usuarios por setor.

## Objetivo

O projeto centraliza o gerenciamento de itens de quatro setores em uma unica aplicacao, mantendo os dados separados por setor e com regras de permissao por perfil.

## Setores Atendidos

- CTIC
- ALMOXARIFADO
- LAB DE DESIGNER
- LAB MAKER

## Perfis De Acesso

- `estagiario`: cadastra itens, consulta estoque e altera quantidades.
- `admin`: possui as permissoes do estagiario e tambem gerencia usuarios do proprio setor.
- `gestor`: nome exibido para o perfil `admin` no Almoxarifado.
- `bolsista`: nome exibido para o perfil `estagiario` nos laboratorios; nos laboratorios, bolsistas apenas consultam itens e relatorios.
- `super_admin`: acesso maximo pelo link protegido do Admin Maximo.

## Principais Recursos

- Login com redirecionamento automatico para o setor do usuario.
- Separacao de itens, usuarios e movimentacoes por setor.
- Cadastro de itens com foto.
- Controle de estoque com historico de movimentacoes.
- Cadastro, edicao e exclusao de usuarios por gestores do proprio setor.
- Relatorio profissional para impressao ou salvamento em PDF.
- Admin Maximo protegido por login e chave no link.
- Temas visuais proprios para Lab Maker e Lab de Designer.
- Rotas automaticas para rodar em `localhost`, IP local, subpasta ou raiz do servidor.

## Tecnologias

- PHP
- MySQL ou MariaDB
- HTML
- CSS
- JavaScript
- Apache

## Estrutura Do Projeto

```text
assets/                 Arquivos CSS e recursos visuais
config/                 Configuracoes da aplicacao e banco
database/               Schema inicial e migrations
includes/               Regras de negocio e funcoes compartilhadas
scripts/                Scripts auxiliares de criacao/teste de usuarios
setores/                Paginas dos setores
templates/              Partes reutilizaveis da interface
uploads/                Pasta para fotos enviadas pelo sistema
admin-maximo.php        Painel do Admin Maximo
index.php               Tela de login
logout.php              Logout comum
sair-admin-maximo.php   Logout especifico do Admin Maximo
```

## Configuracao Do Banco

Por padrao, o arquivo `config/database.php` usa a configuracao comum do XAMPP:

```text
host: 127.0.0.1
banco: sas
usuario: root
senha: vazia
```

Em servidor, voce pode configurar por variaveis de ambiente:

```bash
DB_HOST=127.0.0.1
DB_NAME=sas
DB_USER=usuario_do_banco
DB_PASS=senha_do_banco
```

## Instalacao Local No XAMPP

1. Copie o projeto para:

```text
C:\xampp\htdocs\sas
```

2. Inicie Apache e MySQL no XAMPP.

3. Importe o banco:

```text
database/schema.sql
```

4. Crie os usuarios iniciais:

```powershell
C:\xampp\php\php.exe scripts\create_ctic_user.php
C:\xampp\php\php.exe scripts\create_almoxarifado_user.php
C:\xampp\php\php.exe scripts\create_lab_users.php
C:\xampp\php\php.exe scripts\create_super_admin.php
```

5. Acesse:

```text
http://localhost/sas/
```

## Usuarios Iniciais De Desenvolvimento

Os scripts criam usuarios para teste local com senha padrao `123456`.

```text
ctic@sas.local
almoxarifado@sas.local
designer@sas.local
bolsista.designer@sas.local
maker@sas.local
bolsista.maker@sas.local
admin.maximo@sas.local
```

Em producao ou em servidor compartilhado, altere as senhas apos o primeiro acesso.

## Admin Maximo

O Admin Maximo exige:

- usuario com perfil `super_admin`;
- chave secreta no link.

Modelo de URL:

```text
http://localhost/sas/admin-maximo.php?k=SUA_CHAVE_SECRETA
```

Para gerar uma nova chave:

```powershell
C:\xampp\php\php.exe scripts\generate_admin_key.php
```

Use o token gerado no link e copie o hash para `config/admin_access.php`.

## Rotas Em Servidor Local Ou Proxmox

O arquivo `config/app.php` vem com:

```php
const APP_BASE_PATH = null;
```

Com `null`, o sistema detecta automaticamente se esta rodando em:

```text
http://localhost/sas/
http://192.168.0.10/sas/
http://192.168.0.10/
http://192.168.0.10/outra-pasta/
```

Se precisar travar manualmente:

```php
const APP_BASE_PATH = '';
const APP_BASE_PATH = '/sas';
const APP_BASE_PATH = '/gestao-recursos';
```

## Instalacao Em Debian/Proxmox LXC

Instale os pacotes:

```bash
apt update
apt install -y apache2 mariadb-server php php-cli php-mysql php-mbstring php-xml php-curl php-gd php-zip libapache2-mod-php git
```

Copie o projeto para:

```text
/var/www/html/sas
```

Ajuste permissoes:

```bash
chown -R www-data:www-data /var/www/html/sas
chmod -R 755 /var/www/html/sas
chmod -R 775 /var/www/html/sas/uploads
```

Importe o banco:

```bash
mysql -u usuario_do_banco -p sas < /var/www/html/sas/database/schema.sql
```

Crie os usuarios iniciais:

```bash
php /var/www/html/sas/scripts/create_ctic_user.php
php /var/www/html/sas/scripts/create_almoxarifado_user.php
php /var/www/html/sas/scripts/create_lab_users.php
php /var/www/html/sas/scripts/create_super_admin.php
```

## Segurança Aplicada

- Senhas salvas com `password_hash`.
- Login com regeneracao de ID de sessao.
- Cookies de sessao com `httponly` e `SameSite=Lax`.
- Formularios protegidos com CSRF.
- Consultas SQL usando prepared statements.
- Upload limitado a imagens JPG, PNG, WEBP ou GIF com no maximo 2 MB.
- Pasta `uploads` protegida contra listagem e execucao de PHP.
- Itens, usuarios e movimentacoes filtrados por setor.
- Admin Maximo isolado das telas setoriais e protegido por chave.

## Preparacao Para GitHub

O `.gitignore` impede o envio de:

- arquivos de upload;
- caches locais;
- arquivos de ambiente;
- pastas internas de ferramentas locais.

Antes de publicar, confira:

```bash
git status
```

## Repositorio

Repositorio remoto:

```text
https://github.com/JosePaulo2003/gestao-itens.git
```
