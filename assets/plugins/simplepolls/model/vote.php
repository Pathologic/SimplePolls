<?php namespace SimplePolls;
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/autoTable.abstract.php');

class Vote extends \autoTable {
    protected $table = 'sp_votes';
    protected $pkName = 'vote_id';
    public $default_field = array(
        'vote_id' => 0,
        'vote_title' => '', //название варианта,
        'vote_image' => '', //картинка
        'vote_poll' => 0, //голосование-родитель
        'vote_value' => 0, //число голосов
        'vote_rank' => 0, //позиция в списке
    );

    /**
     * Обнуление заданных вариантов
     * @param $ids
     * @return $this
     */
    public function reset($ids) {
        $_ids = $this->cleanIDs($ids, ',');
        if (is_array($_ids) && $_ids != array()) {
            $id = $this->sanitarIn($_ids);
            if(!empty($id)){
                $this->query("UPDATE {$this->makeTable($this->table)} SET `vote_value`=0 WHERE`vote_id` IN ({$id})");
            }
        } else throw new Exception('Invalid IDs list for reset: <pre>' . print_r($ids, 1) . '</pre>');
        return $this;
    }

    /**
     * Удаление вариантов для заданных голосований
     * @param $ids
     * @return $this
     * @throws \Exception
     */
    public function deletePoll($ids) {
        $_ids = $this->cleanIDs($ids, ',');
        if (!empty($_ids)) {
            $id = implode(',',$_ids);
            if(!empty($id)){
                $this->query("DELETE from {$this->makeTable($this->table)} where `vote_poll` IN ({$id})");
            }
        } else throw new \Exception('Invalid IDs list for delete: <pre>' . print_r($ids, 1) . '</pre>');
        $this->query("ALTER TABLE {$this->makeTable($this->table)} AUTO_INCREMENT = 1");
        return $this;
    }

    /**
     * Обнуление вариантов для заданных голосований
     * @param $ids
     * @return $this
     * @throws \Exception
     */
    public function resetPoll($ids) {
        $_ids = $this->cleanIDs($ids, ',');
        if (!empty($_ids)) {
            $id = implode(',',$_ids);
            if(!empty($id)){
                $this->query("UPDATE {$this->makeTable($this->table)} SET `vote_value`=0 WHERE`vote_poll` IN ({$id})");
            }
        } else throw new \Exception('Invalid IDs list for reset: <pre>' . print_r($ids, 1) . '</pre>');
        return $this;
    }

    public function save($fire_events = null, $clearCache = false)
    {
        if ($this->newDoc) {
            $q = $this->query("SELECT count(`vote_id`) FROM {$this->makeTable($this->table)} WHERE `vote_poll`={$this->get('vote_poll')}");
            $this->field['vote_rank'] = $this->modx->db->getValue($q);
        }
        return parent::save();
    }

    public function vote($ids = array()) {
        $_ids = $this->cleanIDs($ids, ',');
        $id = implode(',',$_ids);
        if(!empty($id)){
            $this->query("UPDATE {$this->makeTable($this->table)} SET `vote_value`=`vote_value` + 1 WHERE `vote_id` IN ({$id})");
        }
    }

    public function correct($id, $num = 0) {
        if (is_integer($num) && $num > 0 && $id) {
            $this->query("UPDATE {$this->makeTable($this->table)} SET `vote_value`=`vote_value` + {$num} WHERE `vote_id` IN ({$id}) AND (`vote_value` + {$num}) > 0");
            $this->close();
            $poll = $this->edit($id)->get('vote_poll');
            include_once('log.php');
            $log = new Log($this->modx);
            $log->create(array(
                'poll' => $poll,
                'ip' => '127.0.0.1',
                'voters' => $num
            ))->save();
        }
    }

    public function makeThumb($folder,$url,$options) {
        if (empty($url)) return false;
        include_once(MODX_BASE_PATH.'assets/lib/Helpers/FS.php');
        include_once(MODX_BASE_PATH.'assets/lib/Helpers/PHPThumb.php');
        $fs = \Helpers\FS::getInstance();
        $thumb = new \Helpers\PHPThumb();
        $inputFile = MODX_BASE_PATH . $fs->relativePath($url);
        $outputFile = MODX_BASE_PATH. $fs->relativePath($folder). '/' . $fs->relativePath($url);
        $dir = $fs->takeFileDir($outputFile);
        $fs->makeDir($dir);
        if ($thumb->create($inputFile,$outputFile,$options)) {
            return true;
        } else {
            $this->modx->logEvent(0, 3, $thumb->debugMessages,  __NAMESPACE__);
            return false;
        }
    }
}
