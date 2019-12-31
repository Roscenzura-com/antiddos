<?php
if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
{
 	$_SERVER['REMOTE_ADDR']=$_SERVER['HTTP_CF_CONNECTING_IP'];
}
 

$config=['whitetime'=>31536000, /*месяц*/ 'bantime'=>604800, /*неделя*/ 'proactivetime'=>3600, /*час*/ 'referertime'=>604800];

$config['admin']['pass']='ddosforum'; // Пароль админки
$config['limit']=5; // Количество заходов в минуту, после превышения появляется капча. Убедитесь, что нет никаких ajax скриптов, которые обращаются чаще (чат, например).
$config['counter']='url'; // счетчик для каждой страницы. Если убрать, то счетчик будет работать для всего сайта и нужно выставить больше значение limit

//$config['referer']=[$_SERVER['HTTP_HOST'], 'yandex.ru', 'google.com', 'google.ru'];
$config['referer']=false; // Рефереры для исключения, ддос боты обычно шлют пустые реферы 
	
$config['search_bots']=['Googlebot'=>'Google', 'yandex.com/bots'=>'Yandex', 'mail.ru'=>'mail.ru'];  // 'msn.com','bing.com'
$config['search_hosts']=['Google'=>['.googlebot.com', '.google.com'], 'Yandex'=>['.yandex.com', '.yandex.ru', '.yandex.net'], 'mail.ru'=>['.mail.ru'], 'msn.com'=>['.msn.com'], 'bing.com'=>['.msn.com'] ];	


//Cloudflare
$configCF=
[
	'email'=>'',  // email вашего аккаунта в Cloudflare
	'key'=>'', // Узнать можно на странице dash.cloudflare.com/profile, Global API Key
	'zone'=>'', // ID домена в Cloudflare, есть во вкладке Overview 
	
	'countries'=>['RU'=>1, 'UA'=>1, 'BY'=>1, 'KZ'=>1, 'LV'=>1] // страны целевого трафика
];

 
  
 	
// Страны для работы через Cloudflare. Их мы будем проверять мягче, всем остальным сразу даем каптчу.
// Проверьте в статистике Лайвинтернет, какие страны за последний год заходят на ваш сайт и составьте свой набор, таблица кодовых обозначений стран здесь ->
// https://support.cloudflare.com/hc/en-us/articles/205072537-What-are-the-two-letter-country-codes-for-the-Access-Rules-

// $allow_country=array('RU'=>'Россия', 'UA'=>'Украина', 'BY'=>'Белоруссия', 'KZ'=>'Казахстан', 'DE'=>'Германия', 'US'=>'США', 'NL'=>'Нидерланды', 'GB'=>'Британия', 'DE'=>'Германия', 'LV'=>'Латвия', 'UZ'=>'Узбекистан', 'GE'=>'Грузия', 'IL'=>'Израиль', 'BG'=>'Болгария', 'LT'=>'Литва', 'FI'=>'Финляндия', 'FR'=>'Франция', 'AZ'=>'Азербайджан', 'AM'=>'Армения', 'CA'=>'Канада', 'KG'=>'Киргизия');
