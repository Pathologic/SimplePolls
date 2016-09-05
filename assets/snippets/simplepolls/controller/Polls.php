<?php namespace FormLister;
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/FormLister.abstract.php');
include_once (MODX_BASE_PATH . 'assets/plugins/simplepolls/model/poll.php');

class Polls extends Core {
    public $polls = array(); //данные по голосованиям
    public $templates = array();
    public $poll = null;
    public $log = array(); //для запрета голсований по кукам или ip
    public $userInfo = array(); //инфа о пользователи
    public $captcha = array();

    public function __construct(\DocumentParser $modx, array $cfg)
    {
        parent::__construct($modx, $cfg);
        $this->poll = new \SimplePolls\Poll($modx);
        $this->userInfo = array(
            'uid'=>(int)$this->modx->getLoginUserID('web'),
            'ip'=>$this->poll->getUserIP()
        );
        $this->setPolls();
        $this->config->setConfig(array(
            'formTpl'=>$this->getCFGDef('tpl')
        ));
    }

    public function render(){
        if ($this->isSubmitted()) {
            $this->process();
        }
        $this->initCaptcha();
        if (!empty($this->polls)) {
            $this->setPlaceholder('polls', $this->renderPolls());
            $out = $this->renderForm();
        } else {
            $out = '';
        }
        return $out;
    }

    public function initCaptcha()
    {
        if ($captcha = $this->getCFGDef('captcha')) {
            $wrapper = MODX_BASE_PATH . "assets/snippets/FormLister/lib/captcha/{$captcha}/wrapper.php";
            if ($this->fs->checkFile($wrapper)) {
                include_once($wrapper);
                $wrapper = $captcha.'Wrapper';
                foreach ($this->polls as $id => &$poll) {
                    /** @var \modxCaptchaWrapper $captcha */
                    $captcha = new $wrapper ($this->modx, array(
                        'id'     => 'poll'.$id,
                        'width'  => $this->getCFGDef('captchaWidth', 100),
                        'height' => $this->getCFGDef('captchaHeight', 60),
                        'inline' => $this->getCFGDef('captchaInline', 1)
                    ));
                    $this->captcha[$id] = $captcha->getValue();
                    $poll['captcha']=$captcha->getPlaceholder();
                }
            }
        }
    }

    public function isSubmitted()
    {
        return $this->formid && ($this->getField('formid') === $this->formid) && ($this->getField('results') === '' || $this->getField('finish') === '');
    }

    /**
     * Вывод голосований
     * @return string
     */
    public function renderPolls() {
        $out = '';
        foreach ($this->polls as $id => $poll) {
            $permissions = $poll['permissions'];
            //если разрешено показывать результаты, то используем шаблон mixedTpl или resultsTpl
            if ($permissions['results']) {
                $mode = $this->isMixedResultsMode() && $permissions['vote'] ? 'mixed' : 'results';
            } else {
                $mode = 'votes';
            }
            //если голосование активно, разрешено для пользователя и не тайное, то показываем общее количество голосов и голосовавших
            $total = !$poll['properties']['hide_results'] && $poll['poll_isactive'] && $permissions['user'] ? $this->parseChunk($this->getCFGDef('totalTpl'),$poll) : '';
            //показываем время начала и конца голосования или сообщение о завершении голосования
            $info = $this->parseChunk($this->getCFGDef($poll['poll_isactive'] ? ($mode == 'results' ? '' : 'infoActiveTpl') : 'infoFinishedTpl'),$poll);
            $plh = array(
                $mode => $this->renderVotes($poll),
                'info' => $info,
                'total' => $total,
                'status' => $this->renderPollStatus($poll),
                'controls' => $this->renderPollControls($poll)
            );
            $plh = array_merge($poll,$plh);
            $out .= $this->parseChunk($this->getCFGDef($mode.'Tpl'),$plh);
        }
        return $out;
    }

