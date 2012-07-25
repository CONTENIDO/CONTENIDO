<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * con_meta_saveart action
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @package    CONTENIDO Backend Includes
 * @version    0.0.1
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET pagetitle = '".$_POST["page_title"]."' WHERE idartlang=".cSecurity::toInteger($_POST["idartlang"]);
$db->query($sql);

$availableTags = conGetAvailableMetaTagTypes();

foreach ($availableTags as $key => $value){
    conSetMetaValue($idartlang,$key,$_POST["META".$value["metatype"]]);
}

$notification->displayNotification("info", i18n("Changes saved"));
?>