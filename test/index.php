<?php
// Ветка поддержки скрипта: http://ddosforum.com/threads/602/
//$_SERVER['REMOTE_ADDR']='228.121.213.213';
//$_SERVER['HTTP_CF_IPCOUNTRY']='TW';
//$_SERVER['HTTP_USER_AGENT']='Mozilla/5.0  AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.106';

$testAntiddos=true;
include('../include.php');

if (is_file('../white/'.$_SERVER['REMOTE_ADDR'])) echo 'white list<br>';

var_dump($_SERVER);