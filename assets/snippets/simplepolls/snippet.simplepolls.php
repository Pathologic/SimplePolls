<?php
$_params = array(
    'config'=>'polls:assets/snippets/simplepolls/config',
    'dir'=>'assets/snippets/simplepolls/controller/',
    'controller'=>'Polls',
    'formid'=>'poll'
);
return $modx->runSnippet('FormLister',array_merge($_params, $params));
