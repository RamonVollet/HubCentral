================================================================================
    HUBCENTRAL - PORTAL DE SISTEMAS INTERNOS
================================================================================

Sistema web desenvolvido para centralizar o acesso a multiplos sistemas internos
com autenticacao, controle de permissao por usuario e painel administrativo.

Projeto mantido para uso interno da Prefeitura Municipal de Boa Esperanca do Sul.

Versao: 5.5

================================================================================
📋 SOBRE O PROJETO
================================================================================

O HubCentral e uma aplicacao web em PHP para organizar o acesso a diversos
modulos/sistemas em um unico portal.

A aplicacao oferece:
- Login com sessao validada no servidor
- Dashboard com exibicao de sites permitidos por usuario
- Controle de acesso por perfil e por pasta de site
- Painel administrativo para gerenciar usuarios e sites
- Upload de icones para os cards de sites
- Personalizacao visual (cor do tema, cores do banner e logo)

================================================================================
🎯 FUNCIONALIDADES PRINCIPAIS
================================================================================

PARA USUARIOS COMUNS:
- Login no portal
- Visualizacao apenas dos sites autorizados
- Abertura de sistemas por card no dashboard
- Logout seguro
- Navegacao para os sistemas por wrapper (iframe)
- Visualizacao do topo padronizado com retorno ao dashboard

PARA ADMINISTRADORES:
- Todos os acessos de usuario comum, mais:
- Cadastro, edicao e exclusao de usuarios
- Alteracao de senha de usuarios
- Definicao de acessos por site para cada usuario
- Cadastro, edicao e remocao de sites do hub
- Validacao de pasta duplicada ao cadastrar/editar site
- Upload/troca de icones dos sites
- Gerenciador de arquivos por site (upload, exclusao individual e em lote)
- Limpeza automatica de subpastas vazias apos exclusao de arquivos
- Exclusao da pasta fisica do site ao remover do hub
- Personalizacao visual global do dashboard:
  * Cor principal do tema
  * Cores do banner superior
  * Logo customizada no topo

================================================================================
🗂️ ESTRUTURA DO PROJETO
================================================================================

hubcentral/
|
|-- index.php                     -> Tela de login
|-- logo_prefeitura.png           -> Logo padrao do sistema
|-- REF.txt                       -> Arquivo de referencia do projeto
|-- README.txt                    -> Este arquivo
|
|-- php/
|   |-- functions.php             -> Funcoes centrais (usuarios/sites/sessoes)
|   |-- auth_check.php            -> Validacao de login e permissao
|   |-- dashboard.php             -> Painel principal do hub
|   |-- admin_manage.php          -> Painel administrativo
|   |-- site.php                  -> Wrapper para abrir sistemas
|   |-- register.php              -> Cadastro de usuario
|   |-- logout.php                -> Encerramento de sessao
|   |-- site_files.php            -> Endpoint AJAX para gerenciamento de arquivos por site
|   `-- contexto.txt              -> Descricao interna dos arquivos PHP
|
|-- components/
|   |-- top_panel.php             -> Painel superior reutilizavel
|   |-- top_panel.css             -> Estilos do painel superior
|   `-- top_panel.js              -> Comportamento do painel superior
|
|-- data/
|   |-- users.json                -> Base de usuarios
|   |-- sites.json                -> Base de sites do hub
|   `-- sessions.json             -> Controle de sessao por usuario
|
|-- site_icons/                   -> Icones enviados via admin
`-- sites/                        -> Sistemas/modulos integrados ao hub

================================================================================
💾 BANCO DE DADOS (JSON)
================================================================================

USERS.JSON:
Armazena os usuarios e permissoes de acesso.
[
  {
    "username": "admin",
    "password": "<hash>",
    "role": "admin",
    "access": ["*"]
  },
  {
    "username": "usuario1",
    "password": "<hash>",
    "role": "user",
    "access": ["laudos", "inventario"]
  }
]

