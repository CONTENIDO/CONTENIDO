<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Defines the Layout related functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.3.2
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *
 *   $Id: functions.lay.php 1185 2010-08-09 08:43:47Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("includes", "functions.tpl.php");
cInclude("includes", "functions.con.php");

/**
 * Edit or Create a new layout
 *
 * @param int $idlay Id of the Layout
 * @param string $name Name of the Layout
 * @param string $description Description of the Layout
 * @param string $code Layout HTML Code
 * @return int $idlay Id of the new or edited Layout
 *
 * @author Olaf Niemann <olaf.niemann@4fb.de>
 * @copryright four for business AG <www.4fb.de>
 */
function layEditLayout($idlay, $name, $description, $code) {

    global $client, $auth, $cfg, $sess, $area_tree, $perm, $cfgClient;

    $db2= new DB_Contenido;
    $db = new DB_Contenido;

    $date = date("Y-m-d H:i:s");
    $author = "".$auth->auth["uname"]."";
    $description = (string) stripslashes($description);
    
    set_magic_quotes_gpc($name);
    set_magic_quotes_gpc($description);
    set_magic_quotes_gpc($code);
    
    if (strlen(trim($name)) == 0) {
        $name = '-- ' . i18n("Unnamed layout") . ' --';
    }
	
	/**
	* START TRACK VERSION
	**/
	$oVersion = new VersionLayout($idlay, $cfg, $cfgClient, $db, $client, $area, $frame);
	
	// Create new Layout Version in cms/version/layout/
	$oVersion->createNewVersion();

	/**
	* END TRACK VERSION
	**/
	
	
    if (!$idlay) {

        $tmp_newid = $db->nextid($cfg["tab"]["lay"]);
        $idlay = $tmp_newid;

        $sql = "INSERT INTO ".$cfg["tab"]["lay"]." (idlay,name, description, deletable, code, idclient, author, created, lastmodified) VALUES ('".Contenido_Security::toInteger($tmp_newid)."', '".Contenido_Security::escapeDB($name, $db)."',
                '".Contenido_Security::escapeDB($description, $db)."', '1', '".$code."', '".Contenido_Security::toInteger($client)."', '".Contenido_Security::escapeDB($author, $db)."',
                '".Contenido_Security::escapeDB($date, $db)."', '".Contenido_Security::escapeDB($date, $db)."')";
        $db->query($sql);

        // set correct rights for element
        cInclude("includes", "functions.rights.php");
        createRightsForElement("lay", $idlay);

        return $idlay;

    } else {

        $sql = "UPDATE ".$cfg["tab"]["lay"]." SET name='".Contenido_Security::escapeDB($name, $db)."', description='".Contenido_Security::escapeDB($description, $db)."', code='".$code."',
                author='".Contenido_Security::escapeDB($author, $db)."', lastmodified='".Contenido_Security::escapeDB($date, $db)."' WHERE idlay='".Contenido_Security::toInteger($idlay)."'";
        $db->query($sql);

        /* Update CODE table*/
        conGenerateCodeForAllartsUsingLayout($idlay);

        return $idlay;
    }

}

function layDeleteLayout($idlay) {
        global $db;
        global $client;
        global $cfg;
        global $area_tree;
        global $perm;

        $sql = "SELECT * FROM ".$cfg["tab"]["tpl"]." WHERE idlay='".Contenido_Security::toInteger($idlay)."'";
        $db->query($sql);
        if ($db->next_record()) {
                return "0301"; // layout is still in use, you cannot delete it
        } else {
                $sql = "DELETE FROM ".$cfg["tab"]["lay"]." WHERE idlay='".Contenido_Security::toInteger($idlay)."'";
                $db->query($sql);
        }

        // delete rights for element
        cInclude("includes", "functions.rights.php");
        deleteRightsForElement("lay", $idlay); 
}
?>