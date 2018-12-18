<?php

/**
 * This file contains the module log class.
 *
 * @package    Core
 * @subpackage Log
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 *
 * This class contains the main functionalities for the module logging
 * in CONTENIDO.
 * The funcationality is almost the same like normal logging with the
 * exception, that log entries contains an additional information about
 * the used module.
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
 * @deprecated [2015-05-21]
 *         This class is no longer supported
 */
class cModuleLog extends cLog {

    /**
     * instance of module model
     *
     * @var cApiModule
     */
    private $_module;

    /**
     * Constructor to create an instance of this class.
     *
     * @deprecated [2015-05-21]
     *         This method is no longer supported (no replacement)
     *
     * @param mixed $writer [optional]
     *                      Writer object (any subclass of cLogWriter),
     *                      or false if cLog should handle the writer creation
     *
     * @throws cInvalidArgumentException
     */
    public function __construct($writer = false) {
        cDeprecated("The cModuleLog classes are no longer supported.");

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
     * setModule automatically buffers basic module information to the
     * log to assist the developer in debugging his modules.
     *
     * @deprecated [2015-05-21]
     *         This method is no longer supported (no replacement)
     * @param int $idmod
     *         The module ID to use
     * @throws cException
     *         if the module with the given idmod could not be loaded
     */
    public function setModule($idmod) {
        cDeprecated("The cModuleLog setModule method are no longer supported.");

        $this->_module = new cApiModule($idmod);
        if ($this->_module->isLoaded() == false) {
            throw new cException('Could not load module information.');
        }
    }

    /**
     * Shortcut Handler Module.
     * Returns the ID and the name of the module.
     *
     * @deprecated [2015-05-21]
     *         This method is no longer supported (no replacement)
     * @return string
     *         ID and name of the module
     */
    public function shModule() {
        cDeprecated("The cModuleLog shModule method are no longer supported.");

        if ($this->_module->isLoaded() == false) {
            return '';
        }

        return $this->_module->get("idmod") . ": " . $this->_module->get("name");
    }

}