SITES.JSON:
Armazena os sistemas exibidos no dashboard.
[
  {
    "name": "Laudos",
    "icon": "file-text",
    "folder": "laudos",
    "roles": ["admin", "user"]
  },
  {
    "name": "Inventario",
    "icon": "monitor",
    "folder": "inventario",
    "roles": ["admin", "user"]
  }
]

SESSIONS.JSON:
Mapeia usuario para session_id valida.
{
  "admin": "abc123...",
  "usuario1": "def456..."
}

================================================================================
🛠️ TECNOLOGIAS UTILIZADAS
================================================================================

FRONTEND:
- HTML5
- CSS3
- JavaScript (ES6+)
- Tailwind CSS (via CDN)
- Lucide Icons (via CDN)

BACKEND:
- PHP 7.4+ (recomendado PHP 8+)
- JSON como armazenamento local

SERVIDOR:
- Apache (XAMPP)

================================================================================
🔐 COMO O SISTEMA FUNCIONA (FLUXO)
================================================================================

FLUXO DE AUTENTICACAO:
1. Usuario acessa index.php
2. Sistema valida usuario e senha
3. Sessao PHP e criada
4. Session_id e salvo em data/sessions.json
5. Usuario e redirecionado para php/dashboard.php

FLUXO DE AUTORIZACAO:
1. Paginas protegidas incluem php/auth_check.php
2. auth_check.php valida:
   - usuario logado
   - session_id valido
   - permissao para o site requisitado
3. Em caso de falha, redireciona para login/dashboard

FLUXO DE ABERTURA DE SISTEMAS:
1. Dashboard lista os sites permitidos
2. Usuario clica em Abrir
3. Requisicao vai para php/site.php?site={folder}
4. Wrapper valida permissao e carrega o sistema em iframe

FLUXO ADMINISTRATIVO:
1. Admin acessa php/admin_manage.php
2. Gerencia usuarios, acessos e sites
3. Alteracoes sao salvas nos arquivos JSON
4. Ao excluir site, registro e pasta fisica podem ser removidos

================================================================================
🎨 CARACTERÍSTICAS DE INTERFACE
================================================================================

No dashboard (area admin), e possivel personalizar:
- Cor principal do tema
- Duas cores do banner superior
- Logo do topo

As preferencias ficam no navegador (localStorage):
- hubcentral_accent_color
- hubcentral_banner_color_1
- hubcentral_banner_color_2
- hubcentral_logo_dataurl

Essas configuracoes sao reaproveitadas em:
- index.php
- php/register.php
- php/dashboard.php
- php/admin_manage.php

================================================================================
📱 RESPONSIVIDADE
================================================================================

DESKTOP:
- Layout completo com cards e controles lado a lado

TABLET:
- Ajuste de espacamento e colunas intermediarias

MOBILE:
- Cards empilhados
- Controles de personalizacao refluem para largura total
- Titulos de banner adaptam para melhor leitura

================================================================================
⚙️ REQUISITOS DO SISTEMA
================================================================================

SERVIDOR:
- Apache/Nginx com PHP habilitado
- PHP 7.4 ou superior
- Permissao de leitura/escrita na pasta data/
- Permissao de leitura/escrita na pasta site_icons/

CLIENTE:
- Navegador moderno (Chrome, Firefox, Edge, Safari)
- JavaScript habilitado

================================================================================
🚀 INSTALAÇÃO E CONFIGURAÇÃO
================================================================================

1. Copie o projeto para o diretorio web:
   Exemplo: C:\xampp\htdocs\hubcentral\

2. Verifique permissoes de escrita:
   - data/
   - site_icons/
   - sites/ (quando houver criacao/remocao de pastas)

3. Garanta os arquivos JSON iniciais em data/:
   - users.json
   - sites.json
   - sessions.json

