<?php

/**
 * This file contains the visible adv debug class.
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
 * Debug object to show info on screen in a box / HTML Block at the top of page.
 * Instead of doing the output immediately using method show, values can be
 * collected and printed to screen in one go.
 * Therefore, there's a box positioned at the left top of the page that can be
 * toggled and hidden.
 *
 * Please note:
 * When using method Debug_VisibleAdv::showAll() you'll produce invalid HTML
 * when having an XHTML doctype.
 *
 * @package    Core
 * @subpackage Debug
 */
class cDebugVisibleAdv implements cDebugInterface, Countable
{

    use cDebugVisibleTrait;

    /**
     * Singleton instance
     *
     * @var cDebugVisibleAdv
     */
    private static $_instance;

    /**
     *
     * @var array
     */
    protected $_aItems;

    /**
     *
     * @var string
     */
    protected $_buffer;

    /**
     * Return singleton instance.
     *
     * @return cDebugVisibleAdv
     */
    public static function getInstance(): cDebugInterface
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
        $this->_aItems = [];
        $this->_buffer = '';
    }

    /**
     * Add a Debug item to internal collection.
     *
     * @param mixed $mVariable
     * @param string $sVariableDescription [optional]
     */
    public function add($mVariable, $sVariableDescription = '')
    {
        $oItem = new cDebugVisibleAdvItem();
        $oItem->setValue($mVariable);
        $oItem->setDescription($sVariableDescription);
        $this->_aItems[] = $oItem;
    }

    /**
     * Reset internal collection with Debug items.
     */
    public function reset()
    {
        $this->_aItems = [];
    }

    /**
     * Writes a line.
     *
     * @see cDebugInterface::out()
     * @param string $sText
     */
    public function out($sText)
    {
        $this->_buffer .= $sText . "\n";
    }

    /**
     * Outputs all Debug items in collection to screen in a HTML Box at left top
     * of the page. The alignment can be configured via setting:
     * - Type: 'debug'
     * - Name: 'debug_to_screen_align'
     * - Value: 'left' or 'right' (default is 'left')
     * No output happen in case of an Ajax request.
     *
     * @throws cInvalidArgumentException
     */
    public function showAll()
    {
        if (cIsAjaxRequest()) {
            return;
        }

        $cfg = cRegistry::getConfig();

        $alignment = cEffectiveSetting::get('debug', 'debug_to_screen_align', 'left');
        $cssClass = $alignment === 'right' ? 'con_dbg_box_align_right' : 'con_dbg_box_align_left';

        if (!empty($this->_buffer)) {
            // Add buffer as a debug item
            $this->add($this->_buffer, 'Buffer');
        }

        $sHtml = '';
        if ($this->count() > 0) {
            $tpl = new cTemplate();

            $tpl->set('s', 'DBG_BOX_CSS_CLASS', $cssClass);

            $i = 1;
            foreach ($this->_aItems as $oItem) {
                $sItemName = cString::getStringLength($oItem->getDescription()) > 0 ? $oItem->getDescription() : ('debug item #' . $i);
                $sItemValue = $this->_prepareValue($oItem->getValue());

                $tpl->set('d', 'DBG_ITEM_COUNT', $i);
                $tpl->set('d', 'DBG_ITEM_NAME', $sItemName);
                $tpl->set('d', 'DBG_ITEM_VALUE', $sItemValue);
                $tpl->next();

                ++$i;
            }
            $sHtml .= $tpl->generate(cRegistry::getBackendPath() . $cfg['path']['templates'] . $cfg['templates']['debug_visibleadv'], true);
        }

        $buffer = str_replace("\'", "\\'", $this->_buffer);
        $buffer = str_replace("\"", "\\\"", $buffer);
        $buffer = str_replace("\n", '\n', $buffer);
        $buffer = str_replace("\r", '', $buffer);

        // making sure that the working directory is right
        $dir = getcwd();
        chdir(cRegistry::getBackendPath());

        $tpl = new cTemplate();
        $tpl->set('s', 'DBG_MESSAGE_CONTENT', $buffer);
        $sHtml .= $tpl->generate($cfg['path']['templates'] . $cfg['templates']['debug_header'], true);

        // switching back to the old directory if needed
        chdir($dir);

        echo $sHtml;
    }

    /**
     * Prepares Debug item value for output as string representation.
     *
     * @param mixed $mValue
     *
     * @return string
     */
    private function _prepareValue($mValue): string
    {
        return $this->_prepareDumpValue($mValue);
    }

    /**
     * Implementation of Countable interface
     *
     * @return int
     */
    public function count(): int
    {
        return (int) sizeof($this->_aItems);
    }

    /**
     * Outputs contents of passed variable in a preformatted, readable way.
     *
     * @param mixed $mVariable
     *         The variable to be displayed.
     * @param string $sVariableDescription [optional]
     *         The variable's name or description.
     * @param bool $bExit [optional]
     *         If set to true, your app will die() after output of current var.
     */
    public function show($mVariable, $sVariableDescription = '', $bExit = false)
    {
        try {
            $oDbgVisible = cDebug::getDebugger(cDebug::DEBUGGER_VISIBLE);
            $oDbgVisible->show($mVariable, $sVariableDescription, $bExit);
        } catch (Exception $e) {
            // throw $e;
            echo $e->getMessage();
        }
    }

}

/**
 * An object representing one Debug item of a Debug_VisibleBlock.
 *
 * @package    Core
 * @subpackage Debug
 */
class cDebugVisibleAdvItem
{

    /**
     *
     * @var mixed
     */
    private $_mValue;

    /**
     *
     * @var string
     */
    private $_sDescription;

    /**
     * Get value of item
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->_mValue;
    }

    /**
     * Set value of item
     *
     * @param mixed $mValue
     */
    public function setValue($mValue)
    {
        $this->_mValue = $mValue;
    }

    /**
     * Get name/description of item
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->_sDescription;
    }

    /**
     * Set name/description of item
     *
     * @param string $sDescription
     */
    public function setDescription(string $sDescription)
    {
        $this->_sDescription = $sDescription;
    }

}
