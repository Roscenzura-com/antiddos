<?PHP
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
$memory=memory_get_usage();
$start = microtime(true);
$dir=__DIR__.'/';
include('config.php');
include('autoload.php');
 
session_start();
$admin = new Admin($config);

if (isset($_POST['del']))
{
	if (!isset($_POST['typeip']))  exit();	
	
	if ( !isset($_SESSION['pass']) || $_SESSION['pass']!=md5($config['admin']['pass']) ) 
	{
		echo 'Ошибка: нужно залогиниться для выполнения операции.';
		exit();
	}
 
 	$rule=urldecode($_POST['del']);
//	echo $rule;
 
	if ($_POST['typeip']=='cf')
	{
		$types=['cf'=>'ip', 'country'=>'country', 'ip_range'=>'ip_range', 'ip6'=>'ip6'];
		
		if ( !$admin->delRule($rule, $types[ $_POST['subtype'] ] ) )
		{
			// echo 'Не получается удалить запись '.$_GET['del']. ', взможно отсутствует подключение к Cloudflare. Удалите вручную правило '.$_GET['del'].' из Security->WAF->Tools.';
			echo $admin->error;
			exit();		
		}
	}
	else
	{
		$dirAllow=array('ban', 'white', 'captcha_ip');
		if (!in_array($_POST['typeip'], $dirAllow) ) exit ();
		
		$admin->del($rule, $_POST['typeip']);
	}
	
	//if ($admin->error) echo $admin->error;

	exit();
}


function viewip($ipfile, $more)
{
	global $admin;
	
	$ip=substr(strrchr($ipfile, "/"), 1);

	if ($more=='ban' || $more=='white' || $more=='captcha_ip') return $ip.' <a data-v="'.$ip.'" class="del"> </a>';
	
	$info=$admin->getIpData($ipfile);
	
	$view=$ip.' - '.$info['country'].' - '.$info['url'].' - '.$info['user_agent'].' ('.$info['reason'];
		
	if ($info['time'])
	{
		if ($info['timer']) 
		{
			$view.=', date block: '.date("Y-m-d h:i:s", $info['time']);
			$view.=', date unblock: '.date("Y-m-d h:i:s", $info['timer']);	
		}
		else
		{
			$view.=', date: '.date("Y-m-d h:i:s", $info['time']);
		}
	}
	
	$view.=') <a data-v="'.$ip.'" class="del"> </a>';

	return $view;
}


function listip($ipdir, $more)
{
	$list=[];
	$add='';
	$items = glob($ipdir.'/*');
	foreach ($items as $ip)
	{
		if (substr($ip,-3)!='txt') $add=viewip($ip, $more); else continue;

		if (isset($_POST['filterip']) && $_POST['filterip'])
		{
			if (strpos($add, $_POST['filterip'])) $list[]=$add;
		}
		elseif ($more=='fakebot') 
		{
			if (strpos($add, 'fake bot')) $list[]=$add;
		}
		elseif ($more=='ban_attack_mode')
		{
			if (strpos($add, 'attack mode')) $list[]=$add;
		}
		else $list[]=$add;
		
//		break;
	}
		
	return $list;	
}


///
function urlQuery($exclude, ...$args)
{
	$get=[];
	foreach ($args as $var) $get[$var]=$var.'='.$_GET[$var].'&'; 
	
	$scriptName=str_replace('index.php','',$_SERVER['SCRIPT_NAME']);
	
	if ($exclude)
	{
		 $query=str_replace($get, '', $_SERVER['QUERY_STRING'].'&');
	}
	else $query=implode('', $get);
	
	return  $query ? ('?'.substr($query,0,-1)) : "";
}


function delrules($type)
{
	global $admin;
	
	//var_dump($type);
	
	$_SERVER['QUERY_STRING']='menu=cf&submenu='.$type;

	$data=$admin->getRules($type);
	if (empty($data)) 
	{
		return '<br><b>Скрипт завершил работу (удаление '.$type.')</b> Для возращения в исходный раздел нажмите <a href="'.urlQuery(1, 'type', 'action').'">здесь</a> <br><br>';
	}
	
	//	exit;
	
	$rule=key($data);
 
	$admin->delRule($rule, $type); 
	
	echo '<meta http-equiv="refresh" content="5">';
	if ( $admin->error )
	{
		echo $admin->error.' / ';

	}
	else echo 'Удалена запись '.$rule. '. <a href="'.urlQuery(1, 'type', 'action').'">Остановить</a>.';
	
	exit();
}




