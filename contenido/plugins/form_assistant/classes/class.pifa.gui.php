<?php

/**
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Creates a page to be displayed in the left bottom frame.
 *
 * @author marcus.gnass
 */
class PifaLeftBottomPage extends cGuiPage {

    /**
     *
     * @throws PifaIllegalStateException
     */
    public function __construct() {

        /**
         *
         * @param string $action to be performed
         */
        global $action;

        /**
         *
         * @param int $idform id of form to be edited
         */
        global $idform;

        parent::__construct('left_bottom', Pifa::getName());

        $this->addScript('../plugins/form_assistant/scripts/left_bottom.js');

        $this->set('s', 'menu', $this->_getMenu());

        // add translations to template
        $this->set('s', 'I18N', json_encode(array(
            'confirm_delete_form' => Pifa::i18n('CONFIRM_DELETE_FORM')
        )));
    }

    /**
     * Get menu with all forms of current client in current language.
     */
    private function _getMenu() {
        global $area;

        $cfg = cRegistry::getConfig();
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();

        // get all forms of current client in current language
        $forms = PifaFormCollection::getByClientAndLang($client, $lang);
        if (false === $forms) {
            return '<!-- no forms for current client/language -->';
        }

        // $formIcon = Pifa::getUrl() . 'images/icon_form.png';

        // create menu
        $menu = new cGuiMenu();
        while (false !== $form = $forms->next()) {

            $idform = $form->get('idform');
            $formName = $form->get('name');

            $menu->setTitle($idform, $formName);

            // create link to show/edit the form
            $link = new cHTMLLink();
            $link->setMultiLink($area, '', $area, PifaRightBottomFormPage::SHOW_FORM);
            $link->setCustom('idform', $idform);
            $menu->setLink($idform, $link);

            // create link to delete the form
            $link = new cHTMLLink();
            $link->setMultiLink($area, PifaRightBottomFormPage::DELETE_FORM, $area, PifaRightBottomFormPage::DELETE_FORM);
            $link->setCustom('idform', $idform);
            $link->setClass('pifa-icon-delete-form');
            $deleteForm = Pifa::i18n('DELETE_FORM');
            $link->setAlt($deleteForm);
            $link->setContent('<img src="' . $cfg['path']['images'] . 'delete.gif" title="' . $deleteForm . '" alt="' . $deleteForm . '">');
            // $menu->setLink($idform, $link);
            $menu->setActions($idform, 'delete', $link);
        }

        return $menu->render(false);
    }
}

/**
 * Creates a page to be displayed in the right bottom frame.
 *
 * @author marcus.gnass
 */
class PifaRightBottomFormPage extends cGuiPage {

    /**
     *
     * @var string
     */
    const SHOW_FORM = 'pifa_show_form';

    /**
     *
     * @var string
     */
    const STORE_FORM = 'pifa_store_form';

    /**
     *
     * @var string
     */
    const DELETE_FORM = 'pifa_delete_form';

    /**
     * model for a single PIFA form
     *
     * @var PifaForm
     */
    private $_pifaForm = NULL;

    /**
     * Creates and aggregates a model for a collection of PIFA forms
     * and another for a single PIFA form.
     *
     * If an ID for an item is given this is loaded from database
     * and its values are stored in the appropriate model.
     *
     * @throws Exception
     */
    public function __construct() {

        /**
         *
         * @param string $action to be performed
         */
        global $action;

        /**
         *
         * @param int $idform id of form to be edited
         */
        global $idform;

        /**
         *
         * @param int $idfield id of field to be edited
         */
        global $idfield;

        parent::__construct('right_bottom', Pifa::getName());

        $this->addStyle('../plugins/' . Pifa::getName() . '/styles/smoothness/jquery-ui-1.8.20.custom.css');
        $this->addStyle('../plugins/' . Pifa::getName() . '/styles/right_bottom.css');
        $this->addScript('../plugins/' . Pifa::getName() . '/scripts/right_bottom.js');

        // create models
        $this->_pifaForm = new PifaForm();

        // load models
        $idform = cSecurity::toInteger($idform);
        if (0 < $idform) {
            if (false === $this->_pifaForm->loadByPrimaryKey($idform)) {
                $msg = Pifa::i18n('FORM_LOAD_ERROR');
                throw new Exception($msg);
            }
        }

        // dispatch action
        try {
            $this->_dispatch($action);
        } catch (InvalidArgumentException $e) {
            $cGuiNotification = new cGuiNotification();
            $notification = $cGuiNotification->returnNotification(cGuiNotification::LEVEL_ERROR, $e->getMessage());
            $this->set('s', 'notification', $notification);
        }

        // add translations to template
        $this->set('s', 'I18N', json_encode(array(
            'cancel' => Pifa::i18n('CANCEL'),
            'save' => Pifa::i18n('SAVE')
        )));
    }

