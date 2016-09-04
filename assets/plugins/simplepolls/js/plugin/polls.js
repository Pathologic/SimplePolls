var Poll = {};
var Vote = {};
(function($){
    Poll.init = function() {
        $('#SimplePolls').append('<table id="pollGrid" width="100%"></table>');
        $('#pollGrid').datagrid({
            url: SimplePolls.Config.url+'?rid='+SimplePolls.Config.rid,
            title: "Голосования",
            height:'750px',
            fitColumns:true,
            pagination:true,
            idField:'poll_id',
            singleSelect:true,
            striped:true,
            checkOnSelect:false,
            selectOnCheck:false,
            emptyMsg:'Голосания еще не созданы',
            columns: [[
                {field:'select',checkbox:true},
                {field:'poll_rank',hidden:true},
                {field:'poll_title',title:'Тема голосования',width:160,sortable:false,
                    formatter: function(value,row) {
                        return '['+row.poll_id+'] '+value
                            .replace(/&/g, '&amp;')
                            .replace(/>/g, '&gt;')
                            .replace(/</g, '&lt;')
                            .replace(/"/g, '&quot;');
                    }
                },
                {field:'poll_begin',title:'Начало',width:70,sortable:false},
                {field:'poll_end',title:'Конец',width:70,sortable:false},
                {field:'poll_isactive',title:'',width:44,fixed:true,sortable:false,
                    formatter: function(value,row){
                        if (row.poll_isactive == 1){
                            out = '<span style="font-size:12px;color:green;">&#127761;</span>';
                        } else {
                            out = '<span style="font-size:12px;color:red;">&#127761;</span>';
                        }
                        if (row.poll_properties.users_only ==1){
                            out += '<span style="font-size:14px;">&#128273;</span>';
                        }
                        if (row.poll_properties.hide_results ==0){
                            out += '<span style="font-size:14px;">&#9733;</span>';
                        }
                        return out;
                    }
                },
                {field:'poll_votes',title:'Голоса',width:60,fixed:true,sortable:false}
            ]],
            toolbar:[
                {
                    iconCls: 'icon-add',
                    text: 'Создать голосование',
                    handler: function(){Poll.create()}
                },{
                    iconCls: 'icon-remove',
                    text: 'Удалить голосование',
                    id: 'removePoll',
                    handler: function(){SimplePolls.deleteRecord($('#pollGrid'),'poll')}
                },{
                    iconCls: 'icon-clear',
                    text: 'Обнулить голосование',
                    id:'resetPoll',
                    handler: function(){SimplePolls.clearVotes($('#pollGrid'),'poll')}
                }
            ],
            view: detailview,
            detailFormatter: function(index, row) {
                return '<div style="padding:5px 5px 15px;">' +
                    '<p>Максимальное количество голосов: ' + row.poll_properties.max_votes + '; ' +
                    'Только для пользователей: ' + row.poll_properties.users_only + '; ' +
                    'Не показывать результаты до завершения: ' + row.poll_properties.hide_results + '</p>' +
                    '<table class="ddv"></table></div>'
                    ;
            },
            onExpandRow: function(parentIndex,row){
                var parent = $(this);
                var ddv = parent.datagrid('getRowDetail',parentIndex).find('table.ddv');
                ddv.edatagrid({
                    title:'Варианты для голосования',
                    emptyMsg:'Варианты еще не созданы',
                    url:SimplePolls.Config.url,
                    updateUrl: SimplePolls.Config.url + '?controller=vote&mode=edit',
                    saveUrl: SimplePolls.Config.url + '?controller=vote&mode=create',
                    queryParams: {
                        controller:'vote',
                        vote_poll:row.poll_id
                    },
                    fitColumns:true,
                    singleSelect:true,
                    height:'auto',
                    idField:'vote_id',
                    columns:[[
                        {field:'vote_title',title:'Название варианта',width:100,
                            editor:{
                                type:'text'
                            },
                            formatter:function(value,row) {
                                return value
                                    .replace(/&/g, '&amp;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/</g, '&lt;')
                                    .replace(/"/g, '&quot;');
                            }
                        },
                        {field: 'vote_image', title: 'Картинка', width: 100, fixed: true,
                            formatter: function(value,row){
                            return '<img style="width:80px;height:80px;padding:3px 0;" src="'+(value == '' ? SimplePolls.Config.siteUrl+SimplePolls.Config.noImage : SimplePolls.Config.thumbPrefix+value)+'">';
                            },
                            editor: {
                                type: 'imageBrowser',
                                options: {
                                    css: 'height:80px;width:80px;padding:3px 0;margin:0 auto;display:block;',
                                    thumb_prefix: SimplePolls.Config.thumbPrefix,
                                    noImage: SimplePolls.Config.siteUrl+SimplePolls.Config.noImage,
                                    browserUrl: SimplePolls.Config.fileBrowserUrl,
                                    opener: 'SimplePolls'
                                }
                            }
                        },
                        {field:'vote_value',title:'Голоса',fixed:true,width:60}
                    ]],
                    onResize:function(){
                        parent.edatagrid('fixDetailRowHeight',parentIndex);
                    },
                    onLoadSuccess:function(){
                        setTimeout(function(){
                            parent.edatagrid('fixDetailRowHeight',parentIndex);
                        },50);
                    },
                    toolbar:[
                        {
                            iconCls: 'icon-add',
                            text: 'Создать',
                            id:'createVote',
                            handler: function(){Vote.create(ddv,row.poll_id)}
                        },{
                            iconCls: 'icon-remove',
                            text: 'Удалить',
                            id: 'removeVote',
                            handler: function(){SimplePolls.deleteRecord(ddv,'vote')}
                        },{
                            iconCls: 'icon-clear',
                            text: 'Обнулить',
                            id:'resetVote',
                            handler: function(){SimplePolls.clearVotes(ddv,'vote')}
                        },{
                            iconCls: 'icon-edit',
                            text: 'Корректировать',
                            id:'resetVote',
                            handler: function(){Vote.correct(ddv)}
                        },
                        {
                            iconCls: 'icon-reload',
                            text: 'Обновить',
                            handler: function(){ddv.datagrid('reload')}
                        },'-',{
                            iconCls: 'icon-save',
                            text: 'Сохранить',
                            id:'saveVote',
                            handler: function(){ddv.edatagrid('saveRow')}
                        },{
                            iconCls: 'icon-cancel',
                            text: 'Отмена',
                            id:'cancelVote',
                            handler: function(){ddv.edatagrid('cancelRow')}
                        }
                    ],
                    onBeforeEdit: function (index, row) {
                        row.editing = true;
                        $('#saveVote,#cancelVote').linkbutton('enable');
                        $('#createVote,#removeVote,#resetVote').linkbutton('disable');
                        parent.datagrid('fixDetailRowHeight',parentIndex);
                    },
                    onAfterEdit: function (index, row) {
                        row.editing = false;
                        $('#saveVote,#cancelVote').linkbutton('disable');
                        $('#createVote,#removeVote,#resetVote').linkbutton('enable');
                        parent.edatagrid('fixDetailRowHeight',parentIndex);
                    },
                    onBeforeLoad: function() {
                        $(this).edatagrid('clearChecked');
                        $('#saveVote,#cancelVote').linkbutton('disable');
                        $('#createVote,#removeVote,#resetVote').linkbutton('enable');
                    },
                    onCancelEdit: function (index, row) {
                        row.editing = false;
                        $('#saveVote,#cancelVote').linkbutton('disable');
                        $('#createVote,#removeVote,#resetVote').linkbutton('enable');
                        parent.edatagrid('fixDetailRowHeight',parentIndex);
                    },
                    onSave:function(){
                        ddv.edatagrid('reload');
                        parent.edatagrid('fixDetailRowHeight',parentIndex);
                    }
                });
                parent.datagrid('fixDetailRowHeight',parentIndex);
            },
            onBeforeLoad: function() {
                $(this).edatagrid('clearChecked');
                //$('#resetPoll,#editPoll').hide();
            },
            onDblClickRow:function(){
                Poll.edit();
            }
        });
    };
    Poll.create = function() {
        $('<div id="addPollDialog"></div>').dialog({
            width:'360px',
            title: 'Новое голосование',
            buttons:[
                {
                    iconCls:'icon-save',
                    text:'Сохранить',
                    handler:function(){
                        $.post(
                            SimplePolls.Config.url + '?mode=create',
                            $('#createPollForm').serializeArray(),
                            function(data){
                                if (data.success) {
                                    $('#addPollDialog').dialog('destroy');
                                    $('#pollGrid').edatagrid('reload');
                                } else {
                                    $.messager.alert('Ошибка', data.message,'error',function () {
                                        $('#importDialog').dialog('close');
                                    })
                                }
                            },
                            'json'
                        ).fail(function() {
                            $.messager.alert('Ошибка', 'Произошла ошибка','error');
                        })
                    }
                },
                {
                    iconCls:'icon-cancel',
                    text:'Отмена',
                    handler:function(){
                        $('#addPollDialog').dialog('destroy');
                    }
                }
            ],
            onBeforeOpen: function() {
                var context = {
                    data:{
                        poll_title:'Новое голосование',
                        poll_parent:SimplePolls.Config.rid,
                        poll_begin:'',
                        poll_end:'',
                        poll_isactive:1,
                        poll_properties:{
                            max_votes:1,
                            users_only:0,
                            hide_results:0
                        }
                    }
                };
                var form = Handlebars.templates.addPoll(context);
                $(this).html(form);
                $('#poll_begin').datetimebox({
                    editable:false,
                    showSeconds:false
                });
                $('#poll_end').datetimebox({
                    editable:false,
                    showSeconds:false
                });
                $('#max_votes').numberspinner({
                    height:22,
                    min: 1,
                    max: 20,
                    editable: true
                });
                $(this).dialog('center');
            },
            onClose:function() {
                $(this).dialog('destroy');
            },
            modal: true
        });
    };
    Poll.edit = function() {
        $('<div id="addPollDialog"></div>').dialog({
            width:'360px',
            title: 'Редактировать голосование',
            buttons:[
                {
                    iconCls:'icon-save',
                    text:'Сохранить',
                    handler:function(){
                        $.post(
                            SimplePolls.Config.url + '?mode=edit',
                            $('#createPollForm').serializeArray(),
                            function(data){
                                if (data.success) {
                                    $('#addPollDialog').dialog('destroy');
                                    $('#pollGrid').edatagrid('reload');
                                } else {
                                    $.messager.alert('Ошибка', data.message,'error',function () {
                                        $('#addPollDialog').dialog('close');
                                    })
                                }
                            },
                            'json'
                        ).fail(function() {
                            $.messager.alert('Ошибка', 'Произошла ошибка','error');
                        })
                    }
                },
                {
                    iconCls:'icon-cancel',
                    text:'Отмена',
                    handler:function(){
                        $('#addPollDialog').dialog('destroy');
                    }
                }
            ],
            onBeforeOpen: function() {
                var context = {
                    data: $('#pollGrid').edatagrid('getSelected')
                };
                var form = Handlebars.templates.addPoll(context);
                $(this).html(form);
                $('#poll_begin').datetimebox({
                    editable:false,
                    showSeconds:false
                });
                $('#poll_end').datetimebox({
                    editable:false,
                    showSeconds:false
                });
                $('#max_votes').numberspinner({
                    height:22,
                    min: 1,
                    max: 20,
                    editable: true
                });
                $(this).dialog('center');
            },
            onClose:function() {
                $(this).dialog('destroy');
            },
            modal: true
        });
    };
    Vote.create = function(grid,parent){
        grid.edatagrid('addRow',{
            index:0,
            row:{
                vote_poll:parent,
                vote_image:'',
                vote_title:'Новый вариант',
                vote_value:0
            }
        });
    };
    Vote.correct = function(grid) {
        var ids = [];
        var options = grid.datagrid('options');
        var pkField = options.idField;
        var row = grid.edatagrid('getSelected');
        if (row) ids.push(row[pkField]);
        if (ids.length){
            $('<div id="correctVoteDialog" style="padding:10px;"><p>Укажите количество дополнительных голосов</p><input type="text" id="correct" value="10" style="width:70px;"></div>').dialog({
                width:'360px',
                title: 'Корректировать результаты',
                buttons:[
                    {
                        iconCls:'icon-save',
                        text:'Выполнить',
                        handler:function(){
                            $.post(
                                SimplePolls.Config.url,
                                {
                                    mode: 'correct',
                                    controller:'vote',
                                    ids:ids.join(),
                                    num:$('#correct').val()
                                },
                                function(data){
                                    if (data.success) {
                                        $('#correctVoteDialog').dialog('destroy');
                                        grid.datagrid('reload');
                                    } else {
                                        $.messager.alert('Ошибка', data.message,'error',function () {
                                            $('#correctVoteDialog').dialog('close');
                                        })
                                    }
                                },
                                'json'
                            ).fail(function() {
                                $.messager.alert('Ошибка', 'Произошла ошибка','error');
                            })
                        }
                    },
                    {
                        iconCls:'icon-cancel',
                        text:'Отмена',
                        handler:function(){
                            $('#correctVoteDialog').dialog('destroy');
                        }
                    }
                ],
                onBeforeOpen: function() {
                    $('#correct').numberspinner({
                        min: 1,
                        height:22,
                        max: null,
                        editable: true
                    });
                    $(this).dialog('center');
                },
                onClose:function() {
                    $(this).dialog('destroy');
                },
                modal: true
            });
        }
    }
})(jQuery);
