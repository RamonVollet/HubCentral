<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/functions.php';

$site = isset($_GET['site']) ? strtolower(trim($_GET['site'])) : '';

if ($site === '' || !preg_match('/^[a-z0-9_-]+$/', $site)) {
    header('Location: /hubcentral/php/dashboard.php?site=invalid');
    exit;
}

$required_site = $site;
require_once __DIR__ . '/auth_check.php';

$sites = load_sites();
$siteName = $site;
$siteFavicon = '/hubcentral/logo_prefeitura.png';
$registered = false;

foreach ($sites as $s) {
    if (!isset($s['folder'])) continue;
    if (strtolower((string)$s['folder']) === $site) {
        $registered = true;
        if (!empty($s['name'])) {
            $siteName = (string)$s['name'];
        }

        if (!empty($s['icon']) && is_string($s['icon']) && strpos($s['icon'], 'site_icons/') === 0) {
            $iconPath = __DIR__ . '/../' . $s['icon'];
            if (file_exists($iconPath)) {
                $siteFavicon = '/hubcentral/' . ltrim($s['icon'], '/');
            }
        }
        break;
    }
}

if (!$registered) {
    header('Location: /hubcentral/php/dashboard.php?site=notfound');
    exit;
}

$siteDir = __DIR__ . '/../sites/' . $site;
if (!is_dir($siteDir)) {
    header('Location: /hubcentral/php/dashboard.php?site=notfound');
    exit;
}

if ($siteFavicon === '/hubcentral/logo_prefeitura.png') {
    $localFavicons = ['favicon.ico', 'favicon.png', 'favicon.svg', 'favicon.jpg', 'favicon.jpeg', 'apple-touch-icon.png'];
    foreach ($localFavicons as $faviconFile) {
        if (file_exists($siteDir . '/' . $faviconFile)) {
            $siteFavicon = '/hubcentral/sites/' . rawurlencode($site) . '/' . rawurlencode($faviconFile);
            break;
        }
    }
}

$entry = null;
if (file_exists($siteDir . '/index.php')) {
    $entry = '/hubcentral/sites/' . rawurlencode($site) . '/index.php';
} elseif (file_exists($siteDir . '/index.html')) {
    $entry = '/hubcentral/sites/' . rawurlencode($site) . '/index.html';
}

if ($entry === null) {
    header('Location: /hubcentral/php/dashboard.php?site=noentry');
    exit;
}

$entry .= (strpos($entry, '?') === false ? '?' : '&') . 'hub_embed=1';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($siteName) ?> - HubCentral</title>
    <link id="hubFavicon" rel="icon" type="image/png" href="<?= htmlspecialchars($siteFavicon) ?>">
    <style>
        html, body {
            margin: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #ffffff;
        }

        .hub-site-frame {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
            z-index: 1;
            background: #ffffff;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/top_panel.php'; ?>

    <iframe
        class="hub-site-frame"
        src="<?= htmlspecialchars($entry) ?>"
        title="<?= htmlspecialchars($siteName) ?>"
    ></iframe>
</body>
</html>
