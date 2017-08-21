var Poll = {};
var Vote = {};
(function($){
    Poll.init = function() {
        $('#SimplePolls').append('<table id="pollGrid" width="100%"></table>');
        $('#pollGrid').datagrid({
            url: SimplePolls.Config.url+'?rid='+SimplePolls.Config.rid,
            title: "Голосования",
            fitColumns:true,
            pagination:true,
            idField:'poll_id',
            singleSelect:true,
            striped:true,
            checkOnSelect:false,
            selectOnCheck:false,
            emptyMsg:'Голосания еще не созданы',
            sortName: 'poll_rank',
            sortOrder: 'DESC',
            columns: [[
                {field:'select',checkbox:true},
                {field:'poll_title',title:'Тема голосования',width:160,sortable:false,
                    formatter: function(value,row) {
                        return '['+row.poll_id+'] '+value
                            .replace(/&/g, '&amp;')
                            .replace(/>/g, '&gt;')
                            .replace(/</g, '&lt;')
                            .replace(/"/g, '&quot;');
                    }
                },
                {field:'poll_begin',title:'Начало',width:120,fixed:true,sortable:true},
                {field:'poll_end',title:'Конец',width:120,fixed:true,sortable:true},
                {field:'poll_isactive',title:'',width:48,fixed:true,sortable:true,
                    formatter: function(value,row){
                        if (row.poll_isactive == 1){
                            out = '<span class="fa fa-circle" style="color:green;" title="Активно">&nbsp;</span>';
                        } else {
                            out = '<span class="fa fa-circle" style="font-size:12px;color:red;" title="Завершено">&nbsp;</span>';
                        }
                        if (row.poll_properties.users_only ==1){
                            out += '<span class="fa fa-key" title="Только для пользователей">&nbsp;</span>';
                        }
                        if (row.poll_properties.hide_results ==0){
                            out += '<span class="fa fa-star" title="Открытое голосование">&nbsp;</span>';
                        }
                        return out;
                    }
                },
                {field:'poll_votes',title:'Голоса/Участники',align:'center',width:120,fixed:true,sortable:true,
                    formatter: function(value,row){
                        return row.poll_votes + '/' + row.poll_voters;
                    }
                }
            ]],
            toolbar:[
                {
                    iconCls: 'fa fa-file',
                    text: 'Создать голосование',
                    handler: function(){Poll.create()}
                },{
                    iconCls: 'fa fa-trash',
                    text: 'Удалить голосование',
                    id: 'removePoll',
                    handler: function(){SimplePolls.deleteRecord($('#pollGrid'),'poll')}
                },{
                    iconCls: 'fa fa-undo',
                    text: 'Обнулить голосование',
                    id:'resetPoll',
                    handler: function(){SimplePolls.clearVotes($('#pollGrid'),'poll')}
                }
            ],
            view: detailview,
            detailFormatter: function(index, row) {
                return '<div style="padding:5px 5px 15px;">' +
                    '<p>Максимальное количество голосов: ' + row.poll_properties.max_votes + '; ' +
                    (row.poll_properties.users_only ? 'Только для пользователей' + '; ' : '') +
                    (row.poll_properties.hide_results ? 'Не показывать результаты до завершения' : '') +                          '</p><table class="ddv" id="child'+row.poll_id+'"></table></div>';
            },
            onExpandRow: function(parentIndex,row){
                var parent = $(this);
                var poll_id = row.poll_id;
                var ddv = $('#child'+poll_id);
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
                        {field:'vote_rank', hidden:true},
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
                        {field: 'vote_image', title: 'Картинка', width: 88, fixed: true,
                            formatter: function(value){
                            return '<img style="width:80px;height:80px;" src="'+(value == '' ? SimplePolls.Config.siteUrl+SimplePolls.Config.noImage : SimplePolls.Config.thumbPrefix+value)+'">';
                            },
                            editor: {
                                type: 'imageBrowser',
                                options: {
                                    css: 'height:80px;width:80px;margin:0 auto;display:block;',
                                    thumb_prefix: SimplePolls.Config.thumbPrefix,
                                    noImage: SimplePolls.Config.siteUrl+SimplePolls.Config.noImage,
                                    browserUrl: SimplePolls.Config.fileBrowserUrl,
                                    opener: 'SimplePolls'
                                }
                            }
                        },
                        {field:'vote_value',title:'Голоса',align:'center',fixed:true,width:60},
                        {field:'vote_blocked',align:'center',title:'Блок',width:44,fixed:true,sortable:false,
                            formatter:function(value){
                                if (value == 1) {
                                    return '<span style="color:red;">Да</span>';
                                }
                                else {
                                    return 'Нет'
                                }
                            },
                            editor:{
                                type:'checkbox',
                                options:{
                                    on: 1,
                                    off: 0
                                }
                            }
                        }
                    ]],
                    onResize:function(){
                        parent.edatagrid('fixDetailRowHeight',parentIndex).edatagrid('fixRowHeight',parentIndex);
                    },
                    onLoadSuccess:function(){
                        $(this).datagrid('enableDnd');
                        setTimeout(function(){
                            parent.edatagrid('fixDetailRowHeight',parentIndex).edatagrid('fixRowHeight',parentIndex);
                        },50);
                    },
                    toolbar:[
                        {
                            iconCls: 'fa fa-file',
                            text: 'Создать вариант',
                            id:'createVote',
                            handler: function(){Vote.create(ddv,row.poll_id)}
                        },{
                            iconCls: 'fa fa-trash',
                            text: 'Удалить',
                            id: 'removeVote',
                            handler: function(){SimplePolls.deleteRecord(ddv,'vote')}
                        },{
                            iconCls: 'fa fa-edit',
                            text: 'Корректировать',
                            id:'resetVote',
                            handler: function(){Vote.correct(ddv)}
                        },
                        {
                            iconCls: 'fa fa-refresh',
                            text: 'Обновить',
                            handler: function(){ddv.datagrid('reload')}
                        },'-',{
                            iconCls: 'fa fa-check',
                            text: 'Сохранить',
                            id:'saveVote',
                            handler: function(){ddv.edatagrid('saveRow')}
                        },{
                            iconCls: 'fa fa-ban',
                            text: 'Отмена',
                            id:'cancelVote',
                            handler: function(){ddv.edatagrid('cancelRow')}
                        }
                    ],
                    onBeforeEdit: function (index, row) {
                        row.editing = true;
                        $('#saveVote,#cancelVote').linkbutton('enable');
                        $('#createVote,#removeVote,#resetVote').linkbutton('disable');
                        parent.edatagrid('fixDetailRowHeight',parentIndex).edatagrid('fixRowHeight',parentIndex);
                    },
                    onAfterEdit: function (index, row) {
                        row.editing = false;
                        $('#saveVote,#cancelVote').linkbutton('disable');
                        $('#createVote,#removeVote,#resetVote').linkbutton('enable');
                        parent.edatagrid('fixDetailRowHeight',parentIndex).edatagrid('fixRowHeight',parentIndex);
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
                        parent.edatagrid('fixDetailRowHeight',parentIndex).edatagrid('fixRowHeight',parentIndex);
                    },
                    onSave:function(){
                        $(this).edatagrid('reload');
                        parent.edatagrid('fixDetailRowHeight',parentIndex).edatagrid('fixRowHeight',parentIndex);
                    },
                    onBeforeDrag: function (row) {
                        $(this).parent().find('tr.datagrid-row').addClass('droppable');
                        if (!row.editing) {
                            $('body').css('overflow-x', 'hidden');
                            $('.datagrid-body',$(this)).css('overflow-y', 'hidden');
                        } else {
                            return false;
                        }
                    },
                    onBeforeDrop: function (targetRow, sourceRow, point) {
                        var grid = $(this);
                        $('body').css('overflow-x', 'auto');
                        $('.datagrid-body',grid).css('overflow-y', 'auto');
                        this.targetRow = targetRow;
                        this.targetRow.index = tgt = grid.edatagrid('getRowIndex', targetRow);
                        this.sourceRow = sourceRow;
                        this.sourceRow.index = src = grid.edatagrid('getRowIndex', sourceRow);
                        this.point = point;
                        dif = tgt - src;
                        if ((point == 'bottom' && dif == -1) || (point == 'top' && dif == 1)) return false;
                    },
                    onDrop: function (targetRow, sourceRow, point) {
                        var grid = $(this);
                        var idField = 'vote_id';
                        var indexField = 'vote_rank';
                        var indexName = 'vote_rank';
                        var orderDir = 'desc';
                        src = this.sourceRow.index;
                        tgt = this.targetRow.index;
                        var data = {
                            'controller':'vote',
                            'target':{},
                            'source':{},
                            'point': point,
                            'vote_poll': poll_id,
                            'orderDir': 'desc'
                        };
                        data['target'][idField] = targetRow[idField];
                        data['target'][indexField] = targetRow[indexField];
                        data['source'][idField] = sourceRow[idField];
                        data['source'][indexField] = sourceRow[indexField];

                        $.ajax({
                            url: SimplePolls.Config.url+'?mode=reorder',
                            type: 'post',
                            dataType: 'json',
                            data: data
                        }).done(function (response) {
                            if (!response.success) {
                                $.messager.alert('Ошибка', 'Произошла ошибка','error');
                                grid.edatagrid('reload');
                            } else {
                                rows = grid.edatagrid('getRows');
                                if (tgt < src) {
                                    rows[tgt][indexName] = targetRow[indexName];
                                    for (var i = tgt; i <= src; i++) {
                                        rows[i][indexName] = rows[i - 1] != undefined ? rows[i - 1][indexName] - (orderDir == 'desc' ? 1 : -1) : rows[i][indexName];
                                        grid.edatagrid('refreshRow', i);
                                    }
                                } else {
                                    rows[tgt][indexName] = targetRow[indexName];
                                    for (var i = tgt; i >= src; i--) {
                                        rows[i][indexName] = rows[i + 1] != undefined ? parseInt(rows[i + 1][indexName]) + (orderDir == 'desc' ? 1 : -1) : rows[i][indexName];
                                        grid.edatagrid('refreshRow', i);
                                    }
                                }
                            }
                        }).fail(function() {
                            $.messager.alert('Ошибка', 'Произошла ошибка','error');
                        });
                    }
                });
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
                    iconCls:'btn-green fa fa-check',
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
                    iconCls:'fa fa-ban btn-red',
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
                    iconCls:'fa fa-check btn-green',
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
                    iconCls:'fa fa-ban btn-red',
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
                        iconCls:'fa fa-check btn-green',
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
                        iconCls:'fa fa-ban btn-red',
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
