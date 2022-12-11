<?php
$config=[];
$config['admin']['pass']='test'; // Пароль админки
$config['adminEmail']='admin@ddosforum.com'; // Емейл для связи (в случае ошибочной блокировки)


$config['bantime']=81536000; // 604800 неделя,  31536000 месяц, 3600 час // 


// Лимиты
$config['limit']=13; // Количество заходов в минуту для добавления IP в бан. Убедитесь, что нет никаких скриптов, которые обращаются чаще (чат, например)
$config['limit_warning']=11; // Количество заходов в минуту, после чего появляется предупреждение.  


/////////
//// Режим "Под Аттакой" предназначен для противодействия "умному"/медленному ддосу, а также хакерским атакам
///////// 

//$config['attack_mode']=false;
$config['attack_mode']=true; // Для включения установите значение true, для отключения false.
$config['attack_mode_timer']=300; 
// Количество заходов со всех IP в минуту при которых включается режим "под атакой".  
$config['limit_attack_mode']=32; // Рекомендуемое значение примерно 1/24 от среднего числа просмотров в сутки во избежании ложных срабатываний.   

// Количество заходов в минуту для срабатывания бана в режиме "под атакой". Если выставить 1, то будут залетать все IP, кроме поисковых ботов.
// Предполагается, что пользователь не будет обновлять страницу после того как увидит соответствующее предупреждение. Боты проигнорируют предупреждение и попадут в бан. 
$config['limit_attack_mode_ban']=3; 

/////////
///////// 


// Целевая ссылка
$config['url']=$_SERVER['REQUEST_URI'];  // 
$config['user_agent']='';




// Альтернативные варианты.
// $config['url']='/';  // Только главная, для внутренних страниц сайта скрипт не срабатывает (на случай, если ддосят только главную)
// $config['url']='/register/'; // Скрипт включается только на странице регистрации для блокировки спам ботов


// Учитывать браузер пользователя для счетчика заходов на страницу. Это может пригодиться, если пользователи вашего сайта используют популярные VPN, таким образом IP у них может повторяться. Подробнее в теме https://ddosforum.com/threads/602/
// В целях безопасности установлено false, потому что боты могут генерировать разные USER_AGENT для каждого IP, хотя обычно используют один и тот же
// $config['user_agent']=$_SERVER['HTTP_USER_AGENT'];



//$config['referer']=[$_SERVER['HTTP_HOST'], 'yandex.ru', 'google.com', 'google.ru'];
$config['referer']=[]; // Рефереры для исключения, ддос боты обычно шлют пустые реферы 
	
$config['search_bots']=['Googlebot'=>'Google', 'yandex.com/bots'=>'Yandex', 'mail.ru'=>'mail.ru'];  // 'msn.com', 'bing.com'   
$config['search_hosts']=['Google'=>['.googlebot.com', '.google.com'], 'Yandex'=>['.yandex.com', '.yandex.ru', '.yandex.net'], 'mail.ru'=>['.mail.ru'], 'msn.com'=>['.msn.com'], 'bing.com'=>['.msn.com'] ];	

$config['cron']=
[
	/*'banlist'=>3600,*/ // переодичность в секундах для обновления списка забаненных (удаления IP время бана которых истекло)
	'counter'=>3600 
];


//Cloudflare
$config['CF']=
[
	'email'=>'',  // email вашего аккаунта в Cloudflare
	'key'=>'', // Узнать можно на странице dash.cloudflare.com/profile, Global API Key
	'zone'=>'', // ID домена в Cloudflare, есть во вкладке Overview 
	
	'countries'=>['RU'=>1, 'UA'=>1, 'BY'=>1, 'KZ'=>1, 'LV'=>1, 'FI'=>1, 'GE'=>1, 'IL'=>1, 'KG'=>1, 'PL'=>1, 'TJ'=>1, 'UZ'=>1], // страны целевого трафика
	'target'=>['ip'=>'ip', 'ip6'=>'ip', 'ip_range'=>'ip_range', 'country'=>'country'],
	'notes'=>[
				'ip'=>'antiddos, %country%, %time%, %url%',	
				'ip_range'=>'antiddos, %country%, %time%',
				'country'=>'antiddos, country, %ip%, %time%',
				'default'=>'antiddos, %time%'
			 ],
	
	 // методы блокировки: challenge (каптча), block (запрет доступа), js_challenge (ява скриптовая проверка, без ввода каптчи, иногда обходится ддос ботами), managed_challenge (каптча только для "подозрительных" IP)
	'block_method'=>['country'=>'managed_challenge', 'ip_range'=>'managed_challenge', 'ip'=>'managed_challenge']  // для стран нецелевого трафика
];
