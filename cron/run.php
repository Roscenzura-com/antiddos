<?PHP
ini_set('register_argc_argv', 1);

include('../config.php');
include('../autoload.php');

$admin= new Admin($config); 

if (isset($_GET['action']))
{
	if ($_SERVER['HTTP_USER_AGENT']!='antiddos')
	{
		$admin->cronLog($_GET['action'].' ошибка, задание крон было вызвано без флага --user-agent, смотрите инструкцию на форуме ddosforum.com' ) ;
	}
}
elseif (isset($argv[1]))
{
	$_GET['action']=$argv[1];
}


if ($_GET['action']=='clearcount')
{
	if ($admin->cronTimer('counter')) $r=$admin->clearList('count');
}
elseif ($_GET['action']=='clearban')
{
	if ($admin->cronTimer('banlist')) $r=$admin->unbanByTime('ip');
}
else exit();

$admin->cronLog($_GET['action'].' '.($r ? 'выполнен' : 'были ошибки') ) ;



//file_put_contents(__DIR__.'/log.txt', $argv[0].' '.$argv[1], FILE_APPEND);
//var_dump($r);
?>
