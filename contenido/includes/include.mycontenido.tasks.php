<?php

/**
 * This file contains the backend page for managing tasks.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cAuth $auth
 * @var cApiUser $currentuser
 * @var array $cfg
 * @var int $client
 * @var int $frame
 * @var string $area
 *
 * @var int $idcommunication
 * @var int $progress
 * @var string $subject
 * @var string $message
 * @var string $userassignment
 * @var string $notiemail
 * @var string $status
 * @var string $priority
 * @var string $enddate
 */

$oPage = new cGuiPage("mycontenido.tasks", "", "1");

// Display critical error if client or language does not exist
$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cSecurity::toInteger(cRegistry::getLanguageId());
if (($client < 1 || !cRegistry::getClient()->isLoaded()) || ($lang < 1 || !cRegistry::getLanguage()->isLoaded())) {
    $message = $client && !cRegistry::getClient()->isLoaded() ? i18n('No Client selected') : i18n('No language selected');
    $oPage->displayCriticalError($message);
    $oPage->render();
    return;
}

$action = $action ?? '';

if (!isset($sortmode)) {
    $sortmode = $currentuser->getUserProperty("system", "tasks_sortmode");
    $sortby = $currentuser->getUserProperty("system", "tasks_sortby");
}

$dateformat = getEffectiveSetting("dateformat", "full", "Y-m-d H:i:s");

if (isset($_REQUEST["listsubmit"])) {
    if (isset($c_restrict)) {
        $c_restrict = true;
        $currentuser->setUserProperty("mycontenido", "hidedonetasks", "true");
    } else {
        $c_restrict = false;
        $currentuser->setUserProperty("mycontenido", "hidedonetasks", "false");
    }
} else {
    if ($currentuser->getUserProperty("mycontenido", "hidedonetasks") == "true") {
        $c_restrict = true;
    } else {
        $c_restrict = false;
    }
}

/**
 *
 */
