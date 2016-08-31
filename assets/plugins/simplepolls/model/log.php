<?php namespace SimplePolls;
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/autoTable.abstract.php');

class Log extends \autoTable {
    protected $table = 'sp_log';
    protected $pkName = 'id';
    public $default_field = array(
        'id' => 0,
        'poll' => 0, //id голосования
        'ip' => 0, //ip пользователя
        'uid' => 0, //id пользователя
        'votedon' => 0 //время голосования
    );
    public function deletePoll($ids) {
        $_ids = $this->cleanIDs($ids, ',');
        if (is_array($_ids) && $_ids != array()) {
            $id = $this->sanitarIn($_ids);
            if(!empty($id)){
                $this->query("DELETE from {$this->makeTable($this->table)} where `poll` IN ({$id})");
            }
        } else throw new Exception('Invalid IDs list for delete: <pre>' . print_r($ids, 1) . '</pre>');
        $this->query("ALTER TABLE {$this->makeTable($this->table)} AUTO_INCREMENT = 1");
        return $this;
    }
    public function save($fire_events = null, $clearCache = false) {
        $this->touch('votedon');
        return parent::save($fire_events,$clearCache);
    }

    public function touch($field){
        $this->set($field, date('Y-m-d H:i:s', time() + $this->modx->config['server_offset_time']));
        return $this;
    }
}