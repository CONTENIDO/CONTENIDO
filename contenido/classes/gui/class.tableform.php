<?php
/**
 * This file contains the table form GUI class.
 *
 * @package Core
 * @subpackage GUI
 * @version SVN Revision $Rev:$
 *
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Table form GUI class.
 *
 * @package Core
 * @subpackage GUI
 */
class cGuiTableForm {

    /**
     *
     * @var array
     */
    public $items = array();

    /**
     *
     * @var array
     */
    public $captions = array();

    /**
     *
     * @var int
     */
    public $id = 0;

    /**
     *
     * @var array
     */
    public $rownames = array();

    /**
     *
     * @var  array
     */
    public $itemType = array();

    /**
     *
     * @var string
     */
    public $formname;

    /**
     *
     * @var string
     */
    public $formmethod;

    /**
     *
     * @var string
     */
    public $formaction;

    /**
     *
     * @var array
     */
    public $formvars = array();

    /**
     *
     * @var string
     */
    public $tableid = "";

    /**
     *
     * @var string
     */
    public $header;

    /**
     *
     * @var string
     */
    public $cancelLink;

    /**
     *
     * @var string
     */
    public $submitjs;

    /**
     *
     * @var array
     */
    public $custom = array();

    /**
     * Creates a new cGuiTableForm with given name, action & method of form.
     *
     * @param string $name of form
     * @param string $action of form defaults to 'main.php'
     * @param string $method of form defaults to 'post'
     */
    public function __construct($name, $action = 'main.php', $method = 'post') {

        // action defaults to 'main.php'
        if ($action == '') {
            $action = 'main.php';
        }

        // set name, action & method
        $this->formname = $name;
        $this->formaction = $action;
        $this->formmethod = $method;

        $this->setActionButton('submit', cRegistry::getBackendUrl() . 'images/but_ok.gif', i18n('Save changes'), 's');
    }

    /**
     *
     * @param string $name
     * @param mixed $value
     */
    public function setVar($name, $value) {
        $this->formvars[$name] = $value;
    }

    /**
     * Adds a new caption, item and row name.
     *
     * @param string $caption
     * @param array|object|string $item
     * @param string $rowname
     */
    public function add($caption, $item, $rowname = "") {

        // handle item as array of items
        if (is_array($item)) {
            $temp = "";
            foreach ($item as $value) {
                if (is_object($value) && method_exists($value, "render")) {
                    $temp .= $value->render();
                } else {
                    $temp .= $value;
                }
            }
            $item = $temp;
        }

        // handle item as object
        if (is_object($item) && method_exists($item, "render")) {
            $item = $item->render();
        }

        // increase ID
        $this->id++;

        // set defaults
        if ($caption == "") {
            $caption = "&nbsp;";
        }
        if ($item == "") {
            $item = "&nbsp;";
        }
        if ($rowname == "") {
            $rowname = $this->id;
        }

        $this->captions[$this->id] = $caption;
        $this->items[$this->id] = $item;
        $this->rownames[$this->id] = $rowname;
    }

    /**
     * Sets an URL as HREF of a cancel icon.
     *
     * @param string $link
     */
    public function addCancel($link) {
        $this->cancelLink = $link;
    }

    /**
     * Sets the header. The header is *set* not *added*!
     *
     * @param string $header
     * @todo rename addHeader() to setHeader()
     */
    public function addHeader($header) {
        $this->header = $header;
    }

    /**
     *
     * @param string $header
     */
    public function addSubHeader($header) {
        $this->id++;
        $this->items[$this->id] = '';
        $this->captions[$this->id] = $header;
        $this->itemType[$this->id] = 'subheader';
    }

    /**
     *
     * @param string $js
     */
    public function setSubmitJS($js) {
        $this->submitjs = $js;
    }

    /**
     *
     * @param unknown_type $id
     * @param unknown_type $event
     */
    public function setActionEvent($id, $event) {
        $this->custom[$id]["event"] = $event;
    }

    /**
     *
     * @param unknown_type $id
     * @param unknown_type $image
     * @param unknown_type $description
     * @param unknown_type $accesskey
     * @param unknown_type $action
     */
    public function setActionButton($id, $image, $description = "", $accesskey = false, $action = false) {
        $this->custom[$id]["image"] = $image;
        $this->custom[$id]["type"] = "actionsetter";
        $this->custom[$id]["action"] = $action;
        $this->custom[$id]["description"] = $description;
        $this->custom[$id]["accesskey"] = $accesskey;
        $this->custom[$id]["event"] = "";
    }

    /**
     *
     * @param unknown_type $id
     * @param unknown_type $title
     * @param unknown_type $description
     */
    public function setConfirm($id, $title, $description) {
        $this->custom[$id]["confirmtitle"] = $title;
        $this->custom[$id]["confirmdescription"] = $description;
    }

