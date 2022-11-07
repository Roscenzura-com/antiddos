<?PHP
spl_autoload_register(function ($class) {
//var_dump(substr(strrchr('\\'.$class, "\\" ), 1)  . ".class.php");
    //include(substr(strrchr('\\'.$class, "\\" ), 1)  . ".class.php");
	include(__DIR__.'/'.$class. ".class.php");
});
?>