class TODOBackendList extends cGuiScrollList
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
    protected $_statustypes;

    /**
     * @var array
     */
    protected $_prioritytypes;

    /**
     * @var string
     */
    protected $_dateFormat;

    /**
     * TODOBackendList constructor.
     */
    public function __construct(TODOCollection $todoItems, cHTMLLink $editLink, string $dateFormat)
    {
        parent::__construct();

        $this->_editLink = $editLink;
        $this->_statustypes = $todoItems->getStatusTypes();
        $this->_prioritytypes = $todoItems->getPriorityTypes();
        $this->_dateFormat = !empty($dateFormat) ? $dateFormat : self::DEFAULT_DATE_FORMAT;
    }

    /**
     * Is called when a new column is rendered.
     *
     * @see cGuiScrollList::onRenderColumn()
     * @param int $column
     *         The current column which is being rendered
     */
    public function onRenderColumn($column)
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
     * Field converting facility.
     * Needs to be overridden in the child class to work properly.
     *
     * @see cGuiScrollList::convert()
     *
     * @param int    $key
     *         Field index
     * @param string $value
     *         Field value
     * @param array  $hidden
     *
     * @return string
     *
     * @throws cException
     */
    public function convert($key, $value, $hidden)
    {
        $cfg = cRegistry::getConfig();
        $backendUrl = cRegistry::getBackendUrl();

        // Image (1) or subject (2)
        if ($key == 1 || $key == 2) {
            $this->_editLink->setCustom('idcommunication', $hidden[1]);
            $this->_editLink->setClass($key == 1 ? 'con_img_button' : '');

            $this->_editLink->setContent($value);
            return $this->_editLink->render();
        }

        // Date
        if ($key == 3) {
            $value = date($this->_dateFormat, strtotime($value));
            return !empty($value) ? $value : '&nbsp;';
        }

        // Status
        if ($key == 5) {
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

            if (!array_key_exists($value, $this->_statustypes)) {
                return i18n("No status type set");
            }

            // Do not display statusicon, only show statustext
            #return cHTMLImage::img("images/reminder/" . $img, $this->_statustypes[$value]);

            return $this->_statustypes[$value];
        }

        // Progress
        if ($key == 7) {
            $amount = round($value / 20);

            // Amount can be between 0 - 5
            $amount = min(max(0, $amount), 5);

            if ($amount != 0) {
                $image = new cHTMLImage($backendUrl . $cfg["path"]["images"] . "reminder/progress.gif");
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
        if ($key == 6) {
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

            $image = new cHTMLImage($backendUrl . $cfg["path"]["images"] . "reminder/" . $img);
            $image->setAlt($this->_prioritytypes[$p]);
            return $image->render();
        }

        // Due date
        if ($key == 8) {
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

if ($action == "todo_save_item") {
    $subject = stripslashes($subject);
    $message = stripslashes($message);

    $todoItem = new TODOItem();
    $todoItem->loadByPrimaryKey($idcommunication);

    $todoItem->set("subject", $subject);
    $todoItem->set("message", $message);
    $todoItem->set("recipient", $userassignment);

    if (isset($reminderdate)) {
        $todoItem->setProperty("todo", "reminderdate", strtotime($reminderdate));
    }

    if (isset($notibackend)) {
        $todoItem->setProperty("todo", "backendnoti", $notibackend);
    }

    if (isset($notiemail)) {
        $todoItem->setProperty("todo", "emailnoti", $notiemail);
    }

    $todoItem->setProperty("todo", "status", $status);

    // Progress can be between 0 - 100
    $progress = min(max(0, $progress), 100);

    $todoItem->setProperty("todo", "priority", $priority);
    $todoItem->setProperty("todo", "progress", $progress);

    $todoItem->setProperty("todo", "enddate", $enddate);

    $todoItem->store();
}

$todoItems = new TODOCollection();
if ($action == "mycontenido_tasks_delete") {
    $todoItems->delete($idcommunication);
}
$recipient = $auth->auth["uid"];
$todoItems->select("recipient = '" . $todoItems->escape($recipient) . "' AND idclient = " . (int) $client);

$editLink = new cHTMLLink();
$editLink->setCLink("mycontenido_tasks_edit", 4, "");
$editLink->setCustom("sortmode", $sortmode);
$editLink->setCustom("sortby", $sortby);

$deleteLink = new cHTMLLink();
$deleteLink->setCLink("mycontenido_tasks", 4, "mycontenido_tasks_delete");
$deleteLink->setCustom("sortby", $sortby);
$deleteLink->setCustom("sortmode", $sortmode);

$list = new TODOBackendList($todoItems, $editLink, $dateformat);

$list->setHeader(
    '&nbsp;', i18n("Subject"), i18n("Created"), i18n("End Date"), i18n("Status"),
    i18n("Priority"), sprintf(i18n("%% complete")), i18n("Due in"), i18n("Actions")
);

$listCount = 0;

while ($todo = $todoItems->next()) {
    if ((($todo->getProperty("todo", "status") != "done") && $c_restrict) || (empty($c_restrict))) {

        $idcommunication = cSecurity::toInteger($todo->get("idcommunication"));
        $subject = $todo->get("subject");
        $created = $todo->get("created");

        $reminder = $todo->getProperty("todo", "enddate");
        $status = $todo->getProperty("todo", "status");
        $priority = $todo->getProperty("todo", "priority");
        $complete = $todo->getProperty("todo", "progress");

        if (trim($subject) == "") {
            $subject = i18n("Unnamed item");
        }

        if (trim($reminder) == "") {
            $reminder = i18n("No end date set");
        } else {
            $reminder = date($dateformat, strtotime($reminder));
        }

        if (trim($status) == "") {
            $status = i18n("No status set");
        }

        $image = cHTMLImage::img("images/but_todo.gif", i18n("Edit item"));

        $img = cHTMLImage::img("images/delete.gif", i18n("Delete item"));
        $deleteLink->setClass('con_img_button');
        $deleteLink->setCustom("idcommunication", $idcommunication);
        $deleteLink->setContent($img);

        $img = cHTMLImage::img("images/but_art_conf2.gif", i18n("Edit item"));
        $editLink->setClass('con_img_button mgr5');
        $editLink->setCustom('idcommunication', $idcommunication);
        $editLink->setContent($img);

        $actions = $editLink->render() . $deleteLink->render();

        if ($todo->getProperty("todo", "enddate") != "") {
            $duein = round((time() - strtotime($todo->getProperty("todo", "enddate"))) / 86400, 2);
        } else {
            $duein = "";
        }

        switch ($priority) {
            case "medium":
                $p = 1;
                break;
            case "high":
                $p = 2;
                break;
            case "immediately":
                $p = 3;
                break;
            default:
                $p = 0;
                break;
        }

        $list->setData($listCount, $image, $subject, $created, $reminder, $status, $p, $complete, $duein, $actions);
        $list->setHiddenData($listCount, $idcommunication, $idcommunication);

        $listCount++;
    }
}

$form = new cGuiTableForm("restrict");
$form->setTableID("todoList");
$form->setHeader(i18n("Restrict display"));
$form->setVar("listsubmit", "true");

$form->unsetActionButton("submit");
$form->setActionButton("submit", "images/but_refresh.gif", i18n("Refresh"), "s");

$form->setVar("area", $area);
$form->setVar("frame", $frame);

$restrict = new cHTMLCheckbox("c_restrict", "true");
$restrict->setLabelText(i18n("Hide done tasks"));

if ($c_restrict) {
    $restrict->setChecked(true);
}

$submit = new cHTMLButton("submit");
$submit->setMode("image");
$submit->setImageSource("images/submit.gif");

$form->add(i18n("Options"), $restrict->render());

if ($listCount == 0) {
    $oPage->displayInfo(i18n("No tasks found"));
    $oPage->setContent([$form]);
} else {
    $sortby = $sortby ?? 1;
    $sortmode = $sortmode ?? 'ASC';

    $list->setSortable(1, true);
    $list->setSortable(2, true);
    $list->setSortable(3, true);
    $list->setSortable(4, true);
    $list->setSortable(5, true);
    $list->setSortable(6, true);
    $list->setSortable(7, true);
    $list->sort(cSecurity::toInteger($sortby), $sortmode);

    $oPage->setContent([$form, $list]);
}
$oPage->render();

$currentuser->setUserProperty("system", "tasks_sortby", $sortby);
$currentuser->setUserProperty("system", "tasks_sortmode", $sortmode);
