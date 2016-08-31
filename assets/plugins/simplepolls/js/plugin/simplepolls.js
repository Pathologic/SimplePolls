var SimplePolls = {};
(function($){
    $.fn.datebox.defaults.formatter =function(date){
        if (!date){return '';}
        return ('0' + date.getDate()).slice(-2) + '.'
            + ('0' + (date.getMonth()+1)).slice(-2) + '.'
            + date.getFullYear();
    };
    $.fn.datebox.defaults.parser=function(s){
        if (!s) return new Date();
        var ss = (s.split('.'));
        var y = parseInt(ss[2],10);
        var m = parseInt(ss[1],10);
        var d = parseInt(ss[0],10);
        if (!isNaN(y) && !isNaN(m) && !isNaN(d)){
            return new Date(y,m-1,d);
        } else {
            return new Date();
        }
    };
    SimplePolls.deleteRecord = function(grid,controller) {
        var ids = [];
        var rows = grid.edatagrid('getChecked');
        var options = grid.datagrid('options');
        var pkField = options.idField;
        if (rows.length) {
            $.each(rows, function(i, row) {
                ids.push(row[pkField]);
            });
        }
        if (!ids.length) {
            var row = grid.edatagrid('getSelected');
            if (row) ids.push(row[pkField]);
        }
        if (ids.length){
            $.messager.confirm('Удаление','Вы уверены?',function(r){
                if (r){
                    $.post(
                        SimplePolls.Config.url,
                        {
                            mode: 'remove',
                            controller:controller,
                            ids:ids.join()
                        },
                        function(result){
                            if (result.success) {
                                grid.edatagrid('reload');
                            } else {
                                $.messager.alert('Ошибка', 'Не удалось удалить','error')
                            }
                        },
                        'json'
                    ).fail(function() {
                        $.messager.alert('Ошибка', 'Произошла ошибка','error');
                    });
                }
            });
        }
    };
    SimplePolls.clearVotes = function(grid,controller) {
        var ids = [];
        var rows = grid.edatagrid('getChecked');
        var options = grid.datagrid('options');
        var pkField = options.idField;
        if (rows.length) {
            $.each(rows, function(i, row) {
                ids.push(row[pkField]);
            });
        }
        if (!ids.length) {
            var row = grid.edatagrid('getSelected');
            if (row) ids.push(row[pkField]);
        }
        if (ids.length){
            $.messager.confirm('Сброс голосов','Вы уверены?',function(r){
                if (r){
                    $.post(
                        SimplePolls.Config.url,
                        {
                            mode: 'reset',
                            controller:controller,
                            ids:ids.join()
                        },
                        function(result){
                            if (result.success) {
                                grid.edatagrid('reload');
                            } else {
                                $.messager.alert('Ошибка', 'Не удалось удалить','error')
                            }
                        },
                        'json'
                    ).fail(function() {
                        $.messager.alert('Ошибка', 'Произошла ошибка','error');
                    });
                }
            });
        }
    };
    SimplePolls.init = function() {
        Handlebars.registerHelper('ifCond', function(v1, v2, options) {
            if(v1 == v2) {
                return options.fn(this);
            }
            return options.inverse(this);
        });
        Poll.init();
    };
})(jQuery);
