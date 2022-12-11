<?PHP
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
session_start();

if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
{
 	$_SERVER['REMOTE_ADDR']=$_SERVER['HTTP_CF_CONNECTING_IP'];
}


// var_dump($_COOKIE);
//unset($_SESSION['pass']);
//var_dump($_SESSION);
// var_dump($_POST);


if (isset($_COOKIE['cf_clearance']))  
{	 
	$cf_clearance=$_COOKIE['cf_clearance'];
}
else
{
	$cf_clearance=false;
}




$_SESSION=$_POST+$_SESSION+['ip'=>'194.165.22.12', 'ua'=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36', 'country'=>'RU', 'ban'=>'', 'white'=>'', 'block_method'=>'managed_challenge'];
if (isset($_GET['block_method'])) $_SESSION['block_method']=$_GET['block_method'];


if (isset($_GET['reset']))
{
	$_SESSION['ban']=$_SESSION['white']='';
}

/*var_dump($_SESSION);*/

$form=[];
$form['ua']='<select name="ua" onChange="form.submit();">
<option value="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36">Chrome</option>
<option value="Mozilla/5.0 (MSIE 10.0; Windows NT 6.1; Trident/5.0)">Internet Explorer</option>
<option value="Mozilla/5.0 (Linux; Android 4.4.2; Nexus 4 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.114 Mobile Safari/537.36">Android</option>
<option value="Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)">Yandex Bot</option>
<option value="Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)">Google Bot</option>
</select>';

$form['country']='<select name="country" onChange="form.submit();">
<option value="RU">RU (Россия)</option>
<option value="UA">UA (Украина)</option>
<option value="KZ">KZ (Казахстан)</option>
<option value="CN">CN (Китай)</option>
<option value="NL">NL (Нидерланды)</option>
</select>';

$form['ip']='<select name="ip" onChange="form.submit();">
<option value="194.165.22.12" >194.165.23.12 (Россия)</option>
<option value="198.16.78.45">198.16.78.45 (Нидерланды)</option>
<option value="2a02:06b8:b000:0c0c:96de:0000:8d08:b71f">2a02:06b8:b000:0c0c:96de:0000:8d08:b71f (Yandex bot IP6)</option>
<option value="213.180.203.3">213.180.203.3 (Yandex bot)</option>
<option value="66.249.64.1">66.249.64.1 (Google Bot)</option>
<option value="'.$_SERVER['REMOTE_ADDR'].'">Ваш настоящий IP ('.$_SERVER['REMOTE_ADDR'].')</option>
</select>';

$form['block_method']='<select name="block_method" onChange="form.submit();">
<option value="block">Block</option>
<option value="js_challenge">JS challenge (проверка на бота без каптчи)</option>
<option value="challenge">Challenge (каптча)</option>
<option value="managed_challenge">Каптча только для "подозрительных"</option>
</select>';


foreach ($form as $k=>$v)
{
	if (isset($_SESSION[$k])) $form[$k]=str_replace('"'.$_SESSION[$k].'"', '"'.$_SESSION[$k].'" selected="selected"', $v);

}



?><!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8" />
	<title>Тестирование скрипта Antiddos</title>
<style type="text/css">

span {font-weight:bold; }
.red { color:#FF0000; }
.green { color:green;  }
body { font:11pt Arial; line-height:22px;}

input[type=submit] { cursor: pointer; }

.status { border:#CCCCCC 1px solid; padding:3px; display:inline-block; margin:10px 0 10px; }
</style>


</head>
<body>
<?php
$ip=$_SERVER['HTTP_CF_CONNECTING_IP']=$_SERVER['REMOTE_ADDR']=$_SESSION['ip']; 
$_SERVER['HTTP_CF_IPCOUNTRY']=$_SESSION['country']; 
$_SERVER['HTTP_USER_AGENT']=$_SESSION['ua']; 
 
/*

function test()
{
	$test1=10;
	
	
	return $test2=$test1;
}

if(test()==10 )
{
 
	echo '<h1>На сайт идет ддос, пожалуйста, не обновляйте страницу, чтобы не попасть в бан. Попробуйте зайти попозже.</h1>';
	exit;
}
exit;	
 */



$url=$_SERVER['REQUEST_URI'];


//echo memory_get_usage();
$memory=memory_get_usage();
$start = microtime(true);

/*echo '<a href="../">Админка</a><br>';*/
$echo='';
$echo.='<form action="index.php" method="post" name="form">';
$echo.='Ваш браузер: '.$form['ua'].'<br>';
$echo.='Ваш IP: '.$form['ip'].'<br>';
$echo.='Ваша страна: '.$form['country'];
$echo.=' </form>';
$start = microtime(true);


$testAntiddos=true;
include('../config.php');
include('../autoload.php');


$ccode=array_keys($config['CF']['countries']);
$ccode=implode(', ', $ccode);


$st_cf='';
if (isset($_GET['cloudflare']) )
{	
	$cf = new Cloudflare($config['CF']);
	
	$cf->test=true;
	$cf->savedir='../cloudflare/';
    
	$date=date('Y-m-d');
	$desc='test '.$date;
 
	
	if (isset($_GET['type']) && isset($_GET['del']) )
	{
		$cf->set('ip');
		if ( $cf->delRule($_GET['del']) )
		{
			 $rule=$_GET['del'];
			 $st_cf="Правило ".$rule.' удалено';
		}
	}
	elseif (isset($_GET['block_ip']))
	{
	 	$type=$cf->isIp6($_SERVER['REMOTE_ADDR']) ? 'ip6' : 'ip';
		
		$cf->set($type);
		if ($cf->ruleExists())
		{
			$cf->updateRule( $_SESSION['block_method'], 'test change rule '.date('Y-m-d') );
			
			$st_cf="IP ".$cf->ip." уже был добавлен в фаерволл Cloudflare (<a href=\"index.php?cloudflare&type=ip&del=".$cf->ip."\">удалить</a>)"; 	
		}
		elseif ( $cf->addIp($cf->ip, $desc, $_SESSION['block_method']) )  $st_cf="Правило для ".$cf->ip.' создано';
		
	}
	elseif (isset($_GET['block_country']))
	{
		$cf->set('country');
		if ($cf->ruleExists())
		{		
			$cf->updateRule( $_SESSION['block_method'], 'test change rule '.date('Y-m-d') );
			$st_cf="Страна ".$cf->country." уже была добавлена в фаерволл Cloudflare  (<a href=\"index.php?cloudflare&type=country&del=".$cf->country."\">удалить</a>)";  		
		}
		if ($cf->addcountry($cf->country, $desc, $_SESSION['block_method']) )  $st_cf="Правило для ".$cf->country.' создано'; 
	}
	elseif (isset($_GET['block_ip_country']))
	{
		if ( $cf->addIp($ip, 'test ip and country', $_SESSION['block_method']) ) $st_cf="Правило для ".$cf->ip.' создано.';
		if (  $cf->addCountry($_SERVER['HTTP_CF_IPCOUNTRY'], 'test ip and country', $_SESSION['block_method']) ) $st_cf.=" Правило для ".$cf->country.' создано.';
	}
	
	if ($cf->error && !$st_cf) $st_cf=$cf->error;
 
 	/*var_dump($cf->response); */
	
	if ($cf_clearance  && !$st_cf)
	{
		if ($cf->ruleExists()) 
		{
			$st_cf='Капча пройдена';
		}
		else
		{
			$st_cf='Капча пройдена, но IP нет в списке правил для Cloudflare';
		}
	}
	
	if (!empty($cf->testLog)) file_put_contents(__DIR__.'/test.txt', implode(PHP_EOL.'----------------'.PHP_EOL, $cf->testLog).PHP_EOL.'----------------'.PHP_EOL, FILE_APPEND);
	
	
	if (!$st_cf) $st_cf='нет действий';
}



$echo.='<br><br><b>Настройки</b>';
$echo.='<br>Лимит заходов на страницу (config.php): '.$config['limit'];
$echo.='<br>Режим блокировки: js_challenge (яваскрипт)';
$echo.='<br>Страны для мягкой блокировки (Cloudflare): '.$ccode;
if ($config['limit_attack_mode'])
{
	$echo.='<br>Лимит посещений сайта за минуту для включения режима "Под аттакой": '.$config['limit_attack_mode'];
	$echo.='<br>Лимит заходов за минуту для бана IP в режиме "Под аттакой": '.$config['limit_attack_mode_ban'];	
}
 

$antiddos = new Antiddos($config);
if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) $antiddos->country_code=$_SERVER['HTTP_CF_IPCOUNTRY'];



$message='';
if ( $_SESSION['ban']==$_SERVER['REMOTE_ADDR'] ) // $antiddos->isBan()
{
	$st='<span class="red">IP забанен</span> (<a href="index.php?reset">сбросить</a>)'; 
}
elseif ($_SESSION['white']==$_SERVER['REMOTE_ADDR']) //($antiddos->isWhite())
{
	$st='<span class="green">IP в белом списке</span>  (<a href="index.php?reset">сбросить</a>)';
}
elseif ($search=$antiddos->isBot())
{
	if ($antiddos->checkBot($search))
	{
		$st='<span class="green">поисковый бот</span>'; 
		$_SESSION['white']=$_SERVER['REMOTE_ADDR'];
	/*	$antiddos->addWhitelist('search bot');*/
	}
	else
	{
		$st='<span class="red">фейковый поисковый бот</span> '; 
		$_SESSION['ban']=$_SERVER['REMOTE_ADDR'];
		/*$antiddos->addBanlist('fake search bot');*/
	}	
}
elseif ( $antiddos->attackModeSwitcher()=='On' )
{
	$attackModeCounter=$antiddos->attackModeCounter();
			
	if ($antiddos->excessLimitAttackMode())
	{
		$st='<span class="red">Превышен лимит в режиме "Под аттакой"</span>';
		$_SESSION['ban']=$_SERVER['REMOTE_ADDR'];
				
		// $antiddos->addBanlist('attack mode limit exceeded', $config['bantime']);
	}
	else
	{
		$st='<span>Не превышен лимит в режиме Под Аттакой</span>';
	}
}
elseif ($antiddos->excessLimitBan())
{
	$st='<span class="red">Превышен лимит для бана</span>';
	$_SESSION['ban']=$_SERVER['REMOTE_ADDR'];
	/*$message='IP добавлен в Cloudflare, но блокировка включается не моментально. Попробуйте <a href="index.php">обновить страницу</a>. ';*/
}
elseif ($antiddos->excessLimitWarn())
{
	$st='<span class="red">Превышен лимит для предупреждения (не обновлять страницу в течении минуты)</span>';
}
else
{
	$st='<span>не превышен лимит</span>';
}

 
$echo.='<br><span class="status"><b>Текущий статус</b>: '.$st.'</span><br><br>';

if ( $antiddos->counter )
{
	$echo.='<b>Тестирование</b><br><a href="index.php">Обновить страницу</a><br>"Внутренние" страницы для тестирования: <a href="?page=1">раз</a>, <a href="?page=2">два</a>, <a href="?page=3">три</a>, <a href="?test=4">четыре</a>, <a href="?test=5">пять</a><br>';
	
	if (!isset($_GET['cloudflare']))
	{
		if ($_SERVER['REQUEST_URI']=='/') $url='Главная'; else $url=substr($_SERVER['REQUEST_URI'],1);
		$echo.='<br>Число заходов на страницу <a href="'.$_SERVER['REQUEST_URI'].'">'.$url.'</a> с вашего IP за миниту: '.$antiddos->counter;
		if ($config['attack_mode'])
		{
			$echo.='<br>Общее число посещений сайта за минуту (со всех IP, на все страницы): '.$antiddos->countAll;
			$echo.='<br>Режим "Под аттакой": '.( ($antiddos->countAll > $config['limit_attack_mode']) ? '<span class="red">Включен</span>' :  '<span class="green">Выключен</span>' ) ;
		}
	}
}
elseif ($message) $echo.= '<font color="red">'.$message.'</font>';


$echo.='<br><br><b>Cloudflare</b><br>';

$echo.='<form method="get" name="cf_form">Выберите метод блокировки в Cloudflare: '.$form['block_method'].' 
<p>
<input type="hidden" name="cloudflare" value="1">
<input type="submit" name="block_ip" value="Заблокировать по IP"> | <input type="submit" name="block_country" value="Заблокировать по стране"> | <input type="submit" name="block_range" value="Заблокировать диапазон (маска 24)">  | <input type="submit" name="block_ip_country" value="Заблокировать по IP и по стране"> <br><br>
<b>Статус Cloudflare</b>: '.($st_cf ?: 'нет действий').'
</p>
</form>';




$debug=[];
function convert($size)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

$memory=memory_get_usage()-$memory;
$debug[]= '<b>Отладка</b><br>Израсходованная память: '. convert($memory);

$finish = microtime(true);

$delta = $finish - $start;
$debug[]= 'Израсходованное время: '. $delta.' сек.';
$debug[]='Подгруженные файлы: ';

$included_files = get_included_files();

foreach ($included_files as $filename) {
    $debug[]=$filename;
}


$echo.='<br><br>'.implode('<br>', $debug);

$echo.='<br><br>Тема поддержки: <a href="https://ddosforum.com/threads/602/">https://ddosforum.com/threads/602/</a>';
$echo.='<br><a href="../admin.php">Админка</a>';
$echo.='<br><br><a href="test.txt">Тест лог</a>';
$echo.='<br><br><a href="index.php?errorlog#errorlog">Лог ошибок</a>';

if (isset($_GET['errorlog']))
{
	$admin = new Admin($config);
	$echo.='<br><a name="errorlog"></a>'.$admin->getLog('errors');
}


echo $echo;
?>
</body>
</html>