    /**
     * Вывод вариантов для голосования
     * @param array $poll
     * @return null|string
     */
    public function renderVotes($poll= array()){
        $out = '';
        $tpl = '';
        $votes = $poll['votes'];
        $permissions = $poll['permissions'];
        //если нужно показывать результаты
        if ($permissions['results']) {
            do {
                //если запрет для пользователя, то показываем сообщение об этом
                if (!$permissions['user']) {
                    $out = $this->parseChunk($this->getCFGDef('resultsUsersOnlyTpl'),array());
                    break;
                }
                //если голосование активное, но тайное, то показываем сообщение об этом
                if ($poll['properties']['hide_results'] && $poll['poll_isactive']) {
                    $out = $this->parseChunk($this->getCFGDef('resultsHiddenTpl'),$poll);
                    break;
                }
                //если голосовать разрешено, то определяем шаблон для вывода вариантов
                if ($permissions['vote'] && $this->isMixedResultsMode()) {
                    $tpl = $this->getCFGDef($poll['properties']['max_votes'] > 1 ? 'multipleMixedTpl' : 'singleMixedTpl');
                } else {
                    $tpl = $this->getCFGDef('resultsVoteTpl');
                }
            } while (false);
        } else { //если нужно показывать варианты для голосования
            do {
                //если запрет для пользователя, то показываем сообщение об этом
                if (!$permissions['user']) {
                    $out = $this->parseChunk($this->getCFGDef('votesUsersOnlyTpl'),array());
                    break;
                }
                $tpl = $this->getCFGDef($poll['properties']['max_votes'] > 1 ? 'multipleVoteTpl' : 'singleVoteTpl');
            } while (false);
        }
        if (!$out) {
            $sort = $this->getCFGDef('sortResults','desc');
            switch ($sort) {
                case 'asc':
                    uasort($votes,function($a, $b) {
                        return $a['vote_value'] - $b['vote_value'];
                    });
                    break;
                case 'desc':
                    uasort($votes,function($a, $b) {
                        return $b['vote_value'] - $a['vote_value'];
                    });
                    break;
            }
            foreach ($votes as $id => $vote) {
                $thumbSnippet = $this->getCFGDef('thumbSnippet');
                $thumbOptions = $this->getCFGDef('thumbOptions');
                if ($thumbSnippet && $thumbOptions) {
                    $vote['thumb'] = $this->modx->runSnippet($thumbSnippet,array(
                        'input'=>$vote['vote_image'],
                        'options'=>$thumbOptions
                    ));
                }
                $out .= $this->parseChunk($tpl,$vote);
            }
        }
        return $out;
    }

    /**
     * @param array $poll
     * @return null|string
     */
    public function renderPollStatus($poll = array()) {
        $out = '';
        $permissions = $poll['permissions'];
        if(!$permissions['vote'] && $permissions['user']) {
            $param = $this->getCFGDef('protection') == 'cookie' || isset($_COOKIE[md5('poll'.$poll['poll_id'])]) ? $this->getCFGDef('statusCookieBlockTpl') : $this->getCFGDef('statusIpBlockTpl');
            $out = $this->parseChunk($param,array());
        }
        return $out;
    }

    /**
     * Вывод кнопок
     * @param array $poll
     * @return null|string
     */
    public function renderPollControls($poll = array()) {
        $controlsTpl = $this->getCFGDef('controlsTpl');
        $voteBtnTpl = $this->getCFGDef('voteBtnTpl');
        $resultsBtnTpl = $this->getCFGDef('resultsBtnTpl');
        $permissions = $poll['permissions'];
        $plh = array();
        do {
            //Если запрет для пользователя или голосование не активно, то не показываем кнопки
            if (!$permissions['user'] || !$poll['poll_isactive']) {
                $plh['voteBtn'] = '';
                $plh['resultsBtn'] = '';
                break;
            }
            //Если можно голосовать, то показываем кнопку "Голосовать"
            if ($permissions['vote']) {
                $plh['voteBtn'] = $this->parseChunk($voteBtnTpl,array());
            }
            //Если голосование тайное, то не показываем кнопку "Результаты"
            if ($poll['properties']['hide_results']) {
                $plh['resultsBtn'] = '';
                break;
            }
            //Если не смотрим результаты, то показываем кнопку "Результаты"
            if (!$permissions['results']) {
                $plh['resultsBtn'] =  $this->parseChunk($resultsBtnTpl,array());
            }
        } while (false);
        return $this->parseChunk($controlsTpl,$plh);
    }

    /**
     * Для смешнного режима голосования нужно чтобы были заданы соответствующие шаблоны
     * @return bool
     */
    public function isMixedResultsMode() {
        return $this->getCFGDef('singleMixedTpl') || $this->getCFGDef('multipleMixedTpl');
    }

