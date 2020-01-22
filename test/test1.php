<?php
// Ветка поддержки скрипта: http://ddosforum.com/threads/602/
//$_SERVER['REMOTE_ADDR']='228.121.213.213';
//$_SERVER['HTTP_CF_IPCOUNTRY']='TW';
//$_SERVER['HTTP_USER_AGENT']='Mozilla/5.0  AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.106';

$testAntiddos=true;
include('../include.php');

if ($status=='search bot')
{
	echo 'поисковый бот'; 
}
elseif ($status=='fake bot') 
{
	echo 'фейковый поисковый бот'; 
}	
elseif ($status=='ban')
{
	echo 'IP добавлен в Cloudflare, но блокировка включается не моментально. Попробуйте <a href="test.php">обновить страницу</a>. ';
}
elseif ($status=='white')
{
	echo 'Для продолжения тестирование очистите папку <a href="../admin.php?menu=whiteip" target="_blank">белого списка</a>';
}
elseif ($status=='captcha_true')
{
	echo 'Для продолжения тестирование очистите папку <a href="../admin.php?menu=banip" target="_blank">черного списка</a> и <a href="../admin.php?menu=cf&ip" target="_blank">удалите правила Cloudflare</a>. ';
}
else echo 'не превышен лимит';
