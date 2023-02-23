<?php

/**
 * This file contains the notification GUI class.
 *
 * @package          Core
 * @subpackage       GUI
 *
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          https://www.contenido.org/license/LIZENZ.txt
 * @link             https://www.4fb.de
 * @link             https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for displaying notifications.
 *
 * Usage:
 * <code>
 * // render a error directly
 * $oNotification = new cGuiNotification();
 * $oNotification->displayNotification(
 *     cGuiNotification::LEVEL_ERROR, 'Foobar does not exists'
 * );
 *
 * // assign a notification to a variable
 * $oNotification = new cGuiNotification();
 * $sNotification = $oNotification->displayNotification(
 *     cGuiNotification::LEVEL_NOTIFICATION, 'Hey dude, you did it!'
 * );
 * </code>
 *
 * @package    Core
 * @subpackage GUI
 */
class cGuiNotification {

    /**
     * Error message level.
     *
     * @var string
     */
    const LEVEL_ERROR = 'error';

    /**
     * Warning message level.
     *
     * @var string
     */
    const LEVEL_WARNING = 'warning';

    /**
     * Info message level.
     *
     * @var string
     */
    const LEVEL_INFO = 'info';

    /**
     * Ok message level.
     *
     * @var string
     */
    const LEVEL_OK = 'ok';

    /**
     * Notification message level.
     *
     * @var string
     */
    const LEVEL_NOTIFICATION = 'notification';

    /**
     * HTML path to images.
     *
     * @var string
     */
    protected $_sPathImages;

    /**
     * Constructor to create an instance of this class.
     */
    public function __construct() {
        global $cfg;
        $this->_sPathImages = cRegistry::getBackendUrl() . $cfg['path']['images'];
    }

    /**
     * Generates message box and returns it back.
     *
     * @param string $sLevel
     *         Message level, one of cGuiNotification::LEVEL_* constants
     * @param string $sMessage
     *         The message to display
     * @param int $iStyle [optional]
     *         Flag tp use styles for display or not (feasible 1 or 0)
     * @return string
     */
    public function returnMessageBox($sLevel, $sMessage, $iStyle = 1) {
        switch ($sLevel) {
            case self::LEVEL_ERROR:
                $sHead = i18n('Error');
                $sHeadClass = 'alertbox_error';
                break;
            case self::LEVEL_WARNING:
                $sHead = i18n('Warning');
                $sHeadClass = 'alertbox_warning';
                break;
            case self::LEVEL_INFO:
                $sHead = i18n('Info');
                $sHeadClass = 'alertbox_info';
                $sMessage = '<span>' . $sMessage . '</span>';
                break;
            case self::LEVEL_OK:
                $sHead = i18n('Ok');
                $sHeadClass = 'alertbox_ok';
                $sMessage = '<span>' . $sMessage . '</span>';
                break;
            default:
                $sHead = i18n('Notification');
                $sHeadClass = 'alertbox_notification';
                $sMessage = '<span>' . $sMessage . '</span>';
                break;
        }

        if ($iStyle == 1) {
            // Box on login page
            $sMessageBox =
                    '<div class="alertbox ' . $sHeadClass . '_color" id="contenido_notification">' .
                    '<h1 class="alertbox_head ' . $sHeadClass . '">' . $sHead . '</h1>' .
                    '<div class="alertbox_message">' . $sMessage . '</div>' .
                    '</div>';
        } else {
            // Simple box
            $sMessageBox =
                    '<div class="alertbox_line ' . $sHeadClass . '_color" id="contenido_notification">' .
                    '<h1 class=" alertbox_head ' . $sHeadClass . ' ' . $sHeadClass . '_color">' . $sHead . '</h1>' .
                    '<div class="alertbox_message ' . $sHeadClass . '_color">' . $sMessage . '</div>' .
                    '</div>';
        }
        return $sMessageBox;
    }

    /**
     * Generates message box and returns it back, uses markup with table.
     *
     * @param string $sLevel
     *         Message level, one of cGuiNotification::LEVEL_* constants
     * @param string $sMessage
     *         The message to display
     * @return string
     */
    public function returnNotification($sLevel, $sMessage) {

        $oNotifySpan = new cHTMLSpan($sMessage);

        switch ($sLevel) {
            case self::LEVEL_ERROR:
                $oNotifySpan->setClass('notify_general notify_error');
                break;
            case self::LEVEL_WARNING:
                $oNotifySpan->setClass('notify_general notify_warning');
                break;
            case self::LEVEL_INFO:
                $oNotifySpan->setClass('notify_general notify_info');
                break;
            case self::LEVEL_OK:
                $oNotifySpan->setClass('notify_general notify_ok');
                break;
            default:
                $oNotifySpan->setClass('notify_general notify_default');
                break;
        }

        $sNoti = '<div id="contenido_notification">';
        $sNoti .= $oNotifySpan->toHtml();
        $sNoti .= '</div>';

        return $sNoti;
    }

    /**
     * Displays small message box directly.
     *
     * @param string $sLevel
     *         Message level, one of cGuiNotification::LEVEL_* constants
     * @param string $sMessage
     *         The message to display
     */
    public function displayNotification($sLevel, $sMessage) {
        echo $this->returnNotification($sLevel, $sMessage) . '<br>';
    }

    /**
     * Displays large message box directly.
     *
     * @param string $sLevel
     *         Message level, one of cGuiNotification::LEVEL_* constants
     * @param string $sMessage
     *         The message to display
     * @param int $iStyle [optional]
     *         Flag tp use styles for display or not (feasible 1 or 0)
     */
    public function displayMessageBox($sLevel, $sMessage, $iStyle = 1) {
        echo $this->returnMessageBox($sLevel, $sMessage, $iStyle) . '<br>';
    }

}
