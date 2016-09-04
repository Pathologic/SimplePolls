<?php
include_once (MODX_BASE_PATH.'assets/snippets/DocLister/core/controller/onetable.php');
class dl_simplepollsDocLister extends onetableDocLister {
    //выборка голосований с подсчетом голосов
    protected function getDocList()
    {
        $out = array();
        $sanitarInIDs = $this->sanitarIn($this->IDs);
        if ($sanitarInIDs != "''" || $this->getCFGDef('ignoreEmpty', '0')) {
            $where = $this->getCFGDef('addWhereList', '');
            if ($where != '') {
                $where = array($where);
            }
            if ($sanitarInIDs != "''") {
                $where[] = "`{$this->getPK()}` IN ({$sanitarInIDs})";
            }

            if (!empty($where)) {
                $where = "WHERE " . implode(" AND ", $where);
            }
            $limit = $this->LimitSQL($this->getCFGDef('queryLimit', 0));
            $fields = "*, IFNULL(SUM(`vote_value`),0) AS `poll_votes`";
            $vTable = $this->modx->getFullTableName('sp_votes');
            $join = "LEFT JOIN {$vTable} ON `poll_id`=`vote_poll`";
            $group = "GROUP BY `poll_id`";
            $rs = $this->dbQuery("SELECT {$fields} FROM {$this->table} {$join} {$where} {$group} {$this->SortOrderSQL($this->getPK())} {$limit}");

            $rows = $this->modx->db->makeArray($rs);
            $out = array();
            foreach ($rows as $item) {
                $out[$item[$this->getPK()]] = $item;
            }
        }
        return $out;
    }
}