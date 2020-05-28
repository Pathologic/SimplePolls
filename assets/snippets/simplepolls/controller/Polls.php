<?php namespace FormLister;

include_once(MODX_BASE_PATH . 'assets/snippets/FormLister/core/FormLister.abstract.php');
include_once(MODX_BASE_PATH . 'assets/plugins/simplepolls/model/poll.php');

/**
 * Class Polls
 * @package FormLister
 */
class Polls extends Core
{
    public $polls = []; //данные по голосованиям
    public $templates = [];
    public $poll;
    public $log = []; //для запрета голсований по кукам или ip
    public $userInfo = []; //инфа о пользователи
    public $captcha = [];

    /**
     * Core constructor.
     * @param  \DocumentParser  $modx
     * @param  array  $cfg
     */
    public function __construct(\DocumentParser $modx, array $cfg)
    {
        parent::__construct($modx, $cfg);
        $this->poll = new \SimplePolls\Poll($modx);
        $this->userInfo = [
            'uid' => (int) $this->modx->getLoginUserID('web'),
            'ip'  => $this->poll->getUserIP()
        ];
        $this->setPolls();
        $this->config->setConfig([
            'formTpl' => $this->getCFGDef('tpl')
        ]);
    }

    /**
     * Сценарий работы
     * Если форма отправлена, то проверяем данные
     * Если проверка успешна, то обрабатываем данные
     * Выводим шаблон
     *
     * @return string
     */
    public function render()
    {
        if ($this->isSubmitted()) {
            $this->process();
        }
        if (!empty($this->polls)) {
            $this->setPlaceholder('polls', $this->renderPolls());
            $out = $this->renderForm();
        } else {
            $out = '';
        }

        return $out;
    }

    /**
     * Загружает класс капчи
     */
    public function initCaptcha()
    {
        if ($captcha = $this->getCFGDef('captcha')) {
            $captcha = preg_replace('/[^a-zA-Z]/', '', $captcha);
            $className = ucfirst($captcha . 'Wrapper');
            $cfg = $this->config->loadArray($this->getCFGDef('captchaParams', []));
            foreach ($this->polls as $id => &$poll) {
                if (!$poll['permissions']['vote']) {
                    continue;
                }
                $cfg['id'] = 'poll' . $id;
                $_captcha = $this->loadModel($className,
                    MODX_BASE_PATH . "assets/snippets/FormLister/lib/captcha/{$captcha}/wrapper.php",
                    [$this->modx, $cfg]);

                if (!is_null($_captcha) && $_captcha instanceof CaptchaInterface) {
                    $_captcha->init();
                    $poll['captcha']['captcha'] = $_captcha->getPlaceholder();
                    if (!isset($poll['captcha']['error'])) {
                        $poll['captcha']['error'] = '';
                    }
                    $this->captcha['poll' . $id] = $_captcha;
                }
            }

        }
    }

    /**
     * @return bool
     */
    public function isSubmitted()
    {
        return parent::isSubmitted() && ($this->getField('results') === '' || $this->getField('finish') === '');
    }

    /**
     * Вывод голосований
     * @return string
     */
    public function renderPolls()
    {
        $out = '';
        foreach ($this->polls as $id => $poll) {
            $permissions = $poll['permissions'];
            //если разрешено показывать результаты, то используем шаблон mixedTpl или resultsTpl
            if ($permissions['results']) {
                $mode = $this->isMixedResultsMode() && $permissions['vote'] && $poll['poll_isactive'] ? 'mixed' : 'results';
            } else {
                $mode = 'votes';
            }
            //если голосование активно, разрешено для пользователя и не тайное, то показываем общее количество голосов и голосовавших
            $total = !$poll['properties']['hide_results'] && $poll['poll_isactive'] && $permissions['user'] ? $this->parseChunk($this->getCFGDef('totalTpl'),
                $poll) : '';
            //показываем время начала и конца голосования или сообщение о завершении голосования
            $info = $this->parseChunk($this->getCFGDef($poll['poll_isactive'] ? ($mode == 'results' ? '' : 'infoActiveTpl') : 'infoFinishedTpl'),
                $poll);
            $plh = [
                $mode        => $this->renderVotes($poll),
                'captcha'    => $this->renderCaptcha($poll),
                'info'       => $info,
                'total'      => $total,
                'status'     => $this->renderPollStatus($poll),
                'controls'   => $this->renderPollControls($poll),
            ];
            unset($poll['votes'], $poll['captcha']);
            $plh = array_merge($poll, $plh);
            $out .= $this->parseChunk($this->getCFGDef($mode . 'Tpl'), $plh);
        }

        return $out;
    }

