<?php
require_once __DIR__ . '/functions.php';
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/auth_check.php';

if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit(json_encode(['error' => 'Acesso negado']));
}

header('Content-Type: application/json; charset=utf-8');

$action = strtolower(trim($_GET['action'] ?? $_POST['action'] ?? ''));
$folder = strtolower(trim($_GET['folder'] ?? $_POST['folder'] ?? ''));

$validFolders = array_column(load_sites(), 'folder');
if ($folder === '' || !in_array($folder, $validFolders, true)) {
    exit(json_encode(['error' => 'Site não encontrado']));
}

$baseDir = realpath(__DIR__ . '/../sites');
if ($baseDir === false) {
    exit(json_encode(['error' => 'Diretório base não encontrado']));
}

$siteDir = $baseDir . DIRECTORY_SEPARATOR . $folder;

function sf_normalize(string $path): ?string {
    $p = str_replace('\\', '/', trim($path));
    $p = trim($p, '/');
    if ($p === '') return null;
    $parts = explode('/', $p);
    $safe  = [];
    foreach ($parts as $part) {
        if ($part === '' || $part === '.') continue;
        if ($part === '..' || preg_match('/[\x00-\x1F\x7F]/', $part)) return null;
        $safe[] = $part;
    }
    return empty($safe) ? null : implode('/', $safe);
}

function sf_list_dir(string $dir, string $rel = ''): array {
    $out = [];
    if (!is_dir($dir)) return $out;
    $items = @scandir($dir);
    if (!$items) return $out;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        $path = $rel === '' ? $item : $rel . '/' . $item;
        if (is_dir($full)) {
            $out = array_merge($out, sf_list_dir($full, $path));
        } else {
            $out[] = [
                'path'  => $path,
                'size'  => filesize($full),
                'mtime' => filemtime($full),
            ];
        }
    }
    return $out;
}

function sf_prune_empty_dirs(string $startDir, string $stopDir): void {
    $current = realpath($startDir);
    $root    = realpath($stopDir);

    if ($current === false || $root === false) return;

    while (
        $current !== false &&
        $current !== $root &&
        strpos($current, $root . DIRECTORY_SEPARATOR) === 0
    ) {
        if (!is_dir($current)) {
            break;
        }

        $items = @scandir($current);
        if (!is_array($items)) {
            break;
        }

        $entries = array_values(array_diff($items, ['.', '..']));
        if (!empty($entries)) {
            break;
        }

        if (!@rmdir($current)) {
            break;
        }

        $parent = dirname($current);
        if ($parent === $current) {
            break;
        }
        $current = $parent;
    }
}

function sf_resolve_site_file(string $siteDir, string $relativePath): ?string {
    $rel = sf_normalize($relativePath);
    if ($rel === null) return null;

    $target   = $siteDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $realFile = realpath($target);
    $realRoot = realpath($siteDir);

    if (
        $realFile === false || $realRoot === false ||
        ($realFile !== $realRoot && strpos($realFile, $realRoot . DIRECTORY_SEPARATOR) !== 0)
    ) {
        return null;
    }

    return is_file($realFile) ? $realFile : null;
}

if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    exit(json_encode(['files' => sf_list_dir($siteDir)]));
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $realFile = sf_resolve_site_file($siteDir, (string)($_POST['file'] ?? ''));
    if ($realFile === null) {
        exit(json_encode(['error' => 'Caminho fora da pasta do site']));
    }

    if (!unlink($realFile)) {
        exit(json_encode(['error' => 'Não foi possível excluir o arquivo']));
    }

    sf_prune_empty_dirs(dirname($realFile), $siteDir);
    exit(json_encode(['ok' => true]));
}

if ($action === 'delete_many' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $files = $_POST['files'] ?? [];
    if (!is_array($files) || empty($files)) {
        exit(json_encode(['error' => 'Nenhum arquivo selecionado']));
    }

    $deleted = 0;
    $failed  = 0;

    foreach ($files as $filePath) {
        $realFile = sf_resolve_site_file($siteDir, (string)$filePath);
        if ($realFile === null) {
            $failed++;
            continue;
        }

        if (!@unlink($realFile)) {
            $failed++;
            continue;
        }

        sf_prune_empty_dirs(dirname($realFile), $siteDir);
        $deleted++;
    }

    exit(json_encode([
        'ok' => true,
        'deleted' => $deleted,
        'failed' => $failed,
    ]));
}

if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_dir($siteDir)) mkdir($siteDir, 0755, true);

    $fileData = $_FILES['files'] ?? null;
    if (!$fileData || !is_array($fileData['name'])) {
        exit(json_encode(['error' => 'Nenhum arquivo recebido']));
    }

    $pathsPost = $_POST['paths'] ?? [];
    $count     = count($fileData['name']);
    $uploaded  = 0;
    $failed    = 0;

    for ($i = 0; $i < $count; $i++) {
        $err = $fileData['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK) { $failed++; continue; }

        $clientPath = isset($pathsPost[$i]) ? (string)$pathsPost[$i] : '';
        $rel        = sf_normalize($clientPath !== '' ? $clientPath : $fileData['name'][$i]);
        if ($rel === null) { $failed++; continue; }

        $dest    = $siteDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $destDir = dirname($dest);

        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            $failed++;
            continue;
        }

        if (move_uploaded_file($fileData['tmp_name'][$i], $dest)) {
            $uploaded++;
        } else {
            $failed++;
        }
    }

    exit(json_encode(['uploaded' => $uploaded, 'failed' => $failed]));
}

exit(json_encode(['error' => 'Ação desconhecida']));
