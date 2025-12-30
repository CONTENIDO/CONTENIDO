<?php

/**
 * This file contains the module log class.
 *
 * @package    Core
 * @subpackage Log
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @deprecated [2015-05-21] This class is no longer supported
 */
class cModuleLog extends cLog
{

    /**
     * instance of module model
     *
     * @var cApiModule
     */
    private $_module;

    /**
     * @throws cInvalidArgumentException
     * @deprecated [2015-05-21] This method is no longer supported (no replacement)
     *
     */
    public function __construct($writer = false)
    {
        cDeprecated("The cModuleLog classes are no longer supported.");

        parent::__construct($writer);

        $this->setShortcutHandler('module', [$this, 'shModule']);
        $this->getWriter()->setOption("log_format", "[%date] [%level] [%module] %message", true);
    }

    /**
     * @deprecated [2015-05-21] This method is no longer supported (no replacement)
     */
    public function setModule($idmod)
    {
        cDeprecated("The cModuleLog setModule method are no longer supported.");

        $this->_module = new cApiModule($idmod);
        if ($this->_module->isLoaded() == false) {
            throw new cException('Could not load module information.');
        }
    }

    /**
     * @deprecated [2015-05-21] This method is no longer supported (no replacement)
     */
    public function shModule()
    {
        cDeprecated("The cModuleLog shModule method are no longer supported.");

        if ($this->_module->isLoaded() == false) {
            return '';
        }

        return $this->_module->get("idmod") . ": " . $this->_module->get("name");
    }

}
