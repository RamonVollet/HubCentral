<?php
//auth_check:
// Coloque este arquivo no topo de cada site (ex: sites/financeiro/index.php)
// Antes de incluir, a página deve definir: $required_site = 'financeiro';

if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/functions.php';


// garante que o usuário esteja logado
if (!isset($_SESSION['user'])) {
header('Location: /hubcentral/index.php');
exit;
}


$user = $_SESSION['user'];


// valida sessão salva no servidor (protege contra sessão hijack / logout remoto)
if (!validate_user_session($user['username'], session_id())) {
session_destroy();
header('Location: /hubcentral/index.php');
exit;
}


// se a página não definiu $required_site, não faz verificação
if (!isset($required_site) || $required_site === null || $required_site === '') {
// sem verificação explícita: permite acesso (mude se preferir negar por default)
return;
}


// normaliza identificadores para comparação (usa identificador de pasta)
$required_site_norm = strtolower(trim($required_site));


// recupera lista de acessos do usuário. aceita chaves 'sites' ou 'access' (compatibilidade)
$access = [];
if (isset($user['role']) && $user['role'] === 'admin') {
// admin sempre tem acesso
return;
} elseif (isset($user['sites']) && is_array($user['sites'])) {
$access = array_map('strtolower', $user['sites']);
} elseif (isset($user['access']) && is_array($user['access'])) {
$access = array_map('strtolower', $user['access']);
}


// aceita '*' wildcard
if (in_array('*', $access, true)) return;


// verifica permissão por folder identifier
if (!in_array($required_site_norm, $access, true)) {
// Acesso negado — redireciona para dashboard com código de erro opcional
// Use caminho relativo ao seu projeto (ajuste '/hubcentral' conforme seu deploy)
header('Location: /hubcentral/php/dashboard.php?access=denied&site=' . urlencode($required_site));
exit;
}


?>