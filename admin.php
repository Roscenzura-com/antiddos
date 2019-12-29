<?PHP
$dir=__DIR__.'/';
include('config.php');

session_start();


function delfiles($dir)
{
	if ($handle = opendir($dir)) {
		while (false !== ($file = readdir($handle))) 
		{ 
			if ($file != "." && $file != "..") unlink($dir.'/'.$file);
		}
		closedir($handle); 
	}
}

function viewip($ipfile, $more=false)
{
	$ip=substr(strrchr($ipfile, "/"), 1);
	
	if (!$more) return $ip;
	
	$info=explode(PHP_EOL, file_get_contents($ipfile));
	
	// $this->country_code, $_SERVER['REQUEST_URI'], $_SERVER['HTTP_USER_AGENT'], $reason, $t, ($t+$time);
	
	$info=$info+['','','','',time() ];
	
	return $ip.' - '.$info[0].' - '.$info[1].' - '.$info[2].' ('.$info[3].', '.date("Y-m-d h:i:s", $info[4]).')';
}


function listip($ipdir, $more)
{
	$list=[];
	$items = glob($ipdir.'/*');
	foreach ($items as $ip)
	{
		$list[]=viewip($ip, $more);
	}
		
		
	return $list;	
}


/*
foreach ($countries as $code=>$mode)
{


}*/


/*
include(__DIR__.'/config.php');
include(__DIR__.'/cloudflare.class.php');
$cf = new Cloudflare($configCF);
$cf->auth($configCF['email'], $configCF['key'], $configCF['zone']); // авторизация на Cloudflare

$id='5679d0020420479e98d5dd4cb2292168';
$r=$cf->delrule($id);


var_dump($id, $r);
*/

function delrules($file)
{
	$dfile=__DIR__.'/cloudflare/'.$file.'.txt';
	$file=file_get_contents($dfile);
	
	$data=json_decode($file, true);
	if (empty($data)) 
	{
		return '<br><br><b>Скрипт завершил работу</b><br><br>';
	}
	
	$key=key($data);
	
	//
	$id=$data[$key]['id'];
	
	include(__DIR__.'/config.php');
	include(__DIR__.'/cloudflare.class.php');
	$cf = new Cloudflare($configCF);
	$cf->auth($configCF['email'], $configCF['key'], $configCF['zone']); // авторизация на Cloudflare
	
	$r=$cf->delrule($id);

	if (!isset($r['success']) || $r['success']==false)
	{
		var_dump($id, $r);
		echo 'Не получается удалить запись '.$key. '. <a href="../admin.php?menu=cf">Остановить удаление</a>';
		echo '<meta http-equiv="refresh" content="3">';
		exit;
	}
	//
	//var_dump($id, $r);
	
	unset($data[$key]);
	file_put_contents($dfile, json_encode($data) );
	
	
	echo 'Удалена запись '.$key. '. <a href="../admin.php?menu=cf">Остановить удаление</a>';
	echo '<meta http-equiv="refresh" content="3">';
	exit;
}




$echo='';


if (isset($_POST['pass']))
{
	$_SESSION['pass']=md5($_POST['pass']);
}

if (isset($_POST['addip']))
{
	// var_dump($dir.'white/'.$_POST['addip']);
	 file_put_contents($dir.'white/'.$_POST['addip'], '');
	 
	 $echo.='ip добавлен <br>';
}	