    /**
     *
     * @param string $tableid
     */
    public function setTableID($tableid) {
        $this->tableid = $tableid;
    }

    /**
     *
     * @param unknown_type $id
     */
    public function unsetActionButton($id) {
        unset($this->custom[$id]);
    }

    /**
     * Renders this cGuiTableForm and either returs ist markup or echoes it
     * immediately.
     *
     * @param bool $return if true then return markup, else echo immediately
     * @return Ambigous <string, mixed>
     */
    public function render($return = true) {
        global $sess, $cfg;

        $tpl = new cTemplate();

        if ($this->submitjs != "") {
            $tpl->set("s", "JSEXTRA", 'onsubmit="' . $this->submitjs . '"');
        } else {
            $tpl->set("s", "JSEXTRA", '');
        }

        $tpl->set("s", "FORMNAME", $this->formname);
        $tpl->set("s", "METHOD", $this->formmethod);
        $tpl->set("s", "ACTION", $this->formaction);

        $this->formvars[$sess->name] = $sess->id;

        $hidden = "";
        if (is_array($this->formvars)) {
            foreach ($this->formvars as $key => $value) {
                $val = new cHTMLHiddenField($key, $value);
                $hidden .= $val->render() . "\n";
            }
        }

        if (!array_key_exists("action", $this->formvars)) {
            $val = new cHTMLHiddenField("", "");
            $hidden .= $val->render() . "\n";
        }

        $tpl->set("s", "HIDDEN_VALUES", $hidden);

        $tpl->set('s', 'ID', $this->tableid);

        $header = "";
        if ($this->header != "") {
            $tablerow = new cHTMLTableRow();
            $tablehead = new cHTMLTableHead();
            $tablehead->setAttribute("colspan", "2");
            $tablehead->setAttribute("valign", "top");
            $tablehead->setContent($this->header);
            $tablerow->setContent($tablehead);
            $header = $tablerow->render();
        }

        $tpl->set('s', 'HEADER', $header);

        if (is_array($this->items)) {
            foreach ($this->items as $key => $value) {
                if ($this->itemType[$key] == 'subheader') {
                    $tablerow = new cHTMLTableRow();
                    $tabledata = new cHTMLTableData();
                    $tabledata->setAttribute("colspan", "2");
                    $tabledata->setAttribute("valign", "top");
                    $tabledata->setContent($this->captions[$key]);
                    $tablerow->setContent($tablehead);

                    $tpl->set('d', 'SUBHEADER', $tablerow->render());
                } else {
                    $tpl->set('d', 'SUBHEADER', '');
                    $tpl->set('d', 'CATNAME', $this->captions[$key]);
                    $tpl->set('d', 'CATFIELD', $value);
                    $tpl->set('d', 'ROWNAME', $this->rownames[$key]);

                    $tpl->next();
                }
            }
        }

        if ($this->cancelLink != "") {
            $image = new cHTMLImage(cRegistry::getBackendUrl() . 'images/but_cancel.gif');
            $link = new cHTMLLink($this->cancelLink);
            $link->setContent($image);

            $tpl->set('s', 'CANCELLINK', $link->render());
        } else {
            $tpl->set('s', 'CANCELLINK', '');
        }

        $custombuttons = "";

        foreach ($this->custom as $key => $value) {
            if ($value["accesskey"] != "") {
                $accesskey = $value["accesskey"];
            } else {
                $accesskey = "";
            }

            $onclick = "";
            if ($value["action"] !== false) {

                if ($value["confirmtitle"] != "") {
                    $action = 'document.forms["' . $this->formname . '"].elements["action"].value = "' . $value['action'] . '";';
                    $action .= 'document.forms["' . $this->formname . '"].submit()';

                    $onclick = 'Con.showConfirmation("' . $value['confirmdescription'] . '", function() { ' . $action . ' });return false;';
                } else {
                    $onclick = 'document.forms["' . $this->formname . '"].elements["action"].value = "' . $value['action'] . '";';
                }
            }

            if ($value["event"] != "") {
                $onclick .= $value["event"];
            }

            $button = new cHTMLFormElement("submit", "", "", "", "", "image_button");
            $button->setAttribute("type", "image");
            $button->setAttribute("src", $value["image"]);
            $button->setAlt($value['description']);
            $button->setAttribute("accesskey", $accesskey);
            $button->setEvent("onclick", $onclick);
            $custombuttons .= $button->render();
        }

        $tpl->set('s', 'EXTRABUTTONS', $custombuttons);

        $tpl->set('s', 'ROWNAME', $this->id);

        $rendered = $tpl->generate(cRegistry::getBackendPath() . $cfg['path']['templates'] . $cfg['templates']['generic_table_form'], true);

        if ($return == true) {
            return $rendered;
        } else {
            echo $rendered;
        }
    }
}
