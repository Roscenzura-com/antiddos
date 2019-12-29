<?PHP
//if (!isset($_GET['action']) || $_SERVER['HTTP_USER_AGENT']!='antiddos') exit('tuk-tuk');

$timer=['clearcount'=>300];


function delfiles($dir)
{
	if ($handle = opendir($dir)) {
		while (false !== ($file = readdir($handle))) 
		{ 
			if ($file != "." && $file != "..") unlink($dir.'/'.$file);
		}
		closedir($handle); 
	}
}

function clearban($dir)
{
	$time=time();
	
	if ($handle = opendir($dir)) {
		while (false !== ($file = readdir($handle))) 
		{ 
			if ($file != "." && $file != "..") 
			{
				$info=explode(PHP_EOL, file_get_contents($dir.'/'.$file));
				
				//var_dump($info);
				if ($info[5]<$time)
				{
					echo $dir.'/'.$file.' '.$info[5].' '.$time.'<br>';
					unlink($dir.'/'.$file);
				}
				else echo $dir.'/'.$file.' '.$info[5].'<br>';
				
				
			}
		}
		closedir($handle); 
	}
}



$dir='../';


$time=time();

$data=unserialize(file_get_contents('timer.txt'));
if (!isset($data[$_GET['action']])) $data[$_GET['action']]=$time;


if ($_GET['action']=='clearcount')
{
	if ($time-$data['clearcount']>$timer['clearcount']) delfiles($dir.'count');
}

 
if ($_GET['action']=='clearban')
{
	 clearban($dir.'ban');
}
 
 
file_put_contents('timer.txt', serialize($data) );
?>