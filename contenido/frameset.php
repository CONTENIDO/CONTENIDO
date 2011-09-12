<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * CONTENIDO main file
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend
 * @version    1.2.3
 * @author     Olaf Niemann, Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created  2003-01-20
 *   modified 2008-06-16, Holger Librenz, Hotfix: added check for illegal calling
 *   modified 2008-06-25, Timo Trautmann, CONTENIDO Framework Constand added
 *   modified 2008-07-02, Frederic Schneider, add security fix and include security_class
 *   modified 2008-10-22, Oliver Lohkemper, update default-value for leftframewidth from 250px to 245px
 *   modified 2009-10-16, Ortwin Pinke, added rewrite of ampersand in frameset url
 *   modified 2010-05-20, Murat Purc, standardized CONTENIDO startup and security check invocations, see [#CON-307]
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}

// CONTENIDO startup process
include_once ('./includes/startup.php');

include_once ($cfg["path"]["classes"] . 'class.template.php');

page_open(
    array('sess' => 'Contenido_Session',
          'auth' => 'Contenido_Challenge_Crypt_Auth',
          'perm' => 'Contenido_Perm'));

i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);

cInclude ("includes", 'cfg_language_de.inc.php');
cInclude ("includes", 'functions.forms.php');

# Create CONTENIDO classes
$db  = new DB_Contenido;
$tpl = new Template;

# Build the CONTENIDO
# Content area frameset
$tpl->reset();

if (isset($_GET["appendparameters"]))
{
	$tpl->set('s', 'LEFT', str_replace("&", "&amp;", $sess->url("frameset_left.php?area=$area&appendparameters=".$_GET["appendparameters"])));
	$tpl->set('s', 'RIGHT', str_replace("&", "&amp;", $sess->url("frameset_right.php?area=$area&appendparameters=".$_GET["appendparameters"])));
	$tpl->set('s', 'WIDTH', getEffectiveSetting("backend", "leftframewidth", 245));
} else {
	$tpl->set('s', 'LEFT', str_replace("&", "&amp;", $sess->url("frameset_left.php?area=$area")));
	$tpl->set('s', 'RIGHT', str_replace("&", "&amp;", $sess->url("frameset_right.php?area=$area")));
	$tpl->set('s', 'WIDTH', getEffectiveSetting("backend", "leftframewidth", 245));
}

$tpl->set('s', 'VERSION', 	$cfg['version']);
$tpl->set('s', 'LOCATION',	$cfg['path']['contenido_fullhtml']);

/* Hide menu-frame for some areas */

/* First of all, fetch the meta data of the area table to check if there's a menuless column */
$aMetadata = $db->metadata($cfg["tab"]["area"]);
$bFound = false;

foreach ($aMetadata as $aFieldDescriptor)
{
	if ($aFieldDescriptor["name"] == "menuless")
	{
		$bFound = true;
		break;
	}
}

$menuless_areas = array();

if ($bFound == true)
{
	/* Yes, a menuless column does exist */
	$sql = "SELECT name FROM ".$cfg["tab"]["area"]." WHERE menuless='1'";
	$db->query($sql);

	while ($db->next_record())
	{
	    $menuless_areas[] = $db->f("name");
	}
} else {
	/* No, use old style hard-coded menuless area stuff */
	$menuless_areas = array("str", "logs", "debug", "system");
}

if ( in_array($area, $menuless_areas) || (isset($menuless) && $menuless == 1)) {
    $menuless = true;
    if (isset($_GET["appendparameters"]))
	{
		$tpl->set('s', 'FRAME[1]', str_replace("&", "&amp;", $sess->url("main.php?area=$area&frame=1&appendparameters=".$_GET["appendparameters"])));
		$tpl->set('s', 'FRAME[2]', str_replace("&", "&amp;", $sess->url("main.php?area=$area&frame=2&appendparameters=".$_GET["appendparameters"])));
		$tpl->set('s', 'FRAME[3]', str_replace("&", "&amp;", $sess->url("main.php?area=$area&frame=3&appendparameters=".$_GET["appendparameters"])));
		$tpl->set('s', 'FRAME[4]', str_replace("&", "&amp;", $sess->url("main.php?area=$area&frame=4&appendparameters=".$_GET["appendparameters"])));
	} else {
		$tpl->set('s', 'FRAME[1]', str_replace("&", "&amp;", $sess->url("main.php?area=$area&frame=1")));
		$tpl->set('s', 'FRAME[2]', str_replace("&", "&amp;", $sess->url("main.php?area=$area&frame=2")));
		$tpl->set('s', 'FRAME[3]', str_replace("&", "&amp;", $sess->url("main.php?area=$area&frame=3")));
		$tpl->set('s', 'FRAME[4]', str_replace("&", "&amp;", $sess->url("main.php?area=$area&frame=4")));
	}
}
$tpl->set('s', 'CONTENIDOPATH', $cfg["path"]["contenido_fullhtml"]."favicon.ico");

if ((isset($menuless) && $menuless == 1)) {
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['frameset_menuless_content']);
} else {
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['frameset_content']);
}

page_close();

?>