    /**
     * Dispatches the given action.
     *
     * @param string $action to be executed
     * @param string $notification
     * @throws InvalidArgumentException if the given action is unknown
     */
    protected function _dispatch($action, $notification = '') {
        global $area;

        // check for permission
        if (!cRegistry::getPerm()->have_perm_area_action($area, $action)) {
            throw new PifaIllegalStateException('no permissions');
        }

        if (NULL === $action) {
            $cGuiNotification = new cGuiNotification();
            $notification = $cGuiNotification->returnNotification(cGuiNotification::LEVEL_INFO, Pifa::i18n('please select a form'));
            $this->set('s', 'notification', $notification);
            $this->set('s', 'content', '');
            return;
        }

        // dispatch actions
        switch ($action) {
            case PifaRightBottomFormPage::SHOW_FORM:
                $this->set('s', 'notification', $notification);
                try {
                    $this->set('s', 'content', $this->_showForm());
                } catch (SmartyCompilerException $e) {
                    $this->set('s', 'content', Pifa::notifyException($e));
                }
                break;

            case PifaRightBottomFormPage::STORE_FORM:
                $notification = '';
                try {
                    $this->_storeForm();
                    $this->setReload();
                    // reload right_top after saving of form
                    $idform = $this->_pifaForm->get('idform');
                    $url = "main.php?area=form&frame=3&idform=$idform&action=" . PifaRightBottomFormPage::SHOW_FORM;
                    $url = cRegistry::getSession()->url($url);
                    $this->addScript("<script type=\"text/javascript\">
                        parent.parent.frames['right'].frames['right_top'].location.href = '$url';
                        </script>");
                } catch (Exception $e) {
                    $notification = Pifa::notifyException($e);
                }
                $this->_dispatch(PifaRightBottomFormPage::SHOW_FORM, $notification);
                break;

            case PifaRightBottomFormPage::DELETE_FORM:
                $notification = '';
                try {
                    $this->_deleteForm();
                    $cGuiNotification = new cGuiNotification();
                    $this->set('s', 'notification', $cGuiNotification->returnNotification(cGuiNotification::LEVEL_INFO, Pifa::i18n('FORM_DELETED')));
                    $this->set('s', 'content', '');
                    $this->setReload();
                } catch (Exception $e) {
                    $notification = Pifa::notifyException($e);
                }
                break;

            default:
                $msg = Pifa::i18n('UNKNOWN_ACTION');
                throw new InvalidArgumentException($msg);
        }
    }

    /**
     * Build and return form for PIFA forms.
     *
     * @return string
     */
    private function _showForm() {
        global $area;

        $cfg = cRegistry::getConfig();

        // get form action
        $formAction = new cHTMLLink();
        $formAction->setCLink($area, 4, PifaRightBottomFormPage::STORE_FORM);
        $formAction = $formAction->getHref();

        // get current or default values for form
        if (!is_null($this->_pifaForm) && $this->_pifaForm->isLoaded()) {
            $idform = $this->_pifaForm->get('idform');
            $nameValue = $this->_pifaForm->get('name');
            $dataTableValue = $this->_pifaForm->get('data_table');
            $methodValue = $this->_pifaForm->get('method');
            $withTimestampValue = (bool) $this->_pifaForm->get('with_timestamp');
        } else {
            $idform = NULL;
            $nameValue = '';
            $dataTableValue = '';
            $methodValue = '';
            $withTimestampValue = true;
        }

        $tpl = Contenido_SmartyWrapper::getInstance(true);
        $tpl->assign('formAction', $formAction);
        $tpl->assign('idform', $idform);
        $tpl->assign('nameValue', $nameValue);
        $tpl->assign('dataTableValue', $dataTableValue);
        $tpl->assign('methodValue', strtoupper($methodValue));
        $tpl->assign('withTimestampValue', $withTimestampValue);
        $tpl->assign('hasWithTimestamp', Pifa::TIMESTAMP_BYFORM === Pifa::getTimestampSetting());
        $tpl->assign('trans', array(
            'legend' => Pifa::i18n('form'),
            'name' => Pifa::i18n('form name'),
            'dataTable' => Pifa::i18n('data table'),
            'method' => Pifa::i18n('method'),
            'withTimestamp' => Pifa::i18n('with timestamp'),
            'pleaseChoose' => Pifa::i18n('please choose'),
            'saveForm' => Pifa::i18n('save form')
        ));

        $out = $tpl->fetch($cfg['templates']['pifa_right_bottom_form']);

        return $out;
    }

    /**
     * Handles a POST request of the first form, showing a forms details.
     */
    private function _storeForm() {

        // determine if item is loaded
        $isLoaded = $this->_pifaForm->isLoaded();

        // read item data from form
        $name = $_POST['name'];
        $name = cSecurity::unescapeDB($name);
        $name = cSecurity::toString($name);
        $name = trim($name);

        $dataTable = $_POST['data_table'];
        $dataTable = trim($dataTable);
        $dataTable = strtolower($dataTable);
        $dataTable = preg_replace('/[^a-z0-9_]/', '_', $dataTable);

        $method = $_POST['method'];
        $method = trim($method);
        $method = strtoupper($method);

        switch (Pifa::getTimestampSetting()) {
            case Pifa::TIMESTAMP_NEVER:
                $withTimestamp = false;
                break;
            case Pifa::TIMESTAMP_BYFORM:
                $withTimestamp = 'on' === $_POST['with_timestamp'];
                break;
            case Pifa::TIMESTAMP_ALWAYS:
                $withTimestamp = true;
                break;
        }

        // validate item data
        if (0 === strlen($name)) {
            $msg = Pifa::i18n('EMPTY_FORMNAME_ERROR');
            throw new Exception($msg);
        }
        if (0 === strlen($dataTable)) {
            $msg = Pifa::i18n('EMPTY_DATETABLENAME_ERROR');
            throw new Exception($msg);
        }
        if (!in_array($method, array(
            'GET',
            'POST'
        ))) {
            $msg = Pifa::i18n('FORM_METHOD_ERROR');
            throw new Exception($msg);
        }

        if ($isLoaded) {
            // remember old table values
            $oldDataTable = $this->_pifaForm->get('data_table');
            $oldWithTimestamp = (bool) $this->_pifaForm->get('with_timestamp');
        } else {
            // create new item for given client & language
            $pifaFormCollection = new PifaFormCollection();
            $this->_pifaForm = $pifaFormCollection->createNewItem(array(
                'idclient' => cRegistry::getClientId(),
                'idlang' => cRegistry::getLanguageId()
            ));
        }

        // set item data
        // Never, really never, call Item->set() if the value doesn't differ
        // from the previous one. Otherwise the genericDb thinks that the item
        // is modified and tries to store it resulting in a return value of
        // false!
        if ($name !== $this->_pifaForm->get('name')) {
            $this->_pifaForm->set('name', $name);
        }
        if ($dataTable !== $this->_pifaForm->get('data_table')) {
            $this->_pifaForm->set('data_table', $dataTable);
        }
        if (0 !== strcasecmp($method, $this->_pifaForm->get('method'))) {
            $this->_pifaForm->set('method', $method);
        }
        if ($withTimestamp !== (bool) $this->_pifaForm->get('with_timestamp')) {
            $this->_pifaForm->set('with_timestamp', $withTimestamp);
        }

        // store item
        if (false === $this->_pifaForm->store()) {
            $msg = Pifa::i18n('FORM_STORE_ERROR');
            $msg = sprintf($msg, $this->_pifaForm->getLastError());
            throw new Exception($msg);
        }

        if ($isLoaded) {
            // optionally alter data table
            // HINT: passing the old values is correct!
            // The new values have already been stored inside the pifaForm
            // object!
            $this->_pifaForm->alterTable($oldDataTable, $oldWithTimestamp);
        } else {
            // create table
            $this->_pifaForm->createTable($withTimestamp);
        }
    }

    /**
     */
    private function _deleteForm() {
        $this->_pifaForm->delete();
        $this->_pifaForm = NULL;
    }
}

/**
 * Creates a page to be displayed in the right bottom frame.
 *
 * @author marcus.gnass
 */
class PifaRightBottomFormFieldsPage extends cGuiPage {