$echo='';
$message='';
 

/*
if (isset($_GET['action']))
{
	if ($_GET['action']=='clearlist')
	
	$admin->clearList($_GET['list']);
	
	$message= 'Операция выполнена. ';
}


*/


if (isset($_POST['pass']))
{
	$_SESSION['pass']=md5($_POST['pass']);
}

if (isset($_POST['addip']))
{
	// var_dump($dir.'white/'.$_POST['addip']);
	 file_put_contents($dir.'white/'.$_POST['addip'], '');
	 
	$message='<div class="red">ip добавлен </div>'; 
}	


if ( !isset($_SESSION['pass']) || $_SESSION['pass']!=md5($config['admin']['pass']) ) 
{
	if (isset($_POST['pass'])) $echo.='<font color="red">Пароль неверный!</font><br><br>';
	
	$echo.='Введите пароль: <form name="form" method="post"><input type="text" name="pass"><input type="submit" value="Отправить"></form>';
	$echo.='<br>'.$_SERVER['REMOTE_ADDR'];
	$echo.='<br>'.$_SERVER['HTTP_CF_IPCOUNTRY'];
}
else
{
	$menu=array('ban'=>'Черный список IP', 'white'=>'Белый список IP', 'captcha_ip'=>'Прошедшие капчу', 'cf'=>'Cloudflare', 'cron'=>'Cron', 'logs'=>'Логи');
	$submenu=array(
					'ban'=>array('ban'=>'Забаненые IP', 'more'=>'Полные данные', 'fakebot'=>'Фейковые поисковые боты', 'ban_attack_mode'=>'Забаненые в режиме Под Атакой'), 
					'white'=>array('white'=>'IP белого списка',  'more'=>'Полные данные'), 
					'captcha_ip'=>array('captcha_ip'=> 'IP прошедшие капчу',  'more'=>'Подробнее'),
					'cf'=>array('cf'=>'Заблокированные IP', 'ip6'=>'IPv6', 'country'=>'Заблокированные страны',  'ip_range'=>'Заблокированные подсети', 'geobot'=>'География ботов'),
					'cron'=>array('cron'=>'Задания Cron'),
					'logs'=>array('logs'=>'Лог ошибок', 'cron'=>'Лог выполнения заданий крон',  'badbot'=>'Плохие боты' ,  'curlLog'=>'Лог запросов'), 
				  );
	
	if (!isset($_GET['menu'])) $_GET['menu']='';
	
	foreach ($menu as $i=>&$t) if ($i!=$_GET['menu']) $t="<a href='admin.php?menu=$i&submenu=$i'>$t</a>"; else $t="<b>$t</b>";
	$echo.='<div class="headmenu">'.implode(' | ', $menu).' | <a href="test/">Тест</a></div>';
	
	
	if (isset($submenu[$_GET['menu']]))
	{
		foreach ($submenu[$_GET['menu']] as $i=>&$t) if ($_GET['submenu']==$i) $t="<b>$t</b>"; else $t="<a href='admin.php?menu={$_GET['menu']}&submenu=$i'>$t</a>"; 
		
		//var_dump($submenu);
		$echo.='<br><div style="padding-left:3px; padding-top:5px;">'.implode(' | ', $submenu[$_GET['menu']]).'</div>';
	}
	
	$echo.='<br>';
	
 
	
	if (isset($_GET['action'])) $_POST=$_GET;
	if (isset($_POST['action'])) 
	{
		switch ($_POST['action'] ) {
			case "clearlist":
				$admin->clearList($_POST['list']);
				$echo.='<span class="red">Операция выполнена</span><br><br>';
				break;
			case "updatebanlist":
				$echo.='<br>'.($admin->unbanByTime('ip') ? ('IP с истекшим сроком блокировки разбанены:<br><br>'.implode('<br>', $admin->unbanLog) ) : 'Ошибка разбана IP с истекшим сроком блокировки').'<br><br>';	
				break;
			case "delrules":
				$echo.='<br>'.delrules($_POST['type']).'<br><br>';	
				break;
			case "clearlog":
				$admin->clearLog($_POST['log']);
					
		}
	}
	
 
	if (!isset($_GET['menu']))
	{
	 // Настройки
	}
	elseif ($_GET['menu']=='ban')  
	{		
		$list=listip($dir.'ban', $_GET['submenu'] );
	} 
	elseif ($_GET['menu']=='white'  )
	{
		$list=listip($dir.'white',  $_GET['submenu'] );
			
		if ($_GET['submenu']=='white') $echo.='<form name="form" method="post"><input type="text" name="addip"> <input type="submit" value="Добавить IP"></form><br><br>';	 
	}
	elseif ($_GET['menu']=='captcha_ip'  )
	{
		$list=listip($dir.'captcha_ip', $_GET['submenu'] );	
	}
	elseif ($_GET['menu']=='cf')
	{
		include(__DIR__.'/Cloudflare.class.php');
		$cf = new Cloudflare($config['CF']);
		
		if (!$config['CF']['email'])
		{
			echo 'Укажите данные доступа к API Cloudflare в config.php'; 
			exit();
		}

		if ($_GET['submenu']=='country')
		{	
			$typeData='country';
		}
		elseif ($_GET['submenu']=='cf')
		{
			$typeData='ip';
		}
		elseif ($_GET['submenu']=='ip6')
		{
			$typeData='ip6';
		}  
		elseif($_GET['submenu']=='ip_range')
		{
			$typeData='ip_range';
		} 
		elseif ($_GET['submenu']=='geobot')
		{
			if (isset($_POST['addcountry']))
			{
				if ( !$cf->addCountry( $_POST['addcountry'], 'antiddos, geobots country, '.date("Y-m-d") ) ) $msg='Ошибка, смотрите лог'; else $msg='Страна '.$_POST['addcountry'].' добавлена в файрвол Cloudflare';
				echo '<script>alert("'.$msg.'");</script>';
			}
			
			$countries=$cf->loadData('country');
			
			
 			$data=$admin->getGeoBots('desc');
			
			$gb='';
			$select='';
			foreach ($data as $code=>$count)
			{
				
				$gb.= $code.': '.$count.'<br>';
				if (!isset($countries[$code])) $select.='<option value="'.$code.'">'.$code.'</option>';
			}	
			
			$echo.='<form name="addcountry" method="post"><select name="addcountry">'.$select.'</select> <input type="submit" value="Добавить страну в файрвол Cloudflare"> </form><br><br>'.$gb;	
		}

		if (isset($typeData))
		{
			$data=$cf->loadData($typeData);
 	
			if ($data)
			{	
				$echo.='Всего: <span id="allCount">'.count($data).'</span><br><br>';
					
				foreach ($data as $rule=>$a)
				{	
					$echo.= '<div>Правило: <b>'.$rule.'</b>; Способ блокировки: <b>'.$a['mode'].'</b> <a data-v="'.$rule.'" class="del"> </a></div>';
				}
				
				if ( $_GET['submenu']=='ip6' || $_GET['submenu']=='ip_range' || $_GET['submenu']=='country' ) $echo.='<br><hr><form method="get"><input type="hidden" name="type" value="'.$_GET['submenu'].'"><button name="action" value="delrules">Удалить все правила</button></font>';
			}
		}
	}
	elseif ($_GET['menu']=='cron')
	{
		$echo.='Период обновления заданий Крон (изменить можно в config.php): ';
		$echo.='<br><div class="bactions" ><form method="post">';
		$echo.='Очистка счетчика: раз в '.($config['cron']['counter']/60).' минут. ';
		$echo.='<input type="hidden" name="list" value="count"><button name="action" value="clearlist">Очистить сейчас</button>';
		
		if (isset($config['cron']['banlist'])) $echo.='<br><br>Разбан IP срок бана которых истек: раз в '.($config['cron']['banlist']/60).' минут. ';
		$echo.='<button name="action" value="updatebanlist">Разбанить сейчас</button> ';	
		$echo.='</form></div>';
	
	}	
	elseif ($_GET['menu']=='logs')
	{
		if ($_GET['submenu']=='logs') $_GET['submenu']='errors';
		
		$log=$admin->getLog($_GET['submenu']);
		if ($log)
		{ 
			$echo.='<div style="padding:5px; overflow:scroll;  height:600px; max-width:100%;border: #ccc 1px solid;" >'.$admin->getLog($_GET['submenu']).'</div>';
			$echo.='<br><div class="bactions" ><form method="post">';
			$echo.='<input type="hidden" name="log" value="'.$_GET['submenu'].'"><button name="action" value="clearlog">Очистить лог</button> ';
			$echo.='</form></div>';
		}
		else
		{
			$echo.='Лог пустой';
		}
		
	}
 
 	//
	if (isset($list))
	{
		$filterCancel='';
		$filter='';
		if (isset($_POST['filterip'])) 
		{  
			$filter=$_POST['filterip'];  
			$filterCancel='<button type="button" onClick="window.location=\''.$_SERVER['REQUEST_URI'].'\'">Сбросить</button>  '; 
		} 
		elseif ($_GET['submenu']=='fakebot') $filter='fake bot'; elseif ($_GET['submenu']=='ban_attack_mode') $filter='attack mode';  
	 
		if ($_GET['submenu']=='more' || $filter ) $echo.='<form name="form" id="filterip" method="post"><input type="text" value="'.$filter.'" name="filterip"> <input type="submit" value="Фильтровать"> '.$filterCancel.'</form><br><br>';
		
		$echo.=($message ? $message.'<br>' : '').'Всего: <span id="allCount">'.count($list).'</span><br><br>';
    	$echo.='<div>'.implode('</div><div>', $list).'</div>';	
		
		
		
		$echo.='<br><div class="bactions" ><form method="post">';
		if ($_GET['submenu']=='ban' || $_GET['submenu']=='white' || $_GET['submenu']=='captcha_ip')
		{ 
			$echo.='<input type="hidden" name="list" value="'.$_GET['menu'].'"><button name="action" value="clearlist">Удалить все</button>';
			if ($_GET['submenu']=='ban') 
			{
				$echo.=' | <button name="action" value="updatebanlist">Удалить IP срок бана которых истек</button> ';	
			}

		}
		$echo.='</form></div>';
	}
}
?><!DOCTYPE html><html><head><meta charset="utf-8" /><title>Админка управления скриптом Antiddos</title>
<style type="text/css">
body{  }
.headmenu { font-size:14pt; }
button { cursor:pointer;}
.red { color:#FF0000; }

.del {
    width: 40px;
    height: 40px;
    border-radius: 40px;
    position: relative;
    z-index: 1;
    cursor: pointer;
}
.del:before {
    content: '+';
    color: #FF0000;
    position: absolute;
    z-index: 2;
    transform: rotate(45deg);
    font-size: 25px;
	font-weight:bold;
    top: -5px;
    left: 6px;
    transition: all 0.3s cubic-bezier(0.77, 0, 0.2, 0.85);
}
 

.del:hover::after {
content:  " удалить " attr(data-v);
position: absolute; 
white-space:nowrap;
left: 23px; top: 1px; 
z-index: 3;  
background: rgba(255,255,230,0.9);  
font-family: Arial, sans-serif;  
font-size: 11px; 
padding: 3px;  
border: 1px solid #333; 
}


.headmenu a{ font-size:14pt; } 

.bactions
{
border-top:1px #CCCCCC solid;
display: inline-block; 
padding-top:10px;

}

 
</style>


</head><body>
<?=$echo?>  

<?PHP if (isset($list) || ( isset($_GET['menu']) && $_GET['menu']=='cf') ) { ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js" type="text/javascript"></script>
<script type="text/javascript">


$( ".del" ).click(function(e) {
 
  e.preventDefault();
  
  var value=encodeURIComponent($(this).attr('data-v'));
  var send={ del: value, typeip: "<?=$_GET['menu']?>", subtype: "<?=$_GET['submenu']?>"  };

  $(this).parent().hide(200);

	
  $.post( "admin.php",  send,  function( data ) {
 
	 if (data) alert( data );
	 else  console.log(send.del + ' удален');
	 
	 $('#allCount').html(  ($('#allCount').html()-1) );
	 
	  
  }).fail(function() {
      
	  alert( "Нет доступа к админке, проверьте подключение Интернета" );
  });
   
});
 

</script>

<?PHP } ?>
</body></html>
