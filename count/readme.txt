Папка счетчика, нужны права на запись. 
На каждый хэш запроса 
md5($_SERVER['REMOTE_ADDR'].$_SERVER['REQUEST_URI'].$_SERVER['HTTP_USER_AGENT'].date("mdhi"))

Создается файл. 
Очистка папки производится через cron или вручную из админки.