    /**
     *
     * @var string
     */
    const SHOW_FIELDS = 'pifa_show_fields';

    /**
     * model for a single PIFA form
     *
     * @var PifaForm
     */
    private $_pifaForm = NULL;

    /**
     * Creates and aggregates a model for a collection of PIFA forms
     * and another for a single PIFA form.
     *
     * If an ID for an item is given this is loaded from database
     * and its values are stored in the appropriate model.
     *
     * @throws Exception
     */
    public function __construct() {

        /**
         *
         * @param string $action to be performed
         */
        global $action;

        /**
         *
         * @param int $idform id of form to be edited
         */
        global $idform;

        /**
         *
         * @param int $idfield id of field to be edited
         */
        global $idfield;

        parent::__construct('right_bottom', Pifa::getName());

        $this->addStyle('../plugins/' . Pifa::getName() . '/styles/smoothness/jquery-ui-1.8.20.custom.css');
        $this->addStyle('../plugins/' . Pifa::getName() . '/styles/right_bottom.css');
        $this->addScript('../plugins/' . Pifa::getName() . '/scripts/right_bottom.js');

        // create models
        $this->_pifaForm = new PifaForm();

        // load models
        $idform = cSecurity::toInteger($idform);
        if (0 < $idform) {
            $ret = $this->_pifaForm->loadByPrimaryKey($idform);
            if (false === $ret) {
                $msg = Pifa::i18n('FORM_LOAD_ERROR');
                throw new Exception($msg);
            }
        }

        // dispatch action
        try {
            $this->_dispatch($action);
        } catch (InvalidArgumentException $e) {
            $cGuiNotification = new cGuiNotification();
            $notification = $cGuiNotification->returnNotification(cGuiNotification::LEVEL_ERROR, $e->getMessage());
            $this->set('s', 'notification', $notification);
        }

        // add translations to template
        $this->set('s', 'I18N', json_encode(array(
            'cancel' => Pifa::i18n('CANCEL'),
            'save' => Pifa::i18n('SAVE'),
            'confirm_delete_field' => Pifa::i18n('CONFIRM_DELETE_FIELD')
        )));
    }

