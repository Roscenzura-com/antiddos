<?php
// Ветка поддержки скрипта: http://ddosforum.com/threads/602/

 
$url=$_SERVER['REQUEST_URI']; // Если ддосят только главную поставьте $url='/';


if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
{
 	$_SERVER['REMOTE_ADDR']=$_SERVER['HTTP_CF_CONNECTING_IP'];
}


if (is_file(__DIR__.'/ban/'.$_SERVER['REMOTE_ADDR'])) // В бане
{
	if (!is_file(__DIR__.'/captcha_ip/'.$_SERVER['REMOTE_ADDR']))	
	{	
		echo 'Вы забанены. Напишите пожалуйста на admin@ddosforum.com, если блокировка необоснована. ';
		
		include(__DIR__.'/config.php');
		
		include(__DIR__.'/cloudflare.class.php');
		
		$cf = new Cloudflare($configCF);
		
		$cf->ip=$_SERVER['REMOTE_ADDR'];
		$cf->country= $_SERVER['HTTP_CF_IPCOUNTRY'];
		$cf->countries=$configCF['countries']; 
		
		$cf->auth($configCF['email'], $configCF['key'], $configCF['zone']); // авторизация на Cloudflare
		
		$desc='antiddos '.$cf->country.' '.date('Y-m-d'); // комментарий для Cloudflare
		
		if ( !$cf->checkCountry() ) // Не целевой трафик
		{
			$cf->counter();
			
			$cf->addcountry($desc, 'challenge'); // вторым параметром передается способ блокировки: challenge (каптча), block (блок), js_challenge (ява скриптовая каптча, иногда обходится ддос ботами)
		}
		elseif ($cf->country=='RU') // Россию блокируем точечно 
		{
			$cf->addip($desc, 'challenge');
		
		}
		else $cf->addrange($desc, 'challenge');
		
		copy(__DIR__.'/ban/'.$_SERVER['REMOTE_ADDR'], __DIR__.'/captcha_ip/'.$_SERVER['REMOTE_ADDR']);
	}	

	/*
	header('HTTP/1.0 403 Forbidden');
	exit;*/
	
	
	
	unset($config, $cf);
}
elseif (!is_file(__DIR__.'/white/'.$_SERVER['REMOTE_ADDR']) && $_SERVER['REQUEST_URI']==$url )
{	
	include(__DIR__.'/config.php');
	include(__DIR__.'/antiddos.class.php');
	

	$antiddos = new Antiddos($config, $_SERVER['REMOTE_ADDR']);
	
	if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) $antiddos->country_code=$_SERVER['HTTP_CF_IPCOUNTRY'];

	
	// Добавляем в белый список хорошие рефереры на 10 дней. Боты могут подменять реферер, при постоянных атаках лучше отключить.
	if ($search=$antiddos->isBot()) // Проверка на поискового бота
	{			
		if ($antiddos->checkBot($search)) // Проверяем подлинность бота. Если проверка не нужна, просто переопределяем переменную $config['search_hosts']=array()
		{ 
			$antiddos->addWhitelist('search bot', $config['whitetime'] ); // Добавляем в белый список 
		}
		else 
		{
			$antiddos->inlog('badbot');
			
			$antiddos->addBanlist('fake bot '.$antiddos->desc, $config['bantime']); // В черный список
		} 			
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
 	
		$antiddos->addBanlist('limit exceeded', $config['bantime']);
	}
	
	if (!isset($testAntiddos)) unset($config, $antiddos);
}