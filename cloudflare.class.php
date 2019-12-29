<?php
class Cloudflare
{ 
	public $authHeader=[];
	public $urlzone	='';
	public $urluser='';	
	public $handler='';
	public $ip;
	public $country='';
	public $response=[];
	public $result=[];	
	public $savedir='';
	//public $settings=['bancountry'=>1];
	public $countries=[]; // Страны целевого трафика
	
 
	function __construct()
	{
		$this->handler= curl_init();
		
		$this->savedir=__DIR__.'/cloudflare/';
	}
 
	
	function checkCountry()
	{
		if (isset($this->countries[$this->country]) && $this->countries[$this->country]) return true; else return false;
	}
	
	
	function isMainCountry()
	{
		if (isset($this->countries[$this->country])) return true; else return false;
	}
	
	
	function counter()
	{
		$file=$this->savedir.'countries/'.$this->country;
		
		if (!file_exists($file)) $c=1; else $c=file_get_contents($file)+1;
		
		return file_put_contents($file, $c);
	}
	
	
	function response()
	{
		$response = curl_exec($this->handler) ;
		curl_close($this->handler);
		
		//var_dump($response);
		
		$this->response=json_decode($response,true);
		$this->result=$this->response['result'];

		return $this->response;
	}
	
	
	function auth($authemail, $authkey, $zone)
	{		
		$this->authHeader= array(
			 'X-Auth-Email: '.$authemail,
			 'X-Auth-Key: '.$authkey,
			 'Content-Type: application/json'
		  );
		  
		$this->urlzone="https://api.cloudflare.com/client/v4/zones/".$zone.'/';
		$this->urluser="https://api.cloudflare.com/client/v4/user/";
 
		curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->handler, CURLOPT_HTTPHEADER, $this->authHeader);
		
		return $this;
	}
	
	
	function send($data)
	{
		curl_setopt($this->handler, CURLOPT_POSTFIELDS, json_encode($data) );
	}
	
	
	function addrule($add, $notes, $mode)
	{
		curl_setopt($this->handler, CURLOPT_URL, $this->urlzone.'firewall/access_rules/rules');	
		
		$data=['mode'=>$mode, 'notes'=>$notes, 'configuration'=>$add];

		$this->send($data);
		$r=$this->response();

		if (isset($r['success']) && $r['success']==true)
		{
			 $this->save( $add['target'], $mode, $add['value']);
			 return true;
		}
		else return false;
	}
	
	
	function delrule($id)
	{
		curl_setopt($this->handler, CURLOPT_URL, $this->urlzone.'firewall/access_rules/rules/'.$id);	
		curl_setopt($this->handler, CURLOPT_CUSTOMREQUEST, 'DELETE');
		
		//$this->send();
		return $this->response();
	}
	
	// Добавляем IP 
	function addip($notes='', $mode='block')   
	{
		if (!$notes) $notes=$this->country.' '.date("Y-m-d");
		
		return $this->addrule(['target'=>'ip', 'value'=>$this->ip], $notes, $mode); 
	}
	
	
	// Добавляем страну
	function addcountry($notes='', $mode='challenge')   
	{
		if (!$notes) $notes=date("Y-m-d");
		return $this->addrule(['target'=>'country', 'value'=>$this->country], $notes, $mode); 
	}
	

	// Добавляем диапазон
	function addrange($notes='', $mode='challenge')   
	{
		if (!$notes) $notes=$this->country.' '.date("Y-m-d");
		$ip=explode('.', $this->ip);
		
		return $this->addrule(['target'=>'ip_range', 'value'=>$ip[0].'.'.$ip[1].'.'.$ip[2].'.0/24'], $notes, $mode); 
	}
	 
	//
	function save($target, $mode, $value)
	{		
		$file=$this->savedir.$target.'.txt';
		$c=file_get_contents($file);
		if (!$c) $arr=[]; else $arr=json_decode($c,true);

		
		$arr[$value]=['id'=>$this->result['id'], 'mode'=>$mode];	
	
		file_put_contents($file, json_encode($arr) ); 
	}

}
?>