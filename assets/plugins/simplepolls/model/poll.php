<?php namespace SimplePolls;
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/autoTable.abstract.php');
include_once (MODX_BASE_PATH . 'assets/plugins/simplepolls/model/vote.php');
include_once (MODX_BASE_PATH . 'assets/plugins/simplepolls/model/log.php');

class Poll extends \autoTable {
    protected $table = 'sp_polls';
    protected $pkName = 'poll_id';
    protected $vote = null;
    public $default_field = array(
        'poll_id' => 0,
        'poll_title' => '', //название голосования
        'poll_parent' => 0, //ресурс-родитель
        'poll_isactive' => 0, //голосоание активно
        'poll_begin' => 0, //дата начала
        'poll_end' => 0, //дата завершения
        'poll_rank' => 0, //позиция в списке,
        'poll_properties' => ''
    );
    protected $jsonFields = array(
        'poll_properties' //настройки голосования
    );

    public function __construct($modx){
        parent::__construct($modx);
        $this->vote = new Vote($modx);
        $this->log = new Log($modx);
    }

    public function delete($ids, $fire_events = null) {
        //при удалении голосований удаляем варианты и записи в логе
        $this->vote->deletePoll($ids);
        $this->log->deletePoll($ids);
        parent::delete($ids, $fire_events);
        $this->query("ALTER TABLE {$this->makeTable($this->table)} AUTO_INCREMENT = 1");
        return $this;
    }

    public function reset($ids) {
        //при обнулении голосований обнуляем голоса за варианты и удаляем записи в логе
        $this->vote->resetPoll($ids);
        $this->log->deletePoll($ids);
        return $this;
    }

    /**
     * Голосование за список вариантов
     * @param null $ids
     * @return $this|bool
     */
    public function vote($ids = null){
        if (!$ids) return false;
        $ids = $this->cleanIDs($ids);
        //увеличиваем число голосов за варианты
        $this->vote->vote($ids);
        //записываем в лог запись о голосовании
        $this->log->create(array(
            'poll'  =>  $this->getID(),
            'ip'    =>  $this->getUserIP(),
            'uid'   =>  $this->modx->getLoginUserID('web')
        ))->save();
        //устанавливаем куку
        $cookie = md5('poll'.$this->getID());
        setcookie($cookie, rand(), strtotime($this->get('poll_end')), '/');
        return $this;
    }

    /**
     * Получение вариантов для голосования
     * @return array
     */
    public function getVotes(){
        if (!$this->newDoc) {
            $out = array();
            $result = $this->query("SELECT * FROM {$this->modx->getFullTableName('sp_votes')} WHERE `vote_poll`={$this->getID()} ORDER BY `vote_rank` DESC");
            while ($row = $this->modx->db->getRow($result)) {
                $out[] = $row;
            }
            $total = array_sum(array_column($out,'vote_value'));
            foreach ($out as &$vote) {
                $vote['percent'] = $total ? round(100*$vote['vote_value']/$total,2) : 0;
                $vote['total_votes'] = $total;
            }
            return array('total'=>$total,'votes'=>$out);
        }
    }

    public function save($fire_events = null, $clearCache = false) {
        if ($this->newDoc) {
            $q = $this->query("SELECT count(`poll_id`) FROM {$this->makeTable($this->table)} WHERE `poll_parent`={$this->get('poll_parent')}");
            $this->field['poll_rank'] = $this->modx->db->getValue($q);
        }
        return parent::save();
    }
}