<?php

/**
 * This file contains the notification GUI class.
 *
 * @package    Core
 * @subpackage GUI
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
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
class cGuiNotification
{

    /**
     * Error message level.
     *
     * @var string
     */
    public const LEVEL_ERROR = 'error';

    /**
     * Warning message level.
     *
     * @var string
     */
    public const LEVEL_WARNING = 'warning';

    /**
     * Info message level.
     *
     * @var string
     */
    public const LEVEL_INFO = 'info';

    /**
     * Ok message level.
     *
     * @var string
     */
    public const LEVEL_OK = 'ok';

    /**
     * Notification message level.
     *
     * @var string
     */
    public const LEVEL_NOTIFICATION = 'notification';

    /**
     * HTML path to images.
     *
     * @var string
     */
    protected $_sPathImages;

    /**
     * Constructor to create an instance of this class.
     */
    public function __construct()
    {
        $cfg = cRegistry::getConfig();
        $this->_sPathImages = cRegistry::getBackendUrl() . $cfg['path']['images'];
    }

    /**
     * Generates message box and returns it back.
     *
     * @param string $level Message level, one of cGuiNotification::LEVEL_* constants
     * @param string $message The message to display
     * @param int $style [optional] Flag tp use styles for display or not (feasible 1 or 0)
     * @return string
     */
    public function returnMessageBox($level, $message, $style = 1): string
    {
        switch ($level) {
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
                $message = '<span>' . $message . '</span>';
                break;
            case self::LEVEL_OK:
                $sHead = i18n('Ok');
                $sHeadClass = 'alertbox_ok';
                $message = '<span>' . $message . '</span>';
                break;
            default:
                $sHead = i18n('Notification');
                $sHeadClass = 'alertbox_notification';
                $message = '<span>' . $message . '</span>';
                break;
        }

        if ($style == 1) {
            // Box on login page
            $messageBox =
                '<div class="alertbox ' . $sHeadClass . '_color" id="contenido_notification">' .
                '<h1 class="alertbox_head ' . $sHeadClass . '">' . $sHead . '</h1>' .
                '<div class="alertbox_message">' . $message . '</div>' .
                '</div>';
        } else {
            // Simple box
            $messageBox =
                '<div class="alertbox_line ' . $sHeadClass . '_color" id="contenido_notification">' .
                '<h1 class=" alertbox_head ' . $sHeadClass . ' ' . $sHeadClass . '_color">' . $sHead . '</h1>' .
                '<div class="alertbox_message ' . $sHeadClass . '_color">' . $message . '</div>' .
                '</div>';
        }
        return $messageBox;
    }

    /**
     * Generates message box and returns it back, uses markup with table.
     *
     * @param string $level Message level, one of cGuiNotification::LEVEL_* constants
     * @param string $message The message to display
     * @return string
     */
    public function returnNotification($level, $message)
    {

        $oNotifySpan = new cHTMLSpan($message);

        switch ($level) {
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

        return sprintf('<div id="contenido_notification">%s</div>', $oNotifySpan->toHtml());
    }

    /**
     * Displays small message box directly.
     *
     * @param string $level Message level, one of cGuiNotification::LEVEL_* constants
     * @param string $message The message to display
     */
    public function displayNotification($level, $message)
    {
        echo $this->returnNotification($level, $message) . '<br>';
    }

    /**
     * Displays large message box directly.
     *
     * @param string $level Message level, one of cGuiNotification::LEVEL_* constants
     * @param string $message The message to display
     * @param int $style [optional] Flag tp use styles for display or not (feasible 1 or 0)
     */
    public function displayMessageBox($level, $message, $style = 1)
    {
        echo $this->returnMessageBox($level, $message, $style) . '<br>';
    }

}
