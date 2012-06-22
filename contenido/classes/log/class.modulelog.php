<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * MySQL Driver for GenericDB
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.3
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created 2004-09-28
 *
 *   $Id$
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cModuleLog extends cBufferedLog
{
    public $_oModule;

    /**
     * Creates a new instance of the CONTENIDO ModuleLog mechanism.
     *
     * cModuleLog is a logging facility which uses cBufferedLog to do its logging,
     * and features automatic module handling.
     *
     * @param oLogger     object    The object to use for logging, or false if a new one should be created.
     * @param idmod        integer    The module ID to use
     */
    public function __construct($oLogger = false, $idmod = 0)
    {
        parent::__construct($oLogger);

        $this->_setShortcutHandler("module", "_shModule");
        $this->setLogFormat("[%date] [%module] [%session] [%level] %message");

        if ($idmod != 0) {
            $this->setModule($idmod);
        }
    }

    /** @deprecated  [2012-05-25] Old constructor function for downwards compatibility */
    public function cModuleLog($oLogger = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($oLogger);
    }

    /**
     * Sets the module to use.
     *
     * setModule automatically buffers basic module information to the log to assist the
     * developer in debugging his modules.
     *
     * @param  int  idmod  The module ID to use
     */
    function setModule($idmod)
    {
        global $client, $lang, $idcat, $idart;

        $this->_oModule = new cApiModule($idmod);

        $this->buffer("-- REQUEST START --", PEAR_LOG_INFO);
        $this->buffer("-- MODULE INFO --", PEAR_LOG_DEBUG);
        $this->buffer("idmod   : ". $this->_oModule->get("idmod"),PEAR_LOG_DEBUG);
        $this->buffer("idclient: ". $client,PEAR_LOG_DEBUG);
        $this->buffer("idlang  : ". $lang,PEAR_LOG_DEBUG);
        $this->buffer("idcat   : ". $idcat,PEAR_LOG_DEBUG);
        $this->buffer("idart   : ". $idart,PEAR_LOG_DEBUG);
        $this->buffer("-- MODULE INFO END --", PEAR_LOG_DEBUG);
    }

    /**
     * Shortcut handler for the module id / name
     * @return id and name of the module
     */
    public function _shModule()
    {
        return ($this->_oModule->get("idmod").": ".$this->_oModule->get("name"));
    }

    /**
     * Appends "REQUEST END" to the stack and commits all messages which are queued on the stack
     */
    public function commit()
    {
        $this->buffer("-- REQUEST END --");
        parent::commit();
    }
}

?>