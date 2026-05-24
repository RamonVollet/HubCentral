<?php
//admin_manage:
require_once __DIR__ . '/functions.php';
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/auth_check.php';
if ($_SESSION['user']['role'] !== 'admin') { header('Location: dashboard.php'); exit; }

$message = '';
$errors = [];

// Recupera mensagens de feedback após redirecionamento (PRG)
if (isset($_SESSION['flash_message'])) {
  $message = (string)$_SESSION['flash_message'];
  unset($_SESSION['flash_message']);
}
if (isset($_SESSION['flash_errors']) && is_array($_SESSION['flash_errors'])) {
  $errors = $_SESSION['flash_errors'];
  unset($_SESSION['flash_errors']);
}

// Criar diretório para ícones se não existir
$icon_dir = __DIR__ . '/../site_icons';
if (!is_dir($icon_dir)) {
    mkdir($icon_dir, 0755, true);
}

// Função para fazer upload de imagem
function upload_site_icon($file) {
    global $icon_dir, $errors;
    
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // Nenhum arquivo enviado
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Erro ao fazer upload da imagem.';
        return null;
    }
    
    // Validar tipo de arquivo
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        $errors[] = 'Tipo de arquivo não permitido. Use: JPG, PNG, GIF, WEBP ou SVG.';
        return null;
    }
    
    // Validar tamanho (máximo 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        $errors[] = 'Imagem muito grande. Máximo: 2MB.';
        return null;
    }
    
    // Gerar nome único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('icon_') . '.' . $extension;
    $filepath = $icon_dir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'site_icons/' . $filename;
    } else {
        $errors[] = 'Erro ao salvar a imagem.';
        return null;
    }
}

