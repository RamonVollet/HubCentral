<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/../php/auth_check.php';

// Quando a página estiver sendo exibida dentro do wrapper central,
// este componente não precisa renderizar novamente.
if (isset($_GET['hub_embed']) && $_GET['hub_embed'] === '1') {
  return;
}

$userName = htmlspecialchars($_SESSION['user']['username']);
?>

<link rel="stylesheet" href="/hubcentral/components/top_panel.css">

<div class="hub-panel">
  <button
    class="hub-panel-btn"
    id="hubPanelToggle"
    aria-label="Painel do Hub"
  >
    ☰
  </button>

  <div class="hub-panel-card" id="hubPanelCard">
    <p class="hub-panel-user">
      Seja bem-vindo(a), <strong><?= $userName ?></strong> 👤
    </p>

    <a href="/hubcentral/php/dashboard.php" class="hub-panel-link">
      Voltar ao Dashboard
    </a>
  </div>
</div>

<script src="/hubcentral/components/top_panel.js"></script>