    /**
     * @param  array  $poll
     * @return string
     */
    public function renderCaptcha($poll = [])
    {
        $out = '';
        if ($poll['permissions']['results'] && $this->isMixedResultsMode() && !$poll['poll_isactive']) {
            return $out;
        };
        if ($poll['permissions']['user'] && $poll['permissions']['vote'] && !empty($poll['captcha'])) {
            $out = $this->parseChunk($this->getCFGDef('captchaTpl'), $poll['captcha']);
        }

        return $out;
    }

    /**
     * Вывод вариантов для голосования
     * @param  array  $poll
     * @return null|string
     */
    public function renderVotes($poll = [])
    {
        $out = '';
        $tpl = '';
        $votes = $poll['votes'];
        $permissions = $poll['permissions'];
        //если нужно показывать результаты
        if ($permissions['results']) {
            do {
                //если запрет для пользователя, то показываем сообщение об этом
                if (!$permissions['user']) {
                    $out = $this->parseChunk($this->getCFGDef('resultsUsersOnlyTpl'), []);
                    break;
                }
                //если голосование активное, но тайное, то показываем сообщение об этом
                if (($poll['properties']['hide_results'] && $poll['poll_isactive'] && !$this->isMixedResultsMode()) || $this->getCFGDef('alwaysHideResults',
                        0) || ($poll['properties']['hide_results'] && $poll['poll_isactive'] && !$permissions['vote'])
                ) {
                    $out = $this->parseChunk($this->getCFGDef('resultsHiddenTpl'), $poll);
                    break;
                }
                //если голосовать разрешено, то определяем шаблон для вывода вариантов
                if ($permissions['vote'] && $this->isMixedResultsMode() && $poll['poll_isactive']) {
                    $tpl = $this->getCFGDef($poll['properties']['max_votes'] > 1 ? 'multipleMixedTpl' : 'singleMixedTpl');
                } else {
                    $tpl = $this->getCFGDef('resultsVoteTpl');
                }
            } while (false);
        } else { //если нужно показывать варианты для голосования
            do {
                //если запрет для пользователя, то показываем сообщение об этом
                if (!$permissions['user']) {
                    $out = $this->parseChunk($this->getCFGDef('votesUsersOnlyTpl'), []);
                    break;
                }
                $tpl = $this->getCFGDef($poll['properties']['max_votes'] > 1 ? 'multipleVoteTpl' : 'singleVoteTpl');
            } while (false);
        }
        if (!$out) {
            $sort = $this->getCFGDef('sortResults', 'desc');
            switch ($sort) {
                case 'asc':
                    uasort($votes, function ($a, $b) {
                        return $a['vote_value'] - $b['vote_value'];
                    });
                    break;
                case 'desc':
                    uasort($votes, function ($a, $b) {
                        return $b['vote_value'] - $a['vote_value'];
                    });
                    break;
            }
            foreach ($votes as $id => $vote) {
                $thumbSnippet = $this->getCFGDef('thumbSnippet');
                $thumbOptions = $this->getCFGDef('thumbOptions');
                if ($vote['vote_image'] && $thumbSnippet && $thumbOptions) {
                    $vote['thumb'] = $this->modx->runSnippet($thumbSnippet, [
                        'input'   => $vote['vote_image'],
                        'options' => $thumbOptions
                    ]);
                }
                $out .= $this->parseChunk($tpl, $vote);
            }
        }

        return $out;
    }

    /**
     * @param  array  $poll
     * @return null|string
     */
    public function renderPollStatus($poll = [])
    {
        $out = '';
        $permissions = $poll['permissions'];
        $param = '';
        if ($permissions['user']) {
            if (isset($_COOKIE[md5('poll' . $poll['poll_id'])]) || $this->getFormData('status')) {
                $param = $this->getCFGDef('statusCookieBlockTpl');
            } elseif (!$permissions['vote']) {
                $param = $this->getCFGDef('statusIpBlockTpl');
            }
            $out = $this->parseChunk($param, []);
        }

        return $out;
    }

