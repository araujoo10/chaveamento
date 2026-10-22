# Chaveamento Esportivo Automático (BracketPro)

Sistema web completo para organização, geração de chaveamento automático e gerenciamento de competições esportivas, desenvolvido estritamente de acordo com as especificações descritas em `requisitos.md` e referenciado na interface do [BracketMaker](https://bracketmaker.app/pt/).

O foco principal da plataforma é **garantir a justiça desportiva**, evitando a manipulação de tabelas, placares e resultados através de regras rígidas de avanço automático e um sistema completo de **auditoria e logs imutáveis**.

---

## 🛠️ Stack Tecnológico Utilizado

- **Backend:** PHP estruturado e modularizado com sessões nativas (`$_SESSION`, `session_start`).
- **Banco de Dados:** MySQL com conexões e queries 100% preparadas via **PDO** (prevenção contra SQL Injection), com auto-detecção e fallback transparente para SQLite caso o servidor MySQL local esteja temporariamente inativo.
- **Frontend:** HTML5 semântico, PHP, CSS3 moderno com tema escuro e **Tailwind CSS**.
- **Design & UX:** Interface responsiva desenhada sob o conceito **Mobile-First**, com navegação por gestos de arrasto (pan) e zoom na visualização da chave.
- **Componentes:**
  - **TOASTS:** Sistema de mensagens e notificações flutuantes para sucessos, alertas e erros (sem alertas nativos do navegador).
  - **MODAIS:** Modais customizados para confirmação de ações críticas (finalização, exclusão, reabertura) e formulário interativo de lançamento de placares com desempate por pênaltis.

---

## 📋 Regras de Negócio Implementadas (CORE)

1. **Criptografia e Segurança de Usuários:**
   - Senhas criptografadas usando o algoritmo nativo **Bcrypt** (`PASSWORD_BCRYPT`).
   - Perfis de acesso diferenciados: **Administrador** (permissão total para lançar/ajustar placares e reabrir/excluir competições) e **Organizador**.

2. **Fluxo do Chaveamento:**
   - **Inserir Informações:** Cadastro do nome da competição, modalidade esportiva, regulamento, método de sorteio e inclusão de disputa de 3º lugar.
   - **Inserção de Equipes:** Aceita lista flexível linha por linha, com botões de exemplos rápidos (8 e 16 equipes) e contagem automática.
   - **Validação de Informações:** Validação de no mínimo 2 participantes, nomes não duplicados e distribuição matemática de confrontos.
   - **Gerar Chaveamento:** Suporta potências de 2 (4, 8, 16, 32, 64) e quantidades fracionadas através de **BYEs (isenção de rodada)** onde os melhores classificados avançam direto para a segunda fase.
   - **Exibir Resultado (Bracket Interativo):** Visualização em árvore com cards de partidas, conectores gráficos, sementes, nomes das equipes, placares e indicadores de classificados. Possibilidade de alternar entre "Chave Gráfica" e "Lista de Partidas".
   - **Permitir Ajustes:** O administrador pode lançar e editar placares das partidas em andamento. Em caso de empate no tempo regulamentar, o sistema solicita obrigatoriamente a pontuação de desempate / pênaltis.
   - **Avanço Automático:** Ao definir o vencedor de um confronto, o classificado avança automaticamente para o slot correto da próxima rodada. Em semifinais com disputa de bronze, os perdedores são encaminhados para a partida de 3º lugar.
   - **Finalizar Competição:** Consolidar o campeão, vice-campeão e 3º colocado em um pódio oficial, bloqueando alterações indevidas.

3. **Página de Históricos e Trilha de Auditoria:**
   - Registro de qualquer alteração no sistema (criação de torneio, geração de chaves, alteração de placares com placar anterior vs novo, tentativas de login, finalização e exclusão).
   - Armazenamento de: Usuário responsável, Endereço IP, Data/Hora, Torneio vinculado, Ação e Diff com dados JSON anteriores e novos.
   - Filtros por torneio, tipo de ação e busca textual.

---

## 📁 Estrutura de Arquivos e Separação Lógica

Seguindo a regra de não construir sistemas monolíticos, o código foi separado de maneira limpa e modular:

```text
chaveamento-automatico/
├── config/
│   ├── bd.php                # Conexão PDO segura com MySQL e auto-criação de schema
│   └── config.php            # Configurações globais, timezone e sessões
├── backend/
│   ├── auth.php              # Login, registro com bcrypt, logout e autenticação
│   ├── chaveamento.php       # Motor matemático de brackets e distribuição de BYEs
│   ├── helpers.php           # Funções auxiliares, sanitização, toasts e formatação
│   ├── logs.php              # Registro e consulta da trilha de auditoria
│   ├── partidas.php          # Lançamento de placares, desempates e avanço de fase
│   └── torneios.php          # CRUD de competições e finalização de campeonatos
├── includes/
│   ├── header.php            # Cabeçalho HTML, Tailwind CDN e meta tags
│   ├── navbar.php            # Barra de navegação responsiva mobile-first
│   ├── footer.php            # Rodapé do sistema
│   ├── modal.php             # Modais customizados (Confirmações e Lançamento de Placar)
│   └── toast.php             # Container e renderizador de notificações Toast
├── assets/
│   ├── css/
│   │   └── custom.css        # Estilos da árvore de chaveamento e conectores
│   └── js/
│       ├── main.js           # Funções globais de toasts, modais e menu mobile
│       └── bracket.js        # Interatividade da chave (pan, zoom, alternância e AJAX)
├── tests/
│   └── test_flow.php         # Teste automatizado do ciclo de vida do torneio
├── database.sql              # Script SQL para criação manual no MySQL/phpMyAdmin
├── index.php                 # Página inicial com métricas e torneios recentes
├── login.php                 # Tela de login com bcrypt
├── registro.php              # Cadastro de novos usuários
├── novo_torneio.php          # Wizard para criação de torneio e cadastro de equipes
├── torneio.php               # Visualização da chave gráfica (Bracket) e partidas
├── torneios.php              # Listagem e filtros de competições
├── historico.php             # Trilha de auditoria e logs detalhados
├── salvar_placar.php         # Endpoint AJAX para atualização de resultados
├── finalizar_torneio.php     # Endpoint para encerramento de competição
├── reabrir_torneio.php       # Endpoint para reabertura por administradores
├── excluir_torneio.php       # Endpoint para exclusão com confirmação
└── seed.php                  # Populador de dados de demonstração
```

---

## 🚀 Como Executar o Projeto

### Opção 1: Servidor Embutido do PHP (Execução Instantânea)

Como o sistema possui auto-configuração com PDO, você pode rodá-lo imediatamente em qualquer terminal:

```powershell
# Acesse a pasta do projeto
cd "C:\Users\Etec Esportes\.gemini\antigravity\scratch\chaveamento-automatico"

# Inicie o servidor PHP
php -S localhost:8000
```

Abra no navegador: **`http://localhost:8000`**

---

### Opção 2: XAMPP / WampServer / MySQL Local

1. Abra o **XAMPP Control Panel** e inicie o **Apache** e o **MySQL**.
2. No phpMyAdmin ou no terminal do MySQL, execute o arquivo:
   ```bash
   mysql -u root < database.sql
   ```
   *(Caso não execute manualmente, o próprio arquivo `config/bd.php` se conecta e cria o banco `chaveamento_db` e todas as tabelas automaticamente).*
3. Copie a pasta `chaveamento-automatico` para dentro do diretório `htdocs` do seu XAMPP (`C:\xampp\htdocs\chaveamento-automatico`).
4. Acesse: **`http://localhost/chaveamento-automatico`**

---

## 🔑 Credenciais Padrão de Demonstração

O sistema já vem pré-configurado com uma conta de Administrador Geral:

- **E-mail:** `admin@etec.sp.gov.br`
- **Senha:** `admin123`
*(A tela de login possui inclusive um botão de preenchimento automático para agilizar os testes).*
