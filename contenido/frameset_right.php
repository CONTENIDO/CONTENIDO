<?php
/*****************************************
* File      :   main.php
* Project   :   Contenido
* Descr     :   Contenido main file
*
* Authors   :   Olaf Niemann
*               Jan Lengowski
*
* Created   :   20.01.2003
* Modified  :   21.03.2003
*
* © four for business AG, www.4fb.de
******************************************/

if (isset($_REQUEST['cfg'])) {
    die ('Illegal call!');
}

include_once ('./includes/startup.php');
include_once ($cfg["path"]["classes"] . 'class.template.php');

page_open(
    array('sess' => 'Contenido_Session',
          'auth' => 'Contenido_Challenge_Crypt_Auth',
          'perm' => 'Contenido_Perm'));

cInclude ("includes", 'functions.i18n.php');

i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);

cInclude ("includes", 'cfg_sql.inc.php');
cInclude ("includes", 'cfg_language_de.inc.php');
cInclude ("includes", 'functions.general.php');
cInclude ("includes", 'functions.forms.php');

# Create Contenido classes
$db  = new DB_Contenido;
$tpl = new Template;

# Build the Contenido
# Content area frameset
$tpl->reset();

if (isset($_GET["appendparameters"]))
{
	$tpl->set('s', 'FRAME[3]', $sess->url("main.php?area=$area&frame=3&appendparameters=".$_GET["appendparameters"]));
	$tpl->set('s', 'FRAME[4]', $sess->url("main.php?area=$area&frame=4&appendparameters=".$_GET["appendparameters"]));
} else {
	$tpl->set('s', 'FRAME[3]', $sess->url("main.php?area=$area&frame=3"));
	$tpl->set('s', 'FRAME[4]', $sess->url("main.php?area=$area&frame=4"));
}
$tpl->set('s', 'VERSION', $cfg['version']);

$tpl->set('s', 'CONTENIDOPATH', $cfg["path"]["contenido_fullhtml"]."favicon.ico");

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['frameset_right']);

page_close();

?>
