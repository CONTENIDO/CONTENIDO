<?php
/*****************************************
* File      :   $RCSfile: help.js.php,v $
* Project   :   Contenido
* Descr     :   Help System
* Modified  :   $Date: 2005/08/22 12:21:18 $
*
* © four for business AG, www.4fb.de
*
* $Id: help.js.php,v 1.3 2005/08/22 12:21:18 timo.hummel Exp $
******************************************/

include_once ('../includes/startup.php');

include_once ($cfg["path"]["contenido"].$cfg["path"]["includes"] . 'functions.i18n.php');

header("Content-Type: text/javascript");

page_open(array('sess' => 'Contenido_Session',
                'auth' => 'Contenido_Challenge_Crypt_Auth',
                'perm' => 'Contenido_Perm'));

i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);
page_close();

$baseurl = $cfg["help_url"] . "front_content.php?version=".$cfg['version']."&help=";
?>

function callHelp (path)
{
	f1 = window.open('<?php echo $baseurl; ?>' + path, 'contenido_help', 'height=500,width=600,resizable=yes,scrollbars=yes,location=no,menubar=no,status=no,toolbar=no');
	f1.focus();
}
