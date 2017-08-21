## SimplePolls - компонент для голосований и опросов
* голосования добавляются к документам;
* можно разрешать голосования только для зарегистрированных пользователей;
результаты голосования могут быть скрыты до его завершения;
* можно голосовать за один, можно за несколько вариантов;
* к вариантам можно прикреплять картинки и выводить их с генерацией превью;
* можно обнулять голосования и накручивать голоса, приостанавливать голосование за какой-либо вариант (пользователи голосуют, но количество голосов не увеличивается);
* можно использовать капчи;
* можно голосовать при просмотре результатов;
* можно блокировать повторные голоса по кукам или по ip.

Для каждого голосования можно настроить время начала и завершения, максимальное количество вариантов для голосования одним участником, доступ для зарегистрированных пользователей, показ результатов до завершения. При голосовании также ведется подсчет участников.

Для работы компонента требуется наличие установленных компонентов DocLister и FormLister. Версия PHP - от 5.6.

## Режимы работы
Для каждого голосования выбирается режим работы:
* вывод вариантов для голосования (по умолчанию);
* если в $_POST есть ключи formid (имя формы), poll (id голосования), vote (id вариантов) и finish (должно быть пустое значение), то считается что произведено голосование. Если переданные параметры допустимы, то показываются результаты и устанавливается блокировка от повторных голосований;
* если в $_POST есть ключ formid, то показываются результаты голосования.

## Общие параметры
### config
Содержит массив со значениями параметров сниппета. См. описание параметра config в FormLister.

Значение по умолчанию - polls:assets/snippets/simplepolls/config.

###protection
Вариант защиты от накруток. 

Возможные значения: ip, cookie.

Значение по умолчанию: cookie.

###maxVotesFromIp
Если включена защита по ip, то можно дать возможность проголосовать с одного ip несколько раз.

Возможные значения: целое число больше нуля.

Значение по умолчанию - 4.

###dateFormat
Формат для вывода дат.

Возможные значения: строка для подстановки в функцию strtotime.

Значение по умолчанию: d.m.Y H:i

###thumbSnippet, thumbOptions
Имя сниппета для создания превью (например, phpthumb). В параметре thumbOptions должна быть строка, определяющая параметры превью. 

Значение по умолчанию - не задано.

###pollIds, parent
Id голосований для вывода. Голосования, в которых не созданы варианты, не выводятся. 

Возможные значения: список id, через запятую.

Значение по умолчанию: все голосования привязанные к текущему документу или к документу, указанному в параметре parent.

###hidePollsUsersOnly
Не выводить голосования, в которых могут участвовать только зарегистрированные пользователи.

Возможные значения: 0 или 1.

Значение по умолчанию - 0.

###alwaysHideResults
Всегда скрывать результаты голосований, независимо от их статуса и настроек.

Возможные значения: 0 или 1.

Значение по умолчанию - 0.

###sortResults
Если задан, то результаты голосования сортируются по количеству голосов в указанном порядке.

Возможные значения: asc (по возрастанию), desc (по убыванию).

Значение по умолчанию: desc.

### captcha, captchaField, captchaParams
См. параметры FormLister.

## Шаблоны общие
### tpl
Шаблон для списка голосований. Голосования выводятся в плейсхолдере [+polls+].
 
Значение по умолчанию - не задано.

###votesTpl
Шаблон для вывода вариантов голосования. В этом шаблоне выводится форма, в которой обязательно должны быть скрытые поля poll с id голосования и formid с именем формы (по умолчанию poll). Доступны плейсхолдеры полей голосования, а также:
* __[+info+]__ - информация о голосовании, см. параметр infoActiveTpl, infoFinishedTpl;
* __[+total+]__  - статистика по голосованию, см. параметр totalTpl;
* __[+votes+]__ - список вариантов для голосования, см. параметр singleVoteTpl, multipleVoteTpl;
* __[+captcha+]__ - вывод блока капчи, см. параметр captchaTpl;
* __[+controls+]__ - вывод блока кнопок, см. параметр controlsTpl.

Плейсхолдеры выводятся в соответствии с настройками голосования.

###resultsTpl
Шаблон для вывода результатов голосования. Доступны плейсхолдеры голосования, а также:
* __[+info+]__ - информация о голосовании, см. параметр infoActiveTpl, infoFinishedTpl;
* __[+total+]__  - статистика по голосованию, см. параметр totalTpl;
* __[+results+]__ - список результатов, см. параметр resultsVoteTpl;
* __[+status+]__ - статус голосования, см. параметр statusCookieBlockTpl, statusIpBlockTpl.

###mixedTpl
Совмещает votesTpl и resultsTpl, т.е. есть возможность проголосовать при просмотре результатов. Вместо плейсхолдеров __[+votes+]__ и __[+results+]__ используется плейсхолдер __[+mixed+]__, см. шаблоны singleMixedTpl, multipleMixedTpl. 

## Шаблоны для вывода вариантов голосования и результатов
### singleVoteTpl, multipleVoteTpl
Шаблон для вывода варианта голосования: singleVoteTpl, если разрешено голосовать только за один вариант; multipleVoteTpl - если за несколько. Доступны плейсхолдеры варианта.

