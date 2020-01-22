<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8" />
	<title>Тестирование скрипта Antiddos</title>
<style type="text/css">

span {font-weight:bold; }
.red { color:#FF0000; }
.green { color:green;  }
body { font:11pt Arial; line-height:20px;}
</style>

</head>
<body>
<?php
//echo memory_get_usage();
$memory=memory_get_usage();

echo 'Ваш браузер: '.$_SERVER['HTTP_USER_AGENT'].'<br>';
echo 'Ваш IP: '.$_SERVER['REMOTE_ADDR'].'<br>';
echo 'Ваша страна: '.$_SERVER['HTTP_CF_IPCOUNTRY'];

$start = microtime(true);


$testAntiddos=true;
include('../include.php');

if ($status=='search bot')
{
	$st='<span class="green">поисковый бот</span>'; 
	$message='';
}
elseif ($status=='fake bot') 
{
	$st='<span class="red">фейковый поисковый бот</span> (забанен)'; 
	$message='';
}	
elseif ($status=='ban')
{
	$st='<span class="red">ddos бот</span> (забанен)';
	$message='IP добавлен в Cloudflare, но блокировка включается не моментально. Попробуйте <a href="test.php">обновить страницу</a>. ';
}
elseif ($status=='white')
{
	$st='<span class="green">IP в белом списке</span>';
	$message='Для продолжения тестирование очистите папку <a href="../admin.php?menu=whiteip" target="_blank">белого списка</a>';
}
elseif ($status=='captcha_true')
{
	$st='<span class="green">IP прошел проверку Cloudflare</span>';
	
	$message='Для продолжения тестирование очистите папку <a href="../admin.php?menu=banip" target="_blank">черного списка</a> и <a href="../admin.php?menu=cf&ip" target="_blank">удалите правила Cloudflare</a>. ';
}
else $st='<span>нейтрально</span> (не превышен лимит)';
 
echo '<br><br>Текущий статус: '.$st.'<br><br>';

echo '<a href="../admin.php" target="_blank">Админка</a>';
echo '<br>Пароль: test<br><br>';
 
// var_dump($antiddos->counter);
 
if (!$status || isset($antiddos->counter) )
{
	echo '"Внутренние" страницы для тестирования: <a href="?page=1">раз</a>, <a href="?page=2">два</a>, <a href="?page=3">три</a>';
	
	if ($_SERVER['REQUEST_URI']=='/') $url='Главная'; else  $url=substr($_SERVER['REQUEST_URI'],1);
	echo '<br>Число заходов на страницу <a href="'.$_SERVER['REQUEST_URI'].'">'.$url.'</a> с вашего IP за миниту: '.$antiddos->counter;
	
	$ccode=array_keys($configCF['countries']);
	$ccode=implode(', ', $ccode);
	

	echo '<br><br><b>Настройки</b>';
	echo '<br>Лимит заходов на страницу (config.php): '.$config['limit'];
	echo '<br>Режим блокировки: js_challenge (яваскрипт)';
	echo '<br>Страны для мягкой блокировки: '.$ccode;
}
elseif ($message)  echo '<font color="red">'.$message.'</font>';


/*
		header('HTTP/1.0 403 Forbidden');
		exit;
*/

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


echo '<br><br>'.implode('<br>', $debug);

echo '<br><br>Тема поддержки: <a href="https://ddosforum.com/threads/602/">https://ddosforum.com/threads/602/</a>';
?>
</body>
</html>
