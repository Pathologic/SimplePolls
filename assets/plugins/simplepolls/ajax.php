<?php
define('MODX_API_MODE', true);
define('IN_MANAGER_MODE', true);

include_once(__DIR__."/../../../index.php");
$modx->db->connect();
if (empty ($modx->config)) {
    $modx->getSettings();
}
if(!isset($_SESSION['mgrValidated'])){
    die();
}

$roles = isset($params['role']) ? explode(',',$params['role']) : false;
if ($roles && !in_array($_SESSION['mgrRole'], $roles)) die();

$mode = (isset($_REQUEST['mode']) && is_scalar($_REQUEST['mode'])) ? $_REQUEST['mode'] : null;
$out = null;

if (isset($_REQUEST['controller']) && $_REQUEST['controller'] == 'vote') {
    include_once(MODX_BASE_PATH.'assets/plugins/simplepolls/controller/vote.php');
    $controllerClass = '\SimplePolls\VoteController';
} else {
    include_once(MODX_BASE_PATH.'assets/plugins/simplepolls/controller/poll.php');
    $controllerClass = '\SimplePolls\PollController';
}

$controller = new $controllerClass($modx);

if (!empty($mode) && method_exists($controller, $mode)) {
    $out = call_user_func_array(array($controller, $mode), array());
}else{
    $out = call_user_func_array(array($controller, 'listing'), array());
}
//$controller->callExit();

echo ($out = is_array($out) ? json_encode($out) : $out);