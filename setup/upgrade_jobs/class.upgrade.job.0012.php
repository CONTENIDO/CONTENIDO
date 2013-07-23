<?php
/**
 * This file contains the upgrade job 12.
 *
 * @package Setup
 * @subpackage UpgradeJob
 * @version SVN Revision $Rev:$
 *
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
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
        if ($this->_setupType == 'setup') {
            switch ($_SESSION["clientmode"]) {
                case "NOCLIENT":
                    break;
                case "CLIENTEXAMPLES":
                    // copy the styles folder to the cms folder for the example
                    // client
                    if (cFileHandler::exists($this->_aCfgClient[1]["path"]["frontend"] . "css")) {
                        cFileHandler::recursiveRmdir($this->_aCfgClient[1]["path"]["frontend"] . "css");
                        mkdir($this->_aCfgClient[1]["path"]["frontend"] . "css");
                    }
                    cFileHandler::recursiveCopy("data/examples/css", $this->_aCfgClient[1]["path"]["frontend"] . "css");

                    // copy the scripts folder to the cms folder for the example
                    // client
                    if (cFileHandler::exists($this->_aCfgClient[1]["path"]["frontend"] . "js")) {
                        cFileHandler::recursiveRmdir($this->_aCfgClient[1]["path"]["frontend"] . "js");
                        mkdir($this->_aCfgClient[1]["path"]["frontend"] . "js");
                    }
                    cFileHandler::recursiveCopy("data/examples/js", $this->_aCfgClient[1]["path"]["frontend"] . "js");

                    // copy the template folder to the cms folder for the
                    // example client
                    if (cFileHandler::exists($this->_aCfgClient[1]["path"]["frontend"] . "templates")) {
                        cFileHandler::recursiveRmdir($this->_aCfgClient[1]["path"]["frontend"] . "templates");
                        mkdir($this->_aCfgClient[1]["path"]["frontend"] . "templates");
                    }
                    cFileHandler::recursiveCopy("data/examples/templates", $this->_aCfgClient[1]["path"]["frontend"] . "templates");

                    // copy the upload folder to the cms folder for the example
                    // client
                    if (cFileHandler::exists($this->_aCfgClient[1]["path"]["frontend"] . "upload")) {
                        cFileHandler::recursiveRmdir($this->_aCfgClient[1]["path"]["frontend"] . "upload");
                        mkdir($this->_aCfgClient[1]["path"]["frontend"] . "upload");
                    }
                    cFileHandler::recursiveCopy("data/examples/upload", $this->_aCfgClient[1]["path"]["frontend"] . "upload");

                    // copy the layout folder to the cms folder for the example
                    // client
                    if (cFileHandler::exists($this->_aCfgClient[1]["path"]["frontend"] . "data/layouts")) {
                        cFileHandler::recursiveRmdir($this->_aCfgClient[1]["path"]["frontend"] . "data/layouts");
                        mkdir($this->_aCfgClient[1]["path"]["frontend"] . "data/layouts");
                    }
                    cFileHandler::recursiveCopy("data/examples/data/layouts", $this->_aCfgClient[1]["path"]["frontend"] . "data/layouts");
                case "CLIENTMODULES":
                    // copy the module folder to the cms folder for the example
                    // client
                    if (cFileHandler::exists($this->_aCfgClient[1]["path"]["frontend"] . "data/modules")) {
                        cFileHandler::recursiveRmdir($this->_aCfgClient[1]["path"]["frontend"] . "data/modules");
                        mkdir($this->_aCfgClient[1]["path"]["frontend"] . "data/modules");
                    }
                    cFileHandler::recursiveCopy("data/examples/data/modules", $this->_aCfgClient[1]["path"]["frontend"] . "data/modules");
            }
        }
    }

}

?>