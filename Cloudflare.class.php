<?php
//namespace Antiddos;

// https://api.cloudflare.com/#ip-access-rules-for-a-user-create-an-ip-access-rule
// https://developers.cloudflare.com/firewall/cf-firewall-rules/actions 
// block, challenge, js_challenge, managed_challenge, allow, log, bypass 


class Cloudflare
{ 
	public $ip;
	public $country;
	public $rule;
	public $response=[];
	public $dir='';
	public $countries=[]; // Страны целевого трафика	
	public $error='';
	public $test=false;
	public $testLog=[];
	
	
	private $urlrules='';
	private $urluser='';
	private $authHeader;
	
	private $type;
	private $target;	
	private $handler='';
	private $rules=[]; 
	private $curlSet=[];
	private $errorsFile;
	private $errorResponse;
 
 
	function __construct($config)
	{
		$this->conf=$config;
			
		$this->countries=$config['countries'];

		$this->dir=__DIR__.'/cloudflare/';
		
		$this->errorsFile=__DIR__.'/log/errors.txt';
		
		$this->curlLogFile=__DIR__.'/log/curlLog.txt';
		
		$this->ip=$_SERVER['REMOTE_ADDR'];
		$this->country=$_SERVER['HTTP_CF_IPCOUNTRY'] ?: '';	
			  
		$this->urluser="https://api.cloudflare.com/client/v4/user/";
		$this->urlrules="https://api.cloudflare.com/client/v4/zones/".$this->conf['zone'].'/firewall/access_rules/rules';
		
		$this->authHeader= array(
			 'X-Auth-Email: '.$this->conf['email'],
			 'X-Auth-Key: '.$this->conf['key'],
			 'Content-Type: application/json'
		);
	}
	
