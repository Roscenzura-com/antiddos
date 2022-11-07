<?PHP
/*use Antiddos;*/

/*use Cloudflare;*/
 

class Admin
{ 
	private $conf;
	public $maxСycles=10000; 
	private $container=[];
	private $cloudflare;
	private $response;
	
	public $unbanLog=[];
	public $error='';
	
	function __construct($conf)
	{
		$this->dir=__DIR__.'/';

		$this->conf=$conf;
		$this->time=time();
	}
	
	
	function load($class, $conf)
	{
		if (isset($this->container[$class])) return $this->container[$class];
		
		return $this->container[$class]=new $class($conf);
	}
	
	
	function getIpData($fileip) 
	{
		if (!file_exists($fileip)) return false;
		
		$ipData=explode(PHP_EOL, file_get_contents($fileip) );
		$ipData=array_pad($ipData, 6, '');

		return array_combine(['country', 'url', 'user_agent', 'reason', 'time', 'timer'],  $ipData);
	}

	
	function clearDir($dir)
	{
		$dir=basename($dir);
		
		$dir=$this->dir.$dir;
		
		$r=true;
		$cycle=1;
		if ($handle = opendir($dir)) {
			while ( false !== ($file = readdir($handle)) ) 
			{ 
				if ($file != "." && $file != "..") $r=unlink($dir.'/'.$file);
				
				// echo $dir.'/'.$file.'<br>';
				
				if ($cycle==$this->maxСycles) break;
				$cycle++;
			}
			closedir($handle); 
		}
		
		return $r;
	}
	
	//
	function clearList($dir)
	{
		return $this->clearDir($dir);
	}
 
	
	//
	function del($ip, $status='ban')
	{
		$ip=basename($ip);
		$ipfile=$this->dir.$status.'/'.$ip;
		
		if (file_exists($ipfile))
		{
			if (!unlink($ipfile)) return false;
			if ($status=='ban') @unlink($this->dir.'captcha_ip/'.$ip);
			
			$this->delRule($ip, 'ip');
		}
		
		return true;
	}
	
	
	//
	function delRule($rule, $type=false)
	{
 		if (!$type) $type=$this->getType($rule);
		
		$CF=$this->load('Cloudflare', $this->conf['CF']);
		$CF->auth();
		
		if (!$CF->delRule($rule, $type)) 
		{
			$this->error=$CF->error;
			$this->response=$CF->response;
	
			return false;
		}
		
		return true;
	}
	
	
	//
	function getType($value)
	{		
		if (filter_var($value, FILTER_VALIDATE_IP) || filter_var($value.'.0', FILTER_VALIDATE_IP)) return 'ip';
		elseif (strlen($value)==2) return 'country';
		else return false;
	}
	
	
	//
	function getGeoBots($sort='asc')
	{
		$list=[];
		$items = glob($this->dir.'countries/*');
		foreach ($items as $codeFile)
		{
			if (substr($codeFile,-3)!='txt')
			{
				$code=basename($codeFile, '.txt');
				
				$list[$code]=file_get_contents($codeFile);
			}
		}
			
		if ($sort=='asc')  asort($list); else arsort($list);
		
		return $list;
	}
	
	
	//
	function getRules($list)
	{
		$CF=$this->load('Cloudflare', $this->conf['CF']);
		
		return $CF->getRules($list);
	}
	
 
	
	
	//
	function cronTimer($action)
	{
		$timerFile=$this->dir.'cron/timer.data';
		$time=time();
 		
		if (!file_exists($timerFile)) 
		{
			$data=array_fill_keys(['banlist', 'counter'], $time);
		}
		elseif (!$data=unserialize(file_get_contents($timerFile)))  exit('Проблема с файлом '.$timerFile);
		
		if (isset($data[$action]))
		{
		    $lastUpdateTime=$data[$action];
			
			$data[$action]=$time;
			file_put_contents($timerFile, serialize( $data ));
			
			return $time-$lastUpdateTime > $this->conf['cron'][$action]; 
		}
	}
	
	
	//
	function unbanByTime()
	{
		$cycle=1; 
		$return='';
		
		$items = glob($this->dir.'ban/*');
		foreach ($items as $ipfile)
		{
			$ip=basename($ipfile);

			if ( filter_var($ip, FILTER_VALIDATE_IP) )
			{
				$data=$this->getIpData($ipfile);  	
				if ($data['timer']  &&  ($data['timer']<$this->time ))
				{		
					if ( !$this->del($ip) ) return false;
			
					$this->unbanLog[]='IP '.$ip.' разбанен (дата блокировки: '.date("Y-m-d H:i:s", $data['time']).', дата разблокировки: '.date("Y-m-d H:i:s", $data['timer']);
				}
				
				if ($cycle==$this->maxСycles) break;
				$cycle++;
			}
		}

		return true;
	}
	
	
	//
	function cronLog($action)
	{
		file_put_contents($this->dir.'log/cron.txt', $action.', время: '.date("Y-m-d H:i:s").PHP_EOL, FILE_APPEND );
	}
	
	
	//
	function getLog($log)
	{
		$content=file_get_contents($this->dir.'log/'.$log.'.txt');

		$content=str_replace([' ', PHP_EOL], ['&nbsp;', '<br>'], $content );
		 
		return $content;
	}
	

	//
	function clearLog($log)
	{
		file_put_contents($this->dir.'log/'.$log.'.txt', '');
	}
	
}
?>
