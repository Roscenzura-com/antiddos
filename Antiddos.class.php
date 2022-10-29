<?php

class Antiddos
{ 
	public $dir='';
	public $url=false; // главная $url='/'; внутренняя $url='/inner.html';
	public $ip;
	public $conf;
	public $country_code=''; 
	public $iphost='';
	public $counter=0; // счетчик посещений на целевую страницу
	public $countAll=0;  // счетчик всех посещений сайта в минуту
 
	function __construct($conf)
	{
		$this->dir=__DIR__.'/';
		
		$this->conf=$conf;
		$this->ip=$_SERVER['REMOTE_ADDR'];
		
		if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) $this->country_code=$_SERVER['HTTP_CF_IPCOUNTRY'];
		
	}
	
	// Проверка на поискового бота
	function isBot()
	{
		foreach($this->conf['search_bots'] as $s=>$name) { if( strpos($_SERVER['HTTP_USER_AGENT'], $s) !== false ) return $name; }
  
		return false;
	}
	
	// 
	function isWhite()
	{	
		return is_file($this->dir.'white/'.$this->ip);
	}
	
	// 
	function isBan()
	{	
		return is_file($this->dir.'ban/'.$this->ip); 
	}
	
	//
	function getIpHost()
	{	
		if (!$this->iphost) $this->iphost=gethostbyaddr($this->ip);

		return $this->iphost; 
	}	
	
	// Проверяем поискового бота на подлинность
	function checkBot($search)
	{
		if (!isset($this->conf['search_hosts'][$search])) return true;
  
  		$iphost=$this->getIpHost();
		
  		foreach($this->conf['search_hosts'][$search] as $host) 
  		{
			if (substr($iphost, -strlen($host) )==$host) return true;
  		}

		return false;
	}
	
	// Добавляем IP в черный или белый список
	function addip($list, $reason, $time=false)  // 86400 - cутки, 604800 - неделя
	{
		$t=time(); 

		$save=[$this->country_code, $_SERVER['REQUEST_URI'], $_SERVER['HTTP_USER_AGENT'], $reason, $t, ($time ? ($t+$time) : '') ];
		
		return file_put_contents($this->dir.$list.'/'.$this->ip, implode(PHP_EOL, $save) );
	}
	
	
	// Хорошие рефереры
	function goodReferer()
	{
		if (empty($this->referer)) return false;
		
		foreach ($this->referer as $r) if (strpos($_SERVER['HTTP_REFERER'],$r)==true) return $r;
		
		return false;	
	}
	
	// Счетчик общих заходов на сайт   
	function attackModeCounter()
	{
		if ($this->countAll) return $this->countAll;
		
		$file_count=$this->dir.'count_attack.txt';
		
		/*$h=date("h");
		$i=date("i");
		
		$i+=$i % 2;*/
		
		$ctime=date("h.i");

		if (!$str=@file_get_contents($file_count)) $str=':';
		list($time, $count)=explode(':',$str);
	
		if ($ctime!=$time) 
		{
			$count='1'; // Сбрасываем счетчик
		}
		else $count+=1;
		
		if (!file_put_contents($file_count, $ctime.':'.$count)) exit('Установите права на запись для файла '.$file_count);

		return $this->countAll=$count;
	}
	
	
	// Переключатель режима "Под Атакой" 
	function attackModeSwitcher()
	{
		if (!$this->conf['attack_mode']) return 'Off';
		
		if (!$this->countAll) $this->attackModeCounter(); // Счетчик всех посещений за минуту
		
		if ($this->countAll > $this->conf['limit_attack_mode']) return 'On'; // else return 'Off';
	}


	// Проверяем, превышен ли лимит обращений к сайту в режиме "Под Атакой". 
	function excessLimitAttackMode()
	{		
		$file=$this->dir.'count/'.$this->ip.date("hi");

		if ($this->conf['limit_attack_mode_ban']==1) return true; // превышен
		
		if ($this->excessLimit($file, $this->conf['limit_attack_mode_ban'])) return true; else return false;
	}
	
	// Проверяем, превышен ли лимит
	function excessLimitBan()
	{		
		$id=$this->ip.$this->conf['url'].$this->conf['user_agent'];
		
		$file=$this->dir.'count/'.md5($id.date("ymdhi")); // Счетчик на минуту для каждой страницы с учетом браузера
		
		if ($this->excessLimit($file, $this->conf['limit'])) return true; else return false;

	}
	
	// Проверяем, нужно ли предупредить пользователя, чтобы сделал паузу
	function excessLimitWarn()
	{
		if (!$this->counter && $this->excessLimitBan()) return true;
		
		if ( $this->counter > $this->conf['limit_warning']  ) return true; else return false;
	}


	//
	function excessLimit($counterFile, $limit)
	{
		if ($this->counter) return $this->counter;
		
		if (!file_exists($counterFile)) // Если за минуту заходов с этого пользователя не было, создаем файл
		{
			if (file_put_contents($counterFile, "1")) $this->counter=1;
			
			if ($limit<2) return true; else return false;
		}
		else
		{
			$this->counter=file_get_contents($counterFile)+1;
			
			if ($this->counter >= $limit) 
			{
				unlink($counterFile); // Удаляем последний счетчик
				return true; // превышен
			}
			else
			{
				file_put_contents($counterFile, $this->counter);
				return false;
			}
		}
	}
	
	
	//
	function addBanlist($desc, $time=0)
	{
		return $this->addip('ban', $desc, ($time ?: $this->conf['bantime']) );
	}
	
	function addWhitelist($desc, $time=0)
	{
		return $this->addip('white', $desc );
	}	
	
	// Переместить ip из одного списка в другой
	function moveip($ip, $list1, $list2)
	{
	    if (!rename($this->dir.$list1.'/'.$ip, $this->dir.$list2.'/'.$ip)) unlink($this->dir.$list1.'/'.$ip);
	}
	
	//
	function inlog($name, $str='')
	{	
		$str=$this->ip." - [".date("Y-m-d h:i:s")."] ".$_SERVER['REQUEST_URI'].' - '.$_SERVER['HTTP_USER_AGENT']. ($str ? ' - '.$str : '');
		
		file_put_contents($this->dir.'log/'.$name.'.txt', $str.PHP_EOL, FILE_APPEND); 
	}

}
?>
