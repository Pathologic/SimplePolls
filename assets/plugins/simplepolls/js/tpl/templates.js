!function(){var l=Handlebars.template,n=Handlebars.templates=Handlebars.templates||{};n.addPoll=l({1:function(){return" checked"},compiler:[6,">= 2.0.0-beta.1"],main:function(l,n,e,a){var t,r=this.lambda,o=this.escapeExpression,i=n.helperMissing,p='<form id="createPollForm" style="padding:10px;">\r\n    <input type="hidden" name="poll_parent" value="'+o(r(null!=(t=null!=l?l.data:l)?t.poll_parent:t,l))+'">\r\n    <input type="hidden" name="poll_id" value="'+o(r(null!=(t=null!=l?l.data:l)?t.poll_id:t,l))+'">\r\n    <div class="form-group">\r\n        <label for="poll_title">Название голосования</label>\r\n        <input type="text" id="poll_title" name="poll_title" style="width:315px;" value="'+o(r(null!=(t=null!=l?l.data:l)?t.poll_title:t,l))+'">\r\n    </div>\r\n    <table class="form-group">\r\n        <tr>\r\n            <td>\r\n                <label for="poll_begin">Начало голосования</label>\r\n                <input type="text" id="poll_begin" name="poll_begin" style="width:158px;" value="'+o(r(null!=(t=null!=l?l.data:l)?t.poll_begin:t,l))+'">\r\n            </td>\r\n            <td>\r\n                <label for="poll_end">Конец голосования</label>\r\n                <input type="text" id="poll_end" name="poll_end" style="width:158px" value="'+o(r(null!=(t=null!=l?l.data:l)?t.poll_end:t,l))+'">\r\n            </td>\r\n        </tr>\r\n    </table>\r\n    <div class="form-group">\r\n    <label for="max_votes">Выбор вариантов</label>\r\n    <input type="text" name="max_votes" id="max_votes" value="'+o(r(null!=(t=null!=(t=null!=l?l.data:l)?t.poll_properties:t)?t.max_votes:t,l))+'" style="width:50px">\r\n    </div>\r\n    <label for="poll_isactive" class="form-group">Активно:\r\n        <input type="checkbox" name="poll_isactive" value="1"';return t=(n.ifCond||l&&l.ifCond||i).call(l,null!=(t=null!=l?l.data:l)?t.poll_isactive:t,"1",{name:"ifCond",hash:{},fn:this.program(1,a),inverse:this.noop,data:a}),null!=t&&(p+=t),p+='>\r\n    </label>\r\n    <label for="users_only" class="form-group">Только для пользователей:\r\n        <input type="checkbox" name="users_only" value="1"',t=(n.ifCond||l&&l.ifCond||i).call(l,null!=(t=null!=(t=null!=l?l.data:l)?t.poll_properties:t)?t.users_only:t,1,{name:"ifCond",hash:{},fn:this.program(1,a),inverse:this.noop,data:a}),null!=t&&(p+=t),p+='>\r\n    </label>\r\n    <label for="hide_results" class="form-group">Тайное голосование:\r\n        <input type="checkbox" name="hide_results" value="1"',t=(n.ifCond||l&&l.ifCond||i).call(l,null!=(t=null!=(t=null!=l?l.data:l)?t.poll_properties:t)?t.hide_results:t,1,{name:"ifCond",hash:{},fn:this.program(1,a),inverse:this.noop,data:a}),null!=t&&(p+=t),p+">\r\n    </label>\r\n</form>\r\n"},useData:!0})}();