<script type="text/javascript">
    (function($){
        SimplePolls.Config = {
            rid:[+id+],
            loaded:false,
            url:'[+url+]',
            fileBrowserUrl:'[+kcfinder_url+]',
            siteUrl:'[+site_url+]',
            noImage:'[+noImage+]',
            thumbPrefix:'[+thumb_prefix+]'
        };
        $('#documentPane').on('click','#SimplePollsTab',function(){
            if (SimplePolls.Config.loaded) {
                $('#pollGrid').edatagrid('reload');
                $(window).trigger('resize');
            } else {
                SimplePolls.init();
                SimplePolls.Config.loaded = true;
            }
        });
        $(window).on('load', function(){
            if ($('#SimplePollsTab')) {
                $('#SimplePollsTab.selected').trigger('click');
            }
        });
        $(window).on('resize',function(){
            if ($('#SimplePollsTab').hasClass('selected')) {
                clearTimeout(this.timeout);
                this.timeout = setTimeout(function () {
                    $('#SimplePolls').width($('body').width() - 60);
                    if (SimplePolls.Config.loaded) {
                        $('#pollGrid').datagrid('getPanel').panel('resize');
                    }
                }, 300);
            }
        })
})(jQuery)
</script>
<div id="SimplePolls" class="tab-page">
    <h2 class="tab" id="SimplePollsTab">[+tabName+]</h2>
</div>
