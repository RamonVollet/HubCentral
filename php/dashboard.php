<?php
require_once __DIR__ . '/functions.php';
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/auth_check.php';

$user = $_SESSION['user'];
$sites = load_sites();
$visible = [];

if ($user['role'] === 'admin') {
    $visible = $sites;
} else {
    $access = isset($user['access']) ? $user['access'] : [];
    foreach ($sites as $s) {
        if (in_array('*', $access) || in_array($s['folder'], $access)) {
            $visible[] = $s;
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard - HubCentral</title>
    <link id="hubFavicon" rel="icon" type="image/png" href="/hubcentral/logo_prefeitura.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
    <style>
        :root {
            --hub-accent: #4caf50;
            --hub-accent-hover: #3b8f41;
            --hub-accent-rgb: 76, 175, 80;
            --hub-banner-1: #075800;
            --hub-banner-2: #032500;
            --hub-bg-body: #1b1f24;
            --hub-bg-section: #23282f;
            --hub-bg-card: #2a2f36;
            --hub-bg-inner: #1e2329;
        }

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
        }

        header {
            background: linear-gradient(-45deg, var(--hub-banner-1), var(--hub-banner-2), var(--hub-banner-1), var(--hub-banner-2));
            background-size: 400% 400%;
            animation: gradientAnimation 12s ease infinite;
            border-radius: 10px;
            padding: 18px 20px !important;
            margin-bottom: 30px !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.6);
            color: white !important;
            position: relative;
            min-height: 76px;
        }

        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .hub-header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 2;
        }

        .hub-header-logo {
            width: 55px;
            height: 55px;
            object-fit: contain;
            display: block;
            flex: 0 0 55px;
        }

        .hub-header-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            margin: 0;
            text-align: center;
            max-width: 62%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            z-index: 1;
        }

        .hub-header-right {
            z-index: 2;
        }

        section {
            background: var(--hub-bg-section) !important;
            border-radius: 14px !important;
            box-shadow: 0 6px 18px rgba(0,0,0,0.7) !important;
            border: 1px solid rgba(var(--hub-accent-rgb), 0.35) !important;
        }

        h2 {
            color: var(--hub-accent) !important;
            font-weight: bold !important;
        }

        .hub-accent-link {
            color: var(--hub-accent) !important;
        }

        .hub-accent-link:hover {
            text-decoration: underline;
        }

        .site-card {
            background: var(--hub-bg-card) !important;
            border: 1px solid var(--hub-accent) !important;
            transition: all 0.3s ease;
        }

        .site-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(var(--hub-accent-rgb), 0.3) !important;
        }

        .site-icon-container {
            width: 48px;
            height: 48px;
            background: var(--hub-bg-inner);
            border: 2px solid var(--hub-accent);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .site-icon-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .site-icon-container i {
            color: var(--hub-accent);
        }

        .btn-open {
            background: var(--hub-accent) !important;
            color: white !important;
            font-weight: bold;
            transition: 0.2s;
        }

        .btn-open:hover {
            background: var(--hub-accent-hover) !important;
        }

        .btn-admin {
            background: #008cff !important;
            color: white !important;
            font-weight: bold;
            transition: 0.2s;
        }

        .btn-admin:hover {
            background: #0070d0 !important;
        }

        .admin-custom-panel {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            min-width: 320px;
            max-width: 560px;
            background: linear-gradient(180deg, rgba(42, 47, 54, 0.98), rgba(30, 35, 41, 0.98));
            border: 1px solid rgba(var(--hub-accent-rgb), 0.35);
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.35);
        }

        .admin-custom-title {
            font-size: 0.82rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #dbe7f6;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .custom-row {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
            background: rgba(17, 24, 39, 0.45);
            border: 1px solid #374151;
            border-radius: 10px;
            padding: 10px;
        }

        .control-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .control-label {
            font-size: 0.78rem;
            letter-spacing: 0.02em;
            color: #cbd5e1;
            font-weight: 600;
        }

        .control-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
        }

        .color-input {
            width: 46px;
            height: 34px;
            border: 1px solid #4b5563;
            border-radius: 8px;
            background: transparent;
            cursor: pointer;
            padding: 0;
        }

        .color-input::-webkit-color-swatch-wrapper {
            padding: 0;
            border-radius: 8px;
        }

        .color-input::-webkit-color-swatch {
            border: none;
            border-radius: 7px;
        }

        .logo-upload-label {
            font-size: 0.78rem;
            color: #cbd5e1;
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .logo-upload-input {
            color: #d1d5db;
            font-size: 0.8rem;
            max-width: 220px;
        }

        .btn-reset-color,
        .btn-mini {
            background: #374151;
            color: #f1f5f9;
            border-radius: 8px;
            border: 1px solid #4b5563;
            padding: 7px 11px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-reset-color:hover,
        .btn-mini:hover {
            background: #4b5563;
            border-color: #6b7280;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .admin-custom-panel {
                min-width: 100%;
            }

            .control-actions {
                margin-left: 0;
            }

            .hub-header-logo {
                width: 46px;
                height: 46px;
                flex-basis: 46px;
            }

            .hub-header-title {
                position: static;
                transform: none;
                max-width: 100%;
                width: 100%;
                margin-top: 8px;
                white-space: normal;
            }
        }
    </style>
</head>
<body class="min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
        <header class="flex items-center justify-between mb-6">
            <div class="hub-header-brand">
                <img id="hubHeaderLogo" class="hub-header-logo" src="/hubcentral/logo_prefeitura.png" alt="Logo">
            </div>
            <h1 class="hub-header-title text-2xl font-semibold">Bem-vindo, <?= htmlspecialchars($user['username']) ?>!</h1>
            <div class="hub-header-right text-sm">
                Você é <strong><?= htmlspecialchars($user['role']) ?></strong> • 
                <a href="logout.php" class="hub-accent-link">Sair</a>
            </div>
        </header>

        <section class="rounded-lg shadow p-6">
            <h2 class="text-lg font-medium mb-4">Seus sites</h2>
            
            <?php if (empty($visible)): ?>
                <div class="text-gray-400">Nenhum site disponível para sua conta.</div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($visible as $s): ?>
                        <div class="site-card border rounded-lg p-4 flex flex-col">
                            <div class="flex items-center gap-3 w-full mb-4">
                                <div class="site-icon-container">
                                    <?php if (!empty($s['icon']) && strpos($s['icon'], 'site_icons/') === 0): ?>
                                        <img src="/hubcentral/<?= htmlspecialchars($s['icon']) ?>" alt="Ícone">
                                    <?php elseif (!empty($s['icon'])): ?>
                                        <i data-lucide="<?= htmlspecialchars($s['icon']) ?>"></i>
                                    <?php else: ?>
                                        <i data-lucide="folder"></i>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-100">
                                        <?= htmlspecialchars($s['name'] ?? $s['folder']) ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        <?= htmlspecialchars($s['folder']) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-auto w-full">
                                <a class="btn-open block text-center py-2 rounded" 
                                   href="site.php?site=<?= rawurlencode($s['folder']) ?>">
                                    Abrir
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($user['role'] === 'admin'): ?>
            <section class="rounded-lg shadow p-6 mt-6">
                <h2 class="text-lg font-medium mb-4">Admin — Gerenciar usuários & sites</h2>
                <a href="admin_manage.php" class="btn-admin inline-block px-4 py-2 rounded">
                    Abrir painel de administração
                </a>
            </section>

            <section class="rounded-lg shadow p-6 mt-6">
                <h2 class="text-lg font-medium mb-4">Personalizar Dashboard</h2>
                <div class="admin-custom-panel">
                    <div class="admin-custom-title">Tema e identidade visual</div>

                    <div class="custom-row">
                        <div class="control-item">
                            <label class="control-label" for="hubBgBodyColor">Fundo</label>
                            <input class="color-input" type="color" id="hubBgBodyColor" value="#1b1f24" aria-label="Selecionar cor de fundo do dashboard">
                        </div>
                        <div class="control-item">
                            <label class="control-label" for="hubBgSectionColor">Seções</label>
                            <input class="color-input" type="color" id="hubBgSectionColor" value="#23282f" aria-label="Selecionar cor das seções do dashboard">
                        </div>
                        <div class="control-item">
                            <label class="control-label" for="hubBgCardColor">Cards</label>
                            <input class="color-input" type="color" id="hubBgCardColor" value="#2a2f36" aria-label="Selecionar cor dos cards do dashboard">
                        </div>
                        <div class="control-actions">
                            <button type="button" id="hubBackgroundReset" class="btn-mini">Resetar Cinzas</button>
                        </div>
                    </div>

                    <div class="custom-row">
                        <div class="control-item">
                            <label class="control-label" for="hubAccentPicker">Cor do Hub</label>
                            <input class="color-input" type="color" id="hubAccentPicker" value="#4caf50" aria-label="Selecionar cor do Hub">
                        </div>
                        <div class="control-actions">
                            <button type="button" id="hubAccentReset" class="btn-reset-color">Resetar Padrão</button>
                        </div>
                    </div>

                    <div class="custom-row">
                        <div class="control-item">
                            <label class="control-label" for="hubBannerColor1">Banner cor 1</label>
                            <input class="color-input" type="color" id="hubBannerColor1" value="#075800" aria-label="Selecionar primeira cor do banner">
                        </div>
                        <div class="control-item">
                            <label class="control-label" for="hubBannerColor2">Banner cor 2</label>
                            <input class="color-input" type="color" id="hubBannerColor2" value="#032500" aria-label="Selecionar segunda cor do banner">
                        </div>
                        <div class="control-actions">
                            <button type="button" id="hubBannerReset" class="btn-mini">Resetar Banner</button>
                        </div>
                    </div>

                    <div class="custom-row">
                        <div>
                            <label for="hubLogoPicker" class="logo-upload-label">Logo do topo</label>
                            <input type="file" id="hubLogoPicker" class="logo-upload-input" accept="image/*" aria-label="Enviar logo personalizada">
                        </div>
                        <div class="control-actions">
                            <button type="button" id="hubLogoReset" class="btn-mini">Resetar Logo</button>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <script>
        const HUB_ACCENT_KEY = 'hubcentral_accent_color';
        const HUB_BANNER_1_KEY = 'hubcentral_banner_color_1';
        const HUB_BANNER_2_KEY = 'hubcentral_banner_color_2';
        const HUB_LOGO_KEY = 'hubcentral_logo_dataurl';
        const HUB_BG_BODY_KEY = 'hubcentral_bg_body_color';
        const HUB_BG_SECTION_KEY = 'hubcentral_bg_section_color';
        const HUB_BG_CARD_KEY = 'hubcentral_bg_card_color';
        const HUB_DEFAULTS = {
            accent: '#4caf50',
            banner1: '#075800',
            banner2: '#032500',
            bgBody: '#1b1f24',
            bgSection: '#23282f',
            bgCard: '#2a2f36',
            logoSrc: '/hubcentral/logo_prefeitura.png'
        };

        function clampColorChannel(value) {
            return Math.max(0, Math.min(255, value));
        }

        function hexToRgb(hex) {
            const m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            if (!m) return null;
            return [parseInt(m[1], 16), parseInt(m[2], 16), parseInt(m[3], 16)];
        }

        function rgbToHex(r, g, b) {
            const toHex = v => clampColorChannel(v).toString(16).padStart(2, '0');
            return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
        }

        function darken(hex, amount) {
            const rgb = hexToRgb(hex);
            if (!rgb) return '#3b8f41';
            return rgbToHex(
                Math.round(rgb[0] * (1 - amount)),
                Math.round(rgb[1] * (1 - amount)),
                Math.round(rgb[2] * (1 - amount))
            );
        }

        function applyBackgroundTones(bodyColor, sectionColor, cardColor) {
            const root = document.documentElement;
            root.style.setProperty('--hub-bg-body', bodyColor);
            root.style.setProperty('--hub-bg-section', sectionColor);
            root.style.setProperty('--hub-bg-card', cardColor);
            root.style.setProperty('--hub-bg-inner', darken(cardColor, 0.28));
        }

        function applyHubAccent(hex) {
            const rgb = hexToRgb(hex);
            if (!rgb) return;
            const root = document.documentElement;
            root.style.setProperty('--hub-accent', hex);
            root.style.setProperty('--hub-accent-hover', darken(hex, 0.18));
            root.style.setProperty('--hub-accent-rgb', `${rgb[0]}, ${rgb[1]}, ${rgb[2]}`);
        }

        function applyBannerColors(color1, color2) {
            const root = document.documentElement;
            root.style.setProperty('--hub-banner-1', color1);
            root.style.setProperty('--hub-banner-2', color2);
        }

        function applyHeaderLogo(logoSrc) {
            const finalLogo = logoSrc || HUB_DEFAULTS.logoSrc;
            const logoEl = document.getElementById('hubHeaderLogo');
            if (logoEl) logoEl.src = finalLogo;

            const faviconEl = document.getElementById('hubFavicon');
            if (faviconEl) faviconEl.href = finalLogo;
        }

        const colorPicker = document.getElementById('hubAccentPicker');
        const colorReset = document.getElementById('hubAccentReset');
        const bannerColor1Picker = document.getElementById('hubBannerColor1');
        const bannerColor2Picker = document.getElementById('hubBannerColor2');
        const bannerReset = document.getElementById('hubBannerReset');
        const logoPicker = document.getElementById('hubLogoPicker');
        const logoReset = document.getElementById('hubLogoReset');
        const bgBodyPicker = document.getElementById('hubBgBodyColor');
        const bgSectionPicker = document.getElementById('hubBgSectionColor');
        const bgCardPicker = document.getElementById('hubBgCardColor');
        const backgroundReset = document.getElementById('hubBackgroundReset');
        const savedAccent = localStorage.getItem(HUB_ACCENT_KEY);
        const savedBanner1 = localStorage.getItem(HUB_BANNER_1_KEY);
        const savedBanner2 = localStorage.getItem(HUB_BANNER_2_KEY);
        const savedLogo = localStorage.getItem(HUB_LOGO_KEY);
        const savedBgBody = localStorage.getItem(HUB_BG_BODY_KEY);
        const savedBgSection = localStorage.getItem(HUB_BG_SECTION_KEY);
        const savedBgCard = localStorage.getItem(HUB_BG_CARD_KEY);

        if (savedAccent) {
            applyHubAccent(savedAccent);
            if (colorPicker) colorPicker.value = savedAccent;
        }

        if (savedBanner1 && savedBanner2) {
            applyBannerColors(savedBanner1, savedBanner2);
            if (bannerColor1Picker) bannerColor1Picker.value = savedBanner1;
            if (bannerColor2Picker) bannerColor2Picker.value = savedBanner2;
        }

        if (savedLogo) {
            applyHeaderLogo(savedLogo);
        }

        if (savedBgBody || savedBgSection || savedBgCard) {
            const bodyColor = savedBgBody || HUB_DEFAULTS.bgBody;
            const sectionColor = savedBgSection || HUB_DEFAULTS.bgSection;
            const cardColor = savedBgCard || HUB_DEFAULTS.bgCard;

            applyBackgroundTones(bodyColor, sectionColor, cardColor);
            if (bgBodyPicker) bgBodyPicker.value = bodyColor;
            if (bgSectionPicker) bgSectionPicker.value = sectionColor;
            if (bgCardPicker) bgCardPicker.value = cardColor;
        }

        if (colorPicker) {
            colorPicker.addEventListener('input', function() {
                const selected = this.value;
                applyHubAccent(selected);
                localStorage.setItem(HUB_ACCENT_KEY, selected);
            });
        }

        if (colorReset) {
            colorReset.addEventListener('click', function() {
                const defaultColor = HUB_DEFAULTS.accent;
                applyHubAccent(defaultColor);
                localStorage.removeItem(HUB_ACCENT_KEY);
                if (colorPicker) colorPicker.value = defaultColor;
            });
        }

        function persistBackgroundTones() {
            const bodyColor = bgBodyPicker ? bgBodyPicker.value : HUB_DEFAULTS.bgBody;
            const sectionColor = bgSectionPicker ? bgSectionPicker.value : HUB_DEFAULTS.bgSection;
            const cardColor = bgCardPicker ? bgCardPicker.value : HUB_DEFAULTS.bgCard;

            applyBackgroundTones(bodyColor, sectionColor, cardColor);
            localStorage.setItem(HUB_BG_BODY_KEY, bodyColor);
            localStorage.setItem(HUB_BG_SECTION_KEY, sectionColor);
            localStorage.setItem(HUB_BG_CARD_KEY, cardColor);
        }

        if (bgBodyPicker) bgBodyPicker.addEventListener('input', persistBackgroundTones);
        if (bgSectionPicker) bgSectionPicker.addEventListener('input', persistBackgroundTones);
        if (bgCardPicker) bgCardPicker.addEventListener('input', persistBackgroundTones);

        if (backgroundReset) {
            backgroundReset.addEventListener('click', function() {
                applyBackgroundTones(HUB_DEFAULTS.bgBody, HUB_DEFAULTS.bgSection, HUB_DEFAULTS.bgCard);
                localStorage.removeItem(HUB_BG_BODY_KEY);
                localStorage.removeItem(HUB_BG_SECTION_KEY);
                localStorage.removeItem(HUB_BG_CARD_KEY);
                if (bgBodyPicker) bgBodyPicker.value = HUB_DEFAULTS.bgBody;
                if (bgSectionPicker) bgSectionPicker.value = HUB_DEFAULTS.bgSection;
                if (bgCardPicker) bgCardPicker.value = HUB_DEFAULTS.bgCard;
            });
        }

        function persistBanner() {
            const c1 = bannerColor1Picker ? bannerColor1Picker.value : HUB_DEFAULTS.banner1;
            const c2 = bannerColor2Picker ? bannerColor2Picker.value : HUB_DEFAULTS.banner2;
            applyBannerColors(c1, c2);
            localStorage.setItem(HUB_BANNER_1_KEY, c1);
            localStorage.setItem(HUB_BANNER_2_KEY, c2);
        }

        if (bannerColor1Picker) bannerColor1Picker.addEventListener('input', persistBanner);
        if (bannerColor2Picker) bannerColor2Picker.addEventListener('input', persistBanner);

        if (bannerReset) {
            bannerReset.addEventListener('click', function() {
                applyBannerColors(HUB_DEFAULTS.banner1, HUB_DEFAULTS.banner2);
                localStorage.removeItem(HUB_BANNER_1_KEY);
                localStorage.removeItem(HUB_BANNER_2_KEY);
                if (bannerColor1Picker) bannerColor1Picker.value = HUB_DEFAULTS.banner1;
                if (bannerColor2Picker) bannerColor2Picker.value = HUB_DEFAULTS.banner2;
            });
        }

        if (logoPicker) {
            logoPicker.addEventListener('change', function(event) {
                const file = event.target.files && event.target.files[0];
                if (!file) return;
                if (!file.type.startsWith('image/')) return;

                const reader = new FileReader();
                reader.onload = function(e) {
                    const dataUrl = e.target && e.target.result;
                    if (typeof dataUrl !== 'string') return;
                    localStorage.setItem(HUB_LOGO_KEY, dataUrl);
                    applyHeaderLogo(dataUrl);
                };
                reader.readAsDataURL(file);
            });
        }

        if (logoReset) {
            logoReset.addEventListener('click', function() {
                localStorage.removeItem(HUB_LOGO_KEY);
                applyHeaderLogo(HUB_DEFAULTS.logoSrc);
                if (logoPicker) logoPicker.value = '';
            });
        }

        if (window.lucide) lucide.replace();
    </script>
</body>
</html>