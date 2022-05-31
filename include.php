<?php
// Ветка поддержки скрипта: http://ddosforum.com/threads/602/
 
$url=$_SERVER['REQUEST_URI']; // Если ддосят только главную поставьте $url='/'; для экономии ресурсов. 

$status=false;

// Это можно убрать, если переменная $_SERVER['REMOTE_ADDR'] содержит IP пользователя, а не Cloudflare
if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
{
 	$_SERVER['REMOTE_ADDR']=$_SERVER['HTTP_CF_CONNECTING_IP'];
}

if (is_file(__DIR__.'/ban/'.$_SERVER['REMOTE_ADDR'])) // ip в бане
{
	include(__DIR__.'/config.php');
	
	if (!is_file(__DIR__.'/captcha_ip/'.$_SERVER['REMOTE_ADDR']))	
	{	
		$status='ban'; 	

		include(__DIR__.'/cloudflare.class.php');
		
		$cf = new Cloudflare($configCF);
		
		$cf->ip=$_SERVER['REMOTE_ADDR'];
		$cf->country= $_SERVER['HTTP_CF_IPCOUNTRY'];
		$cf->countries=$configCF['countries']; 
		
		// авторизация на Cloudflare
		if ( !$cf->auth($configCF['email'], $configCF['key'], $configCF['zone']) )
		{
			exit ('Не получилось подключиться к Cloudflare, проверьте настройки подключения.'); 
		}
		
		$desc='antiddos '.$cf->country.' '.date('Y-m-d'); // комментарий для Cloudflare
		
		$cf->counterCountries(); // Счетчик по странам, папка countries, в админке: Cloudflare->География ботов

		if ( !$cf->checkCountry() ) // Не целевой трафик
		{
			$r=$cf->addcountry($desc, 'challenge'); // доступные способы блокировки: challenge (каптча), block (блок), js_challenge (ява скриптовая каптча, иногда обходится ддос ботами)
		
			if (!$r['success']) $r=$cf->addrange($desc, 'challenge'); // если не получилось добавить страну, баним по диапазону
		}
		else
		{
			$cf->addip($desc, 'challenge'); // Целевой трафик блокируем точечно
		}
		/*
		elseif ($cf->country=='RU') // Россию блокируем точечно 
		{
			$cf->addip($desc, 'js_challenge');	
		}
		else $cf->addrange($desc, 'challenge'); 
		*/
		
		$cf->close();
				
		$cf->captchaPass(); // IP прошел проверку Cloudflare, откладываем его в папку captcha_ip
	}
	else
	{
		$status='captcha_true';			
			
		///var_dump( $cf->getRules('ip'));	
		
		// Если ддосер вручную прошел капчу
		
		$config['limit']=$configCF['limit']; // Переопределяем настройки
		
		include(__DIR__.'/antiddos.class.php');
		$antiddos = new Antiddos($config, $_SERVER['REMOTE_ADDR']);

		if ($antiddos->excessLimit())  // Проверка на превышение лимита
		{
			$status='ban';
			
			include(__DIR__.'/cloudflare.class.php');
			$cf = new Cloudflare($configCF);
			$cf->auth($configCF['email'], $configCF['key'], $configCF['zone']);
			
			$cf->ip=$_SERVER['REMOTE_ADDR'];
			$cf->country= $_SERVER['HTTP_CF_IPCOUNTRY'];
	
			if ($id=$cf->getId('ip'))
			{
				$r=$cf->changeRuleMode($id, 'block'); // Баним окончательно 
				
				$cf->save('ip', $cf->ip, 'block');
			}
			else
			{
				$r=$cf->addip('The user passed the captcha and exceeded the limit of requests '.$cf->country, 'block');
			}
		}
		
	
	}	

	exit('Вы временно забанены, попробуйте зайти попозже');
		
	if (!isset($testAntiddos)) unset($config, $cf);
}
elseif (!is_file(__DIR__.'/white/'.$_SERVER['REMOTE_ADDR']) && $_SERVER['REQUEST_URI']==$url )
{	
	include(__DIR__.'/config.php');
	include(__DIR__.'/antiddos.class.php');

	$antiddos = new Antiddos($config, $_SERVER['REMOTE_ADDR']);
	
	if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) $antiddos->country_code=$_SERVER['HTTP_CF_IPCOUNTRY'];
	
	$attack_mode=false;
	if ($config['limit_attack_mode']!=0) 
	{
		$ctime=date("h.i");
		$file_count=__DIR__.'/count_attack.txt';
		
		if (!$str=file_get_contents($file_count)) $str=':';
		list($time, $count)=explode(':',$str);
	
		if ($ctime!=$time) 
		{
			$count='1';
		}
		else $count+=1;
	
		if ( $count > $config['limit_attack_mode'] ) $attack_mode=true;
		
		if (!file_put_contents($file_count, $ctime.':'.$count)) exit('Установите права на запись для файла '.__DIR__.'/count_attack.txt');
	}
	
	
	if ($search=$antiddos->isBot()) // Проверка на поискового бота
	{			
		if ($antiddos->checkBot($search)) // Проверяем подлинность бота. Если проверка не нужна, просто переопределяем переменную $config['search_hosts']=array()
		{ 
			$status='search bot';
			$antiddos->addWhitelist('search bot', $config['whitetime'] ); // Добавляем в белый список 
		}
		else 
		{
			$status='fake bot';
			$antiddos->inlog('badbot');
			
			$antiddos->addBanlist('fake bot '.$antiddos->desc, $config['bantime']); // В черный список
		} 			
	}
	elseif($attack_mode)
	{
		$antiddos->attackMode();
		
		echo 'На сайт идет ддос, пожалуйста, не обновляйте страницу, чтобы не попасть в бан. Попробуйте зайти попозже.';
		exit;
	
	}	
	/*
	elseif (isset($config['referer'][0]) && $r=$antiddos->goodReferer()) // Реферер
	{
		$antiddos->inlog('referer');
		
		$antiddos->addWhitelist($r.' good referer', $config['referertime'] );
	}
	*/
	/*
	elseif (!$config['limit']) // Если лимит запросов не задан, добавляем все IP в бан. Для удаления из бана пользователь должен будет пройти каптчу
	{
		$antiddos->addBanlist('proactive ban', $config['proactivetime']); // Баним на час
	}
	*/
	elseif ($antiddos->excessLimit())  // Проверка на превышение лимита
	{
 		$status='ban';
		$antiddos->addBanlist('limit exceeded', $config['bantime']);
	}
	
	if (!isset($testAntiddos)) unset($config, $antiddos);
}
else $status='white';


 
