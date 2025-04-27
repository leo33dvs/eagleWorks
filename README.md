# EagleWorks - Plataforma de Conexão entre Freelancers e Empresas

## Descrição
EagleWorks é uma plataforma web responsiva que conecta freelancers e empresas. A plataforma permite dois tipos de cadastros (freelancers e empresas), oferece perfis públicos para ambos, inclui um sistema de busca para encontrar freelancers, suporta avaliações mútuas, e também oferece um sistema de mensagens internas.

## Tecnologias Utilizadas
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **Backend**: PHP
- **Banco de Dados**: MySQL/PostgreSQL (ambos suportados)

## Paleta de Cores
- Azul escuro: #1E3A8A
- Branco: #FFFFFF
- Dourado: #D4AF37

## Funcionalidades Principais
1. **Sistema de Autenticação**
   - Login e cadastro para freelancers e empresas
   - Recuperação de senha

2. **Perfis Personalizados**
   - Perfil para freelancers com portfólio, habilidades e disponibilidade
   - Perfil para empresas com informações corporativas e projetos

3. **Sistema de Busca**
   - Busca por profissão, região e palavras-chave
   - Filtros avançados para refinar resultados

4. **Sistema de Avaliação**
   - Avaliações mútuas entre freelancers e empresas
   - Sistema de 5 estrelas com comentários

5. **Painel de Controle**
   - Dashboard personalizado para cada tipo de usuário
   - Estatísticas e informações relevantes

## Estrutura do Banco de Dados
- Tabela `users`: Informações de autenticação
- Tabela `freelancers`: Dados específicos dos freelancers
- Tabela `companies`: Dados específicos das empresas
- Tabela `ratings`: Avaliações entre usuários
- Tabela `messages`: Sistema de mensagens internas

## Instalação

### Requisitos
- PHP 7.4 ou superior
- MySQL 5.7 ou PostgreSQL 12 ou superior
- Servidor web (Apache, Nginx)

### Passos para Instalação
1. Clone ou extraia os arquivos para seu servidor web
2. Crie um banco de dados MySQL ou PostgreSQL
3. Importe o arquivo SQL apropriado:
   - Para MySQL: `database.sql`
   - Para PostgreSQL: `database_pgsql.sql`
4. Configure a conexão com o banco de dados em `includes/db.php`
5. Execute o script `setup.php` para criar os diretórios necessários
6. Acesse a plataforma pelo navegador

## Adaptação para PostgreSQL
Para usar com PostgreSQL em vez de MySQL:
1. Use o arquivo `database_pgsql.sql` para criar o esquema do banco de dados
2. Certifique-se de que o arquivo `includes/db.php` esteja configurado para PostgreSQL

## Estrutura de Arquivos
- `assets/`: Arquivos CSS, JavaScript e imagens
- `includes/`: Arquivos de configuração e funções comuns
- `*.php`: Páginas principais da aplicação

## Créditos
Desenvolvido por: [Seu Nome/Equipe]