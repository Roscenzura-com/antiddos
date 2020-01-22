<?php
class Antiddos
{ 
	public $dir='';
	public $url=false; // главная $url='/'; внутренняя $url='/inner.html';
	public $ip;
	public $conf;
	public $country_code=''; // назначается в файле include.php
	public $desc='';
	public $counter=1;
 
	function __construct($conf, $ip)
	{
		$this->dir=__DIR__.'/';
		
		$this->conf=$conf;
		$this->ip=$ip;
	}
	
	// Проверка на поискового бота
	function isBot()
	{
		foreach($this->conf['search_bots'] as $s=>$name) { if( strpos($_SERVER['HTTP_USER_AGENT'], $s) !== false ) return $name; }
  
		return false;
	}
	
	// Проверяем поискового бота на подлинность
	function checkBot($search)
	{
		if (!isset($this->conf['search_hosts'][$search])) return true;
  
  		$iphost=gethostbyaddr($this->ip);
		
  		foreach($this->conf['search_hosts'][$search] as $host) 
  		{
			if (substr($iphost, -strlen($host) )==$host) return true;
  		}
		
		$this->desc=$iphost;

		return false;
	}
	
	// Добавляем IP в черный или белый список
	function addip($list, $reason, $time)  // 86400 - cутки, 604800 - неделя
	{
		$t=time(); 

		$save=[$this->country_code, $_SERVER['REQUEST_URI'], $_SERVER['HTTP_USER_AGENT'], $reason, $t, ($t+$time)];
		
		return file_put_contents($this->dir.$list.'/'.$this->ip, implode(PHP_EOL, $save) );
	}
	
	
	// Хорошие рефереры
	function goodReferer()
	{
		foreach ($this->referer as $r) if (strpos($_SERVER['HTTP_REFERER'],$r)==true) return $r;
		
		return false;	
	}
	
	// Проверяем, превышен ли лимит
	function excessLimit()
	{		
		$id=$this->ip;
		
		if ($this->conf['counter']['url']==1)
		{
			$id.=$_SERVER['REQUEST_URI'];
		}
		
		if ($this->conf['counter']['user_agent']==1)
		{
			$id.=$_SERVER['HTTP_USER_AGENT'];
		}
		
		$file=$this->dir.'count/'.md5($id.date("ymdhi")); // Счетчик на минуту для каждой страницы с учетом браузера
		
		
		if (!file_exists($file)) // Если за минуту заходов с этого пользователя не было, создаем файл
		{
			file_put_contents($file, "1");
					
			return false; // не превышен
		}
		else
		{
			$this->counter=file_get_contents($file)+1;
			
			if ($this->counter > $this->conf['limit']) 
			{
				unlink($file); // Удаляем последний счетчик
				return true; // превышен
			}
			else
			{
				file_put_contents($file, $this->counter);
				
				return false; //  не превышен
			}
		}
	}

	
	function addBanlist($desc, $time)
	{
		return $this->addip('ban', $desc, $time);
	}
	
	function addWhitelist($desc, $time)
	{
		return $this->addip('white', $desc, $time);
	}	
	
	// Переместить ip из одного списка в другой
	function moveip($ip, $list1, $list2)
	{
	    if (!rename($this->dir.$list1.'/'.$ip, $this->dir.$list2.'/'.$ip)) unlink($this->dir.$list1.'/'.$ip);
	}
	
	//
	function inlog($name, $str='')
	{	
		$str=$this->ip." - [".date("Y-m-d h:i:s")."] ".$_SERVER['REQUEST_URI'].' - '.$_SERVER['HTTP_USER_AGENT'];
		
		file_put_contents($this->dir.'log/'.$name.'.txt', $str.PHP_EOL, FILE_APPEND); 
	}

}
?>