### singleMixedTpl, multipleMixedTpl
То же самое, что singleVoteTpl и multipleVoteTpl, но для смешанного режима.

### resultsVoteTpl
Вывод результата голосования за вариант в обычном режиме. Плейсхолдеры те же, что и для singleVoteTpl.

### resultsHiddenTpl
Выводится, если нельзя показать результаты.

### votesUsersOnlyTpl, resultsUsersOnlyTpl
Выводится вместо списка вариантов или результатов, если голосование только для зарегистрированных пользователей.

## Шаблоны статуса голосования
### statusCookieBlockTpl, statusIpBlockTpl
Выводятся если сработало ограничение по куки или Ip.

## Шаблоны информации о голосовании
###infoActiveTpl, infoFinishedTpl
Выводятся в зависимости от того, активно голосование или нет.  

## Шаблон статистики по голосованию
### totalTpl
Выводитеся, если голосование активно, разрешено для пользователя и не тайное. Можно использовать плейсхолдеры голосования, например общее количество голосов и участников.

## Шаблоны элементов управления
### controlsTpl
Определяет вид блока с кнопками для голосования и просмотра результатов. Выводится, если разрешено показать хотя бы одну кнопку. Плейсхолдеры:
* __[+voteBtn+]__ - кнопка для голосования;
* __[+resultsBtn+]__ - кнопка для просмотра результатов.

### resultsBtnTpl, voteBtnTpl
Шаблоны кнопок для просмотра результатов и голосования.

## Шаблоны капчи
### captchaTpl
Шаблон блока капчи. Плейсхолдеры:
* __[+captcha+]__ - изображение капчи;
* __[+error+]__ - сообщение об ошибке, см. errorTpl.

### errorTpl
Шаблон сообщения об ошибке капчи, сообщение выводится в плейсхолдер __[+message+]__.

## Плейсхолдеры голосования
Соответствуют полям таблицы sp_polls:
* __[+poll_id+]__ - id голосования;
* __[+poll_title+]__ - название голосования; 
* __[+poll_isactive+]__ - активность голосования; 
* __[+poll_properties+]__ - настройки голосования в формате json; 
* __[+poll_parent+]__ - документ, к которому привязано голосование; 
* __[+poll_rank+]__ - позиция голосования в списке, не используется;
* __[+poll_voters+]__ - количество участников;
* __[+poll_begin+]__ - время начала голосования;
* __[+poll_end+]__ - время завершения голосования.

Устанавливаются дополнительно:
* __[+e.poll_title+]__ - название голосования с экранированием; 
* __[+begin+]__ - время начала голосования, отформатированное; 
* __[+end+]__ - время завершения голосования, отформатированное;
* __[+total_votes+]__ - общее количество голосов;

## Плейсхолдеры варианта для голосования
Соответствуют полям таблицы sp_votes:
* __[+vote_id+]__ - id варианта;
* __[+vote_title+]__ - название варианта; 
* __[+vote_image+]__ - относительный путь к прикрепленному изображению; 
* __[+vote_value+]__ - количество голосов; 
* __[+vote_blocked+]__ - если 1, то голосование за этот вариант приостановлено; 
* __[+vote_poll+]__ - голосование, к которому относится вариант; 
* __[+vote_rank+]__ - позиция варианта в списке.

Устанавливаются дополнительно:
* __[+e.vote_title+]__ - название варианта, экранированное; 
* __[+total_votes+]__ - общее количество голосов;
* __[+percent+]__ - отношение количества голосов за вариант к общему количеству голосов;
* __[+thumb+]__ - превью изображения (см. параметр thumbSnippet, thumbOptions).

## Пример отправки формы через ajax
Соответствует конфигу polls.json.

Клиентская часть:
```javascript
    // все голосования должны быть внутри div.polls
    $('.polls').on('submit','form',function(e){
        e.preventDefault();
    });
    // каждое голосование должно быть внутри div
    $('.polls').on('click','form button[type=submit]',function(e){
        e.preventDefault();
        var name   = $(this).attr('name');
        if (typeof name == 'undefined') return;
        var form = $(this).parents('form');
        var data = form.serializeArray();
        data.push({name: name, value: ''});
        $.post('/assets/snippets/simplepolls/ajax.php',data,function(response){
            form.parent().replaceWith(response);
        });
    });
```
Серверная часть, файл /assets/snippets/simplepolls/ajax.php:
```php
<?php
define('MODX_API_MODE', true);
include_once(__DIR__."/../../../index.php");
$modx->db->connect();
if (empty ($modx->config)) {
    $modx->getSettings();
}
$modx->invokeEvent("OnWebPageInit");
if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') || strpos($_SERVER['HTTP_REFERER'],$modx->config['site_url']) !== 0){
    $modx->sendErrorPage();
}
$poll = isset($_POST['poll']) ? $_POST['poll'] : 0;
//здесь необхиодимо также указать конфиг, если он отличается от polls.json
$out = $modx->runSnippet('SimplePolls',array("pollIds"=>$poll));

exit($out);
```