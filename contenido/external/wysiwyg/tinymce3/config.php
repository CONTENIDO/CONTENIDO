<?php
// ================================================
// TINYMCE 1.45rc1 PHP WYSIWYG editor config
// ================================================
// Main editor configuration file for CONTENIDO
// ================================================
//								  www.dayside.net
// ================================================
// Author: Martin Horwath, horwath@dayside.net
// TINYMCE 1.45rc1 Fileversion , 2005-06-10 v0.0.3
// ================================================

// include CONTENIDO config file
$contenido_path = implode (DIRECTORY_SEPARATOR , array_slice(explode(DIRECTORY_SEPARATOR , dirname(__FILE__)), 0, -3)) . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR;

if (file_exists( $contenido_path . 'startup.php'))
{
	@include_once ($contenido_path . 'startup.php');
} else {
	@include_once ($contenido_path . 'config.php');
}

cInclude ("includes", 'functions.con.php');
cInclude ("includes", 'functions.general.php');
cInclude ("includes", 'functions.i18n.php');
cInclude ("includes", 'functions.api.php');

$db = new DB_Contenido;

if ($cfgClient["set"] != "set") // CONTENIDO
{
	rereadClients();
}


?>
