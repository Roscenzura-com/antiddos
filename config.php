<?php
if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
{
 	$_SERVER['REMOTE_ADDR']=$_SERVER['HTTP_CF_CONNECTING_IP'];
}
 

$config=['whitetime'=>31536000, /*�����*/ 'bantime'=>604800, /*������*/ 'proactivetime'=>3600, /*���*/ 'referertime'=>604800];

 
$config['limit']=5; // ���������� ������� � ������, ����� ���������� ���������� �����. ���������, ��� ��� ������� ajax ��������, ������� ���������� ���� (���, ��������).
$config['counter']='url'; // ������� ��� ������ ��������. ���� ������, �� ������� ����� �������� ��� ����� ����� � ����� ��������� ������ �������� limit

//$config['referer']=[$_SERVER['HTTP_HOST'], 'yandex.ru', 'google.com', 'google.ru'];
$config['referer']=false; // �������� ��� ����������, ���� ���� ������ ���� ������ ������ 
	
$config['search_bots']=['Googlebot'=>'Google', 'yandex.com/bots'=>'Yandex', 'mail.ru'=>'mail.ru'];  // 'msn.com','bing.com'
$config['search_hosts']=['Google'=>['.googlebot.com', '.google.com'], 'Yandex'=>['.yandex.com', '.yandex.ru', '.yandex.net'], 'mail.ru'=>['.mail.ru'], 'msn.com'=>['.msn.com'], 'bing.com'=>['.msn.com'] ];	


//Cloudflare
$configCF=
[
	'email'=>'masterdevelop@yandex.ru',  // email ������ �������� � Cloudflare
	'key'=>'df8f81eb373a9e3083e9b5fd03f6752e6a8c2', // ������ ����� �� �������� dash.cloudflare.com/profile, Global API Key
	'zone'=>'e4f75381f7238ac6b51cc2fe33127206', // ID ������ � Cloudflare, ���� �� ������� Overview 
	
	'countries'=>['RU'=>1, 'UA'=>1, 'BY'=>1, 'KZ'=>1, 'LV'=>1] // ������ �������� �������
];

 
  
$config['admin']['pass']='ddosforum.com';
 	
// ������ ��� ������ ����� Cloudflare. �� �� ����� ��������� �����, ���� ��������� ����� ���� ������.
// ��������� � ���������� ������������, ����� ������ �� ��������� ��� ������� �� ��� ���� � ��������� ���� �����, ������� ������� ����������� ����� ����� ->
// https://support.cloudflare.com/hc/en-us/articles/205072537-What-are-the-two-letter-country-codes-for-the-Access-Rules-

// $allow_country=array('RU'=>'������', 'UA'=>'�������', 'BY'=>'����������', 'KZ'=>'���������', 'DE'=>'��������', 'US'=>'���', 'NL'=>'����������', 'GB'=>'��������', 'DE'=>'��������', 'LV'=>'������', 'UZ'=>'����������', 'GE'=>'������', 'IL'=>'�������', 'BG'=>'��������', 'LT'=>'�����', 'FI'=>'���������', 'FR'=>'�������', 'AZ'=>'�����������', 'AM'=>'�������', 'CA'=>'������', 'KG'=>'��������');