	//
	function set($type, $rule=false, $target=false)
	{
		$this->type=$type;
		$this->target=$target ?: $this->target($type);
		
		if (!$this->rule=$rule)
		{
			if ($this->target=='ip_range') $this->rule=$this->ipMask24($this->ip); else $this->rule=$this->{$this->target};	
		}
		
		$this->loadLocalRules($type);
	}
	
	
	//
	function target($type)
	{
		return $this->conf['target'][$type];
	}
 
	
	//
	function isIp6($ip=false)
	{
		return filter_var( ($ip ?: $this->rule), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
	}
 
	
	function checkCountry($country=false) 
	{
		$country=$country ?: $this->country;
		if ( !$country || empty($this->countries) ) return true; 
		
		if (isset($this->countries[$country]) && $this->countries[$country]) return true; else return false;
	}
 
 
	function counterGeoBots()
	{
		$file=$this->dir.'countries/'.$this->country;
		
		if (!file_exists($file)) $c=1; else $c=file_get_contents($file);
		if (!is_numeric($c)) $c=1;
		
		return file_put_contents($file, $c+1);
	}
	
	
	function response()
	{
		$handler=curl_init();
			
		$curlSet=[CURLOPT_RETURNTRANSFER=>1, CURLOPT_HTTPHEADER=>$this->authHeader]+$this->curlSet;
		
	//	 print_r($curlSet);
		curl_setopt_array($handler, $curlSet);		
		$response = curl_exec($handler);

		$this->response=json_decode($response,true);
			
	//	print_r($this->response);
		
		$this->curlSet=[];
	
		curl_close($handler);

		return $this->response;
	}
	
	
	function curlSet(...$args)
	{
		if (!is_array($args[0])) 
		{
			$this->curlSet[$args[0]]=$args[1];
		}
		else
		{
			$this->curlSet+=$args[0];
		}
	}
	
	
	function send($data)
	{	 
		$this->curlSet(CURLOPT_POSTFIELDS, json_encode($data));
	}
	

	function auth()
	{		
		if (!$this->conf['email']) return false; else return true;
	}
	

	//
	function ruleExists($rule=false)
	{
		if (!$this->type) return $this->error('Не задан тип правила');
		$rule=$rule ?: $this->rule; 

		return isset($this->rules[$this->type][$rule]);
	}
	
		
	//
	function getRule($rule=false, $value=false)
	{
		$rule=$rule ?: $this->rule; 
		if (!$this->ruleExists($rule)) return false;

		if (!$value) return $this->rules[$this->type][$rule]; else return $this->rules[$this->type][$rule][$value];
	}
	
	
	//
	function getRuleId($rule=false)
	{
		return $this->getRule($rule, 'id');
	}
	
	//
	function getRuleMode($rule=false)
	{
		return $this->getRule($rule, 'mode');
	}
	
	
	//	
	function addRule($rule=false, $notes=false, $mode=false, $type=false)
	{	
		if ($type) $this->set($type, $rule); 
			
		if (!$type=$this->type) return $this->error('Не задан тип правила');
		
		$this->curlSet(CURLOPT_URL, $this->urlrules);
 
		$mode=$mode ?: $this->blockMethod();
		$notes=$notes ?: $this->getNotes();
		$rule=$rule ?: $this->rule; 

		$data=['mode'=>$mode, 'notes'=>$notes, 'configuration'=>['target'=>$this->target, 'value'=>$rule]];

		$this->send($data);
		
		if ( $r=$this->response() and isset($r['result']['id'])  )
		{
			$this->addLocalRule($rule, $r['result']['id'], $mode);
			
			return true;
		}
		else
		{
			return $this->error('Ошибка добавления правила для '.$rule);
		}
	}
		
	
	//
	function updateRule($mode, $note=false)
	{
		if (!$rule=$this->getRule()) return $this->error('Правила '.$this->rule['value'].' нет в списке правил '.$type);

		if ($mode==$rule['mode'] and !$note)
		{
		 	if ($this->test) $this->testLog('Без изменений: режим блокировки '.$mode.' уже установлен для правила '.$this->rule['value']);
			return true;		
		}	
					
		$this->curlSet( [CURLOPT_URL => $this->urlrules.'/'.$rule['id'], CURLOPT_CUSTOMREQUEST=>'PATCH'] );
				
		$data=['mode'=>$mode];
		if($note) $data['notes']=$note;
		
		$this->send($data);

		if ( $this->isSuccess( $this->response() ) )
		{
			$this->updateLocalRule($mode);
			return true;
		}
		else
		{
			return $this->error('Ошибка изменения метода блокировки для правила '.$rule['value']);
		}
	}
	
	
	//
	function delRule($rule=false) 
	{  
		if (!$type=$this->type) return $this->error('Не задан тип правила');
		
		$rule=$rule ?: $this->rule; 
		
		if ($id=$this->getRuleId($rule))
		{
			$this->curlSet([CURLOPT_URL=>$this->urlrules.'/'.$id, CURLOPT_CUSTOMREQUEST=>'DELETE']);
			$this->delLocalRule($rule);
		}
		else
		{
			return $this->error('Правила '.$rule.' нет в локальном хранилище');
		}
		
		if ( $this->isSuccess( $this->response() ) )
		{	
			return true;
		}	
		else
		{	
			return $this->error('Ошибка удаления правила firewall');
		}
	}
 
 
	//
	function blockMethod($type=false)
	{
		$type=$type ?: $this->type;
		return ($this->conf['block_method'][$type] ?: 'block');
	}
	
	
	// Добавляем IP 
	function addIp($ip=false, $notes=false, $mode=false)   
	{	 
		return $this->addRule( $ip, $notes, $mode, 'ip' ); 
	}
	
	
	// Добавляем страну
	function addCountry($country=false, $notes=false, $mode=false)   
	{
		$country=$country ?: $this->country;
				
		if ($this->checkCountry($country))
		{
			return $this->error('Страна '.$country.' из белого списка, нельзя добавить в firewall');
		}	

		return $this->addRule($country, $notes, $mode, 'country'); 
	}


	// Добавляем диапазон
	function addRange($range, $notes=false, $mode=false)   
	{
		return $this->addRule($range, $notes, $mode, 'ip_range'); 
	}
	
	
	// 
	function addMask24($ip=false, $notes=false, $mode=false)   
	{
		return $this->addRule( $this->ipMask24($ip), $notes, $mode, 'ip_range' ); 
	}
	
	
	//
	function setCaptcha($rule, $notes='')
	{	
		return $this->addrule($rule, $notes, 'challenge'); 
	}
	
	
	//
	function ipMask24($ip=false)   
	{
		$ip=explode('.', ($ip ?: $this->ip) );
		
		return  $ip[0].'.'.$ip[1].'.'.$ip[2].'.0/24'; 
	}
	
	
	// IP прошел каптчу
	function captchaTrue()
	{
		rename(__DIR__.'/ban/'.$this->ip, __DIR__.'/captcha_ip/'.$this->ip);
	} 
	
	
	//
	function getNotes($type=false)
	{
		$type=$type ?: $this->type;
		$notes=$this->conf['notes'];
		
		$find=[ '%time%', '%country%', '%url%', '%browser%', '%ip%' ];
		$replace=[ date("Y-m-d H:i:s"), $this->country, $_SERVER['REQUEST_URI'], $_SERVER['HTTP_USER_AGENT'], $this->ip ];

		if (!isset($notes[$type])) $type='default';
		
		$notes=str_replace( $find, $replace, $notes[$type] );
 
		return $notes;
	}
	
	
	//
	function getRules($type=false)
	{	
		if (!isset($this->rules[$type]) ) $this->loadLocalRules($type);
		
		return $this->rules[$type];
	}
	
	//
	function loadData($type)
	{
		$type=$type ?: $this->type;
		$fileData=$this->dir.$type.'.data';

		if (!file_exists($fileData)) return [];
		
		if ($c=file_get_contents($fileData)) return json_decode($c,true); else return [];
	}
	
	
	//
	function loadLocalRules($type=false)
	{	
		$this->rules[$type]=$this->loadData($type);
	}
	
	
	//
	function isSuccess($response=false)
	{
		if (!$response) $response=$this->response;
		
		return ( isset($response['success']) && $response['success']==true );	
	}
	
	
	//
	function updateLocalRule($mode)
	{		
		$this->rules[$this->type][$this->rule]['mode']=$mode;
	}
	
	
	//
	function addLocalRule($rule, $id, $mode)
	{		
		$this->rules[$this->type][$rule]=['id'=>$id, 'mode'=>$mode];
	}


	//
	function delLocalRule($rule)
	{
		$rule=$rule ?: $this->rule;
		unset($this->rules[$this->type][$rule]);
	}
	
	
	//
	function saveRules($type=false, $data=false)
	{			
		if (!$type)
		{
			foreach ($this->rules as $type => $data) $this->saveRules($type, $data);
		}
		
		file_put_contents($this->dir.$type.'.data', json_encode($data) ); 
		
		unset($this->rules[$type]);
	}
	
	//
	function logError($error)
	{	
		file_put_contents($this->errorsFile, $error.($this->errorResponse ? PHP_EOL.$this->errorResponse : PHP_EOL).'----------------'.PHP_EOL, FILE_APPEND);
	}
	
	
	//
	function error($error, $stop=false)
	{			
		if (!empty($this->response['errors'])) $error.=': '.$this->getError();

		$this->logError($error);
		
		if ($stop) exit($error);
		
		$this->error=$this->errorResponse='';
		
		return false;
	}	
	
	
	//
	function getError()
	{ 
		if ($this->errorResponse) return $this->errorResponse;
		
		if (isset($this->response['errors'][0]['message']))
		{
			$msg=$this->response['errors'][0]['message'];
			$error=str_replace(
								['firewallaccessrules.api.duplicate_of_existing', 'firewallaccessrules.api.not_found', 'Please wait and consider throttling your request speed'], 
								['правило уже существует', 'правила нет в firewall', 'Слишком частые запросы к API Cloudflare'], 
				   				$msg 
							  );
			
			if ($msg==$error) $this->errorResponse=print_r($this->response,true); else return $error;
		}

		return 'неизвестная ошибка';
	}
	
	
	//
	function curlLog($rule)
	{	
		$date=', '.date("Y-m-d H:i:s");
		$rule_country='правила '.$rule;
		if ($this->isSuccess())
		{
			$log='Запрос выполнен для '.$rule_country.$date;
		}
		else 
		{
			$log='Запрос не выполнен для '.$rule_country.', '.$this->getError().$date;
		
		} 
		
		file_put_contents($this->curlLogFile, $log.PHP_EOL, FILE_APPEND);
	}
	
	
	//
	function __destruct()
	{
		$this->saveRules();
	}
}
?>
