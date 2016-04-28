<?php

/**
 * This file contains the backend page for managing tasks.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

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
class TODOBackendList extends cGuiScrollList {

    /**
     * @var array
     */
    protected $_statustypes;

    /**
     * @var array
     */
    protected $_prioritytypes;

    /**
     * TODOBackendList constructor.
     */
    public function __construct() {
        global $todoitems;

        parent::__construct();

        $this->_statustypes = $todoitems->getStatusTypes();
        $this->_prioritytypes = $todoitems->getPriorityTypes();
    }


    /**
     * Old constructor
     */
    public function TODOBackendList() {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        $this->__construct();
    }

    /**
     * Is called when a new column is rendered.
     *
     * @see cGuiScrollList::onRenderColumn()
     * @param unknown_type $column
     *         The current column which is being rendered
     */
    public function onRenderColumn($column) {
        if ($column == 6 || $column == 5) {
            $this->objItem->updateAttributes(array("align" => "center"));
        } else {
            $this->objItem->updateAttributes(array("align" => "left"));
        }

        if ($column == 7) {
            $this->objItem->updateAttributes(array("style" => "width: 85px;"));
        } else {
            $this->objItem->updateAttributes(array("style" => ""));
        }
    }

    /**
     * Field converting facility.
     * Needs to be overridden in the child class to work properbly.
     *
     * @see cGuiScrollList::convert()
     * @param unknown_type $field
     *         Field index
     * @param unknown_type $value
     *         Field value
     * @param unknown_type $hiddendata
     * @return unknown
     */
    public function convert($key, $value, $hidden) {
        global $link, $dateformat, $cfg;

        $backendUrl = cRegistry::getBackendUrl();

        if ($key == 2) {
            $link->setCustom("idcommunication", $hidden[1]);
            $link->setContent($value);
            return $link->render();
        }

        if ($key == 3) {
            return date($dateformat, strtotime($value));
        }

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

            $backendUrl = cRegistry::getBackendUrl();

            $image = new cHTMLImage($backendUrl . $cfg["path"]["images"] . "reminder/" . $img);
            $image->setAlt($this->_statustypes[$value]);

            //Do not display statuicon, only show statustext
            //return $image->render();
            return $this->_statustypes[$value];
        }

        if ($key == 7) {
            $amount = $value / 20;

            if ($amount < 0) {
                $amount = 0;
            }

            if ($amount > 5) {
                $amount = 5;
            }

            $amount = round($amount);

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

        if ($key == 6) {
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

        if ($key == 8) {

            if ($value !== "") {
                if (round($value, 2) == 0) {
                    return i18n("Today");
                } else {
                    if ($value < 0) {
                        return number_format(0 - $value, 2, ',', '') . " " . i18n("Day(s)");
                    } else {
                        return '<font color="red">' . number_format(0 - $value, 2, ',', '') . " " . i18n("Day(s)") . '</font>';
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

    $todoitem = new TODOItem();
    $todoitem->loadByPrimaryKey($idcommunication);

    $todoitem->set("subject", $subject);
    $todoitem->set("message", $message);
    $todoitem->set("recipient", $userassignment);

    if (isset($reminderdate)) {
        $todoitem->setProperty("todo", "reminderdate", strtotime($reminderdate));
    }

    if (isset($notibackend)) {
        $todoitem->setProperty("todo", "backendnoti", $notibackend);
    }

    $todoitem->setProperty("todo", "emailnoti", $notiemail);

    $todoitem->setProperty("todo", "status", $status);

    if ($progress < 0) {
        $progress = 0;
    }

    if ($progress > 100) {
        $progress = 100;
    }

    $todoitem->setProperty("todo", "priority", $priority);
    $todoitem->setProperty("todo", "progress", $progress);

    $todoitem->setProperty("todo", "enddate", $enddate);

    $todoitem->store();
}

$cpage = new cGuiPage("mycontenido.tasks", "", "1");

$todoitems = new TODOCollection();

if ($action == "mycontenido_tasks_delete") {
    $todoitems->delete($idcommunication);
}

$recipient = $auth->auth["uid"];

$todoitems->select("recipient = '" . $todoitems->escape($recipient) . "' AND idclient=" . (int) $client);

$list = new TODOBackendList();

$list->setHeader(
    '&nbsp;', i18n("Subject"), i18n("Created"), i18n("End Date"), i18n("Status"),
    i18n("Priority"), sprintf(i18n("%% complete")), i18n("Due in"), i18n("Actions")
);

$lcount = 0;

$link = new cHTMLLink();
$link->setCLink("mycontenido_tasks_edit", 4, "");
$link->setCustom("sortmode", $sortmode);
$link->setCustom("sortby", $sortby);

while ($todo = $todoitems->next()) {
    if ((($todo->getProperty("todo", "status") != "done") && ($c_restrict == true)) || ($c_restrict == '')) {

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

        $link->setCustom("idcommunication", $todo->get("idcommunication"));
        $link->setContent('<img id="myContenidoTodoButton" src="images/but_todo.gif" alt="" border="0">');

        $mimg = $link->render();

        $link->setContent($subject);

        $msubject = $link->render();

        $idcommunication = $todo->get("idcommunication");

        $delete = new cHTMLLink();
        $delete->setCLink("mycontenido_tasks", 4, "mycontenido_tasks_delete");
        $delete->setCustom("idcommunication", $idcommunication);
        $delete->setCustom("sortby", $sortby);
        $delete->setCustom("sortmode", $sortmode);

        $img = new cHTMLImage("images/delete.gif");
        $img->setAlt(i18n("Delete item"));

        $delete->setContent($img->render());

        $properties = $link;

        $img = new cHTMLImage("images/but_art_conf2.gif");
        $img->setAlt(i18n("Edit item"));
        $img->setStyle("padding-right: 4px;");
        $properties->setContent($img);

        $actions = $properties->render() . $delete->render();

        if ($todo->getProperty("todo", "enddate") != "") {
            $duein = round((time() - strtotime($todo->getProperty("todo", "enddate"))) / 86400, 2);
        } else {
            $duein = "";
        }

        switch ($priority) {
            case "low":
                $p = 0;
                break;
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
                break;
        }

        $list->setData($lcount, $mimg, $subject, $created, $reminder, $status, $p, $complete, $duein, $actions);
        $list->setHiddenData($lcount, $idcommunication, $idcommunication);

        $lcount++;
    }
}

$form = new cGuiTableForm("restrict");
$form->setTableID("todoList");
$form->addHeader(i18n("Restrict display"));
$form->setVar("listsubmit", "true");

$form->unsetActionButton("submit");
$form->setActionButton("submit", "images/but_refresh.gif", i18n("Refresh"), "s");

$form->setVar("area", $area);
$form->setVar("frame", $frame);

$restrict = new cHTMLCheckbox("c_restrict", "true");
$restrict->setLabelText(i18n("Hide done tasks"));

if ($c_restrict == true) {
    $restrict->setChecked(true);
}

$submit = new cHTMLButton("submit");
$submit->setMode("image");
$submit->setImageSource("images/submit.gif");

$form->add(i18n("Options"), $restrict->render());

if ($lcount == 0) {
    $cpage->displayInfo(i18n("No tasks found"));
    $cpage->setContent(array($form));
} else {
    if (!isset($sortby)) {
        $sortby = 1;
    }

    if (!isset($sortmode)) {
        $sortmode = "ASC";
    }

    $list->setSortable(1, true);
    $list->setSortable(2, true);
    $list->setSortable(3, true);
    $list->setSortable(4, true);
    $list->setSortable(5, true);
    $list->setSortable(6, true);
    $list->setSortable(7, true);
    $list->sort($sortby, $sortmode);

    $cpage->setContent(array($form, $list));
}
$cpage->render();

$currentuser->setUserProperty("system", "tasks_sortby", $sortby);
$currentuser->setUserProperty("system", "tasks_sortmode", $sortmode);

?>