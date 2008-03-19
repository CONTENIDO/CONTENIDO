<?php
function checkAndInclude ($filename)
{
	if (file_exists($filename) && is_readable($filename))
	{
		include_once($filename);
	} else {
		echo "ERROR: Can't include $filename. Our directory is ".getcwd()."\n";	
	}
}

checkAndInclude("lib/class.template.php");
checkAndInclude("lib/functions.system.php");
checkAndInclude("lib/functions.phpinfo.php");
checkAndInclude("lib/functions.mysql.php");

session_start();
list($root_path, $root_http_path) = getSystemDirectories();

$tpl = new Template;
$tpl->set("s", "CONTENIDO_ROOT", $root_path);
$tpl->set("s", "CONTENIDO_WEB", $root_http_path);
$tpl->set("s", "MYSQL_HOST", $_SESSION["dbhost"]);
$tpl->set("s", "MYSQL_DB", $_SESSION["dbname"]);
$tpl->set("s", "MYSQL_USER", $_SESSION["dbuser"]);
$tpl->set("s", "MYSQL_PASS", $_SESSION["dbpass"]);
$tpl->set("s", "MYSQL_PREFIX", $_SESSION["dbprefix"]);

if (hasMySQLiExtension() && !hasMySQLExtension())
{
	$tpl->set("s", "DB_EXTENSION", "mysqli");	
} else {
	$tpl->set("s", "DB_EXTENSION", "mysql");	
}

if ($_SESSION["start_compatible"] == true)
{
	$tpl->set("s", "START_COMPATIBLE", "true");	
} else {
	$tpl->set("s", "START_COMPATIBLE", "false");
}

$tpl->set("s", "NOLOCK", $_SESSION["nolock"]);	

if ($_SESSION["configmode"] == "save")
{
	@unlink($root_path."/contenido/includes/config.php");
	
	@$handle = fopen($root_path."/contenido/includes/config.php", "wb");
	
	@fwrite($handle, $tpl->generate("templates/config.php.tpl", true, false));
	@fclose($handle);
	
	if (!file_exists($root_path."/contenido/includes/config.php"))
	{
		$_SESSION["configsavefailed"] = true;
	} else {
		unset($_SESSION["configsavefailed"]);	
	}
	
	
	
} else {
	header("Content-Type: application/octet-stream");
	header("Etag: ".md5(mt_rand()));
	header("Content-Disposition: attachment;filename=config.php");
	$tpl->generate("templates/config.php.tpl", false, false);
}

?>