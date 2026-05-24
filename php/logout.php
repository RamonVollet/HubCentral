<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/functions.php';
if (isset($_SESSION['user'])) { $u = $_SESSION['user']['username']; clear_user_session_id($u); }
$_SESSION = [];
if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']); }
session_destroy(); header('Location: /hubcentral/index.php'); exit;
?>