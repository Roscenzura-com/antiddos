<?php
//namespace Antiddos;

// https://api.cloudflare.com/#firewall-rules-create-firewall-rules
//  Create firewall rules
// https://developers.cloudflare.com/firewall/cf-firewall-rules/actions 
// block, challenge, js_challenge, managed_challenge, allow, log, bypass 


class Cloudflare
{ 
	public $authHeader=[];
	public $urlzone	='';
	public $urluser='';	
	public $handler='';
	private $id=['ip'=>0, 'ip_range'=>0, 'country'=>0]; // id записи в firewall Cloudflare
	public $ip;
	public $country='';
	public $ip_range='';
	public $response=[];
	public $result=[];	
	public $savedir='';
	private $conf;

	private $rules=['ip'=>false, 'ip_range'=>false, 'country'=>false];
	private $rulesById=[];
	public $countries=[]; // Страны целевого трафика	
	public $error='';
	private $errorsFile;
	
	public $test=false;
	public $testLog=[];
	
 
	function __construct($config)
	{
		$this->conf=$config;
			
		$this->countries=$config['countries'];
		
		$this->handler= curl_init();
		
		$this->savedir=__DIR__.'/cloudflare/';
		
		$this->errorsFile=__DIR__.'/log/errors.txt';
		
		$this->ip=$_SERVER['REMOTE_ADDR'];
		
		$this->country=$_SERVER['HTTP_CF_IPCOUNTRY'] ?: '';	
	}
 
	
	function checkCountry() 
	{
		if ( !$this->country || empty($this->countries) ) return true; 
		
		if (isset($this->countries[$this->country]) && $this->countries[$this->country]) return true; else return false;
	}
 
 
	function counterGeoBots()
	{
		$file=__DIR__.'/countries/'.$this->country;
		
		if (!file_exists($file)) $c=1; else $c=file_get_contents($file);
		if (!is_numeric($c)) $c=1;
		
		return file_put_contents($file, $c+1);
	}
	
	
	function response()
	{
		$response = curl_exec($this->handler);

		$this->response=json_decode($response,true);
		
		if ($this->test) $this->testLog($this->response); 

		return $this->response;
	}
	
	
	
	function close()
	{
		curl_close($this->handler);
		
		/*if ($this->test) file_put_contents($this->savedir.'test.txt', implode(PHP_EOL.'----------------'.PHP_EOL, $this->testLog), FILE_APPEND);*/
	}
	


	function auth()
	{		
		if (!$this->conf['email']) return false;
		
		$this->authHeader= array(
			 'X-Auth-Email: '.$this->conf['email'],
			 'X-Auth-Key: '.$this->conf['key'],
			 'Content-Type: application/json'
		  );
		  
		$this->urlzone="https://api.cloudflare.com/client/v4/zones/".$this->conf['zone'].'/';
		$this->urluser="https://api.cloudflare.com/client/v4/user/";
 
		curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->handler, CURLOPT_HTTPHEADER, $this->authHeader);
		