if ( !isset($_SESSION['pass']) || $_SESSION['pass']!=md5($config['admin']['pass']) ) 
{
	if (isset($_POST['pass'])) $echo.='<font color="red">Пароль неверный!</font><br><br>';
	
	$echo.='Введите пароль: <form name="form" method="post"><input type="text" name="pass"><input type="submit" value="Отправить"></form>';
	$echo.='<br>'.$_SERVER['REMOTE_ADDR'];
	$echo.='<br>'.$_SERVER['HTTP_CF_IPCOUNTRY'];
	/* var_dump($_SERVER);*/
}
else
{
	$menu=array('banip'=>'Список забаненных IP', 'whiteip'=>'Белый список IP', 'captcha_ip'=>'Прошедшие капчу', 'cf'=>'Cloudflare правила', 'clearcount'=>'Очистить счетчик');
	$submenu=array('banip'=>array('clearban'=>'Очистить', 'more'=>'Подробнее'), 'whiteip'=>array('clearwhite'=>'Очистить', 'more'=>'Подробнее'), 'captcha_ip'=>array('clearcaptcha_ip'=>'Очистить', 'more'=>'Подробнее'), 'cf'=>array('country'=>'Заблокированные страны', 'ip'=>'Заблокированные IP', 'range'=>'Заблокированные подсети', 'clearcountry'=>'Удалить страны', 'clearip'=>'Удалить IP', 'clearrange'=>'Удалить подсети') );
	
	if (!isset($_GET['menu'])) $_GET['menu']='';
	
	foreach ($menu as $i=>&$t) if ($i!=$_GET['menu']) $t="<a href='admin.php?menu=$i'>$t</a>"; else $t="<b>$t</b>";
	$echo.='<div class="headmenu">'.implode(' | ', $menu).'</div>';
	
	
	if (isset($submenu[$_GET['menu']]))
	{
		foreach ($submenu[$_GET['menu']] as $i=>&$t)  $t="<a href='admin.php?menu={$_GET['menu']}&$i'>$t</a>";
		
		//var_dump($submenu);
		$echo.='<br><div style="padding-left:3px; padding-top:5px;">'.implode(' | ', $submenu[$_GET['menu']]).'</div>';
	}
	
	$echo.='<br><br>';
	
	/*
	$echo ='<div style="padding:5px 0 0 5px;">
			<a href="?banip">Список забаненных IP</a> | <a href="?clearban">Очистить черный список</a> | <a href="?clearwhite">Очистить белый список</a> | <a href="?clearcount">Очистить счетчик</a>
			</div><br>';
	*/
	
	if (!isset($_GET['more'])) $_GET['more']=false; else $_GET['more']=true;
	
	if (!isset($_GET['menu']))
	{
	 // Настройки
	}
	elseif ($_GET['menu']=='banip')  
	{		
		if (isset($_GET['clearban']))
		{
			delfiles($dir.'ban');
		
			$echo.= 'Операция выполнена.';
		}
		else
		{
			$list=listip($dir.'ban', $_GET['more']);
			
			$echo.='Всего: '.count($list).'<br><br>';
			$echo.=implode('<br>',$list);
		}
	}
	elseif ($_GET['menu']=='whiteip'  )
	{
		if (isset($_GET['clearwhite']))
		{
			delfiles($dir.'white');
			
			$echo.= 'Операция выполнена. ';
		}
		else
		{
			$list=listip($dir.'white', $_GET['more']);
			
			$echo.='<form name="form" method="post"><input type="text" name="addip"><input type="submit" value="Добавить IP"></form><br><br>';
			$echo.='Всего: '.count($list).'<br><br>';
			$echo.=implode('<br>',$list);		
		}	
		 
	}
	elseif ($_GET['menu']=='captcha_ip'  )
	{
		if (isset($_GET['clearcaptcha_ip']))
		{
			delfiles($dir.'clearcaptcha_ip');
			
			$echo.= 'Операция выполнена. ';
		}
		else
		{			
			$list=listip($dir.'captcha_ip', $_GET['more']);
			
			$echo.='Всего: '.count($list).'<br><br>';
			$echo.=implode('<br>',$list);		
		}	
		 
	}
	elseif ($_GET['menu']=='clearcount')
	{
		delfiles($dir.'count');
		
		$echo.= 'Операция выполнена:'.$dir.'count';
	}
	elseif ($_GET['menu']=='cf')
	{
		if (isset($_GET['country']))
		{
			$file='country';
		}
		elseif (isset($_GET['ip']))
		{
			$file='ip';
		} 
		elseif(isset($_GET['ip_range']))
		{
			$file='ip_range';
		} 
		elseif (isset($_GET['clearcountry']))
		{
			$echo.=delrules('country');
		} 
		elseif(isset($_GET['clearip']))
		{
			$echo.=delrules('ip');
		} 
		elseif(isset($_GET['clearrange']))
		{
			$echo.=delrules('ip_range');
		}		
		
		if (isset($file))
		{
			$file=file_get_contents($dir.'cloudflare/'.$file.'.txt');
			
			$echo.='<br>';
			if ($file)
			{
				$data=json_decode($file, true);
				
				foreach ($data as $rule=>$a)
				{
					$echo.= 'Правило: <b>'.$rule.'</b>; Способ блокировки: <b>'.$a['mode'].'</b><br>';
				
				}
				
				//var_dump($data);
			}
		}
		
		
		
		
	}
}
?><!DOCTYPE html><html><head><meta charset="utf-8" /><style type="text/css">
body{  }
.headmenu { font-size:14pt; }
.headmenu a{ font-size:14pt; } </style></head><body>
<?=$echo?>
</body></html>