    /**
     * Вывод кнопок
     * @param  array  $poll
     * @return null|string
     */
    public function renderPollControls($poll = [])
    {
        $out = '';
        $controlsTpl = $this->getCFGDef('controlsTpl');
        $voteBtnTpl = $this->getCFGDef('voteBtnTpl');
        $resultsBtnTpl = $this->getCFGDef('resultsBtnTpl');
        $permissions = $poll['permissions'];
        $plh = [];
        do {
            //Если запрет для пользователя или голосование не активно, то не показываем кнопки
            if (!$permissions['user'] || !$poll['poll_isactive']) {
                $plh['voteBtn'] = '';
                $plh['resultsBtn'] = '';
                break;
            }
            if ($poll['permissions']['results'] && $this->isMixedResultsMode() && !$poll['poll_isactive']) {
                $plh['voteBtn'] = '';
                break;
            };
            //Если можно голосовать, то показываем кнопку "Голосовать"
            if ($permissions['vote']) {
                $plh['voteBtn'] = $this->parseChunk($voteBtnTpl, []);
            }
            //Если голосование тайное, то не показываем кнопку "Результаты"
            if ($poll['properties']['hide_results']) {
                $plh['resultsBtn'] = '';
                break;
            }
            //Если не смотрим результаты, то показываем кнопку "Результаты"
            if (!$permissions['results']) {
                $plh['resultsBtn'] = $this->parseChunk($resultsBtnTpl, []);
            }
        } while (false);

        if (!empty($plh['resultsBtn']) || !empty($plh['voteBtn'])) {
            $out = $this->parseChunk($controlsTpl, $plh);
        }

        return $out;
    }

    /**
     * Для смешнного режима голосования нужно чтобы были заданы соответствующие шаблоны
     * @return bool
     */
    public function isMixedResultsMode()
    {
        return $this->getCFGDef('singleMixedTpl') || $this->getCFGDef('multipleMixedTpl');
    }