    /**
     * Dispatches the given action.
     *
     * @param string $action to be executed
     * @param string $notification
     * @throws InvalidArgumentException if the given action is unknown
     */
    protected function _dispatch($action, $notification = '') {
        global $area;

        // check for permission
        if (!cRegistry::getPerm()->have_perm_area_action($area, $action)) {
                $msg = Pifa::i18n('NO_PERMISSIONS');
                throw new PifaIllegalStateException($msg);
        }

        if (NULL === $action) {
            $this->set('s', 'notification', Pifa::i18n('please select a form'));
            $this->set('s', 'content', '');
            return;
        }

        // dispatch actions
        switch ($action) {

            case PifaRightBottomFormFieldsPage::SHOW_FIELDS:
                $this->set('s', 'notification', $notification);
                try {
                    $this->set('s', 'content', $this->_showFields());
                } catch (SmartyCompilerException $e) {
                    $this->set('s', 'content', Pifa::notifyException($e));
                }
                break;

            default:
                $msg = Pifa::i18n('UNKNOWN_ACTION');
                throw new InvalidArgumentException($msg);

        }
    }

    /**
     */
    private function _showFields() {
        global $area;

        $cfg = cRegistry::getConfig();

        if ($this->_pifaForm->isLoaded()) {

            $idform = $this->_pifaForm->get('idform');
            $idfield = $_GET['idfield'];

            $fieldTypes = $this->_getFieldTypes();
            $fields = $this->_pifaForm->getFields();

            $editField = new cHTMLLink();
            $editField->setCLink('form_ajax', 4, PifaAjaxHandler::GET_FIELD_FORM);
            $editField->setCustom('idform', $idform);
            $editField = $editField->getHref();

            $deleteField = new cHTMLLink();
            $deleteField->setCLink('form_ajax', 4, PifaAjaxHandler::DELETE_FIELD);
            $deleteField->setCustom('idform', $idform);
            $deleteField = $deleteField->getHref();
        } else {

            $idform = NULL;
            $idfield = NULL;

            $fieldTypes = NULL;
            $fields = NULL;

            $editField = NULL;
            $deleteField = NULL;
        }

        // get and fill template
        $tpl = Contenido_SmartyWrapper::getInstance(true);

        // translations
        $tpl->assign('trans', array(
            'legend' => Pifa::i18n('fields'),
            'pleaseSaveFirst' => Pifa::i18n('please save first'),
            'dialogTitle' => Pifa::i18n('edit field'),
            'edit' => Pifa::i18n('EDIT'),
            'delete' => Pifa::i18n('DELETE'),
            'obligatory' => Pifa::i18n('OBLIGATORY')
        ));

        // params
        $tpl->assign('ajaxParams', implode('&', array(
            'area=form_ajax',
            'frame=4',
            'contenido=' . cRegistry::getBackendSessionId()
        )));
        $tpl->assign('dragParams', implode('&', array(
            'area=form_ajax',
            'frame=4',
            'contenido=' . cRegistry::getBackendSessionId(),
            'action=' . PifaAjaxHandler::GET_FIELD_FORM,
            'idform=' . $idform
        )));
        $tpl->assign('sortParams', implode('&', array(
            'area=form_ajax',
            'frame=4',
            'contenido=' . cRegistry::getBackendSessionId(),
            'action=' . PifaAjaxHandler::REORDER_FIELDS,
            'idform=' . $this->_pifaForm->get('idform')
        )));

        // data
        $tpl->assign('idform', $idform);
        $tpl->assign('idfield', $idfield);

        $tpl->assign('fields', $fields);
        // $tpl->assign('fieldTypes', PifaField::getFieldTypeIds());
        $tpl->assign('fieldTypes', PifaField::getFieldTypeNames());

        // for partial
        $tpl->assign('editField', $editField);
        $tpl->assign('deleteField', $deleteField);
        // define path to partial template for displaying a single field row
        $tpl->assign('partialFieldRow', $cfg['templates']['pifa_ajax_field_row']);

        $out = $tpl->fetch($cfg['templates']['pifa_right_bottom_fields']);

        return $out;
    }

