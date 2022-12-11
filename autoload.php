<?PHP
spl_autoload_register(function ($class) {
	include(__DIR__.'/'.$class. ".class.php");
});
?>
