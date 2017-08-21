<?php namespace SimplePolls;
include_once (MODX_BASE_PATH . 'assets/plugins/simplepolls/model/vote.php');
include_once(MODX_BASE_PATH.'assets/lib/APIHelpers.class.php');

/**
 * Class VoteController
 * @package SimplePolls
 */
class VoteController {
    protected $modx = null;
    protected $vote = null;
    public $dlParams = array(
        "controller"    =>  "onetable",
        "table"         =>  "sp_votes",
        "idField"       =>  "vote_id",
        "api"           =>  1,
        "idType"        =>  "documents",
        "ignoreEmpty"   =>  1,
        "parentField"   =>  "vote_poll",
        'JSONformat'    =>  "new",
        'orderBy'       =>  "vote_rank DESC",
    );

    /**
     * VoteController constructor.
     * @param \DocumentParser $modx
     */
    public function __construct (\DocumentParser $modx) {
        $this->modx = $modx;
        $this->vote = new Vote($modx);
    }

    /**
     * @return array
     */
    public function create() {
        $pollId =  isset($_REQUEST['vote_poll']) ? (int)$_REQUEST['vote_poll'] : 0;
        if (!$pollId) return array('success'=>false);
        $fields = array(
            'vote_poll' => $pollId,
            'vote_title' => \APIhelpers::e($_REQUEST['vote_title']),
            'vote_image' => \APIhelpers::e($_REQUEST['vote_image'])
        );
        $out = $this->vote->create($fields)->save();
        if ($out) return $this->vote->toArray();
    }

    /**
     * @return array
     */
    public function edit() {
        $id =  isset($_REQUEST['vote_id']) ? (int)$_REQUEST['vote_id'] : 0;
        if (!$id) return array('success'=>false);
        $fields = array(
            'vote_title' => $_REQUEST['vote_title'],
            'vote_image' => $_REQUEST['vote_image'],
            'vote_blocked' => (int)$_REQUEST['vote_blocked'] > 0 ? 1 : 0
        );
        $out = $this->vote->edit($id)->fromArray($fields)->save();
        if ($out) return $this->vote->toArray();
    }

    /**
     * @return array
     */
    public function correct() {
        $out = array();
        $id = isset($_REQUEST['ids']) && is_scalar($_REQUEST['ids']) ? (int)$_REQUEST['ids'] : 0;
        $num  = isset($_REQUEST['num']) && is_scalar($_REQUEST['num']) ? (int)$_REQUEST['num'] : 0;
        $this->vote->correct($id, $num);
        $out['success'] = true;
        return $out;
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
            if ($this->vote->delete($ids)) {
                $out['success'] = true;
            }
        }
        return $out;
    }

    /**
     * @return string
     */
    public function listing() {
        $pollId =  isset($_REQUEST['vote_poll']) ? (int)$_REQUEST['vote_poll'] : 0;
        $this->dlParams['addWhereList'] = "`vote_poll`={$pollId}";
        return $this->modx->runSnippet("DocLister", $this->dlParams);
    }

    /**
     * @return array
     */
    public function reorder()
    {
        $out = array();
        $pollId =  isset($_REQUEST['vote_poll']) ? (int)$_REQUEST['vote_poll'] : 0;
        $source = $_POST['source'];
        $target = $_POST['target'];
        $point = $_POST['point'];
        $orderDir = $_POST['orderDir'];
        $rows = $this->vote->reorder($source, $target, $point, $pollId, $orderDir);

        if ($rows) {
            $out['success'] = true;
        } else {
            $out['success'] = false;
        }

        return $out;
    }

    /**
     *
     */
    public function thumb()
    {
        include_once(MODX_BASE_PATH.'assets/lib/Helpers/FS.php');
        $fs = \Helpers\FS::getInstance();
        $url = $_REQUEST['url'];
        $w = $h = 80;
        $thumbOptions = "w={$w}&h={$h}&far=C&bg=FFFFFF";
        $thumbsCache = 'assets/.spThumbs/';

        $file = MODX_BASE_PATH . $thumbsCache . $url;
        if ($fs->checkFile($file)) {
            $info = getimagesize($file);
            if ($w != $info[0] || $h != $info[1]) {
                @$this->vote->makeThumb($thumbsCache, $url, $thumbOptions);
            }
        } else {
            @$this->vote->makeThumb($thumbsCache, $url, $thumbOptions);
        }
        session_start();
        header("Cache-Control: private, max-age=10800, pre-check=10800");
        header("Pragma: private");
        header("Expires: " . date(DATE_RFC822, strtotime(" 360 day")));
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == filemtime($file))) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT', true, 304);
            $this->isExit = true;
            return;
        }
        header("Content-type: image/jpeg");
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT');
        ob_clean();
        readfile($file);
    }
}
