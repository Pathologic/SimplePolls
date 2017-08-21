<?php namespace SimplePolls;
include_once (MODX_BASE_PATH . 'assets/plugins/simplepolls/model/poll.php');
include_once (MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');

/**
 * Class PollController
 * @package SimplePolls
 */
class PollController {
    protected $modx = null;
    protected $poll = null;
    public $dlParams = array(
        "controller"    =>  "dl_simplepolls",
        "table"         =>  "sp_polls",
        "selectFields"  =>  "c.*,IFNULL(`v`.`poll_votes`,0) AS `poll_votes`",
        "idField"       =>  "poll_id",
        "api"           =>  1,
        "idType"        =>  "documents",
        "ignoreEmpty"   =>  1,
        "JSONformat"    =>  "new",
        "display"       =>  10,
        "offset"        =>  0,
        "dir"           =>  "assets/plugins/simplepolls/controller/dl/"
    );

    /**
     * PollController constructor.
     * @param \DocumentParser $modx
     */
    public function __construct (\DocumentParser $modx) {
        $this->modx = $modx;
        $this->poll = new Poll($modx);
    }

    /**
     * @return array
     */
    public function create() {
        $rid = (int)$_REQUEST['poll_parent'];
        if (!$rid) return array('success'=>false);
        $begin = \DateTime::createFromFormat('d.m.Y H:i', $_REQUEST['poll_begin']);
        $end = \DateTime::createFromFormat('d.m.Y H:i', $_REQUEST['poll_end']);
        if (!$begin) $begin = new \DateTime();
        if (!$end) {
            $end = new \DateTime();
            $end->add(new \DateInterval('P1M'));
        }
        if ($begin > $end) list($begin,$end) = array($end,$begin);
        $active = (int)isset($_REQUEST['poll_isactive']);
        $current = new \DateTime();
        if ($begin > $current || $current > $end) $active = 0;
        $fields = array(
            'poll_title' => \APIhelpers::e($_REQUEST['poll_title']),
            'poll_parent' => $rid,
            'poll_begin' => $begin->format('Y-m-d H:i'),
            'poll_end' => $end->format('Y-m-d H:i'),
            'poll_isactive' => $active,
            'poll_properties' => array(
                "max_votes"=>(int)$_REQUEST['max_votes'] > 0 ? (int)$_REQUEST['max_votes'] : 1,
                "users_only"=>(int)isset($_REQUEST['users_only']),
                "hide_results"=>(int)isset($_REQUEST['hide_results'])
            )
        );
        $out = $this->poll->create($fields)->save();
        if ($out) return array('success'=>true,'data'=>$this->poll->toArray());
    }

    /**
     * @return array
     */
    public function edit() {
        if (!isset($_REQUEST['poll_id'])) return array('success'=>false);
        $id = (int)$_REQUEST['poll_id'];
        $begin = \DateTime::createFromFormat('d.m.Y H:i', $_REQUEST['poll_begin']);
        $end = \DateTime::createFromFormat('d.m.Y H:i', $_REQUEST['poll_end']);
        if (!$begin) $begin = new \DateTime();
        if (!$end) {
            $end = new \DateTime();
            $end->add(new \DateInterval('P1M'));
        }
        if ($begin > $end) list($begin,$end) = array($end,$begin);
        $active = (int)isset($_REQUEST['poll_isactive']);
        $current = new \DateTime();
        if ($begin > $current || $current > $end) $active = 0;
        $fields = array(
            'poll_title' => $_REQUEST['poll_title'],
            'poll_begin' => $begin->format('Y-m-d H:i'),
            'poll_end' => $end->format('Y-m-d H:i'),
            'poll_isactive' => $active,
            'poll_properties' => array(
                "max_votes"=>(int)$_REQUEST['max_votes'] > 0 ? (int)$_REQUEST['max_votes'] : 1,
                "users_only"=>(int)isset($_REQUEST['users_only']),
                "hide_results"=>(int)isset($_REQUEST['hide_results'])
            )
        );
        $out = $this->poll->edit($id)->fromArray($fields)->save();
        if ($out) return array('success'=>true);
    }

    /**
     * @return array
     */
    public function remove()
    {
        $out = array();
        $ids = isset($_REQUEST['ids']) ? (string)$_REQUEST['ids'] : '';
        $out['success'] = false;
        if (!empty($ids)) {
            if ($this->poll->delete($ids)) {
                $out['success'] = true;
            }
        }
        return $out;
    }

    /**
     * @return array
     */
    public function reset()
    {
        $out = array();
        $ids = isset($_REQUEST['ids']) ? (string)$_REQUEST['ids'] : '';
        $out['success'] = false;
        if (!empty($ids)) {
            if ($this->poll->reset($ids)) {
                $out['success'] = true;
            }
        }
        return $out;
    }

    /**
     * @return string
     */
    public function listing() {
        $rid = isset($_REQUEST['rid']) ? (int)$_REQUEST['rid'] : 0;
        $this->dlParams['addWhereList'] = "`poll_parent`={$rid}";
        $this->dlParams['prepare'] = function($data){
            $data['poll_properties'] = \jsonHelper::jsonDecode($data['poll_properties']);
            $data['poll_begin']= date('d.m.Y H:i',strtotime($data['poll_begin']));
            $data['poll_end']= date('d.m.Y H:i',strtotime($data['poll_end']));
            return $data;
        };
        if (isset($_REQUEST['rows'])) $this->dlParams['display'] = (int)$_REQUEST['rows'];
        $offset = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
        $offset = $offset ? $offset : 1;
        $offset = $this->dlParams['display']*abs($offset-1);
        $this->dlParams['offset'] = $offset;
        if (isset($_POST['sort'])) {
            $this->dlParams['sortBy'] = preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['sort']);
        }
        if (isset($_POST['order']) && in_array(strtoupper($_POST['order']), array("ASC", "DESC"))) {
            $this->dlParams['sortDir'] = $_POST['order'];
        }
        return $this->modx->runSnippet("DocLister", $this->dlParams);
    }
}