    /**
     * Получение информации о голосованиях
     */
    protected function setPolls(){
        $pollIds = $this->getCFGDef('pollIds');
        //Если указаны id голосований
        if ($pollIds) {
            $pollIds = $this->config->loadArray($pollIds);
            $pollIds = implode(',',$this->poll->cleanIDs($pollIds));
            $where = "`poll_id` IN ({$pollIds})";
        } else {
        //если указаны id ресурсов
            $parents = (int)$this->getCFGDef('parent',$this->modx->documentIdentifier);
            $parents = implode(',',$this->poll->cleanIDs($parents));
            $where = "`poll_parent` IN ({$parents})";
        }
        $updateIds = array();
        $polls = array();

        $pollsTable = $this->modx->getFullTableName('sp_polls');

        $result = $this->modx->db->query("SELECT * FROM {$pollsTable} WHERE {$where} ORDER BY `poll_parent` ASC, `poll_rank` DESC");

        while ($row = $this->modx->db->getRow($result)) {
            $id = $row['poll_id'];
            $polls[$id] = $row;
            //добавляем поля, которые пригодятся при выводе голосований
            $polls[$id]['begin'] = $this->formatDate($row['poll_begin']);
            $polls[$id]['end'] = $this->formatDate($row['poll_end']);
            $polls[$id]['e.poll_title'] = \APIhelpers::e($row['poll_title']);
            $polls[$id]['properties'] = \jsonHelper::jsonDecode($row['poll_properties'], array('assoc' => 1));
            $polls[$id]['total_votes'] = 0;
            $polls[$id]['captcha'] = '';
            //выключаем завершенные голосования
            if (time() > strtotime($row['poll_end'])) {
                $updateIds[] = $id;
                $polls[$id]['poll_isactive'] = 0;
            }
        }
        if (!empty($polls)) {
            $votes = $this->setVotes(array_keys($polls));
            //Добавляем данные о вариантах
            if (!empty($votes)) {
                foreach ($polls as $id => &$poll) {
                    $poll['total_votes'] = array_sum(array_column($votes[$id],'vote_value'));
                    foreach ($votes[$id] as $vote) {
                        //добавляем поля, которые пригодятся при выводе вариантов
                        $vote['thumb'] = '';
                        $vote['e.vote_title'] = \APIhelpers::e($vote['vote_title']);
                        $vote['total_votes'] = $poll['total_votes'];
                        $vote['percent'] = $poll['total_votes'] ? round(100*$vote['vote_value']/$poll['total_votes'],2) : 0;
                         $poll['votes'][$vote['vote_id']] = $vote;
                    }
                    //Если в голосовании нет вариантов, то пропускаем его
                    if (empty($poll['votes']) || ($poll['properties']['users_only'] && $this->getCFGDef('hidePollsUsersOnly',0))) unset($polls[$id]);
                    $cookie = md5('poll' . $id);
                    //если голосование обнулено, то удаляем куку
                    if (!$poll['total_votes'] && isset($_COOKIE[$cookie])) {
                        setcookie($cookie, null, time()-3600, '/');
                    }
                }
            }
            $this->polls = $polls;
            //Добавляем информацию о блокировках
            $this->setLog();
            //Добавляем разрешения
            $this->setPermissions();
        }
        //Обновляем статус завершенных голосований
        if (!empty($updateIds)) {
            $ids = implode(',',$updateIds);
            $this->modx->db->query("UPDATE {$pollsTable} SET `poll_isactive`=0 WHERE `poll_id` IN ({$ids})");
        }
    }

    /**
     * Получение вариантов для голосований
     * @param array $polls
     * @return array
     */
    protected function setVotes($polls = array()){
        if (!$polls) return array();
        $ids = implode(',',$this->poll->cleanIDs($polls));
        $vTable = $this->modx->getFullTableName('sp_votes');
        $result = $this->modx->db->query("SELECT * FROM {$vTable} WHERE `vote_poll` IN ({$ids}) ORDER BY `vote_poll` ASC, `vote_rank` DESC");
        $votes = $this->modx->db->makeArray($result);
        $out = array();
        if (!empty($votes)) {
            foreach ($votes as $vote) {
                $out[$vote['vote_poll']][$vote['vote_id']] = $vote;
            }
        }
        return $out;
    }

