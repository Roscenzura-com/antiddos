<?php
// Ветка поддержки скрипта: http://ddosforum.com/threads/602/
/*
$memory=memory_get_usage();
$start = microtime(true);
*/
$url=$_SERVER['REQUEST_URI']; // Если ддосят только главную поставьте $url='/'; для экономии ресурсов. 

//var_dump($url);
$status=false;

// Это можно убрать, если переменная $_SERVER['REMOTE_ADDR'] содержит IP пользователя, а не Cloudflare
if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
{
 	$_SERVER['REMOTE_ADDR']=$_SERVER['HTTP_CF_CONNECTING_IP'];
}

if (isset($_COOKIE['cf_clearance']))  // Кука прохождения капчи, сработает только если IP был добавлен в фаерволл Cloudflare  
{	 
	$cf_clearance=$_COOKIE['cf_clearance'];
}
else
{
	$cf_clearance=false;
}
 

if (is_file(__DIR__.'/ban/'.$_SERVER['REMOTE_ADDR'])) // ip в бане
{
	$status='ban';
	
	include(__DIR__.'/config.php');
	include(__DIR__.'/Cloudflare.class.php');
	
	$cf = new Cloudflare($config['CF']);
	
	$type=$cf->isIp6() ? 'ip6' : 'ip';
	$cf->set($type);

	if (!is_file(__DIR__.'/captcha_ip/'.$cf->ip)) // Капчу еще не проходили, то есть забанены первый раз	
	{		
		if ($cf->ruleExists()) 
		{		
			if ($cf_clearance) // Кука Cloudflare после прохождения каптчи 
			{
				$cf->captchaTrue(); // Удаляет IP из бана
				//echo 'captchaTrue';
			
				$status='captcha_true';	
			}
		}
		else
		{
			if ( !$cf->auth() ) // авторизация на Cloudflare
			{
				exit ('Не установлены настройки подключения к Cloudflare.'); 
			}
								
			$cf->addIp();  
			
			$cf->counterGeoBots(); // Счетчик по странам, папка countries, в админке: Cloudflare->География ботов
	
			$cf->set('country');
			if ( !$cf->checkCountry() ) // Страна не целевого трафика
			{
				if (!$cf->ruleExists()) 
				{
					if ( !$cf->addCountry() )
					{
						$cf->addMask24(); 
					}
				}	
			}
		}	
	}
	else // Если уже проходили капчу ранее, то есть были разбанены 
	{
		if ( !$cf->auth() ) // авторизация на Cloudflare
		{
			exit ('Не установлены настройки подключения к Cloudflare.'); 
		}
	
		if ($id=$cf->ruleExists()) // Проверяем есть ли такой IP в правилах
		{
			// Баним жестко без проверки на каптчу, потому что пользователь уже проходил каптчу и снова превысил лимит
			$r=$cf->updateRule( "block", 'antiddos 2.0 change rule '.date('Y-m-d') ); // .date('Y-m-d')	
		}
		else
		{
			$cf->addIp();
		}
	}
	
	if ($cf->error) echo $cf->error.'<br>';
	
	if ($status=='ban')
	{
		echo 'Вы временно забанены, попробуйте зайти попозже. '.($config['adminEmail'] ? 'Если произошла ошибка, напишите на Email '.$config['adminEmail'] : '');
		exit();
	}
}
elseif (is_file(__DIR__.'/white/'.$_SERVER['REMOTE_ADDR']))
{
	$status='white';

}
elseif ( $_SERVER['REQUEST_URI']==$url  )
{	
	 
	include(__DIR__.'/config.php');
	include(__DIR__.'/Antiddos.class.php');

	$antiddos = new Antiddos($config); // $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_CF_IPCOUNTRY']
	
	if ($search=$antiddos->isBot()) // Проверка на поискового бота
	{			
		if ($antiddos->checkBot($search)) // Проверяем подлинность бота. Если проверка не нужна, просто переопределяем переменную $config['search_hosts']=array()
		{ 
			$status='search bot';
			$antiddos->addWhitelist('search bot'); // Добавляем в белый список 
		}
		else 
		{
			$status='fake bot';
			$antiddos->inlog('badbot', $antiddos->getIpHost() );
			
			$antiddos->addBanlist('fake bot '.$antiddos->getIpHost(), $config['bantime']); // В черный список
		} 			
	}
	elseif( $antiddos->attackModeSwitcher()=='On' )
	{
		if ($antiddos->excessLimitAttackMode()) $antiddos->addBanlist('attack mode limit exceeded', $config['bantime']);
		
		echo '<h1>На сайт идет ддос, пожалуйста, не обновляйте страницу, чтобы не попасть в бан. Попробуйте зайти попозже.</h1>';
		exit;
	}	
	elseif ($r=$antiddos->goodReferer()) // Реферер
	{
		$antiddos->inlog('referer');
		
		// $antiddos->addWhitelist($r.' good referer', $config['referertime'] );
	}
	elseif ($antiddos->excessLimitBan())  // Проверка на превышение лимита
	{
 		$status='ban';
		$antiddos->addBanlist('limit exceeded', $config['bantime']);
	}
	elseif ($antiddos->excessLimitWarn())
	{
		$status='warn';
		echo '<h1>С вашего IP слишком много заходов в минуту. Пожалуйста, не обновляйте страницу в течении минуты, чтобы не попасть в бан.</h1>';
		exit;
	}
		
//	echo $antiddos->counter;
}
