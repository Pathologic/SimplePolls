<?php
if (!IN_MANAGER_MODE) {
    die();
}
$e = $modx->event;
if ($e->name == 'OnDocFormRender') {
    include_once (MODX_BASE_PATH . 'assets/plugins/simplepolls/lib/plugin.php');
    global $modx_lang_attribute;
    $plugin = new \SimplePolls\Plugin($modx, $modx_lang_attribute);
    if ($id) {
        $output = $plugin->render();
    } else {
        $output = $plugin->renderEmpty();
    }
    if ($output) {
        $modx->event->output($output);
    }
}
if ($e->name == 'OnEmptyTrash') {
    if (empty($ids)) {
        return;
    }
    $ids = implode(',', $ids);
    $result = $modx->db->query("SELECT `poll_id` FROM {$modx->getFullTableName('sp_polls')} WHERE `poll_parent` IN ({$ids})");
    $ids = $modx->db->getColumn('poll_id', $result);
    if (empty($ids)) {
        return;
    }
    $ids = implode(',', $ids);
    include_once (MODX_BASE_PATH . 'assets/plugins/simplepolls/model/poll.php');
    $polls = new \SimplePolls\Poll($modx);
    $polls->delete($ids);
}
