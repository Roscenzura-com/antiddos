# antiddos
Скрипты для защиты от ддос

PHP-скрипт для защиты от ддос, способный противостоять даже сильным ддос-атакам, благодаря связке с Cloudflare.
Добавляет IP ботов напрямую в Cloudflare. Есть автоматическое удаление записей по крону.  

### Инструкция по настройке

1. Создайте папку для скрипта с уникальным именем, например, `antiddos-2020` и залейте туда файлы.   
2. Добавьте права на запись для следующих папок: ban, white, captcha_ip, cloudflare, countries, count, log
3. Задайте пароль для админки, данные аккаунта Cloudflare и лимиты для блокировки в файле config.php
4. Добавьте в крон задание `<адрес сайта>/<папка скрипта>/cron/run.php?action=clearcount` с интервалом 10-60 минут (чем ниже посещаемость, тем больше)
5. Добавьте в крон задание `<адрес сайта>/<папка скрипта>/cron/run.php?action=clearban` для автоматической очистки бан-листа (чистить можно и вручную из админки)

### Инструкция по установке 

1. Добавьте в самый верх исполняемого файла код `include($_SERVER['DOCUMENT_ROOT'].'/<папка скрипта>/include.php');`
2. Если ддосят только главную, поменяйте в файле `<папка скрипта>/include.php` переменную $url на `$url='/';`

Скрипты для тестирования находятся в папке `<адрес сайта>/<папка со скриптом>/test/`

Админка: `<адрес сайта>/<папка со скриптом>/admin.php`

Тема поддержки: https://ddosforum.com/threads/602/

### Обновления 22.01.2020

1. Добавлена возможность менять конфигурацию хэша в config.php
2. Добавлена возможность блокировки ботов даже в случае ручного прохождения капчи ддосером
3. В админке добавлен подраздел "География ботов" к разделу Cloudflare

### Обновления 31.05.2022

1. Добавлен режим "Под атакой".  
2. Добавлен подраздел меню "Фейковые поисковые боты".
