# HubCentral

Portal web em PHP para centralizar o acesso a sistemas internos com autenticacao, controle de permissao por usuario e gerenciamento administrativo.

## Visao Geral

O HubCentral organiza multiplos modulos em um unico ponto de entrada. Cada usuario enxerga apenas os sistemas autorizados, enquanto administradores podem gerenciar usuarios, acessos, sites e arquivos de cada modulo.

## Principais Recursos

- Login com sessao validada no servidor
- Controle de acesso por usuario, perfil e pasta de site
- Dashboard com cards de sistemas permitidos
- Wrapper de abertura de modulo com validacao de permissao
- Painel administrativo para usuarios e sites
- Upload e troca de icones dos cards
- Gerenciamento de arquivos por site (upload, exclusao individual e em lote)
- Personalizacao visual (cor do tema, cores do banner e logo)

## Stack Tecnologica

- Frontend: HTML5, CSS3, JavaScript (ES6+), Tailwind via CDN, Lucide Icons
- Backend: PHP 7.4+ (recomendado PHP 8+)
- Persistencia: arquivos JSON locais
- Servidor: Apache (XAMPP)

## Estrutura do Projeto

```text
hubcentral/
|-- index.php
|-- README.md
|-- README.txt
|-- components/
|   |-- top_panel.php
|   |-- top_panel.css
|   `-- top_panel.js
|-- data/
|   |-- users.json
|   |-- sites.json
|   `-- sessions.json
|-- php/
|   |-- functions.php
|   |-- auth_check.php
|   |-- dashboard.php
|   |-- admin_manage.php
|   |-- site.php
|   |-- register.php
|   |-- logout.php
|   `-- site_files.php
|-- site_icons/
`-- sites/
```

## Fluxo de Funcionamento

1. Usuario acessa a tela de login em `index.php`.
2. O sistema valida credenciais e cria sessao.
3. A sessao e associada ao usuario em `data/sessions.json`.
4. O dashboard exibe apenas os sites permitidos para o usuario.
5. Ao abrir um modulo, o wrapper valida permissao antes de carregar o sistema.

## Instalacao (XAMPP)

1. Copie o projeto para `C:/xampp/htdocs/hubcentral`.
2. Garanta permissao de leitura/escrita em `data/`, `site_icons/` e `sites/`.
3. Verifique se existem os arquivos JSON iniciais em `data/`.
4. Acesse `http://localhost/hubcentral/`.

## Arquivos de Dados

- `data/users.json`: usuarios, perfis e acessos
- `data/sites.json`: cadastro de sistemas do hub
- `data/sessions.json`: mapeamento de sessoes ativas

## Seguranca

- Senhas devem ser armazenadas com hash (`password_hash`/`password_verify`).
- Validacao de sessao e permissao em paginas protegidas.
- Recomendado backup periodico de `data/*.json` e `site_icons/`.
- Em ambiente de producao, restringir acesso direto a arquivos sensiveis.

## Ambito

Projeto para uso pessoal ou interno em ambientes admistrativos
