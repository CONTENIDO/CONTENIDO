<?php

/**
 * This file contains the todo-backend scrollable lists GUI class.
 *
 * @since      CONTENIDO 4.10.2 - Class code extracted from `contenido/includes/include.mycontenido.tasks.php`.
 * @package    Core
 * @subpackage GUI
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

class cGuiScrollListMyContenidoTasks extends cGuiScrollList
{

    /**
     * Default date format as fallback
     */
    const DEFAULT_DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var cHTMLLink
     */
    protected $_editLink;

    /**
     * @var array
     */
    protected $_statusTypes;

    /**
     * @var array
     */
    protected $_priorityTypes;

    /**
     * @var string
     */
    protected $_dateFormat;

    /**
     * cGuiScrollListMyContenidoTasks constructor.
     */
    public function __construct(TODOCollection $todoItems, cHTMLLink $editLink, string $dateFormat)
    {
        parent::__construct();

        $this->_editLink = $editLink;
        $this->_statusTypes = $todoItems->getStatusTypes();
        $this->_priorityTypes = $todoItems->getPriorityTypes();
        $this->_dateFormat = !empty($dateFormat) ? $dateFormat : self::DEFAULT_DATE_FORMAT;
    }

    /**
     * Is called when a new column is rendered.
     *
     * @param int $column
     *         The current column which is being rendered
     * @see cGuiScrollList::onRenderColumn()
     */
    public function onRenderColumn(int $column)
    {
        if ($column == 6 || $column == 5) {
            $this->objItem->updateAttributes(["align" => "center"]);
        } else {
            $this->objItem->updateAttributes(["align" => "left"]);
        }

        if ($column == 7) {
            $this->objItem->updateAttributes(["style" => "width: 85px;"]);
        } else {
            $this->objItem->updateAttributes(["style" => ""]);
        }
    }

    /**
     * @inheritDoc
     * @throws cException
     * @see cGuiScrollList::convert()
     */
    public function convert(int $field, $value, array $hiddenData): string
    {
        $cfg = cRegistry::getConfig();
        $backendUrl = cRegistry::getBackendUrl();

        // Image (1) or subject (2)
        if ($field == 1 || $field == 2) {
            $this->_editLink->setCustom('idcommunication', $hiddenData[1]);
            $this->_editLink->setClass($field == 1 ? 'con_img_button' : '');

            $this->_editLink->setContent($value);
            return $this->_editLink->render();
        }

        // Date
        if ($field == 3) {
            $value = date($this->_dateFormat, strtotime($value));
            return !empty($value) ? $value : '&nbsp;';
        }

        // Status
        if ($field == 5) {
            if (!array_key_exists($value, $this->_statusTypes)) {
                return i18n("No status type set");
            }

            /*
            // Do not display statusicon, show only statustext
            switch ($value) {
                case "new":
                    $img = "status_new.gif";
                    break;
                case "progress":
                    $img = "status_inprogress.gif";
                    break;
                case "done":
                    $img = "status_done.gif";
                    break;
                case "deferred":
                    $img = "status_deferred.gif";
                    break;
                case "waiting":
                    $img = "status_waiting.gif";
                    break;
                default:
                    break;
            }
            return cHTMLImage::img("images/reminder/" . $img, $this->_statusTypes[$value]);
            */

            return $this->_statusTypes[$value];
        }

        // Progress
        if ($field == 7) {
            $amount = round($value / 20);

            // Amount can be between 0 - 5
            $amount = min(max(0, $amount), 5);

            if ($amount != 0) {
                $image = new cHTMLImage($backendUrl . $cfg['path']['images'] . "reminder/progress.gif");
                $image->setAlt(sprintf(i18n("%d %% complete"), $value));
                $ret = "";

                for ($i = 0; $i < $amount; $i++) {
                    $ret .= $image->render();
                }

                return $ret;
            } else {
                return '&nbsp;';
            }
        }

        // Priority
        if ($field == 6) {
            $p = $img = '';

            switch ($value) {
                case 0:
                    $img = "prio_low.gif";
                    $p = "low";
                    break;
                case 1:
                    $img = "prio_medium.gif";
                    $p = "medium";
                    break;
                case 2:
                    $img = "prio_high.gif";
                    $p = "high";
                    break;
                case 3:
                    $img = "prio_veryhigh.gif";
                    $p = "immediately";
                    break;
                default:
                    break;
            }

            $image = new cHTMLImage($backendUrl . $cfg['path']['images'] . "reminder/" . $img);
            $image->setAlt($this->_priorityTypes[$p]);
            return $image->render();
        }

        // Due date
        if ($field == 8) {
            if ($value !== "") {
                if (round($value, 2) == 0) {
                    return i18n("Today");
                } else {
                    if ($value < 0) {
                        return number_format(0 - $value, 2, ',', '') . " " . i18n("Day(s)");
                    } else {
                        return '<span style="color:red">' . number_format(0 - $value, 2, ',', '') . " " . i18n("Day(s)") . '</span>';
                    }
                }
            } else {
                return '&nbsp;';
            }
        }

        return $value;
    }

}

/**
 * @deprecated [2024-02-04] Since 4.10.2, use {@see cGuiScrollListMyContenidoTasks} instead!
 */
class TODOBackendList extends cGuiScrollListMyContenidoTasks
{

    public function __construct(TODOCollection $todoItems, cHTMLLink $editLink, string $dateFormat)
    {
        cDeprecated("The class TODOBackendList is deprecated since CONTENIDO 4.10.2, use cGuiScrollListMyContenidoTasks instead.");
        parent::__construct($todoItems, $editLink, $dateFormat);
    }

}