		return true;
	}
	
	
	function send($data)
	{
		curl_setopt($this->handler, CURLOPT_POSTFIELDS, json_encode($data) );
	}
	
	
	function changeRuleMode($id, $mode, $note=false)
	{
		if (isset($this->rulesById[$id]))
		{
			$rule=$this->rulesById[$id];
			$cmode=$rule['mode'];
			  
			if ($mode==$cmode) return true;
		}
		else return false;		
		
		curl_setopt($this->handler, CURLOPT_URL, $this->urlzone.'firewall/access_rules/rules/'.$id);	
		curl_setopt($this->handler, CURLOPT_CUSTOMREQUEST, 'PATCH');
				
		$data=['mode'=>$mode];
		if($note) $data['notes']=$note;
		
		$this->send($data);

		if ( $this->isSuccess( $this->response() ) )
		{
			$this->save( $rule['target'], $rule['value'], $mode);
			return true;
		}
		else
		{
			$this->error='Ошибка изменения метода блокировки для правила '.$rule['value'].': '.$this->getError();
			$this->logError(true);
			
			return false;
		}
	}
	
	
	function addrule($add, $notes, $mode)
	{
		curl_setopt($this->handler, CURLOPT_URL, $this->urlzone.'firewall/access_rules/rules');	
		
		$data=['mode'=>$mode, 'notes'=>$notes, 'configuration'=>$add];

		$this->send($data);
		$r=$this->response();
		
		if (isset($r['result']['id'])  )
		{
			$this->result=$r['result'];
			$this->save( $add['target'], $add['value'], $mode, $r['result']['id'] );
			return true;
		}
		else
		{
			$this->error='Ошибка добавления правила для '.$add['value'].': '.$this->getError();
			$this->logError(true);
			return false;
		}
	}
	
	
	function delRule(...$params) // id or rule
	{
		/*if (!isset($params[1]))
		{
			$id=$params[0];
			$rule=$this->rulesById[$id];
			$target=$rule['target'];
			$value=$rule['value'];
		}*/
		
		$target=$params[1];
		$rule=$params[0];
		$rules=$this->getRules($target);

		if (isset($rules[$rule]['id']))
		{
			curl_setopt($this->handler, CURLOPT_URL, $this->urlzone.'firewall/access_rules/rules/'.$rules[$rule]['id'] );	
			curl_setopt($this->handler, CURLOPT_CUSTOMREQUEST, 'DELETE');
			
			unset($this->rules[$target][$rule]);   
			$this->saveRules($target);
		}
		else
		{
			$this->error='Запись '.$rule.' уже была удалена';
			$this->logError();	
			
			return false;
		}
		
		
		//$r=( $this->response() ?: ['error'=>true] ) + [ 'error'=>false, 'success'=>false ];

		if ( $this->isSuccess( $this->response() ) )
		{	
			return true;
		}	
		else
		{	
			$this->error='Ошибка удаления записи firewall: '.$this->getError();
			$this->logError(true);
			
			return false;
		}
	}
	
	
	//
	function delRuleIP($rule)
	{
		return $this->delRule($rule, 'ip');
	}
	
	//
	function delRuleRange($rule)
	{
		return $this->delRule($rule, 'ip_range');
	}
	
	//
	function delRuleCountry($rule)
	{
		return $this->delRule($rule, 'country');
	}	
	
	//
	function delRuleById($id)
	{
		if (!isset($this->rulesById[$id])) return false;
		
		return $this->delRule($this->rulesById[$id]['value'], $this->rulesById[$id]['target']);
	}
	
	//
	function blockMethod($type)
	{
		return ($this->conf['block_method'][$type] ?: 'block' );
	
	}

	//
	function getRuleId($value, $target)
	{
		$rules=$this->getRules($target);
		
		if (!isset($rules[$value])) return false; else return $rules[$value]['id'];
	}
	
	// Добавляем IP 
	function addip($notes='', $mode='block')   
	{
		if (!$notes) $notes=$this->country.' '.date("Y-m-d");
		
		return $this->addrule(['target'=>'ip', 'value'=>$this->ip], $notes, $mode); 
	}
	
	
	// Добавляем страну
	function addcountry($notes='', $mode='managed_challenge')   
	{
		if ($this->checkCountry())
		{
			$this->logError();
			$this->error='Страна '.$this->country.' из белого списка, нельзя добавить в firewall';
			   
			return false; 
		}
		
		if (!$notes) $notes='antiddos block country '.date("Y-m-d");
 	
		return $this->addrule(['target'=>'country', 'value'=>$this->country], $notes, $mode); 
	}
	

	// Добавляем диапазон
	function addrange($notes='', $mode='managed_challenge')   
	{
		if (!$notes) $notes=$this->country.' '.date("Y-m-d");
		
		return $this->addrule([ 'target' => 'ip_range', 'value' => $this->ip3byte($this->ip).'.0/24' ], $notes, $mode); 
	}
	
	
	// 
	function ip3byte($ip)
	{
		$ip=explode('.', $ip);
		
		return  $ip[0].'.'.$ip[1].'.'.$ip[2]; 		
	}
		
	/*
	function mask24($ip)   
	{
		$ip=explode('.', $ip);
		
		return  $ip[0].'.'.$ip[1].'.'.$ip[2].'.0/24'; 
	}
	*/
	
	
	// IP прошел каптчу
	function captchaTrue()
	{
		rename(__DIR__.'/ban/'.$this->ip, __DIR__.'/captcha_ip/'.$this->ip);
	} 
	
	
	///
	function getRules($target)
	{	
		if ($this->rules[$target]===false)
		{
			$file=$this->savedir.$target.'.txt';
			$c=file_get_contents($file);
			
			if (!$c) $this->rules[$target]=[]; else $this->rules[$target]=json_decode($c,true);
		}
		
		return $this->rules[$target];
	}
	

	//
	function save($target, $value, $mode, $id=false)
	{		
		$arr=$this->getRules($target);
		
		if ($target=='ip_range') $value=$this->ip3byte($value);
		
		//if (isset($arr[$value])) $arr[$value]['mode']=$mode; 
		if (!$id) $arr[$value]['mode']=$mode; 
		else 
		{
			$arr["$value"]=['id'=>$id, 'mode'=>$mode];	
		}
		
		$this->rules[$target]=$arr;
		$this->saveRules($target);
	}
	
	
	//
	function saveRules($target)
	{			
		file_put_contents($this->savedir.$target.'.txt', json_encode($this->rules[$target]) ); 
	}
	
	
	//
	function getId($target, $value=false)
	{
		$arr=$this->getRules($target);
		
		if (!$value)
		{
		 	if ($this->id[$target]) return $this->id[$target];
			
			if ($target=='ip_range' ) $value=$this->ip3byte($this->ip); else $value=$this->{$target};
		}	
	
		if (!isset($arr[$value])) return false; 
		
		$rule=$arr[$value];
		
		$this->rulesById[$rule['id']]=['target'=>$target, 'mode'=>$rule['mode'], 'value'=>$value ];
		
		return $this->id[$target]=$rule['id'];
	}
	
	
	//
	function logError($response=false)
	{	
		file_put_contents($this->errorsFile, $this->error.($response ? PHP_EOL.print_r($this->response,true) : PHP_EOL).'----------------'.PHP_EOL, FILE_APPEND);
	}
	
	
	//
	function isSuccess($response=false)
	{
		if (!$response) $response=$this->response;
		
		return ( isset($response['success']) && $response['success']==true );	
	}
	
	
	//
	function getError()
	{ 
		if (isset($this->response['errors'][0]['message']))
		{
			$error=str_replace(['firewallaccessrules.api.duplicate_of_existing', 'firewallaccessrules.api.not_found'], ['правило уже существует', 'правила нет в firewall'], $this->response['errors'][0]['message'] );
		}
		else $error='неизвестная ошибка';
 	
		return $error;
	}
	
	
	//
	function testLog($value)
	{	
		$this->testLog[]=print_r($value,true);
	}
}
?>
