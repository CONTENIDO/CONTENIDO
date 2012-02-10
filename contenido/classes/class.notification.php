<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Class for displaying notifications.
 *
 * Usage:
 * <code>
 * // render a error directly
 * $oNotification = new Contenido_Notification();
 * $oNotification->displayNotification(
 *     Contenido_Notification::LEVEL_ERROR, 'Foobar does not exists'
 * );
 *
 * // assign a notification to a variable
 * $oNotification = new Contenido_Notification();
 * $sNotification = $oNotification->displayNotification(
 *     Contenido_Notification::LEVEL_NOTIFICATION, 'Hey dude, you did it!'
 * );
 * </code>
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.1.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  unknown
 *   modified 2008-04-04, Timo Trautmann, added new colors and functions for direct output
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2011-05-19, Murat Purc, adapted to PHP 5, formatted and documented code
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class Contenido_Notification
{
    /**
     * Error message level
     * @var string
     */
    const LEVEL_ERROR = 'error';

    /**
     * Warning message level
     * @var string
     */
    const LEVEL_WARNING = 'warning';

    /**
     * Info message level
     * @var string
     */
    const LEVEL_INFO = 'info';

    /**
     * Notification message level
     * @var string
     */
    const LEVEL_NOTIFICATION = 'notification';

    /**
     * Colors configuration
     * @var array
     */
    protected $_aColors;

    /**
     * HTML path to images
     * @var string
     */
    protected $_sPathImages;


    /**
     * Constructor
     */
    public function __construct()
    {
        global $cfg;
        $this->_aColors = $cfg['color'];
        $this->_sPathImages = $cfg['path']['contenido_fullhtml'] . $cfg['path']['images'];
    }


    /** @deprecated  [2011-05-19] Old constructor function for downwards compatibility */
    public function Contenido_Notification()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }


    /**
     * Generates message box and returns it back.
     *
     * @param   string  $sLevel  Message level, one of Contenido_Notification::LEVEL_* constants
     * @param   string  $sMessage  The message to display
     * @param   int     $iStyle   Flag tp use styles for display or not (feasible 1 or 0)
     * @return  string
     */
    public function messageBox($sLevel, $sMessage, $iStyle = 1)
    {
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
                $sMessage = '<span style="color:#435d06">' . $sMessage . '</span>';
                break;
            default:
                $sHead = i18n('Notification');
                $sHeadClass = 'alertbox_notification';
                $sMessage = '<span style="color:#435d06">' . $sMessage . '</span>';
                break;
        }

        if ($iStyle == 1) {
            // Box on login page
            $sMessageBox =
                '<div class="alertbox ' . $sHeadClass . '_color" id="contenido_notification" style="border-top:0px;">' .
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
     * @param   string  $sLevel  Message level, one of Contenido_Notification::LEVEL_* constants
     * @param   string  $sMessage  The message to display
     * @return  string
     */
    public function returnNotification($sLevel, $sMessage)
    {
        switch ($sLevel) {
            case self::LEVEL_ERROR:
                $sBgColor = $this->_aColors['notify_error'];
                $sImgPath = $this->_sPathImages . 'icon_fatalerror.gif';
                break;
            case self::LEVEL_WARNING:
                $sBgColor = $this->_aColors['notify_warning'];
                $sImgPath = $this->_sPathImages . 'icon_warning.gif';
                break;
            case self::LEVEL_INFO:
                $sMessage = '<span style="color:#435d06">' . $sMessage . '</span>';
                $sBgColor = $this->_aColors['notify_info'];
                $sImgPath = $this->_sPathImages . 'but_ok.gif';
                break;
            default:
                $sMessage = '<span style="color:#435d06">'.$sMessage.'</span>';
                $sBgColor = $this->_aColors['notify'];
                $sImgPath = $this->_sPathImages . 'but_ok.gif';
                break;
        }

        $oTable = new Table($sBgColor, 'solid', 0, 2, '#FFFFFF', '#FFFFFF', '#FFFFFF', true, false);

        $sNoti = '<div id="contenido_notification" style="position:relative;left:0;top:0;z-index:10;">';
        $sNoti .= $oTable->start_table();
        $sNoti .= $oTable->header_row();
        $sNoti .= $oTable->borderless_cell('<img src="' . $sImgPath . '" alt="">');
        $sNoti .= $oTable->borderless_cell(
            '<font color="' . $sBgColor . '" style="font-family:Verdana,Arial,Helvetica,'
          . 'Sans-Serif;font-size:11px;">' . $sMessage . '</font>', 'left', 'middle'
        );
        $sNoti .= $oTable->end_row();
        $sNoti .= $oTable->end_table();
        $sNoti .= '</div>';

        return $sNoti;
    }


    /**
     * Displays small message box directly.
     *
     * @param   string  $sLevel  Message level, one of Contenido_Notification::LEVEL_* constants
     * @param   string  $sMessage  The message to display
     * @return  void
     */
    public function displayNotification($sLevel, $sMessage)
    {
        echo $this->returnNotification($sLevel, $sMessage) . '<br>';
    }


    /**
     * Displays large message box directly.
     *
     * @param   string  $sLevel  Message level, one of Contenido_Notification::LEVEL_* constants
     * @param   string  $sMessage  The message to display
     * @param   int     $iStyle   Flag tp use styles for display or not (feasible 1 or 0)
     * @return  void
     */
    public function displayMessageBox($sLevel, $sMessage, $iStyle = 1)
    {
        echo $this->messageBox($sLevel, $sMessage, $iStyle) . '<br>';
    }

}

?>