<?php
/**
 * This file contains the upgrade job 11.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @version    SVN Revision $Rev:$
 *
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 12.
 * Copy the example client to cms folder if needed
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0012 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.0";

    public function _execute() {
        global $cfg, $db, $cfgClient;

        switch($_SESSION["clientmode"]) {
        	case "NOCLIENT":
        		break;
        	case "CLIENTEXAMPLES":
        		//copy the styles folder to the cms folder for the example client
        		if(cFileHandler::exists($cfgClient[1]["path"]["frontend"]."css")) {
        			cFileHandler::recursiveRmdir($cfgClient[1]["path"]["frontend"]."css");
        			mkdir($cfgClient[1]["path"]["frontend"]."css");
        		}
        		cFileHandler::recursiveCopy("data/examples/data/css", $cfgClient[1]["path"]["frontend"]."css");
        		
        		//copy the scripts folder to the cms folder for the example client
        		if(cFileHandler::exists($cfgClient[1]["path"]["frontend"]."js")) {
        			cFileHandler::recursiveRmdir($cfgClient[1]["path"]["frontend"]."js");
        			mkdir($cfgClient[1]["path"]["frontend"]."js");
        		}
        		cFileHandler::recursiveCopy("data/examples/data/js", $cfgClient[1]["path"]["frontend"]."js");
        		
        		//copy the template folder to the cms folder for the example client
        		if(cFileHandler::exists($cfgClient[1]["path"]["frontend"]."templates")) {
        			cFileHandler::recursiveRmdir($cfgClient[1]["path"]["frontend"]."templates");
        			mkdir($cfgClient[1]["path"]["frontend"]."templates");
        		}
        		cFileHandler::recursiveCopy("data/examples/data/templates", $cfgClient[1]["path"]["frontend"]."templates");
        		
        		//copy the upload folder to the cms folder for the example client
        		if(cFileHandler::exists($cfgClient[1]["path"]["frontend"]."upload")) {
        			cFileHandler::recursiveRmdir($cfgClient[1]["path"]["frontend"]."upload");
        			mkdir($cfgClient[1]["path"]["frontend"]."upload");
        		}
        		cFileHandler::recursiveCopy("data/examples/data/upload", $cfgClient[1]["path"]["frontend"]."upload");
        		
        		//copy the layout folder to the cms folder for the example client
        		if(cFileHandler::exists($cfgClient[1]["path"]["frontend"]."data/layouts")) {
        			cFileHandler::recursiveRmdir($cfgClient[1]["path"]["frontend"]."data/layouts");
        			mkdir($cfgClient[1]["path"]["frontend"]."data/layouts");
        		}
        		cFileHandler::recursiveCopy("data/examples/data/layouts", $cfgClient[1]["path"]["frontend"]."data/layouts");
        	case "CLIENTMODULES":
        		//copy the module folder to the cms folder for the example client
        		if(cFileHandler::exists($cfgClient[1]["path"]["frontend"]."data/modules")) {
        			cFileHandler::recursiveRmdir($cfgClient[1]["path"]["frontend"]."data/modules");
        			mkdir($cfgClient[1]["path"]["frontend"]."data/modules");
        		}
        		cFileHandler::recursiveCopy("data/examples/data/modules", $cfgClient[1]["path"]["frontend"]."data/modules");
        }
    }

}
?>