<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Defines the Layout related functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.3.2
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude ("includes", "functions.tpl.php");
cInclude ("includes", "functions.con.php");
cInclude ("classes", "class.layoutInFile.php");
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

    global $client, $auth, $cfg, $sess, $lang, $area_tree, $perm, $cfgClient;

    $db2= new DB_Contenido;
    $db = new DB_Contenido;

    $date = date("Y-m-d H:i:s");
    $author = "".$auth->auth["uname"]."";
    $description = (string) stripslashes($description);
    $notification = new Contenido_Notification();
    set_magic_quotes_gpc($name);
    set_magic_quotes_gpc($description);

    set_magic_quotes_gpc($code);

    if (strlen(trim($name)) == 0) {
        $name = i18n('-- Unnamed layout --');
    }

    #replace all not allowed characters..
    $layoutAlias = Contenido_Module_Handler::getCleanName(strtolower($name));

     #constructor for the layout in filesystem
     $layoutInFile = new LayoutInFile($idlay, stripslashes($code), $cfg, $lang);

    /**
    * START TRACK VERSION
    **/
    $oVersion = new VersionLayout($idlay, $cfg, $cfgClient, $db, $client, $area, $frame);
    #save layout from file and not from db
    $oVersion->setCode($layoutInFile->getLayoutCode());
    // Create new Layout Version in cms/version/layout/
    $oVersion->createNewVersion();

    /**
    * END TRACK VERSION
    **/


    if (!$idlay) {

        //$tmp_newid = $db->nextid($cfg["tab"]["lay"]);

        $sql = "INSERT INTO ".$cfg["tab"]["lay"]." (name,alias, description, deletable, idclient, author, created, lastmodified) VALUES ('".Contenido_Security::escapeDB($name, $db)."',
                '".Contenido_Security::escapeDB($layoutAlias, $db)."','".Contenido_Security::escapeDB($description, $db)."', '1', '".Contenido_Security::toInteger($client)."', '".Contenido_Security::escapeDB($author, $db)."',
                '".Contenido_Security::escapeDB($date, $db)."', '".Contenido_Security::escapeDB($date, $db)."')";
        $db->query($sql);
        $idlay = $db->getLastInsertedId($cfg["tab"]["lay"]);

        if( $layoutInFile->saveLayout(stripslashes($code)) == false)
            $notification->displayNotification("error", i18n("Can't save layout in file"));
        else
            $notification->displayNotification(Contenido_Notification::LEVEL_INFO, i18n("Saved layout succsessfully!"));

        // set correct rights for element
        cInclude ("includes", "functions.rights.php");
        createRightsForElement("lay", $idlay);

        return $idlay;

    } else {

        $sql = "";
         #save the layout in file system
        $layoutInFile = new LayoutInFile($idlay, stripslashes($code),  $cfg, $lang);
        #name changed
        if($layoutAlias != $layoutInFile->getLayoutName() ) {

            #exist layout in directory
            if( LayoutInFile::existLayout($layoutAlias, $cfgClient, $client) == true) {

                #save in old directory
                if($layoutInFile->saveLayout(stripslashes($code)) == false)
                    $notification->displayNotification("error", i18n("Can't save layout in file!"));

                #display error
                $notification->displayNotification("error", i18n("Can't rename the layout!"));
                die();
            }

            #rename the directory
            if($layoutInFile->rename($layoutInFile->getLayoutName(),$layoutAlias)) {

                if($layoutInFile->saveLayout(stripslashes($code)) == false)
                    $notification->displayNotification("error", i18n("Can't save layout in file!"));
                else {
                    $notification->displayNotification(Contenido_Notification::LEVEL_INFO, i18n("Renamed layout succsessfully!"));
                     $sql = "UPDATE ".$cfg["tab"]["lay"]." SET name='".Contenido_Security::escapeDB($name, $db)."', alias='".Contenido_Security::escapeDB($layoutAlias, $db)."' , description='".Contenido_Security::escapeDB($description, $db)."',
                            author='".Contenido_Security::escapeDB($author, $db)."', lastmodified='".Contenido_Security::escapeDB($date, $db)."' WHERE idlay='".Contenido_Security::toInteger($idlay)."'";
                }
            } else {#rename not successfully
                #save layout
                if($layoutInFile->saveLayout(stripslashes($code)) == false)
                    $notification->displayNotification("error", i18n("Can't save layout file!"));

            }
        } else {#name dont changed

            if( $layoutInFile->saveLayout(stripslashes($code))== false) {
                $notification->displayNotification("error", i18n("Can't save layout in file!"));

            }
            else  {
                    $notification->displayNotification(Contenido_Notification::LEVEL_INFO, i18n("Saved layout succsessfully!"));
                $sql = "UPDATE ".$cfg["tab"]["lay"]." SET name='".Contenido_Security::escapeDB($name, $db)."', alias='".Contenido_Security::escapeDB($layoutAlias, $db)."' , description='".Contenido_Security::escapeDB($description, $db)."',
                            author='".Contenido_Security::escapeDB($author, $db)."', lastmodified='".Contenido_Security::escapeDB($date, $db)."' WHERE idlay='".Contenido_Security::toInteger($idlay)."'";
                }
            }


       #update if work on file successfully
       if($sql != "")
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
        $notification = new Contenido_Notification();

        $sql = "SELECT * FROM ".$cfg["tab"]["tpl"]." WHERE idlay='".Contenido_Security::toInteger($idlay)."'";
        $db->query($sql);
        if ($db->next_record()) {
                return "0301"; // layout is still in use, you cannot delete it
        } else {


                #save the layout in file system
                $layoutInFile = new LayoutInFile($idlay,"", $cfg, 1);
                if( $layoutInFile->eraseLayout()) {
                    $sql = "DELETE FROM ".$cfg["tab"]["lay"]." WHERE idlay='".Contenido_Security::toInteger($idlay)."'";
                    $db->query($sql);
                } else {

                    $notification->displayNotification("error", i18n("Can't delete layout!"));
                }

        }

        // delete rights for element
        cInclude ("includes", "functions.rights.php");
        deleteRightsForElement("lay", $idlay);

}
?>