<?php
require_once __DIR__ . '/functions.php';
if (!isset($_SESSION)) session_start();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $p2 = $_POST['password2'] ?? '';

    if ($u === '' || $p === '' || $p2 === '') {
        $errors[] = 'Preencha todos os campos.';
    } elseif ($p !== $p2) {
        $errors[] = 'As senhas não coincidem.';
    } elseif (username_exists($u)) {
        $errors[] = 'Nome de usuário já existe.';
    } else {
        add_user($u, $p, 'user', ['laudos']);
        $success = 'Cadastro realizado! Você já pode entrar.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cadastro - HubCentral</title>
  <link id="hubFavicon" rel="icon" type="image/png" href="/hubcentral/logo_prefeitura.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
</head>
<style>
  :root {
    --hub-accent: #4caf50;
    --hub-accent-hover: #45a049;
    --hub-accent-soft: #66bb6a;
    --hub-accent-glow: 102, 255, 153;
    --hub-accent-rgb: 76, 175, 80;
    --hub-banner-1: #075800;
    --hub-banner-2: #032500;
    --hub-bg-body: #1b1f24;
    --hub-bg-card: #2a2f36;
    --hub-bg-inner: #1e2329;
    --hub-logo-src: '/hubcentral/logo_prefeitura.png';
  }

  /* ======== FUNDO DO SISTEMA ======== */
  body {
    background-color: var(--hub-bg-body) !important;
    background-image: repeating-linear-gradient(
        45deg,
        rgba(255,255,255,0.03) 0px,
        rgba(255,255,255,0.03) 2px,
        transparent 2px,
        transparent 6px
    ) !important;
    font-family: Arial, Helvetica, sans-serif !important;
    color: #e2e8f0;
    margin: 0;
    padding: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }

  /* ======== BANNER SUPERIOR ANIMADO ======== */
  .banner {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    background: linear-gradient(-45deg, var(--hub-banner-1), var(--hub-banner-2), var(--hub-banner-1), var(--hub-banner-2));
    background-size: 400% 400%;
    animation: gradientAnimation 15s ease infinite;
    color: white;
    text-align: center;
    padding: 20px;
    font-size: 22px;
    font-weight: bold;
    box-shadow: 0 3px 6px rgba(0,0,0,0.4);
    z-index: 1000;
  }

  @keyframes gradientAnimation {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }

  /* ======== ÁREA DE CONTEÚDO (para não ficar atrás do banner) ======== */
  .content-wrapper {
    margin-top: 80px;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
  }

  /* ======== CONTAINER DE CADASTRO ======== */
  .register-container {
    background: var(--hub-bg-card) !important;
    border-radius: 12px !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.8) !important;
    border: 1px solid var(--hub-accent) !important;
    padding: 40px !important;
    width: 100%;
    max-width: 420px;
    animation: fadeIn 0.5s ease-in-out;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* ======== LOGO CENTRALIZADA ======== */
  .logo {
    text-align: center;
    margin-bottom: 15px;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .logo img {
    max-width: 120px;
    height: auto;
    display: block;
    margin: 0 auto;
  }

  /* ======== BEM-VINDO ======== */
  .welcome {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
    color: var(--hub-accent);
    text-align: center;
  }

  /* ======== TÍTULO ======== */
  h2 {
    margin-bottom: 20px;
    color: #fff;
    text-align: center;
    font-size: 20px;
  }

  /* ======== LABELS ======== */
  label {
    display: block !important;
    margin-bottom: 18px !important;
  }

  label span {
    display: block !important;
    color: #ccc !important;
    font-weight: bold !important;
    margin-bottom: 8px !important;
    font-size: 14px !important;
    text-align: left !important;
  }

  /* ======== INPUTS ======== */
  input {
    width: 100% !important;
    background: var(--hub-bg-inner) !important;
    border: 1px solid var(--hub-accent) !important;
    color: #eee !important;
    border-radius: 6px !important;
    padding: 12px 16px !important;
    font-size: 14px !important;
    transition: all 0.3s ease !important;
    box-sizing: border-box !important;
  }

  input:focus {
    border-color: var(--hub-accent-soft) !important;
    box-shadow: 0 0 6px rgba(var(--hub-accent-glow),0.5) !important;
    outline: none !important;
  }

  input::placeholder {
    color: #6b7280 !important;
  }

  /* ======== BOTÃO DE CADASTRAR ======== */
  button {
    width: 100% !important;
    background: var(--hub-accent) !important;
    border: none !important;
    color: white !important;
    font-size: 16px !important;
    font-weight: bold !important;
    border-radius: 8px !important;
    padding: 14px 24px !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3) !important;

    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    margin-bottom: 17px !important;
  }

  button:hover {
    background: var(--hub-accent-hover) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 8px rgba(0,0,0,0.4) !important;
  }

  button:active {
    transform: translateY(0) !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
  }

  /* ======== LINK DE VOLTAR AO LOGIN ======== */
  .login-link {
    color: var(--hub-accent) !important;
    text-decoration: none !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    text-align: center !important;
    display: block !important;
    font-size: 14px !important;
  }

  .login-link:hover {
    color: var(--hub-accent-soft) !important;
    text-decoration: underline !important;
  }

  /* ======== MENSAGENS DE ERRO ======== */
  .error-message {
    background: rgba(255,0,0,0.12) !important;
    border: 1px solid #ff6060 !important;
    color: #ffb4b4 !important;
    border-radius: 8px !important;
    padding: 12px 16px !important;
    margin-bottom: 20px !important;
  }

  .error-message ul {
    list-style: disc !important;
    padding-left: 20px !important;
    margin: 0 !important;
  }

  .error-message li {
    margin: 4px 0 !important;
  }

  /* ======== MENSAGENS DE SUCESSO ======== */
  .success-message {
    background: rgba(var(--hub-accent-rgb),0.15) !important;
    border: 1px solid var(--hub-accent) !important;
    color: var(--hub-accent-soft) !important;
    border-radius: 8px !important;
    padding: 12px 16px !important;
    margin-bottom: 20px !important;
    text-align: center !important;
    font-weight: 600 !important;
  }

  /* ======== RESPONSIVIDADE ======== */
  @media (max-width: 640px) {
    .banner {
      font-size: 18px;
      padding: 15px;
    }

    .content-wrapper {
      margin-top: 70px;
      padding: 20px 15px;
    }

    .register-container {
      padding: 30px 25px !important;
    }

    .welcome {
      font-size: 16px;
    }

    h2 {
      font-size: 18px;
    }

    .logo img {
      max-width: 100px;
    }
  }
</style>
<body>
  <!-- Banner Superior -->
  <div class="banner">Cadastro - HubCentral</div>

  <!-- Área de Conteúdo -->
  <div class="content-wrapper">
    <div class="register-container">
      <div class="logo">
        <img id="hubMainLogo" src="/hubcentral/logo_prefeitura.png" alt="Logo da Prefeitura">
      </div>

      <div class="welcome">Criar sua conta</div>
      <h2>Preencha os dados abaixo</h2>

      <?php if ($errors): ?>
        <div class="error-message">
          <ul>
            <?php foreach ($errors as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="success-message">
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <form method="post" action="">
        <label>
          <span>Usuário</span>
          <input name="username" required placeholder="Digite seu usuário" />
        </label>
        
        <label>
          <span>Senha</span>
          <input type="password" name="password" required placeholder="Digite sua senha" />
        </label>
        
        <label>
          <span>Repita a senha</span>
          <input type="password" name="password2" required placeholder="Confirme sua senha" />
        </label>
        
        <button type="submit">
          <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i>
          Cadastrar
        </button>

        <a class="login-link" href="/hubcentral/index.php">Voltar ao login</a>
      </form>
    </div>
  </div>

  <script>
    (function applyHubAccentTheme() {
      const key = 'hubcentral_accent_color';
      const saved = localStorage.getItem(key);
      if (!saved) return;

      const m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(saved);
      if (!m) return;

      const r = parseInt(m[1], 16);
      const g = parseInt(m[2], 16);
      const b = parseInt(m[3], 16);
      const dark = (v) => Math.max(0, Math.min(255, Math.round(v * 0.85)));
      const soft = (v) => Math.max(0, Math.min(255, Math.round(v + (255 - v) * 0.22)));

      const root = document.documentElement;
      root.style.setProperty('--hub-accent', `#${m[1]}${m[2]}${m[3]}`);
      root.style.setProperty('--hub-accent-hover', `rgb(${dark(r)}, ${dark(g)}, ${dark(b)})`);
      root.style.setProperty('--hub-accent-soft', `rgb(${soft(r)}, ${soft(g)}, ${soft(b)})`);
      root.style.setProperty('--hub-accent-glow', `${r}, ${g}, ${b}`);
      root.style.setProperty('--hub-accent-rgb', `${r}, ${g}, ${b}`);
    })();

    (function applyHubBannerAndLogoTheme() {
      const banner1 = localStorage.getItem('hubcentral_banner_color_1');
      const banner2 = localStorage.getItem('hubcentral_banner_color_2');
      const logoData = localStorage.getItem('hubcentral_logo_dataurl');
      const defaultLogo = '/hubcentral/logo_prefeitura.png';

      const root = document.documentElement;
      if (banner1) root.style.setProperty('--hub-banner-1', banner1);
      if (banner2) root.style.setProperty('--hub-banner-2', banner2);

      const logoEl = document.getElementById('hubMainLogo');
      if (logoEl && logoData) {
        logoEl.src = logoData;
      }

      const faviconEl = document.getElementById('hubFavicon');
      if (faviconEl) {
        faviconEl.href = logoData || defaultLogo;
      }
    })();

    (function applyHubBackgroundTheme() {
      const bodyColor = localStorage.getItem('hubcentral_bg_body_color');
      const cardColor = localStorage.getItem('hubcentral_bg_card_color');

      const root = document.documentElement;
      if (bodyColor) root.style.setProperty('--hub-bg-body', bodyColor);
      if (cardColor) {
        root.style.setProperty('--hub-bg-card', cardColor);

        const m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(cardColor);
        if (m) {
          const clamp = (v) => Math.max(0, Math.min(255, v));
          const r = clamp(Math.round(parseInt(m[1], 16) * 0.72));
          const g = clamp(Math.round(parseInt(m[2], 16) * 0.72));
          const b = clamp(Math.round(parseInt(m[3], 16) * 0.72));
          root.style.setProperty('--hub-bg-inner', `rgb(${r}, ${g}, ${b})`);
        }
      }
    })();

    if (window.lucide) lucide.createIcons();
  </script>
</body>
</html>