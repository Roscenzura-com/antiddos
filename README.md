# antiddos 1.0
Скрипты для защиты от ддос

Тема поддержки для вопросов и предложений: https://www.ddosforum.com/threads/1045/

PHP-скрипт для защиты от ддос, в связке с Cloudflare.
Добавляет IP ботов напрямую в Cloudflare. Есть автоматическое удаление записей по крону.  

### Инструкция по настройке

1. Создайте папку для скрипта с уникальным именем, например, `antiddos-2022` и залейте туда файлы.   
2. Добавьте права на запись для следующих папок: ban, white, captcha_ip, cloudflare, countries, count, log
3. Задайте пароль для админки, данные доступа к API Cloudflare и лимиты для блокировки в файле config.php
4. Добавьте в крон задание `<адрес сайта>/<папка скрипта>/cron/run.php?action=clearcount` с интервалом 10-60 минут
5. Добавьте в крон задание `<адрес сайта>/<папка скрипта>/cron/run.php?action=clearban` для автоматической очистки бан-листа (чистить можно и вручную из админки)

Пример записи cron
```
/usr/bin/wget --user-agent="antiddos" "http://[домен]/[папка скрипта]/cron/run.php?action=clearcount"
```

Пример записи cron через PHP
```
/usr/bin/php /[путь на сервере к папке скрипта]/cron/run.php clearban
```


### Инструкция по установке 

Добавьте в самый верх исполняемого файла (чаще всего index.php, после <?PHP) код `include($_SERVER['DOCUMENT_ROOT'].'/<папка скрипта>/include.php');`

При работе с Cloudflare можно поставить исключения для кешируемых файлов, если они загружаются через скрипт. Все равно ддосер напрямую их атаковать не сможет (запросы будут идти на Cloudflare). 
Например, так (для движка Xenforo):
```
if (!strpos($_SERVER['REQUEST_URI'], '?css=') && !strpos($_SERVER['REQUEST_URI'], '.jpg?') && !strpos($_SERVER['REQUEST_URI'], '.js?'))
{
	include($_SERVER['DOCUMENT_ROOT'].'/[папка скрипта]/include.php');
}
```
Тогда счетчик не будет работать на картинки, файлы стилей и скрипты, что снижает вероятность ложного срабатывания. 

Если ддосят только главную, то можно подгружать скрипт так.
```
if ($_SERVER['REQUEST_URI'] == '/')
{
	include($_SERVER['DOCUMENT_ROOT'].'/[папка скрипта]/include.php');
}
```

Админка: `<адрес сайта>/<папка со скриптом>/admin.php`


### Алгоритм работы скрипта

1) Проверка User Agent на поискового бота. Подлинные боты добавляются в белый список (определяются по хосту), а фейковые в бан лист. 

2) Проверка IP или IP+браузер на превышение лимита обращений (за минуту) к текущей странице сайта. В случае превышения лимита IP добавляется в бан-лист. Также показывается сообщение с email администратора для связи, на случай ошибочной блокировки.

3) Если забаненный пользователь снова посещает сайт, то в файрвол Cloudflare добавляется правило блокировки по IP, а также по стране, если в настройках установлен список стран целевой аудитории и страна пользователя не из этого списка. По умолчанию, в качестве метода блокировки устанавливается капча (managed_challenge). Если в качестве метода блокировки установлен запрет доступа (block), то действий описанных в пунте 4 и 5 не происходит.

4) Если пользователь проходит капчу Cloudflare, то разбанивается на сайте. А точнее, IP пользователя из бан листа переносится в список IP прошедших капчу.

5) Если пользователь повторно превышает лимит (после разбана на предыдущем шаге), то меняется правило блокировки в Cloudflare с капчи на запрет доступа. После чего IP пользователя может быть разбанен либо вручную администратором, либо автоматически, после истечения срока блокировки.


По умолчанию в настройках включен режим "Под Атакой", для противодействия "умному ддос". 

### Алгоритм работы режима "Под атакой"

1) В случае превышения лимита общего числа обращений к сайту (за минуту), показывается заглушка "сайт под ддос атакой, зайдите попозже".

2) Пользователи, проигнорировавшие предупреждение, попадают в бан (дальше см выше шаг 3 алгоритма работы скрипта).

3) Как только общее число обращений к сайту станет ниже установленного лимита, работа сайта автоматически восстанавливается.
