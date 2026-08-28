# MathPlay Solutions — Guia de Instalação

## Pré-requisitos
- XAMPP instalado e rodando (Apache + MySQL)

## Passo a Passo

### 1. Importar o Banco de Dados
1. Acesse: http://localhost/phpmyadmin
2. Clique em "Novo" para criar banco de dados
3. Vá em "Importar" → selecione o arquivo: `database/mathplay.sql`
4. Clique em "Executar"

### 2. Iniciar o XAMPP
- Abra o XAMPP Control Panel
- Inicie Apache e MySQL

### 3. Acessar o Sistema
- Abra o navegador em: http://localhost/vortex/

### 4. Criar Conta de Professor
- Acesse: http://localhost/vortex/register.php
- Selecione "Professor" ao cadastrar

### 5. IA Adaptativa (Opcional)
- Obtenha sua chave em: https://console.anthropic.com/
- Edite o arquivo: `api/generate_questions.php`
- Substitua `YOUR_ANTHROPIC_API_KEY_HERE` pela sua chave

## Estrutura de Arquivos

```
vortex/
├── index.php              ← Página inicial
├── login.php              ← Login
├── register.php           ← Cadastro
├── dashboard.php          ← Dashboard do aluno
├── profile.php            ← Perfil e conquistas
├── database/
│   └── mathplay.sql       ← Script do banco de dados
├── games/
│   ├── index.php          ← Catálogo de jogos
│   ├── fractions.php      ← Jogo: Chef das Frações
│   └── geometry.php       ← Jogo: Construtor de Cidades
├── teacher/
│   ├── dashboard.php      ← Painel do professor
│   └── reports.php        ← Relatórios detalhados
├── api/
│   ├── save_score.php     ← API: salva pontuação
│   └── generate_questions.php ← API: IA Claude
├── includes/
│   ├── db.php             ← Conexão MySQL
│   ├── auth.php           ← Autenticação
│   ├── header.php         ← Header/navbar
│   ├── footer.php         ← Footer
│   └── logout.php         ← Logout
├── assets/
│   ├── css/style.css      ← CSS principal
│   └── js/main.js         ← JavaScript principal
└── logo/
    └── logo.png           ← Logo da plataforma
```

## URLs do Sistema

| Página | URL |
|--------|-----|
| Home | http://localhost/vortex/ |
| Login | http://localhost/vortex/login.php |
| Cadastro | http://localhost/vortex/register.php |
| Dashboard | http://localhost/vortex/dashboard.php |
| Perfil | http://localhost/vortex/profile.php |
| Jogos | http://localhost/vortex/games/index.php |
| Chef das Frações | http://localhost/vortex/games/fractions.php |
| Construtor de Cidades | http://localhost/vortex/games/geometry.php |
| Painel Professor | http://localhost/vortex/teacher/dashboard.php |
| Relatórios | http://localhost/vortex/teacher/reports.php |
| phpMyAdmin | http://localhost/phpmyadmin |
