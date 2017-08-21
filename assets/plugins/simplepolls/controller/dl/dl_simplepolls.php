<?php
include_once (MODX_BASE_PATH.'assets/snippets/DocLister/core/controller/onetable.php');

/**
 * Class dl_simplepollsDocLister
 */
class dl_simplepollsDocLister extends onetableDocLister {
    /**
     * Генерация имени таблицы с префиксом и алиасом
     *
     * @param string $name имя таблицы
     * @param string $alias желаемый алиас таблицы
     * @return string имя таблицы с префиксом и алиасом
     */
    public function getTable($name, $alias = '')
    {
        $table = parent::getTable($name, $alias);
        if ($name == 'sp_polls') {
            $join = " LEFT JOIN (SELECT `vote_poll`, SUM(`vote_value`) AS `poll_votes` FROM {$this->getTable('sp_votes')} GROUP BY `vote_poll`) `v` ON `v`.`vote_poll` = `c`.`poll_id`";
            $table .= $join;
        }

        return $table;
    }
}
