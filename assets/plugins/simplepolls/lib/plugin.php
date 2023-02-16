<?php namespace SimplePolls;
include_once (MODX_BASE_PATH . 'assets/lib/SimpleTab/plugin.class.php');

class Plugin extends \SimpleTab\Plugin {
    public $pluginName = 'SimplePolls';
    public $tpl = 'assets/plugins/simplepolls/tpl/simplepolls.tpl';
    public $emptyTpl = 'assets/plugins/simplepolls/tpl/empty.tpl';
    public $jsListDefault = 'assets/plugins/simplepolls/js/scripts.json';
    public $jsListCustom = 'assets/plugins/simplepolls/js/custom.json';
    public $jsListEmpty = 'assets/plugins/simplepolls/js/empty.json';
    public $cssListDefault = 'assets/plugins/simplepolls/css/styles.json';
    public $cssListCustom = 'assets/plugins/simplepolls/css/custom.json';

    protected $checkId = false;

    public  function getTplPlaceholders() {
        $ph = array(
            'lang'			=>	$this->lang_attribute,
            'url'			=> 	$this->modx->config['site_url'].'assets/plugins/simplepolls/ajax.php',
            'site_url'		=>	$this->modx->config['site_url'],
            'manager_url'	=>	MODX_MANAGER_URL,
            'thumb_prefix' 	=> 	$this->modx->config['site_url'].'assets/plugins/simplepolls/ajax.php?controller=vote&mode=thumb&url=',
            'kcfinder_url'	=> 	MODX_MANAGER_URL."media/browser/mcpuk/browse.php?type=images",
            'w' 			=> 	isset($this->params['w']) ? $this->params['w'] : '80',
            'h' 			=> 	isset($this->params['h']) ? $this->params['h'] : '80',
            'noImage' 		=> 	'assets/plugins/simplepolls/css/noimg.jpg'
        );
        return array_merge($this->params,$ph);
    }
    public function checkTable() {
        $prefix = $this->modx->db->config['table_prefix'];
        $sql = "SHOW TABLES LIKE '{$prefix}sp_polls'";
        $flag = $this->modx->db->getRecordCount( $this->modx->db->query($sql));
        $sql = "SHOW TABLES LIKE '{$prefix}sp_votes'";
        $flag &= $this->modx->db->getRecordCount( $this->modx->db->query($sql));
        $sql = "SHOW TABLES LIKE '{$prefix}sp_log'";
        $flag &= $this->modx->db->getRecordCount( $this->modx->db->query($sql));
        return $flag;
    }

    public function createTable() {
        $prefix = $this->modx->db->config['table_prefix'];
        $sql = <<< OUT
CREATE TABLE IF NOT EXISTS `{$prefix}sp_polls` (
`poll_id` int(10) NOT NULL auto_increment,
`poll_title` varchar(255) NOT NULL,
`poll_isactive` int(1) NOT NULL default 1,
`poll_properties` varchar(255) NOT NULL,
`poll_parent` int(10) NOT NULL,
`poll_rank` int(10) NOT NULL,
`poll_begin` datetime NOT NULL,
`poll_end` datetime NOT NULL,
`poll_voters` int(10) NOT NULL,
PRIMARY KEY  (`poll_id`),
KEY `poll_isactive` (`poll_isactive`),
KEY `poll_parent` (`poll_parent`),
KEY `poll_rank` (`poll_rank`),
KEY `poll_begin` (`poll_begin`),
KEY `poll_end` (`poll_end`),
KEY `poll_voters` (`poll_voters`)
) ENGINE=InnoDB COMMENT='Polls table for SimplePolls plugin.';
OUT;
        $this->modx->db->query($sql);
        $sql = <<< OUT
CREATE TABLE IF NOT EXISTS `{$prefix}sp_votes` (
`vote_id` int(10) NOT NULL auto_increment,
`vote_image` varchar(255) NOT NULL,
`vote_title` varchar(255) NOT NULL,
`vote_poll` int(10) NOT NULL,
`vote_rank` int(10) NOT NULL,
`vote_value` int(10) NOT NULL,
`vote_blocked` int(1) NOT NULL,
PRIMARY KEY  (`vote_id`),
CONSTRAINT `sp_votes_ibfk_1` 
FOREIGN KEY (`vote_poll`) 
REFERENCES `{$prefix}sp_polls` (`poll_id`) 
ON DELETE CASCADE,
KEY `vote_rank` (`vote_rank`),
KEY `vote_poll` (`vote_poll`),
KEY `vote_value` (`vote_value`)
) ENGINE=InnoDB COMMENT='Votes table for SimplePolls plugin.';
OUT;
        $this->modx->db->query($sql);
        $sql = <<< OUT
CREATE TABLE IF NOT EXISTS `{$prefix}sp_log` (
`id` int(10) NOT NULL auto_increment,
`poll` int(10) NOT NULL,
`ip` varchar(255) NOT NULL,
`uid` int(10) NOT NULL,
`votedon` datetime NOT NULL,
`vote` TEXT NOT NULL,
`phone` varchar(255) NOT NULL,
PRIMARY KEY  (`id`),
CONSTRAINT `sp_log_ibfk_1` 
FOREIGN KEY (`poll`) 
REFERENCES `{$prefix}sp_polls` (`poll_id`) 
ON DELETE CASCADE,
KEY `poll` (`poll`),
KEY `ip` (`ip`),
KEY `uid` (`uid`),
KEY `votedon` (`votedon`),
KEY `phone` (`phone`)
) ENGINE=InnoDB COMMENT='Log table for SimplePolls plugin.';
OUT;
        $this->modx->db->query($sql);
        return true;
    }
}
