# EagleWorks

EagleWorks é uma plataforma web responsiva para conectar freelancers e empresas.

## Recursos

- Sistema de perfis para freelancers e empresas
- Sistema de busca avançada
- Sistema de avaliações
- Interface responsiva
- Segurança (autenticação, senhas com hash, proteção contra SQL Injection)

## Tecnologias

- PHP 8
- PostgreSQL / MySQL
- HTML5, CSS3, JavaScript
- Bootstrap 5

## Instalação

1. Clone este repositório
2. Configure seu servidor web (Apache, Nginx, etc)
3. Importe o banco de dados usando `database_pgsql.sql` (PostgreSQL) ou `database.sql` (MySQL)
4. Configure a conexão do banco de dados em `includes/db.php`
5. Acesse o projeto pelo navegador

## Estrutura de Diretórios

- `assets/` - Arquivos CSS, JavaScript e imagens
- `includes/` - Arquivos de configuração, funções e componentes reutilizáveis
- `*.php` - Páginas principais da aplicação