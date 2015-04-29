<?php
/**
 * This file contains the module log class.
 *
 * @package    Core
 * @subpackage Log
 * @version    SVN Revision $Rev:$
 *
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains the main functionalities for the module logging in
 * CONTENIDO.
 * The funcationality is almost the same like normal logging with the exception,
 * that log entries contains an additional information about the used module.
 *
 * Example:
 * $writer = cLogWriter::factory("File", array('destination' =>
 * 'contenido.log'));
 *
 * $log = new cModuleLog($writer);
 * $log->setModule(1);
 * $log->log("Anything you want to log.");
 *
 * @package    Core
 * @subpackage Log
 */
class cModuleLog extends cLog {

    /**
     * @var cApiModule
     *         instance of module model
     */
    private $_module;

    /**
     * Constructor of the module log.
     *
     * @param mixed $writer
     *         Writer object (any subclass of cLogWriter),
     *         or false if cLog should handle the writer creation
     *
     */
    public function __construct($writer = false) {
        parent::__construct($writer);

        $this->setShortcutHandler('module', array(
            $this,
            'shModule'
        ));
        $this->getWriter()->setOption("log_format", "[%date] [%level] [%module] %message", true);
    }

    /**
     * Sets the module to use.
     *
     * setModule automatically buffers basic module information to the log to
     * assist the developer in debugging his modules.
     *
     * @param int $idmod
     *         The module ID to use
     * @throws cException
     *         if the module with the given idmod could not be loaded
     */
    public function setModule($idmod) {
        $this->_module = new cApiModule($idmod);
        if ($this->_module->isLoaded() == false) {
            throw new cException('Could not load module information.');
        }
    }

    /**
     * Shortcut Handler Module.
     * Returns the ID and the name of the module.
     *
     * @return string
     *         ID and name of the module
     */
    public function shModule() {
        if ($this->_module->isLoaded() == false) {
            return '';
        }

        return $this->_module->get("idmod") . ": " . $this->_module->get("name");
    }

}

?>