    /**
     * Returns a multidimensional array with available field types.
     * This array contains values for the label and icon of the field type.
     *
     * @return array
     */
    private function _getFieldTypes() {
        $fieldTypes = array();
        foreach (PifaField::getFieldTypeIds() as $fieldTypeId) {
            $fieldTypes[$fieldTypeId] = array();
            $fieldTypes[$fieldTypeId]['id'] = $fieldTypeId;
            $fieldTypes[$fieldTypeId]['label'] = PifaField::getFieldTypeName($fieldTypeId);
            // icon is not used atm
            $fieldTypes[$fieldTypeId]['icon'] = PifaField::getFieldTypeIcon($fieldTypeId);
        }

        return $fieldTypes;
    }
}

/**
 * Creates a page to be displayed in the right bottom frame.
 *
 * @author marcus.gnass
 */
class PifaRightBottomFormDataPage extends cGuiPage {

    /**
     *
     * @var string
     */
    const SHOW_DATA = 'pifa_show_data';

    /**
     * model for a single PIFA form
     *
     * @var PifaForm
     */
    private $_pifaForm = NULL;

    /**
     * Creates and aggregates a model for a collection of PIFA forms
     * and another for a single PIFA form.
     *
     * If an ID for an item is given this is loaded from database
     * and its values are stored in the appropriate model.
     *
     * @throws Exception
     */
    public function __construct() {

        /**
         *
         * @param string $action to be performed
         */
        global $action;

        /**
         *
         * @param int $idform id of form to be edited
         */
        global $idform;

        /**
         *
         * @param int $idfield id of field to be edited
         */
        global $idfield;

        parent::__construct('right_bottom', Pifa::getName());

        $this->addStyle('../plugins/' . Pifa::getName() . '/styles/smoothness/jquery-ui-1.8.20.custom.css');
        $this->addStyle('../plugins/' . Pifa::getName() . '/styles/right_bottom.css');
        $this->addScript('../plugins/' . Pifa::getName() . '/scripts/right_bottom.js');

        // create models
        $this->_pifaForm = new PifaForm();

        // load models
        $idform = cSecurity::toInteger($idform);
        if (0 < $idform) {
            if (false === $this->_pifaForm->loadByPrimaryKey($idform)) {
                $msg = Pifa::i18n('FORM_LOAD_ERROR');
                throw new Exception($msg);
            }
        }

        // dispatch action
        try {
            $this->_dispatch($action);
        } catch (InvalidArgumentException $e) {
            $cGuiNotification = new cGuiNotification();
            $notification = $cGuiNotification->returnNotification(cGuiNotification::LEVEL_ERROR, $e->getMessage());
            $this->set('s', 'notification', $notification);
        }

        // add translations to template
        $this->set('s', 'I18N', json_encode(array(
            'cancel' => Pifa::i18n('CANCEL'),
            'save' => Pifa::i18n('SAVE')
        )));
    }