4. Acesse no navegador:
   http://localhost/hubcentral/

5. Entre com um usuario valido e acesse o dashboard.

================================================================================
📝 NOTAS IMPORTANTES
================================================================================

SEGURANCA:
- Senhas sao armazenadas com hash (password_hash)
- Sessao e validada por session_id persistido
- Recomenda-se proteger pastas sensiveis em producao

BACKUP:
- Fazer backup periodico de data/*.json
- Fazer backup de site_icons/ se houver icones personalizados

OPERACAO:
- O painel admin usa padrao PRG (Post/Redirect/Get)
- Evita duplicacao de operacoes ao atualizar a pagina (F5)
- Ao excluir arquivos no gerenciador, subpastas vazias sao removidas automaticamente
- Exclusao em lote de arquivos disponivel via selecao multipla com checkbox

================================================================================
🔄 HISTORICO DE ATUALIZAÇÕES
================================================================================

v5.5 (12/03/2026):
- Validacao de pasta duplicada ao criar ou editar site (frontend + backend)
- Limpeza automatica de subpastas vazias ao excluir arquivos no gerenciador
- Selecao multipla de arquivos com checkbox no gerenciador de arquivos
- Botao "Excluir Selecionados" com contador de itens marcados
- Checkbox "Selecionar todos" com estado intermediario (indeterminate)
- Endpoint backend delete_many para exclusao em lote segura
- Refatoracao da validacao de caminho (sf_resolve_site_file) reutilizada em
  exclusao individual e em lote

v5.2:
- Gerenciador de arquivos por site (upload via drag-and-drop, botao de pasta)
- Upload de pasta com estrutura preservada (webkitRelativePath)
- Barra de progresso de upload
- Exclusao de arquivos individuais no servidor
- Listagem recursiva de arquivos do site

================================================================================
🔄 POSSIVEIS PATCHS DE ATUALIZAÇÕES FUTURAS
================================================================================

CURTO PRAZO:
- Exportacao de usuarios e acessos
- Auditoria basica de alteracoes no painel admin
- Selecao por faixa (Shift+clique) no gerenciador de arquivos

MEDIO PRAZO:
- Persistencia de tema no servidor por usuario
- Logs de seguranca e tentativas de login
- Paginacao e filtros avancados no painel admin

LONGO PRAZO:
- Migracao para banco relacional (MySQL/PostgreSQL)
- API REST para integracao externa
- SSO e integracao com diretorio corporativo


================================================================================
🔍 TROUBLESHOOTING
================================================================================

PROBLEMA: Login falha mesmo com usuario correto
SOLUCAO: Verifique users.json e se o hash da senha esta valido.

PROBLEMA: Acesso negado a um site
SOLUCAO: Verifique access do usuario em users.json e folder em sites.json.

PROBLEMA: Site nao abre no wrapper
SOLUCAO: Confirme se existe sites/{folder}/index.php ou index.html.

PROBLEMA: Logo customizada nao aparece
SOLUCAO: Limpe o localStorage ou use o botao de reset de logo no dashboard.

PROBLEMA: Alteracoes de admin nao persistem
SOLUCAO: Verifique permissao de escrita em data/ e site_icons/.

================================================================================
📄 LICENÇA
================================================================================

Este projeto é open source e pode ser baixado, utilizado e modificado por
qualquer pessoa, para estudos e afins, desde que os créditos dos autores
sejam mantidos.

O software é fornecido "como está", sem garantias de funcionamento ou
suporte oficial.

================================================================================
📞 SUPORTE E CONTRIBUIÇÕES
================================================================================

Para relatar bugs, sugestões ou contribuições:
- Abra uma issue no repositório do projeto
- Descreva o problema/sugestão detalhadamente
- Inclua prints ou exemplos se possível

Agradeço por usar meu sistema!
================================================================================
👾 produzido e disponibilizado por Ramon Buzutti Vollet 👾
================================================================================