    /**
     * Получение блокировок голосований
     */
    public function setLog() {
        $log = array();
        $mode = $this->getCFGDef('protection','cookie');
        foreach ($this->polls as $id => $poll) {
            //по умолчанию все активные голосования не заблокированы
            $log[$id] = $poll['poll_isactive'];
        }
        if ($mode == 'cookie') {
            foreach($this->polls as $id => $poll) {
                //не блокировать, если нет куки или голоса обнулены
                $log[$id] &= !isset($_COOKIE[md5('poll'.$poll['poll_id'])]) || !$poll['total_votes'];
            }
        } else {
            $ids = array_keys($this->polls);
            $ids = implode(',',$this->poll->cleanIDs($ids));
            //если пользователь не аноним, то смотрим, голосовал ли он раньше иначе проверяем по ip
            $condition = $this->userInfo['uid'] ? "`uid`={$this->userInfo['uid']}" : "`ip`='{$this->userInfo['ip']}' AND `uid`=0";
            $result = $this->modx->db->query("SELECT `poll` FROM {$this->poll->makeTable('sp_log')} WHERE `poll` IN ({$ids}) AND {$condition}");
            $result = $this->modx->db->getColumn('poll',$result);
            foreach ($result as $id) {
                $log[$id] = false;
            }
        }
        $this->log = $log;
    }

    public function setPermissions() {
        foreach ($this->polls as $id => &$poll) {
            //если голосование только для пользователей, то смотрим, авторизоан ли пользователь, иначе разрешаем
            $user = $poll['properties']['users_only'] ? (bool)$this->userInfo['uid'] : true;
            //разрешаем голосовать, если для голосования не установлена блокировка
            $vote = $this->log[$poll['poll_id']];
            //разрешаем показ результатов, если нажата кнопка и указано id голосования и такое голосование существует, или если голосование не активно или если установлена блокировка
            $results = (isset($_REQUEST['results']) && isset($_REQUEST['poll']) && $_REQUEST['poll'] == $id) || !$poll['poll_isactive'] || !$vote;
            $poll['permissions'] = array(
                'user'    => $user,
                'vote'    => $vote,
                'results' => $results,
            );
        }
    }

     protected function formatDate($value) {
        $format = $this->getCFGDef('dateFormat','d.m.Y H:i');
        return date($format,strtotime($value));
    }

    /**
     * Процесс голосования
     */
    public function process()
    {
        $poll = (int)$this->getField('poll');
        $vote = $this->getField('vote');
        $finish = $this->getField('finish');
        //выход, если не нажата кнопка "Голосовать" или нет id голосования или нет id варианта
        if ($finish !== '' || !$poll || !$vote) return;
        $votes = is_array($vote) ? $vote : array($vote);
        $sn = session_name();
        $permissions = $this->polls[$poll]['permissions'];
        //проверяем разрешено ли голосовать
        $flag = $permissions['vote'] &&
                $permissions['user'] &&
                //нет ли попытки проголосовать не с сайта
                isset($_COOKIE[$sn]) &&
                //есть ли для голосования указанные варианты
                count(array_intersect_key(array_flip($votes), $this->polls[$poll]['votes'])) === count($votes);
        //если нужно, то проверяем капчу
        if (!empty($this->captcha)) $flag &= ($this->getField($this->getCFGDef('captchaField','vericode')) == $this->captcha[$poll]);
        //дополнительно проверяем ограничение по количеству вариантов для голосования
        if($votes && $flag && (count($votes) <= $this->polls[$poll]['properties']['max_votes'])) {
            //если все в порядке, то засчитываем голоса и получаем обновленную информацию о вариантах для голосования
            $votes = $this->poll->edit($poll)->vote($votes)->getVotes();
            //обновляем данные о голосованиях
            //ставим для голосования блокировку
            $this->log[$poll] = false;
            //обновляем данные о вариантах
            $this->polls[$poll]['votes'] = $votes['votes'];
            //обновляем общее число голосов
            $this->polls[$poll]['total_votes'] = $votes['total'];
            //обновляем общее число участников
            $this->polls[$poll]['poll_voters'] += 1;
            //запрещаем голосовать
            $this->polls[$poll]['permissions']['vote'] = false;
            //разрешаем смотреть результаты
            $this->polls[$poll]['permissions']['results'] = true;
            $this->setFormStatus(true);
        }
    }
}