    /**
     * Dispatches the given action.
     *
     * @param string $action to be executed
     * @param string $notification
     * @throws InvalidArgumentException if the given action is unknown
     */
    protected function _dispatch($action, $notification = '') {
        global $area;

        // check for permission
        if (!cRegistry::getPerm()->have_perm_area_action($area, $action)) {
            $msg = Pifa::i18n('NO_PERMISSIONS');
            throw new PifaIllegalStateException($msg);
        }

        if (NULL === $action) {
            $this->set('s', 'notification', Pifa::i18n('please select a form'));
            $this->set('s', 'content', '');
            return;
        }

        // dispatch actions
        switch ($action) {

            case PifaRightBottomFormDataPage::SHOW_DATA:
                $this->set('s', 'notification', $notification);
                try {
                    $this->set('s', 'content', $this->_showData());
                } catch (SmartyCompilerException $e) {
                    $this->set('s', 'content', Pifa::notifyException($e));
                }
                break;

            default:
                $msg = Pifa::i18n('UNKNOWN_ACTION');
                throw new InvalidArgumentException($msg);

        }
    }

    /**
     */
    private function _showData() {
        $cfg = cRegistry::getConfig();

        $tpl = Contenido_SmartyWrapper::getInstance(true);

        // translations
        $tpl->assign('trans', array(
            'legend' => Pifa::i18n('data'),
            'pleaseSaveFirst' => Pifa::i18n('please save first'),
            'export' => Pifa::i18n('download data as CSV')
        ));

        $tpl->assign('exportUrl', 'main.php?' . implode('&', array(
            'area=form_ajax',
            'frame=4',
            'contenido=' . cRegistry::getBackendSessionId(),
            'action=' . PifaAjaxHandler::EXPORT_DATA,
            'idform=' . $this->_pifaForm->get('idform')
        )));
        $tpl->assign('form', $this->_pifaForm);
        $tpl->assign('getFileUrl', 'main.php?' . implode('&', array(
            'area=form_ajax',
            'frame=4',
            'contenido=' . cRegistry::getBackendSessionId(),
            'action=' . PifaAjaxHandler::GET_FILE
        )));

        try {
            $tpl->assign('fields', $this->_pifaForm->getFields());
        } catch (Exception $e) {
            $tpl->assign('fields', Pifa::notifyException($e));
        }

        $tpl->assign('withTimestamp', (bool) $this->_pifaForm->get('with_timestamp'));

        try {
            $tpl->assign('data', $this->_pifaForm->getData());
        } catch (Exception $e) {
            $tpl->assign('data', Pifa::notifyException($e));
        }

        $out = $tpl->fetch($cfg['templates']['pifa_right_bottom_data']);

        return $out;
    }
}
