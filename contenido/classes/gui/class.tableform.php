<?php

/**
 * This file contains the table form GUI class.
 *
 * @package Core
 * @subpackage GUI
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
     * accept charset of form tag
     *
     * @var string
     */
    private $_acceptCharset = '';

    /**
     *
     * @var array
     */
    public $items = [];

    /**
     *
     * @var array
     */
    public $captions = [];

    /**
     *
     * @var int
     */
    public $id = 0;

    /**
     *
     * @var array
     */
    public $rownames = [];

    /**
     *
     * @var  array
     */
    public $itemType = [];

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
    public $formvars = [];

    /**
     *
     * @var string
     */
    public $tableid = '';

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
    public $custom = [];

    /**
     * Constructor to create an instance of this class.
     *
     * Creates a new cGuiTableForm with given name, action & method of form.
     *
     * @param string $name
     *         of form
     * @param string $action [optional]
     *         of form defaults to 'main.php'
     * @param string $method [optional]
     *         of form defaults to 'post'
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
     * @param string $value
     */
    public function setVar($name, $value) {
        $this->formvars[$name] = $value;
    }

    /**
     * Adds a new caption, item and row name.
     *
     * @param string $caption
     * @param array|object|string $item
     * @param string $rowname [optional]
     */
    public function add($caption, $item, $rowname = '') {
        // handle item as array of items
        if (is_array($item)) {
            $temp = '';
            foreach ($item as $value) {
                if (is_object($value) && method_exists($value, 'render')) {
                    $temp .= $value->render();
                } else {
                    $temp .= $value;
                }
            }
            $item = $temp;
        }

        // handle item as object
        if (is_object($item) && method_exists($item, 'render')) {
            $item = $item->render();
        }

        // increase ID
        $this->id++;

        // set defaults
        if ($caption == '') {
            $caption = '&nbsp;';
        }
        if ($item == '') {
            $item = '&nbsp;';
        }
        if ($rowname == '') {
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
     * Sets the accept-charset attribute of form tag.
     *
     * @param string $charset
     */
    public function setAcceptCharset($charset) {
        $this->_acceptCharset = $charset;
    }

    /**
     *
     * @param string $id
     * @param string $event
     */
    public function setActionEvent($id, $event) {
        $this->custom[$id]['event'] = $event;
    }

    /**
     *
     * @param string $id
     * @param string $image
     * @param string $description [optional]
     * @param bool $accesskey [optional]
     * @param bool $action [optional]
     * @param bool $disabled [optional]
     */
    public function setActionButton($id, $image, $description = '', $accesskey = false, $action = false, $disabled = false) {
        $this->custom[$id]['image'] = $image;
        $this->custom[$id]['type'] = 'actionsetter';
        $this->custom[$id]['action'] = $action;
        $this->custom[$id]['description'] = $description;
        $this->custom[$id]['accesskey'] = $accesskey;
        $this->custom[$id]['event'] = '';
        $this->custom[$id]['disabled'] = $disabled;
    }

    /**
     *
     * @param string $id
     * @param string $title
     * @param string $description
     */
    public function setConfirm($id, $title, $description) {
        $this->custom[$id]['confirmtitle'] = $title;
        $this->custom[$id]['confirmdescription'] = $description;
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
     * @param string $id
     */
    public function unsetActionButton($id) {
        unset($this->custom[$id]);
    }

    /**
     * Renders this cGuiTableForm and either returs ist markup or echoes
     * it immediately.
     *
     * @param bool $return [optional]
     *                     if true then return markup, else echo immediately
     *
     * @return string|void
     * @throws cInvalidArgumentException
     */
    public function render($return = true) {
        $sess = cRegistry::getSession();
        $cfg = cRegistry::getConfig();
        $tpl = new cTemplate();

        $tpl->set('s', 'JSEXTRA', $this->renderJsExtraAttribute());

        $tpl->set('s', 'FORMNAME', $this->formname);
        $tpl->set('s', 'METHOD', $this->formmethod);
        $tpl->set('s', 'ACTION', $this->formaction);

        $this->formvars[$sess->name] = $sess->id;

        $tpl->set('s', 'HIDDEN_VALUES', $this->renderHiddenValues());

        $tpl->set('s', 'ID', $this->tableid);

        $tablehead = $this->renderHeader();
        $tpl->set('s', 'HEADER', $this->renderHeader());

        foreach ($this->items as $key => $value) {
            if (!empty($this->itemType[$key]) && $this->itemType[$key] == 'subheader') {
                $tablerow = new cHTMLTableRow();
                $tabledata = new cHTMLTableData();
                $tabledata->setAttribute('colspan', '2');
                $tabledata->setAttribute('valign', 'top');
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

        $tpl->set('s', 'CANCELLINK', $this->renderCancelLink());

        $tpl->set('s', 'EXTRABUTTONS', $this->renderCustomButtons());

        $tpl->set('s', 'ROWNAME', $this->id);

        $rendered = $tpl->generate(cRegistry::getBackendPath() . $cfg['path']['templates'] . $cfg['templates']['generic_table_form'], true);

        if ($return == true) {
            return $rendered;
        } else {
            echo $rendered;
        }
    }

    /**
     * @return string
     */
    protected function renderJsExtraAttribute() {
        $jsAttribute = '';

        if ($this->submitjs != '') {
            if (cString::getStringLength($this->_acceptCharset) > 0) {
                $jsAttribute = 'onsubmit="' . $this->submitjs
                    . '" accept-charset="' . $this->_acceptCharset . '"';
            } else {
                $jsAttribute = 'onsubmit="' . $this->submitjs . '"';
            }
        } else {
            if (cString::getStringLength($this->_acceptCharset) > 0) {
                $jsAttribute = 'accept-charset="' . $this->_acceptCharset . '"';
            }
        }

        return $jsAttribute;
    }

    /**
     * @return string
     */
    protected function renderHiddenValues() {
        $hidden = '';

        if (is_array($this->formvars)) {
            foreach ($this->formvars as $key => $value) {
                $val = new cHTMLHiddenField($key, $value);
                $hidden .= $val->render() . "\n";
            }
        }

        if (!array_key_exists('action', $this->formvars)) {
            $val = new cHTMLHiddenField('', '');
            $hidden .= $val->render() . "\n";
        }

        return $hidden;
    }

    /**
     * @return string
     */
    protected function renderHeader() {
        $header = '';

        if ($this->header != '') {
            $tablerow = new cHTMLTableRow();
            $tablehead = new cHTMLTableHead();
            $tablehead->setAttribute('colspan', '2');
            $tablehead->setAttribute('valign', 'top');
            $tablehead->setContent($this->header);
            $tablerow->setContent($tablehead);
            $header = $tablerow->render();
        }

        return $header;
    }

    /**
     * @return string
     */
    protected function renderCancelLink() {
        $cancelLink = '';

        if ($this->cancelLink != '') {
            $image = new cHTMLImage(cRegistry::getBackendUrl() . 'images/but_cancel.gif');
            $link = new cHTMLLink($this->cancelLink);
            $link->setContent($image);
            $cancelLink = $link->render();
        }

        return $cancelLink;
    }

    /**
     * @return string
     */
    protected function renderCustomButtons() {
        $custombuttons = '';

        foreach ($this->custom as $key => $value) {
            if ($value['accesskey'] != '') {
                $accesskey = $value['accesskey'];
            } else {
                $accesskey = '';
            }

            $onclick = '';
            if ($value['disabled'] === false) {
                if ($value['action'] !== false) {
                    if ($value['confirmtitle'] != '') {
                        $action = 'document.forms["' . $this->formname . '"].elements["action"].value = "' . $value['action'] . '";'
                            . 'document.forms["' . $this->formname . '"].submit()';
                        $onclick = 'Con.showConfirmation("' . $value['confirmdescription'] . '", function() { ' . $action . ' });return false;';
                    } else {
                        $onclick = 'document.forms["' . $this->formname . '"].elements["action"].value = "' . $value['action'] . '";';
                    }
                }

                if ($value['event'] != '') {
                    $onclick .= $value['event'];
                }
            }

            $button = new cHTMLFormElement('submit', '', '', '', '', 'image_button');
            $button->setAttribute('type', 'image');
            $button->setAttribute('src', $value['image']);
            $button->setAlt($value['description']);
            $button->setAttribute('accesskey', $accesskey);
            $button->setEvent('onclick', $onclick);
            if ($value['disabled']) {
                $button->updateAttribute('disabled', 'disabled');
            }
            $custombuttons .= $button->render();
        }

        return $custombuttons;
    }

}
