<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * This class  transfers the old modul-system in the
 * new modul-system. Also from Db-table oriented structure
 * to the file-system orinted structure.
 *
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.0
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <info@contenido.org>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since
 *
 * {@internal
 *   created 2010-12-22
 *
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

checkAndInclude(dirname(__FILE__)."/../class.security.php");
checkAndInclude(dirname(__FILE__)."/../../includes/functions.api.string.php");
checkAndInclude(dirname(__FILE__)."/class.module.handler.php");
checkAndInclude(dirname(__FILE__)."/class.module.filetranslation.php");

/**
 *
 * This class  transfers the old modul-system in the
 * new modul-system. Also from Db-table oriented structure
 * to the file-system orinted structure.
 * @author rusmir.jusufovic
 *
 */
class Contenido_UpgradeJob extends Contenido_Module_Handler
{
    protected $_db = null;

    public function __construct($db)
    {
        $this->_db = $db;

        try {
            parent::__construct();
        } catch (Exception $e) {
            cWarning(__FILE__, __LINE__, $e->getMessage());
        }
    }

    /**
     * This method clean the name of moduls table $cfg["tab"]["mod"].
     * Clean means all the charecters (�,*+#...) will be replaced.
     *
     */
    private function _changeNameCleanURL()
    {
        $myDb = clone $this->_db;
        $db = clone $this->_db;

        //select all moduls
        $sql = sprintf("SELECT * FROM %s", $this->_cfg["tab"]["mod"]);
        $db->query($sql);

        while ($db->next_record()) {
            //clear name  from not allow charecters
            $newName = cApiStrCleanURLCharacters($db->f("name"));
            if ($newName != $db->f("name")) {
                $mySql = sprintf("UPDATE %s SET name='%s' WHERE idmod=%s", $this->_cfg["tab"]["mod"], $newName, $db->f("idmod"));
                $myDb->query($mySql);
            }
        }
    }

    /**
     * This method will be transfer the moduls from $cfg["tab"]["mod"] to the
     * file system. This Method will be call by setup
     */
    public function convertModulesToFile($setuptype)
    {
        $db = clone $this->_db;

        if ($setuptype == "upgrade") {
            //clean name oft module (Umlaute, not allowed character ..), prepare for file system
            $this->_changeNameCleanURL();

            //select all frontendpaht of the clients, frontendpaht is in  the table $cfg["tab"]["clients"]
            $sql = sprintf("SELECT * FROM %s ORDER BY idmod", $this->_cfg["tab"]["mod"]);
            $db->query($sql);

            // create all main module directories
            $this->createAllMainDirectories();

            while ($db->next_record()) {
                // init the ModulHandler with all data of the modul
                // inclusive client
                $this->_initWithDatabaseRow($db);

                // make new module only if modul not exist in directory
                if ($this->modulePathExists() != true) {
                    // we need no error handling here because module could still exist from previous version
                    if ($this->createModule($db->f("input"), $db->f("output")) == true) {
                        // save module translation
                        $translations = new Contenido_Module_FileTranslation($db->f("idmod"));
                        $translations->saveTranslations();
                    }
                }
            }
        }

        //remove input and output fields from db
        $sql = sprintf("ALTER TABLE %s DROP input, DROP output", $this->_cfg["tab"]["mod"]);
        $db->query($sql);
    }
}

?>