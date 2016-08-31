<script type="text/javascript">
    (function($){
        SimplePolls.Config = {
            rid:[+id+],
            theme:'[+theme+]',
            loaded:false,
            url:'[+url+]',
            fileBrowserUrl:'[+kcfinder_url+]',
            siteUrl:'[+site_url+]',
            noImage:'[+noImage+]',
            thumbPrefix:'[+thumb_prefix+]'
        };
        $(window).load(function(){
            if ($('#SimplePollsTab')) {
                $('#SimplePollsTab.selected').trigger('click');
            }
        });
        $(document).ready(function(){
            $('#SimplePollsTab').click(function(){
                if (SimplePolls.Config.loaded) {
                    $('#pollGrid').datagrid('reload');
                } else {
                    SimplePolls.init();
                    SimplePolls.Config.loaded = true;
                    $(window).trigger('resize'); //stupid hack
                }
            })
        })
    })(jQuery)
</script>
<div id="SimplePolls" class="tab-page" style="width:100%;-moz-box-sizing: border-box; box-sizing: border-box;">
    <h2 class="tab" id="SimplePollsTab">[+tabName+]</h2>
</div>