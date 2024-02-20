<?php

/**
 * This file contains the visible debug class.
 *
 * @package    Core
 * @subpackage Debug
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Debug object to show info on screen.
 * In case you cannot output directly to screen when debugging a live system,
 * this object writes
 * the info to a file located in /data/log/debug.log.
 *
 * @package    Core
 * @subpackage Debug
 */
class cDebugVisible implements cDebugInterface
{

    use cDebugVisibleTrait;

    /**
     * Singleton instance
     *
     * @var cDebugVisible
     */
    private static $_instance;

    /**
     * Return singleton instance.
     *
     * @return cDebugVisible
     */
    static public function getInstance(): cDebugInterface
    {
        if (self::$_instance == NULL) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor to create an instance of this class.
     */
    private function __construct()
    {
    }

    /**
     * Writes a line.
     * This method does nothing!
     *
     * @param string $sText
     * @see cDebugInterface::out()
     */
    public function out($sText)
    {
    }

    /**
     * Outputs contents of passed variable in a preformatted, readable way
     *
     * @param mixed $mVariable
     *                                     The variable to be displayed
     * @param string $sVariableDescription [optional]
     *                                     The variable's name or description
     * @param bool $bExit [optional]
     *                                     If set to true, your app will die() after output of current var
     *
     * @throws cInvalidArgumentException
     */
    public function show($mVariable, $sVariableDescription = '', $bExit = false)
    {
        $varText = $this->_prepareDumpValue($mVariable);

        $tpl = new cTemplate();
        $tpl->set('s', 'STYLES', $this->_getStyles());
        $tpl->set('s', 'VAR_DESCRIPTION', $sVariableDescription);
        $tpl->set('s', 'VAR_TEXT', $varText);

        $cfg = cRegistry::getConfig();
        $tpl->generate(cRegistry::getBackendPath() . $cfg['path']['templates'] . $cfg['templates']['debug_visible']);
        if ($bExit === true) {
            die('<p class="cms_debug_footer"><b>debugg\'ed</b></p>');
        }
    }

    /**
     * Interface implementation
     *
     * @param mixed $mVariable
     * @param string $sVariableDescription [optional]
     */
    public function add($mVariable, $sVariableDescription = '')
    {
    }

    /**
     * Interface implementation
     */
    public function reset()
    {
    }

    /**
     * Interface implementation
     */
    public function showAll()
    {
    }

}