function normalize_relative_upload_path($path) {
  $normalized = str_replace('\\', '/', (string)$path);
  $normalized = trim($normalized);
  $normalized = trim($normalized, '/');

  if ($normalized === '') return null;

  $parts = explode('/', $normalized);
  $safe = [];

  foreach ($parts as $part) {
    if ($part === '' || $part === '.') continue;
    if ($part === '..') return null;
    if (preg_match('/[\x00-\x1F\x7F]/', $part)) return null;
    $safe[] = $part;
  }

  if (empty($safe)) return null;

  return implode('/', $safe);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---------------- Users: delete user ----------------
    if (isset($_POST['user_action']) && $_POST['user_action'] === 'delete_user') {
        $target = trim($_POST['delete_username'] ?? '');
        if ($target && $target !== 'admin') {
            remove_user($target);
            $message = 'Usuário "' . htmlspecialchars($target) . '" foi removido.';
        } else {
            $errors[] = 'Usuário inválido para exclusão.';
        }
    }

    // ---------------- Sites: add / edit / delete ----------------
    if (isset($_POST['site_action'])) {
        $site_action = $_POST['site_action'];
        
        if ($site_action === 'add') {
            $name = trim($_POST['site_name'] ?? '');
            $icon = trim($_POST['site_icon'] ?? '');
          $folder = strtolower(trim($_POST['site_folder'] ?? ''));
            
            // Upload de imagem se houver
            if (isset($_FILES['site_icon_upload']) && $_FILES['site_icon_upload']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploaded_icon = upload_site_icon($_FILES['site_icon_upload']);
                if ($uploaded_icon) {
                    $icon = $uploaded_icon;
                }
            }
            
            if ($name && $folder) {
                $path = __DIR__ . '/../sites/' . $folder;
              $folderAlreadyInJson = false;
              foreach (load_sites() as $existingSite) {
                if (($existingSite['folder'] ?? '') === $folder) {
                  $folderAlreadyInJson = true;
                  break;
                }
              }

              if ($folderAlreadyInJson || is_dir($path)) {
                $errors[] = 'Já existe um site com esse nome de pasta. Escolha outro.';
              } else {
                if (!is_dir($path) && !mkdir($path, 0755, true)) {
                  $errors[] = 'Não foi possível criar a pasta do site.';
                } elseif (add_site($name, $icon, $folder)) {
                  $message = 'Site adicionado.';
                } else {
                  $errors[] = 'Já existe um site com esse nome de pasta. Escolha outro.';
                }
              }
            } else {
                $errors[] = 'Nome e pasta são obrigatórios para adicionar site.';
            }
            
        } elseif ($site_action === 'upload_folder') {
          $folder = strtolower(trim($_POST['site_folder'] ?? ''));
          $siteExists = false;
          foreach (load_sites() as $siteItem) {
            if (($siteItem['folder'] ?? '') === $folder) {
              $siteExists = true;
              break;
            }
          }

          if ($folder === '') {
            $errors[] = 'Pasta do site inválida para upload.';
          } elseif (!$siteExists) {
            $errors[] = 'Site não encontrado para receber upload.';
          } elseif (!isset($_FILES['site_folder_files'])) {
            $errors[] = 'Nenhum arquivo foi recebido para upload.';
          } else {
            $targetRoot = __DIR__ . '/../sites/' . $folder;
            if (!is_dir($targetRoot)) {
              mkdir($targetRoot, 0755, true);
            }

            $files = $_FILES['site_folder_files'];
            $names = $files['name'] ?? [];
            $tmpNames = $files['tmp_name'] ?? [];
            $errorsList = $files['error'] ?? [];

            if (!is_array($names) || !is_array($tmpNames) || !is_array($errorsList)) {
              $errors[] = 'Formato de upload inválido.';
            } else {
              $queue = [];

              for ($i = 0; $i < count($names); $i++) {
                if (($errorsList[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                  continue;
                }

                $relative = normalize_relative_upload_path($names[$i] ?? '');
                if ($relative === null) {
                  continue;
                }

                $queue[] = [
                  'relative' => $relative,
                  'tmp' => $tmpNames[$i] ?? '',
                  'error' => $errorsList[$i] ?? UPLOAD_ERR_NO_FILE,
                ];
              }

              if (empty($queue)) {
                $errors[] = 'Nenhum arquivo válido foi selecionado para upload.';
              } else {
                $firstSegment = null;
                $stripFirstSegment = true;

                foreach ($queue as $item) {
                  if (strpos($item['relative'], '/') === false) {
                    $stripFirstSegment = false;
                    break;
                  }

                  $segments = explode('/', $item['relative'], 2);
                  if ($segments[0] === '') {
                    $stripFirstSegment = false;
                    break;
                  }

                  if ($firstSegment === null) {
                    $firstSegment = $segments[0];
                  } elseif ($firstSegment !== $segments[0]) {
                    $stripFirstSegment = false;
                    break;
                  }
                }

                $uploadedCount = 0;
                $failedCount = 0;

                foreach ($queue as $item) {
                  if ($item['error'] !== UPLOAD_ERR_OK) {
                    $failedCount++;
                    continue;
                  }

                  $relativePath = $item['relative'];
                  if (
                    $stripFirstSegment &&
                    $firstSegment !== null &&
                    substr($relativePath, 0, strlen($firstSegment) + 1) === ($firstSegment . '/')
                  ) {
                    $relativePath = substr($relativePath, strlen($firstSegment) + 1);
                  }

                  if ($relativePath === '' || $relativePath === false) {
                    $failedCount++;
                    continue;
                  }

                  $destination = $targetRoot . '/' . $relativePath;
                  $destinationDir = dirname($destination);

                  if (!is_dir($destinationDir) && !mkdir($destinationDir, 0755, true)) {
                    $failedCount++;
                    continue;
                  }

                  if (move_uploaded_file($item['tmp'], $destination)) {
                    $uploadedCount++;
                  } else {
                    $failedCount++;
                  }
                }

                if ($uploadedCount > 0) {
                  $message = "Upload concluído para '{$folder}': {$uploadedCount} arquivo(s) enviado(s).";
                }
                if ($failedCount > 0) {
                  $errors[] = "{$failedCount} arquivo(s) não puderam ser enviados.";
                }
                if ($uploadedCount === 0 && $failedCount === 0) {
                  $errors[] = 'Nenhum arquivo foi processado no upload.';
                }
              }
            }
          }

        } elseif ($site_action === 'delete') {
            $folder = $_POST['site_folder'] ?? '';
            if ($folder) {
                // Buscar o site para deletar a imagem se for upload
                $sites = load_sites();
                foreach ($sites as $s) {
                    if ($s['folder'] === $folder && strpos($s['icon'], 'site_icons/') === 0) {
                        $icon_path = __DIR__ . '/../' . $s['icon'];
                        if (file_exists($icon_path)) {
                            unlink($icon_path);
                        }
                    }
                }
                delete_site($folder);
                $message = 'Site removido da lista e pasta apagada.';
            } else {
                $errors[] = 'Pasta inválida para remoção.';
            }
            
        } elseif ($site_action === 'edit') {
            $folder = $_POST['site_folder'] ?? '';
            $name = $_POST['site_name'] ?? '';
            $icon = $_POST['site_icon'] ?? '';
            $newFolder = trim($_POST['new_folder'] ?? '');
            
            // Upload de nova imagem se houver
            if (isset($_FILES['site_icon_upload_' . $folder]) && $_FILES['site_icon_upload_' . $folder]['error'] !== UPLOAD_ERR_NO_FILE) {
                // Deletar imagem antiga se for upload
                if (strpos($icon, 'site_icons/') === 0) {
                    $old_icon_path = __DIR__ . '/../' . $icon;
                    if (file_exists($old_icon_path)) {
                        unlink($old_icon_path);
                    }
                }
                
                $uploaded_icon = upload_site_icon($_FILES['site_icon_upload_' . $folder]);
                if ($uploaded_icon) {
                    $icon = $uploaded_icon;
                }
            }
            
            if ($folder && $name) {
              if (update_site($folder, $name, $icon, $newFolder)) {
                $message = 'Site atualizado.';
              } else {
                $errors[] = 'Não foi possível atualizar. A pasta informada já existe.';
              }
            } else {
                $errors[] = 'Nome/pasta inválidos para edição.';
            }
        }
    }

    // ---------------- Users: admin changes other user's password ----------------
    if (isset($_POST['user_action']) && $_POST['user_action'] === 'change_pwd_by_admin') {
        $target = trim($_POST['target_user'] ?? '');
        $newpwd = trim($_POST['new_password_user'] ?? '');
        if ($target && $newpwd) {
            update_user_password($target, $newpwd);
            clear_user_session_id($target);
            $message = 'Senha do usuário ' . htmlspecialchars($target) . ' alterada.';
        } else {
            $errors[] = 'Selecione um usuário e informe a nova senha.';
        }
    }

    // ---------------- Users: admin changes own password ----------------
    if (isset($_POST['user_action']) && $_POST['user_action'] === 'change_my_pwd') {
        $newpwd = trim($_POST['new_password_my'] ?? '');
        if ($newpwd) {
            update_user_password($_SESSION['user']['username'], $newpwd);
            set_user_session_id($_SESSION['user']['username'], session_id());
            $message = 'Sua senha foi alterada.';
        } else {
            $errors[] = 'Informe uma nova senha válida.';
        }
    }

    // ---------------- Access: update user's access list ----------------
    if (isset($_POST['access_action']) && $_POST['access_action'] === 'save_access') {
        $uname = trim($_POST['access_username'] ?? '');
        $access = $_POST['access'] ?? [];
        if ($uname !== '') {
            if (!is_array($access)) $access = [];
            update_user_access($uname, $access);
            clear_user_session_id($uname);
            $message = 'Acessos atualizados para ' . htmlspecialchars($uname);
        } else {
            $errors[] = 'Usuário inválido ao salvar acessos.';
        }
    }

    // Evita reenvio de formulário no F5 (Post/Redirect/Get)
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_errors'] = $errors;
    header('Location: admin_manage.php');
    exit;
}

// Load current data
$sites = load_sites();
$users = load_users();

// Pagination setup for users
$per_page = 10;
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$total_users = count($users);
$total_pages = max(1, (int)ceil($total_users / $per_page));
$start = ($page - 1) * $per_page;
$users_paged = array_slice($users, $start, $per_page);

?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Painel Admin - HubCentral</title>
  <link id="hubFavicon" rel="icon" type="image/png" href="/hubcentral/logo_prefeitura.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
  <link rel="stylesheet" href="admin_style.css">
</head>
<style>
  :root {
    --hub-accent: #4caf50;
    --hub-accent-hover: #3b8f41;
    --hub-accent-soft: #66bb6a;
    --hub-accent-rgb: 76, 175, 80;
    --hub-accent-glow: 102, 255, 153;
    --hub-banner-1: #075800;
    --hub-banner-2: #032500;
    --hub-bg-body: #1b1f24;
    --hub-bg-section: #23282f;
    --hub-bg-card: #2a2f36;
    --hub-bg-inner: #1e2329;
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
}

/* ======== BARRA SUPERIOR ANIMADA ======== */
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

header h1 {
    color: white !important;
}

@keyframes gradientAnimation {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* ======== LOGO DA PREFEITURA ======== */
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

/* ======== CARDS PRINCIPAIS ======== */
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
  color: var(--hub-accent-soft) !important;
}

.hub-accent-link:hover {
  text-decoration: underline !important;
}

/* ======== INPUTS ======== */
input,
select {
  background: var(--hub-bg-inner) !important;
  border: 1px solid var(--hub-accent) !important;
    color: #e0ffe0 !important;
    border-radius: 6px !important;
}

input:focus,
select:focus {
  border-color: var(--hub-accent-soft) !important;
  box-shadow: 0 0 6px rgba(var(--hub-accent-glow),0.5) !important;
}

/* ======== BOTÃO DE UPLOAD MELHORADO ======== */
.file-upload-wrapper {
    position: relative;
    overflow: hidden;
    display: block;
    width: 100%;
    margin-top: 12px;
}

.file-upload-wrapper input[type=file] {
    position: absolute;
    left: -9999px;
}

.file-upload-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
    color: #e2e8f0;
    border: 1px solid #4a5568;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
    font-weight: 600;
    width: 100%;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.file-upload-label:hover {
    background: linear-gradient(135deg, #5a6678 0%, #3d4758 100%);
    border-color: #5a6678;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.4);
}

.file-upload-label:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.file-upload-label i {
    width: 18px;
    height: 18px;
}

.file-preview {
    max-width: 40px;
    max-height: 40px;
    object-fit: contain;
    border-radius: 4px;
}

/* ======== BOTÕES PRINCIPAIS MELHORADOS ======== */
button {
    border-radius: 8px !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3) !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
}

button:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 8px rgba(0,0,0,0.4) !important;
}

button:active {
    transform: translateY(0) !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
}

.bg-green-600 {
  background: var(--hub-accent) !important;
  border: 1px solid var(--hub-accent) !important;
}

.bg-green-600:hover {
  background: var(--hub-accent-hover) !important;
  border-color: var(--hub-accent-hover) !important;
}

.bg-blue-600 {
  background: var(--hub-accent) !important;
  border: 1px solid var(--hub-accent) !important;
}

.bg-blue-600:hover {
  background: var(--hub-accent-hover) !important;
  border-color: var(--hub-accent-hover) !important;
}

.bg-red-600 {
    background: linear-gradient(135deg, #e53935 0%, #c62828 100%) !important;
    border: 1px solid #e53935 !important;
}

.bg-red-600:hover {
    background: linear-gradient(135deg, #ef5350 0%, #e53935 100%) !important;
    border-color: #ef5350 !important;
}

/* ======== BOTÃO DE SALVAR ACESSOS ======== */
.save-access-btn {
    background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%) !important;
    border: 1px solid #2196f3 !important;
    padding: 10px 20px !important;
    border-radius: 8px !important;
    color: white !important;
    font-weight: 600 !important;
    width: 100% !important;
    margin-top: 12px !important;
}

.save-access-btn:hover {
    background: linear-gradient(135deg, #42a5f5 0%, #2196f3 100%) !important;
    border-color: #42a5f5 !important;
}

/* ======== ÁREA DE CHECKBOXES ======== */
.access-checkbox-area {
  background: var(--hub-bg-inner) !important;
    border: 1px solid #3a4149 !important;
    border-radius: 8px !important;
    padding: 12px !important;
    margin-top: 8px !important;
}

.access-checkbox-label {
  background: var(--hub-bg-card) !important;
    border: 1px solid #4a5568 !important;
    padding: 8px 12px !important;
    border-radius: 6px !important;
    transition: all 0.2s ease !important;
    cursor: pointer !important;
}

.access-checkbox-label:hover {
    background: #343941 !important;
    border-color: #5a6678 !important;
    transform: translateY(-1px) !important;
}

.access-checkbox-label input[type="checkbox"] {
  accent-color: var(--hub-accent) !important;
    width: 18px !important;
    height: 18px !important;
    cursor: pointer !important;
}

/* ======== LISTA DE SITES / USUÁRIOS ======== */
.user-item,
.border,
.shadow,
.shadow-md {
  background: var(--hub-bg-card) !important;
    border-color: #3a4149 !important;
}

.user-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.7) !important;
}

/* ======== MENSAGENS ======== */
.bg-green-50 {
    background: rgba(0,255,0,0.12) !important;
    border: 1px solid #00ff55 !important;
    color: #b4ffb4 !important;
}

.bg-red-50 {
    background: rgba(255,0,0,0.12) !important;
    border: 1px solid #ff6060 !important;
    color: #ffb4b4 !important;
}

/* ======== PAGINAÇÃO ======== */
.bg-sky-600 {
  background: var(--hub-accent) !important;
}

.bg-sky-600:hover {
  background: var(--hub-accent-hover) !important;
}

.bg-gray-200 {
    background: #2b3037 !important;
    color: #ddd !important;
}

.bg-gray-200:hover {
    background: #353b43 !important;
}

.icon-display {
    width: 40px;
    height: 40px;
    border-radius: 6px;
  border: 1px solid var(--hub-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--hub-bg-inner);
}

.icon-display img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* ======== DIVISÓRIA VISUAL ======== */
.divider {
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, #4a5568 50%, transparent 100%);
    margin: 16px 0;
}

/* ======== TOAST NOTIFICATION ======== */
.hub-toast-container {
  position: fixed;
  top: 16px;
  right: 16px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 8px;
  pointer-events: none;
}

.hub-toast {
  min-width: 260px;
  max-width: 380px;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 14px;
  box-shadow: 0 8px 22px rgba(0,0,0,0.4);
  opacity: 0;
  transform: translateY(-8px);
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.hub-toast.show {
  opacity: 1;
  transform: translateY(0);
}

.hub-toast.success {
  background: rgba(24, 96, 49, 0.95);
  border: 1px solid var(--hub-accent);
  color: #e6ffe9;
}

.hub-toast.error {
  background: rgba(116, 32, 32, 0.95);
  border: 1px solid #ef5350;
  color: #ffe6e6;
}

@media (max-width: 768px) {
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
<body class="min-h-screen p-6">

  <div class="max-w-7xl mx-auto">

    <header class="flex items-center justify-between mb-6">
      <div class="hub-header-brand">
        <img id="hubHeaderLogo" class="hub-header-logo" src="/hubcentral/logo_prefeitura.png" alt="Logo">
      </div>
      <h1 class="hub-header-title text-2xl font-bold">Painel Admin — HubCentral</h1>
      <a href="dashboard.php" class="hub-header-right hub-accent-link">Voltar</a>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      <!-- SITES -->
      <section class="rounded-2xl p-6">
        <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
          <i data-lucide="layout-grid"></i>
          Gerenciar Sites
        </h2>

        <!-- add site -->
        <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 mb-6">
          <input type="hidden" name="site_action" value="add">

          <input name="site_name" placeholder="Nome do site" class="p-2" required>
          <input name="site_folder" placeholder="Pasta (ex: laudos)" class="p-2" required>
          
          <div class="file-upload-wrapper">
            <input type="file" name="site_icon_upload" id="site_icon_upload" accept="image/*">
            <label for="site_icon_upload" class="file-upload-label">
              <i data-lucide="upload"></i>
              Enviar imagem do ícone
            </label>
          </div>
          
          <small class="text-gray-400">Envie uma imagem para o ícone (JPG, PNG, GIF, SVG - máx 2MB)</small>

          <button class="mt-2 px-3 py-2 bg-green-600 text-white rounded">
            <i data-lucide="plus"></i>
            Adicionar site
          </button>
        </form>

        <h3 class="font-semibold mb-3">Sites existentes</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?php foreach($sites as $s): ?>
            <div class="border rounded-2xl p-5 user-item">

              <div class="flex items-center gap-3 mb-3">
                <div class="icon-display">
                  <?php if (strpos($s['icon'], 'site_icons/') === 0): ?>
                    <img src="/hubcentral/<?= htmlspecialchars($s['icon']) ?>" alt="Ícone" class="file-preview">
                  <?php else: ?>
                    <i data-lucide="globe"></i>
                  <?php endif; ?>
                </div>

                <div>
                  <div class="font-semibold"><?= htmlspecialchars($s['name']) ?></div>
                  <div class="text-xs text-gray-400"><?= htmlspecialchars($s['folder']) ?></div>
                </div>
              </div>

              <form id="edit-<?= htmlspecialchars($s['folder']) ?>" method="post" enctype="multipart/form-data" class="grid gap-2 mb-3">
                <input type="hidden" name="site_action" value="edit">
                <input type="hidden" name="site_folder" value="<?= htmlspecialchars($s['folder']) ?>">
                <input type="hidden" name="site_icon" value="<?= htmlspecialchars($s['icon']) ?>">

                <input name="site_name" value="<?= htmlspecialchars($s['name']) ?>" class="p-2" required>
                <input name="new_folder" value="<?= htmlspecialchars($s['folder']) ?>" class="p-2" required>
              </form>

              <div class="flex justify-center gap-2">
                <button form="edit-<?= htmlspecialchars($s['folder']) ?>" 
                        class="px-3 py-2 bg-blue-600 text-white rounded">
                  <i data-lucide="save"></i>
                  Salvar
                </button>

                <form method="post" onsubmit="return confirm('Remover site da lista?')" class="inline">
                  <input type="hidden" name="site_action" value="delete">
                  <input type="hidden" name="site_folder" value="<?= htmlspecialchars($s['folder']) ?>">
                  <button class="px-3 py-2 bg-red-600 text-white rounded">
                    <i data-lucide="trash-2"></i>
                    Remover
                  </button>
                </form>
              </div>

              <div class="file-upload-wrapper">
                <input type="file" name="site_icon_upload_<?= htmlspecialchars($s['folder']) ?>" 
                       id="site_icon_upload_<?= htmlspecialchars($s['folder']) ?>" accept="image/*"
                       form="edit-<?= htmlspecialchars($s['folder']) ?>">
                
                <label for="site_icon_upload_<?= htmlspecialchars($s['folder']) ?>" class="file-upload-label">
                  <i data-lucide="image"></i>
                  <?php if (strpos($s['icon'], 'site_icons/') === 0): ?>
                    Trocar ícone
                  <?php else: ?>
                    Adicionar ícone
                  <?php endif; ?>
                </label>
              </div>

              <button type="button"
                      class="file-upload-label manage-files-btn"
                      data-folder="<?= htmlspecialchars($s['folder']) ?>"
                      style="margin-top:12px;">
                <i data-lucide="folder-open"></i>
                Gerenciar Arquivos
              </button>

            </div>
          <?php endforeach; ?>
        </div>

      </section>

      <!-- USERS -->
      <section class="rounded-2xl p-6">
        <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
          <i data-lucide="users"></i>
          Gerenciar Usuários
        </h2>

        <div class="mb-4 flex gap-2">
          <input id="userSearch" type="text" placeholder="Buscar usuário..." class="flex-1 p-2">
          <button id="sortUsers" type="button" class="px-3 py-2 bg-gray-200 rounded">
            Ordenar
          </button>
        </div>

        <div class="space-y-3 user-list">
          <?php foreach($users_paged as $u): ?>
            <div class="border rounded-2xl p-3 user-item" data-username="<?= htmlspecialchars($u['username']) ?>">

              <div class="flex items-start justify-between gap-4">

                <div>
                  <div class="font-semibold"><?= htmlspecialchars($u['username']) ?></div>
                  <div class="text-xs text-gray-400"><?= htmlspecialchars($u['role']) ?></div>
                </div>

                <div class="flex flex-col gap-3 w-full md:w-auto">

                  <div class="flex items-center gap-2">
                    <form method="post" class="flex gap-2">
                      <input type="hidden" name="user_action" value="change_pwd_by_admin">
                      <input type="hidden" name="target_user" value="<?= htmlspecialchars($u['username']) ?>">
                      <input name="new_password_user" placeholder="Nova senha" class="p-2">
                      <button class="px-3 py-2 bg-green-600 text-white rounded">Alterar</button>
                    </form>

                    <?php if ($u['username'] !== 'admin'): ?>
                      <form method="post">
                        <input type="hidden" name="user_action" value="delete_user">
                        <input type="hidden" name="delete_username" value="<?= htmlspecialchars($u['username']) ?>">
                        <button class="px-3 py-2 bg-red-600 text-white rounded"
                          onclick="return confirm('Excluir usuário?')">
                          Excluir
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>

                  <?php if ($u['username'] !== 'admin'): ?>
                    <form method="post" class="grid gap-2">
                      <input type="hidden" name="access_action" value="save_access">
                      <input type="hidden" name="access_username" value="<?= htmlspecialchars($u['username']) ?>">

                      <div class="flex flex-wrap gap-2">
                        <?php foreach($sites as $s): ?>
                          <label class="inline-flex items-center gap-2 p-1 border rounded bg-[#2a2f36]">
                            <input type="checkbox" name="access[]" value="<?= htmlspecialchars($s['folder']) ?>" class="h-4 w-4"
                             <?= in_array($s['folder'], $u['access'] ?? []) ? "checked" : "" ?>>
                            <span class="text-sm text-gray-200"><?= htmlspecialchars($s['name']) ?></span>
                          </label>
                        <?php endforeach; ?>
                      </div>

                      <div class="flex justify-end">
                        <button class="px-3 py-2 bg-blue-600 text-white rounded">Salvar Acessos</button>
                      </div>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
  </div>

  <!-- ====== MODAL GERENCIAR ARQUIVOS ====== -->
  <!-- DEVE ficar ANTES do <script> para que getElementById funcione -->
  <div id="fileManagerModal"
       style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,0.78);backdrop-filter:blur(4px);align-items:center;justify-content:center;">

    <div style="width:min(820px,95vw);max-height:88vh;display:flex;flex-direction:column;background:var(--hub-bg-section);border:1px solid rgba(var(--hub-accent-rgb),0.4);border-radius:16px;box-shadow:0 24px 70px rgba(0,0,0,0.85);overflow:hidden;">

      <!-- Cabeçalho -->
      <div style="padding:18px 22px;background:var(--hub-bg-inner);border-bottom:1px solid #3a4149;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0;">
        <div>
          <div style="font-weight:700;font-size:16px;color:var(--hub-accent);">Gerenciar Arquivos</div>
          <div id="fmFolderLabel" style="font-size:12px;color:#9ca3af;margin-top:2px;"></div>
        </div>
        <button id="fmClose" type="button" onclick="fmClose()"
                style="background:rgba(255,255,255,0.07);border:1px solid #4a5568;color:#e2e8f0;cursor:pointer;padding:7px 10px;border-radius:7px;line-height:1;display:flex;align-items:center;justify-content:center;box-shadow:none!important;transform:none!important;">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <!-- Zona de upload -->
      <div id="fmDropZone"
           style="margin:18px 22px 0;border:2px dashed rgba(var(--hub-accent-rgb),0.4);border-radius:10px;padding:20px 16px 16px;text-align:center;transition:all 0.2s;flex-shrink:0;">
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--hub-accent);margin:0 auto 10px;display:block;"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
        <div style="color:#e2e8f0;font-weight:600;font-size:14px;margin-bottom:4px;">Arraste arquivos ou pasta aqui</div>
        <div style="color:#9ca3af;font-size:12px;margin-bottom:14px;">ou use os botões abaixo</div>

        <!-- Dois botões lado a lado -->
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
          <label for="fmFileInput"
                 style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;background:linear-gradient(135deg,#4a5568,#2d3748);border:1px solid #4a5568;border-radius:8px;color:#e2e8f0;cursor:pointer;font-size:13px;font-weight:600;transition:all 0.2s;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Selecionar Arquivos
          </label>
          <input type="file" id="fmFileInput" multiple
                 style="position:absolute;left:-9999px;opacity:0;">

          <label for="fmFolderInput"
                 style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;background:linear-gradient(135deg,var(--hub-accent),var(--hub-accent-hover));border:1px solid var(--hub-accent);border-radius:8px;color:#fff;cursor:pointer;font-size:13px;font-weight:600;transition:all 0.2s;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><polyline points="12 11 12 17"/><polyline points="9 14 12 11 15 14"/></svg>
            Selecionar Pasta
          </label>
          <input type="file" id="fmFolderInput" webkitdirectory directory multiple
                 style="position:absolute;left:-9999px;opacity:0;">
        </div>
      </div>

      <!-- Barra de progresso -->
      <div id="fmProgressWrap" style="display:none;margin:12px 22px 0;flex-shrink:0;">
        <div style="height:6px;background:#2d3748;border-radius:3px;overflow:hidden;">
          <div id="fmProgressBar" style="height:100%;background:var(--hub-accent);width:0%;transition:width 0.35s;"></div>
        </div>
        <div id="fmProgressLabel" style="font-size:12px;color:#9ca3af;margin-top:5px;text-align:center;"></div>
      </div>

      <!-- Lista de arquivos -->
      <div style="padding:16px 22px 20px;flex:1;overflow-y:auto;min-height:0;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
          <span style="font-size:11px;font-weight:700;color:#6b7280;letter-spacing:.05em;">ARQUIVOS NO SERVIDOR</span>
          <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#cbd5e1;cursor:pointer;">
              <input type="checkbox" id="fmSelectAll" style="accent-color:var(--hub-accent);cursor:pointer;">
              Selecionar todos
            </label>
            <button id="fmDeleteSelected" type="button"
                    style="background:#b91c1c;border:none;color:white;padding:6px 12px;border-radius:6px;font-size:12px;cursor:pointer;opacity:.65;" disabled>
              Excluir Selecionados
            </button>
            <span id="fmFileCount" style="font-size:12px;color:#9ca3af;"></span>
          </div>
        </div>
        <div id="fmFileList" style="display:flex;flex-direction:column;gap:5px;"></div>
      </div>

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
      root.style.setProperty('--hub-accent-rgb', `${r}, ${g}, ${b}`);
      root.style.setProperty('--hub-accent-glow', `${r}, ${g}, ${b}`);
    })();

    (function applyHubBannerAndLogoTheme() {
      const banner1 = localStorage.getItem('hubcentral_banner_color_1');
      const banner2 = localStorage.getItem('hubcentral_banner_color_2');
      const logoData = localStorage.getItem('hubcentral_logo_dataurl');
      const defaultLogo = '/hubcentral/logo_prefeitura.png';

      const root = document.documentElement;
      if (banner1) root.style.setProperty('--hub-banner-1', banner1);
      if (banner2) root.style.setProperty('--hub-banner-2', banner2);
      const logoEl = document.getElementById('hubHeaderLogo');
      if (logoEl && logoData) logoEl.src = logoData;

      const faviconEl = document.getElementById('hubFavicon');
      if (faviconEl) {
        faviconEl.href = logoData || defaultLogo;
      }
    })();

    (function applyHubBackgroundTheme() {
      const bodyColor = localStorage.getItem('hubcentral_bg_body_color');
      const sectionColor = localStorage.getItem('hubcentral_bg_section_color');
      const cardColor = localStorage.getItem('hubcentral_bg_card_color');

      const root = document.documentElement;
      if (bodyColor) root.style.setProperty('--hub-bg-body', bodyColor);
      if (sectionColor) root.style.setProperty('--hub-bg-section', sectionColor);
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

    const flashMessage = <?= json_encode($message, JSON_UNESCAPED_UNICODE) ?>;
    const flashErrors = <?= json_encode(array_values($errors), JSON_UNESCAPED_UNICODE) ?>;

    function showToast(text, type = 'success', duration = 3500) {
      if (!text) return;

      let container = document.querySelector('.hub-toast-container');
      if (!container) {
        container = document.createElement('div');
        container.className = 'hub-toast-container';
        document.body.appendChild(container);
      }

      const toast = document.createElement('div');
      toast.className = 'hub-toast ' + type;
      toast.textContent = text;
      container.appendChild(toast);

      requestAnimationFrame(() => toast.classList.add('show'));

      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 250);
      }, duration);
    }

    if (flashMessage) {
      showToast(flashMessage, 'success', 3500);

      if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('HubCentral', { body: flashMessage });
      }
    }

    if (Array.isArray(flashErrors)) {
      flashErrors.forEach(err => showToast(err, 'error', 5000));
    }

    // search filter
    document.getElementById('userSearch').addEventListener('input', function(){
      const q = this.value.toLowerCase();
      document.querySelectorAll('.user-list .user-item[data-username]').forEach(item => {
        const username = (item.dataset.username || '').toLowerCase();
        item.style.display = username.includes(q) ? '' : 'none';
      });
    });

    // sort users on current page
    document.getElementById('sortUsers').addEventListener('click', function(){
      const list = document.querySelector('.user-list');
      const items = Array.from(list.children);
      items.sort((a,b) => a.dataset.username.localeCompare(b.dataset.username));
      items.forEach(it => list.appendChild(it));
    });

    // ====== GERENCIADOR DE ARQUIVOS ======
    // NOTA: o modal HTML está acima deste script (antes da tag <script>)
    // para garantir que os elementos existam quando os listeners são registrados.
    let fmFolder = '';
    let fmSelectedFiles = new Set();

    function fmOpen(folder) {
      fmFolder = folder;
      fmSelectedFiles = new Set();
      document.getElementById('fmFolderLabel').textContent = 'sites/' + folder;
      document.getElementById('fileManagerModal').style.display = 'flex';
      document.getElementById('fmProgressWrap').style.display = 'none';
      document.getElementById('fmFileInput').value = '';
      document.getElementById('fmFolderInput').value = '';
      fmRefreshSelectionControls();
      fmLoadFiles();
    }

    function fmClose() {
      document.getElementById('fileManagerModal').style.display = 'none';
      fmFolder = '';
      fmSelectedFiles = new Set();
      fmRefreshSelectionControls();
    }

    function fmFmt(bytes) {
      if (bytes < 1024) return bytes + ' B';
      if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
      return (bytes / 1048576).toFixed(2) + ' MB';
    }

    function fmRefreshSelectionControls() {
      const checks = Array.from(document.querySelectorAll('.fm-file-check'));
      const total = checks.length;
      const selected = checks.filter(ch => ch.checked).length;
      const selectAll = document.getElementById('fmSelectAll');
      const btn = document.getElementById('fmDeleteSelected');

      if (total === 0) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
        selectAll.disabled = true;
        btn.disabled = true;
        btn.style.opacity = '.65';
        btn.textContent = 'Excluir Selecionados';
        return;
      }

      selectAll.disabled = false;
      selectAll.checked = selected === total;
      selectAll.indeterminate = selected > 0 && selected < total;

      btn.disabled = selected === 0;
      btn.style.opacity = selected === 0 ? '.65' : '1';
      btn.textContent = selected > 0 ? ('Excluir Selecionados (' + selected + ')') : 'Excluir Selecionados';
    }

    function fmSetAllSelected(checked) {
      const checks = Array.from(document.querySelectorAll('.fm-file-check'));
      checks.forEach(ch => {
        ch.checked = checked;
        const path = ch.dataset.path || '';
        if (!path) return;
        if (checked) fmSelectedFiles.add(path);
        else fmSelectedFiles.delete(path);
      });
      fmRefreshSelectionControls();
    }

    function fmToggleSelection(checkbox) {
      const path = checkbox.dataset.path || '';
      if (!path) return;

      if (checkbox.checked) fmSelectedFiles.add(path);
      else fmSelectedFiles.delete(path);

      fmRefreshSelectionControls();
    }

    async function fmLoadFiles() {
      const list     = document.getElementById('fmFileList');
      const countEl  = document.getElementById('fmFileCount');
      list.innerHTML = '<div style="color:#9ca3af;text-align:center;padding:24px;font-size:13px;">Carregando...</div>';
      fmRefreshSelectionControls();

      try {
        const res  = await fetch('site_files.php?action=list&folder=' + encodeURIComponent(fmFolder));
        const data = await res.json();

        if (data.error) {
          list.innerHTML = '<div style="color:#ef5350;text-align:center;padding:24px;">' + data.error + '</div>';
          return;
        }

        const files = data.files || [];
        const currentPaths = new Set(files.map(f => f.path));
        fmSelectedFiles.forEach(path => {
          if (!currentPaths.has(path)) fmSelectedFiles.delete(path);
        });
        countEl.textContent = files.length + ' arquivo(s)';

        if (files.length === 0) {
          fmSelectedFiles.clear();
          list.innerHTML = '<div style="color:#9ca3af;text-align:center;padding:24px;font-size:13px;">Nenhum arquivo na pasta.</div>';
          fmRefreshSelectionControls();
          return;
        }

        list.innerHTML = '';
        files.forEach(f => {
          const row = document.createElement('div');
          row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 12px;background:var(--hub-bg-card);border-radius:7px;border:1px solid #3a4149;';
          row.innerHTML =
            '<div style="flex:1;min-width:0;display:flex;align-items:center;gap:8px;">'
            + '<input type="checkbox" class="fm-file-check" style="accent-color:var(--hub-accent);cursor:pointer;" />'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#9ca3af;flex-shrink:0;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'
            + '<span style="font-size:12px;color:#e2e8f0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + f.path + '">' + f.path + '</span>'
            + '</div>'
            + '<span style="font-size:11px;color:#9ca3af;flex-shrink:0;white-space:nowrap;">' + fmFmt(f.size) + '</span>'
            + '<button type="button" onclick="fmDelete(this,\'' + f.path.replace(/\\/g,'\\\\').replace(/'/g,"\\'") + '\')" '
            + 'style="background:#c62828;border:none;color:white;padding:4px 10px;border-radius:5px;font-size:11px;cursor:pointer;flex-shrink:0;border-radius:6px;box-shadow:none!important;transform:none!important;">Excluir</button>';

          const checkbox = row.querySelector('.fm-file-check');
          if (checkbox) {
            checkbox.dataset.path = f.path;
            checkbox.checked = fmSelectedFiles.has(f.path);
            checkbox.addEventListener('change', function() {
              fmToggleSelection(this);
            });
          }

          list.appendChild(row);
        });

        fmRefreshSelectionControls();
      } catch (e) {
        fmSelectedFiles.clear();
        list.innerHTML = '<div style="color:#ef5350;text-align:center;padding:24px;">Erro ao carregar arquivos.</div>';
        fmRefreshSelectionControls();
      }
    }

    async function fmDelete(btn, filePath) {
      if (!confirm('Excluir "' + filePath + '"?')) return;
      btn.disabled = true;
      const fd = new FormData();
      fd.append('action', 'delete');
      fd.append('folder', fmFolder);
      fd.append('file', filePath);
      const res  = await fetch('site_files.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.ok) {
        fmSelectedFiles.delete(filePath);
        fmLoadFiles();
      } else {
        alert('Erro: ' + (data.error || 'falha ao excluir'));
        btn.disabled = false;
      }
    }

    async function fmDeleteSelected() {
      const files = Array.from(fmSelectedFiles);
      if (files.length === 0) return;

      if (!confirm('Excluir ' + files.length + ' arquivo(s) selecionado(s)?')) return;

      const btn = document.getElementById('fmDeleteSelected');
      btn.disabled = true;
      btn.textContent = 'Excluindo...';

      const fd = new FormData();
      fd.append('action', 'delete_many');
      fd.append('folder', fmFolder);
      files.forEach(filePath => fd.append('files[]', filePath));

      try {
        const res = await fetch('site_files.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.error) {
          alert('Erro: ' + data.error);
          fmRefreshSelectionControls();
          return;
        }

        const deleted = Number(data.deleted || 0);
        const failed = Number(data.failed || 0);

        if (deleted > 0) {
          files.forEach(path => fmSelectedFiles.delete(path));
          showToast(deleted + ' arquivo(s) excluído(s).', failed > 0 ? 'error' : 'success', 3500);
        }
        if (failed > 0) {
          showToast(failed + ' arquivo(s) não puderam ser excluídos.', 'error', 4500);
        }

        fmLoadFiles();
      } catch (e) {
        alert('Erro na conexão ao excluir em lote.');
        fmRefreshSelectionControls();
      }
    }

    async function fmUpload(fileList) {
      if (!fileList || fileList.length === 0) return;
      const wrap  = document.getElementById('fmProgressWrap');
      const bar   = document.getElementById('fmProgressBar');
      const label = document.getElementById('fmProgressLabel');

      wrap.style.display  = 'block';
      bar.style.width     = '10%';
      label.style.color   = '#e2e8f0';
      label.textContent   = 'Enviando ' + fileList.length + ' arquivo(s)...';

      const fd = new FormData();
      fd.append('action', 'upload');
      fd.append('folder', fmFolder);
      for (let i = 0; i < fileList.length; i++) {
        fd.append('files[]', fileList[i]);
        fd.append('paths[]', fileList[i].webkitRelativePath || fileList[i].name);
      }

      bar.style.width = '50%';

      try {
        const res  = await fetch('site_files.php', { method: 'POST', body: fd });
        const data = await res.json();
        bar.style.width = '100%';

        if (data.error) {
          label.textContent = 'Erro: ' + data.error;
          label.style.color = '#ef5350';
        } else {
          const msg = data.uploaded + ' arquivo(s) enviado(s)' + (data.failed > 0 ? ', ' + data.failed + ' falhou(ram)' : '');
          label.textContent = msg;
          label.style.color = data.failed > 0 ? '#ffa726' : 'var(--hub-accent)';
          setTimeout(() => { wrap.style.display = 'none'; bar.style.width = '0%'; fmLoadFiles(); }, 2000);
        }
      } catch (e) {
        bar.style.width = '100%';
        label.textContent = 'Erro na conexão.';
        label.style.color = '#ef5350';
      }

      document.getElementById('fmFileInput').value = '';
      document.getElementById('fmFolderInput').value = '';
    }

    document.querySelectorAll('.manage-files-btn').forEach(btn => {
      btn.addEventListener('click', () => fmOpen(btn.dataset.folder));
    });

    document.getElementById('fmClose').addEventListener('click', fmClose);
    document.getElementById('fmSelectAll').addEventListener('change', function() {
      fmSetAllSelected(this.checked);
    });
    document.getElementById('fmDeleteSelected').addEventListener('click', fmDeleteSelected);
    document.getElementById('fileManagerModal').addEventListener('click', function(e) {
      if (e.target === this) fmClose();
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') fmClose(); });

    document.getElementById('fmFileInput').addEventListener('change', function() {
      if (this.files.length > 0) fmUpload(this.files);
    });

    document.getElementById('fmFolderInput').addEventListener('change', function() {
      if (this.files.length > 0) fmUpload(this.files);
    });

    const dropZone = document.getElementById('fmDropZone');
    dropZone.addEventListener('dragover', e => {
      e.preventDefault();
      dropZone.style.borderColor = 'var(--hub-accent)';
      dropZone.style.background  = 'rgba(var(--hub-accent-rgb),0.09)';
    });
    dropZone.addEventListener('dragleave', () => {
      dropZone.style.borderColor = 'rgba(var(--hub-accent-rgb),0.4)';
      dropZone.style.background  = '';
    });
    dropZone.addEventListener('drop', e => {
      e.preventDefault();
      dropZone.style.borderColor = 'rgba(var(--hub-accent-rgb),0.4)';
      dropZone.style.background  = '';
      if (e.dataTransfer.files.length > 0) fmUpload(e.dataTransfer.files);
    });

    if (window.lucide) lucide.replace();
  </script>

</body>
</html>