    /**
     * Получение информации о голосованиях
     */
    protected function setPolls()
    {
        $pollIds = $this->getCFGDef('pollIds');
        if (!isset($this->modx->documentIdentifier)) {
            $this->modx->documentIdentifier = 0;
        }
        //Если указаны id голосований
        if ($pollIds) {
            $pollIds = $this->config->loadArray($pollIds);
            $pollIds = implode(',', $this->poll->cleanIDs($pollIds));
            $where = "`poll_id` IN ({$pollIds})";
        } else {
            //если указаны id ресурсов
            $parents = (int) $this->getCFGDef('parent', $this->modx->documentIdentifier);
            $parents = implode(',', $this->poll->cleanIDs($parents));
            $where = "`poll_parent` IN ({$parents})";
        }
        $updateIds = [];
        $polls = [];

        $pollsTable = $this->modx->getFullTableName('sp_polls');

        $result = $this->modx->db->query("SELECT * FROM {$pollsTable} WHERE {$where} ORDER BY `poll_parent` ASC, `poll_rank` DESC");

        while ($row = $this->modx->db->getRow($result)) {
            $id = $row['poll_id'];
            $polls[$id] = $row;
            //добавляем поля, которые пригодятся при выводе голосований
            $polls[$id]['begin'] = $this->formatDate($row['poll_begin']);
            $polls[$id]['end'] = $this->formatDate($row['poll_end']);
            $polls[$id]['e.poll_title'] = \APIhelpers::e($row['poll_title']);
            $polls[$id]['properties'] = \jsonHelper::jsonDecode($row['poll_properties'], ['assoc' => 1]);
            $polls[$id]['total_votes'] = 0;
            $polls[$id]['captcha'] = [];
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
                    $total_votes = 0;
                    //Если в голосовании нет вариантов, то пропускаем его
                    if (empty($votes[$id]) || ($poll['properties']['users_only'] && $this->getCFGDef('hidePollsUsersOnly',
                                0))
                    ) {
                        unset($polls[$id]);
                        continue;
                    }
                    foreach ($votes[$id] as $vote) {
                        $total_votes += $vote['vote_value'];
                    }
                    $poll['total_votes'] = $total_votes;
                    foreach ($votes[$id] as $vote) {
                        //добавляем поля, которые пригодятся при выводе вариантов
                        $vote['thumb'] = '';
                        $vote['e.vote_title'] = \APIhelpers::e($vote['vote_title']);
                        $vote['total_votes'] = $poll['total_votes'];
                        $vote['percent'] = $poll['total_votes'] ? round(100 * $vote['vote_value'] / $poll['total_votes'],
                            2) : 0;
                        $poll['votes'][$vote['vote_id']] = $vote;
                    }
                    $cookie = md5('poll' . $id);
                    //если голосование обнулено, то удаляем куку
                    if (!$poll['total_votes'] && isset($_COOKIE[$cookie])) {
                        setcookie($cookie, null, time() - 3600, '/');
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
            $ids = implode(',', $updateIds);
            $this->modx->db->query("UPDATE {$pollsTable} SET `poll_isactive`=0 WHERE `poll_id` IN ({$ids})");
        }
    }

    /**
     * Получение вариантов для голосований
     * @param  array  $polls
     * @return array
     */
    protected function setVotes($polls = [])
    {
        if (!$polls) {
            return [];
        }
        $ids = implode(',', $this->poll->cleanIDs($polls));
        $vTable = $this->modx->getFullTableName('sp_votes');
        $result = $this->modx->db->query("SELECT * FROM {$vTable} WHERE `vote_poll` IN ({$ids}) ORDER BY `vote_rank` DESC");
        $out = [];
        while ($vote = $this->modx->db->getRow($result)) {
            $out[$vote['vote_poll']][$vote['vote_id']] = $vote;
        }

        return $out;
    }

    /**
     * Получение блокировок голосований
     */
    public function setLog()
    {
        $log = [];
        $mode = $this->getCFGDef('protection', 'cookie');
        foreach ($this->polls as $id => $poll) {
            //по умолчанию все активные голосования не заблокированы
            $log[$id] = $poll['poll_isactive'];
        }
        if ($mode == 'ip') {
            $ids = array_keys($this->polls);
            $ids = implode(',', $this->poll->cleanIDs($ids));
            //если пользователь не аноним, то смотрим, голосовал ли он раньше иначе проверяем по ip
            $condition = $this->userInfo['uid'] ? "`uid`={$this->userInfo['uid']}" : "`ip`='{$this->userInfo['ip']}' AND `uid`=0";
            $result = $this->modx->db->query("SELECT `poll`, COUNT(`poll`) AS `cnt` FROM {$this->poll->makeTable('sp_log')} WHERE `poll` IN ({$ids}) AND {$condition} GROUP BY `poll`");
            while ($row = $this->modx->db->getRow($result)) {
                if ($row['cnt'] >= $this->getCFGDef('maxVotesFromIp', 4)) {
                    $log[$row['poll']] = false;
                }
            }
        }
        foreach ($this->polls as $id => $poll) {
            //не блокировать, если нет куки или голоса обнулены
            $log[$id] &= $mode == 'ip' || !isset($_COOKIE[md5('poll' . $poll['poll_id'])]) || !$poll['total_votes'];
        }
        $this->log = $log;

    }

    /**
     *
     */
    public function setPermissions()
    {
        foreach ($this->polls as $id => &$poll) {
            //если голосование только для пользователей, то смотрим, авторизоан ли пользователь, иначе разрешаем
            $user = $poll['properties']['users_only'] ? (bool) $this->userInfo['uid'] : true;
            //разрешаем голосовать, если для голосования не установлена блокировка
            $vote = $this->log[$poll['poll_id']];
            //разрешаем показ результатов, если нажата кнопка и указано id голосования и такое голосование существует, или если голосование не активно или если установлена блокировка
            $results = (isset($_REQUEST['results']) && isset($_REQUEST['poll']) && $_REQUEST['poll'] == $id) || !$poll['poll_isactive'] || !$vote;
            $poll['permissions'] = [
                'user'    => $user,
                'vote'    => $vote,
                'results' => $results,
            ];
        }
    }

    /**
     * @param $value
     * @return false|string
     */
    protected function formatDate($value)
    {
        $format = $this->getCFGDef('dateFormat', 'd.m.Y H:i');

        return date($format, strtotime($value));
    }

    /**
     * Процесс голосования
     */
    public function process()
    {
        $poll = (int) $this->getField('poll');
        $vote = $this->getField('vote');
        $finish = $this->getField('finish');
        //выход, если не нажата кнопка "Голосовать" или нет id голосования или нет id варианта
        if ($finish !== '' || !$poll || !$vote) {
            return;
        }
        $votes = is_array($vote) ? $vote : [$vote];
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
        if (!empty($this->getCFGDef('captcha')) && !empty($this->captcha) && $flag) {
            $captcha = $this->captcha['poll' . $poll];
            $result = $captcha::validate($this, $this->getField($this->getCFGDef('captchaField', 'vericode')),
                $captcha);
            if ($result !== true) {
                $this->polls[$poll]['captcha']['error'] = $this->parseChunk($this->getCFGDef('errorTpl',
                    '@CODE:<span class="help-block">[+message+]</span>'),
                    ['message' => $result]);
            }
            $flag &= $result === true ? true : false;
        }
        //дополнительно проверяем ограничение по количеству вариантов для голосования
        if ($votes && $flag && (count($votes) <= $this->polls[$poll]['properties']['max_votes'])) {
            //если все в порядке, то засчитываем голоса и получаем обновленную информацию о вариантах для голосования
            $votes = $this->poll->edit($poll)->set('phone', $this->getField('logphone'))->vote($votes)->getVotes();
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
