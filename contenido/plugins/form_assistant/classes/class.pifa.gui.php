<?php

/**
 *
 * @package Plugin
 * @subpackage PIFA Form Asistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') or die('Illegal call');

/**
 * Creates a page to be displayed in the left bottom frame.
 *
 * @author marcus.gnass
 */
class PifaLeftBottomPage extends cGuiPage {

    /**
     *
     * @throws IllegalStateException
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

        // dispatch action
        try {
            $this->_dispatch($action, $idform);
        } catch (InvalidArgumentException $e) {
            $cGuiNotification = new cGuiNotification();
            $notification = $cGuiNotification->returnNotification(cGuiNotification::LEVEL_ERROR, $e->getMessage());
            $this->set('s', 'notification', $notification);
        }

        $this->set('s', 'menu', $this->_getMenu());

    }

    /**
     * Dispatches the given action.
     *
     * @param string $action to be executed
     * @param string $notification
     * @throws InvalidArgumentException if the given action is unknown
     */
    private function _dispatch($action, $idform, $notification = '') {

        global $area;

        // check for permission
        $perm = cRegistry::getPerm();
        if (!$perm->have_perm_area_action($area, $action)) {
            throw new IllegalStateException('no permissions');
        }

        if (NULL === $action) {
            $this->set('s', 'notification', '');
            return;
        }

        switch ($action) {

            case 'delete_form':

                $form = new PifaForm($idform);
                $form->delete();

                $cGuiNotification = new cGuiNotification();
                $this->set('s', 'notification', $cGuiNotification->returnNotification(cGuiNotification::LEVEL_INFO, Pifa::i18n('FORM_DELETED')));

                break;

            default:
                throw new InvalidArgumentException('unknown action ' . $action);

        }

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
            return '';
        }

        $formIcon = $cfg['path']['images'] . 'form.gif';

        // create menu
        $menu = new cGuiMenu();
        while (false !== $form = $forms->next()) {

            $idform = $form->get('idform');
            $formName = $form->get('name');

            $menu->setTitle($idform, $formName);
            $menu->setImage($idform, $formIcon);

            // add 'show form' link
            $link = new cHTMLLink();
            // main.php?area=form&frame=4&action=show_form&idform=6&contenido=75ijqvmgbgc2dt61gt8pfcmg01
            $link->setCLink($area, 4, 'show_form');
            $link->setTargetFrame('right_bottom');
            $link->setCustom('idform', $idform);
            $link->setAlt(Pifa::i18n('SHOW_FORM'));
            $link->setContent('name ' . $formName);
            $menu->setLink($idform, $link);

            // add 'delete' action
            $delete = new cHTMLLink();
            $delete->setCLink($area, 2, 'delete_form');
            $delete->setTargetFrame('left_bottom');
            $delete->setCustom('idform', $idform);
            $delete->setClass('pifa-icon-delete-form');
            $deleteForm = Pifa::i18n('DELETE_FORM');
            $delete->setAlt($deleteForm);
            $delete->setContent('<img src="' . $cfg['path']['images'] . 'delete.gif" title="' . $deleteForm . '" alt="' . $deleteForm . '">');
            $menu->setActions($idform, 'delete', $delete);

        }

        return $menu->render(false);

    }

}

/**
 * Creates a page to be displayed in the right bottom frame.
 *
 * @author marcus.gnass
 */
class PifaRightBottomPage extends cGuiPage {

    /**
     * model for collection of PIFA forms
     *
     * @var PifaFormCollection
     */
    private $_pifaFormCollection = NULL;

    /**
     * model for a single PIFA form
     *
     * @var PifaForm
     */
    private $_pifaForm = NULL;

    /**
     * model for collection of PIFA fields
     *
     * @var PifaFieldCollection
     */
    private $_pifaFieldCollection = NULL;

    /**
     * model for a single PIFA field
     *
     * @var PifaField
     */
    private $_pifaField = NULL;

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

        $this->addStyle('smoothness/jquery-ui-1.8.20.custom.css');
        $this->addStyle('../plugins/' . Pifa::getName() . '/styles/right_bottom.css');
        $this->addScript('../plugins/' . Pifa::getName() . '/scripts/right_bottom.js');

        // create models
        $this->_pifaFormCollection = new PifaFormCollection();
        $this->_pifaForm = new PifaForm();
        $this->_pifaFieldCollection = new PifaFieldCollection();
        $this->_pifaField = new PifaField();

        // load models
        $idform = cSecurity::toInteger($idform);
        if (0 < $idform) {
            $ret = $this->_pifaForm->loadByPrimaryKey($idform);
            if (false === $ret) {
                throw new Exception('could not load form');
            }
        }

        $idfield = cSecurity::toInteger($idfield);
        if (0 < $idfield) {
            $ret = $this->_pifaField->loadByPrimaryKey($idfield);
            if (false === $ret) {
                throw new Exception('could not load field');
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
        $perm = cRegistry::getPerm();
        if (!$perm->have_perm_area_action($area, $action)) {
            throw new IllegalStateException('no permissions');
        }

        if (NULL === $action) {
            $this->set('s', 'notification', Pifa::i18n('please select a form'));
            $this->set('s', 'form_tab', Pifa::i18n('form'));
            $this->set('s', 'fields_tab', Pifa::i18n('fields'));
            $this->set('s', 'data_tab', Pifa::i18n('data'));
            $this->set('s', 'form_form', '');
            $this->set('s', 'fields_form', '');
            $this->set('s', 'form_data', '');
            return;
        }

        // most of these actions can be deleted cause they are called via AJAX
        // now
        switch ($action) {
            case 'show_form':
                $this->set('s', 'notification', $notification);
                $this->set('s', 'form_tab', Pifa::i18n('form'));
                $this->set('s', 'fields_tab', Pifa::i18n('fields'));
                $this->set('s', 'data_tab', Pifa::i18n('data'));
                try {
                    $this->set('s', 'form_form', $this->_showForm());
                } catch (SmartyCompilerException $e) {
                    $this->set('s', 'form_form', Pifa::notifyException($e));
                }
                $this->_dispatch('show_fields');
                $this->_dispatch('show_data');
                break;

            case 'show_fields':
                try {
                    $this->set('s', 'fields_form', $this->_showFields());
                } catch (SmartyCompilerException $e) {
                    $this->set('s', 'fields_form', Pifa::notifyException($e));
                }
                break;

            case 'show_data':
                try {
                    $this->set('s', 'form_data', $this->_showData());
                } catch (SmartyCompilerException $e) {
                    $this->set('s', 'form_data', Pifa::notifyException($e));
                }
                break;

            case 'store_form':
                $notification = '';
                try {
                    $this->_storeForm();
                } catch (Exception $e) {
                    $notification = Pifa::notifyException($e);
                }
                $this->_dispatch('show_form', $notification);
                break;

            case 'add_field':
                try {
                    $this->_addField();
                } catch (Exception $e) {
                    $notification = Pifa::notifyException($e);
                }
                $this->_dispatch('show_form', $notification);
                break;

            case 'store_fields':
                try {
                    $this->_storeField();
                } catch (Exception $e) {
                    $notification = Pifa::notifyException($e);
                }
                $this->_dispatch('show_form', $notification);
                break;

            default:
                throw new InvalidArgumentException('unknown action ' . $action);

        }

    }

    /**
     */
    private function _showForm() {

        global $area;

        $cfg = cRegistry::getConfig();

        $action = new cHTMLLink();
        $action->setCLink($area, 4, 'store_form');

        $formAction = $action->getHref();
        $idform = NULL;
        $nameValue = '';
        $dataTableValue = '';
        $methodValue = '';

        if ($this->_pifaForm->isLoaded()) {
            $idform = $this->_pifaForm->get('idform');
            $nameValue = $this->_pifaForm->get('name');
            $dataTableValue = $this->_pifaForm->get('data_table');
            $methodValue = $this->_pifaForm->get('method');
        }
        $methodValue = strtoupper($methodValue);

        $tpl = Contenido_SmartyWrapper::getInstance(true);
        $tpl->assign('formAction', $formAction);
        $tpl->assign('idform', $idform);
        $tpl->assign('nameValue', $nameValue);
        $tpl->assign('dataTableValue', $dataTableValue);
        $tpl->assign('methodValue', $methodValue);

        $tpl->assign('trans', array(
            'legend' => Pifa::i18n('form'),
            'name' => Pifa::i18n('form name'),
            'dataTable' => Pifa::i18n('data table'),
            'method' => Pifa::i18n('method'),
            'pleaseChoose' => Pifa::i18n('please choose'),
            'saveForm' => Pifa::i18n('save form')
        ));

        $out = $tpl->fetch($cfg['templates']['pifa_right_bottom_form']);

        return $out;

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
            'legend' => Pifa::i18n('All form types'),
            'pleaseSaveFirst' => Pifa::i18n('please save first'),
            'dialogTitle' => Pifa::i18n('edit form'),
            'edit' => Pifa::i18n('edit'),
            'delete' => Pifa::i18n('delete')
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
        $tpl->assign('fieldTypes', PifaField::getFieldTypeIds());

        // for partial
        $tpl->assign('editField', $editField);
        $tpl->assign('deleteField', $deleteField);
        // define path to partial template for displaying a single field row
        $tpl->assign('partialFieldRow', $cfg['templates']['pifa_ajax_field_row']);

        $out = $tpl->fetch($cfg['templates']['pifa_right_bottom_fields']);

        return $out;

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

        try {
            $tpl->assign('data', $this->_pifaForm->getData());
        } catch (Exception $e) {
            $tpl->assign('data', Pifa::notifyException($e));
        }

        $out = $tpl->fetch($cfg['templates']['pifa_right_bottom_data']);

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
        $name = trim($name);

        $dataTable = $_POST['data_table'];
        $dataTable = trim($dataTable);
        $dataTable = strtolower($dataTable);
        $dataTable = preg_replace('/[^a-z0-9_]/', '_', $dataTable);

        $method = $_POST['method'];
        $method = trim($method);
        $method = strtoupper($method);

        // validate item data
        if (0 === strlen($name)) {
            throw new Exception('form name must not be empty');
        }
        if (0 === strlen($dataTable)) {
            throw new Exception('data table name must not be empty');
        }
        if (!in_array($method, array(
            'GET',
            'POST'
        ))) {
            throw new Exception('request method must be either GET or POST');
        }

        if ($isLoaded) {
            // remember old table name
            $oldDataTable = $this->_pifaForm->get('data_table');
        } else {
            // create new item for given client & language
            $this->_pifaForm = $this->_pifaFormCollection->createNewItem(array(
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
        if ($method !== $this->_pifaForm->get('method')) {
            $this->_pifaForm->set('method', $method);
        }

        // store item
        if (false === $this->_pifaForm->store()) {
            throw new Exception('could not store form: ' . $this->_pifaForm->getLastError());
        }

        if ($isLoaded) {
            // rename table if name has changed
            // HINT: passing the old data table name is correct!
            // The new table name has already been stored
            // inside the pifaForm object!
            $this->_pifaForm->renameTable($oldDataTable);
        } else {
            // create table
            $this->_pifaForm->createTable();
        }

    }

    /**
     * Handles a POST request of the second form, showing a select box to add
     * new form fields.
     * For new form fields the field_type and a label is stored.
     */
    private function _addField() {

        // determine if item is loaded
        if ($this->_pifaField->isLoaded()) {
            throw new Exception('field is already loaded');
        }

        // read item data from form
        $idform = $_POST['idform'];
        $idform = cSecurity::toInteger($idform);
        $fieldType = $_POST['field_type'];
        $fieldType = cSecurity::toInteger($fieldType);
        $columnName = $_POST['new_column_name'];
        $columnName = trim($columnName);
        $columnName = strtolower($columnName);

        // validate item data
        if (0 >= $idform) {
            throw new Exception('form id must not be empty');
        }
        if (0 >= $fieldType) {
            throw new Exception('field type must not be empty');
        }
        if (0 === strlen($columnName)) {
            throw new Exception('column name must not be empty');
        }

        // create new item
        $this->_pifaField = $this->_pifaFieldCollection->createNewItem();

        // set item data
        // Never, really never, call Item->set() if the value doesn't differ
        // from the previous one. Otherwise the genericDb thinks that the item
        // is modified and tries to store it resulting in a return value of
        // false!
        if ($idform !== $this->_pifaField->get('idform')) {
            $this->_pifaField->set('idform', $idform);
        }
        if ($fieldType !== $this->_pifaField->get('field_type')) {
            $this->_pifaField->set('field_type', $fieldType);
        }
        if ($columnName !== $this->_pifaField->get('column_name')) {
            $this->_pifaField->set('column_name', $columnName);
        }

        // store item
        if (false === $this->_pifaField->store()) {
            throw new Exception('could not store field: ' . $this->_pifaField->getLastError());
        }

        // add column for current field to table of current form
        $this->_pifaForm->addColumn($this->_pifaField);

    }

    /**
     */
    private function _deleteField() {

        $this->_pifaField->delete();

    }

    /**
     * Handles a POST request of the third form, storing a form fields details.
     *
     * @see http://dev.mysql.com/doc/refman/5.0/en/identifiers.html
     */
    private function _storeField() {

        if (!$this->_pifaField->isLoaded()) {
            throw new Exception('field is not loaded');
        }

        // remember old column name
        $oldColumnName = $this->_pifaField->get('column_name');

        /*
         * Read item data from form, validate item data and finally set item
         * data. Which data is editable depends upon the field type. So a
         * certain data will only be stored if its field is shown in the form.
         * Never, really never, call Item->set() if the value doesn't differ
         * from the previous one. Otherwise the genericDb thinks that the item
         * is modified and tries to store it resulting in a return value of
         * false!
         */

        // According to the MySQL documentation table and column names must
        // not be longer than 64 charcters.
        if ($this->_pifaField->showField('column_name')) {
            $columnName = cSecurity::toString($_POST['column_name']);
            $columnName = trim($columnName);
            $columnName = strtolower($columnName);
            $columnName = preg_replace('/[^a-z0-9_]/', '_', $columnName);
            $columnName = substr($columnName, 0, 64);
            if ($columnName !== $this->_pifaField->get('column_name')) {
                $this->_pifaField->set('column_name', $columnName);
            }
        }

        if ($this->_pifaField->showField('label')) {
            $label = cSecurity::toString($_POST['label']);
            $label = trim($label);
            $label = substr($label, 0, 255);
            if ($label !== $this->_pifaField->get('label')) {
                $this->_pifaField->set('label', $label);
            }
        }

        if ($this->_pifaField->showField('default_value')) {
            $defaultValue = cSecurity::toString($_POST['default_value']);
            $defaultValue = trim($defaultValue);
            $defaultValue = substr($defaultValue, 0, 255);
            if ($defaultValue !== $this->_pifaField->get('default_value')) {
                $this->_pifaField->set('default_value', $defaultValue);
            }
        }

        if ($this->_pifaField->showField('option_labels')) {
            $optionLabels = cSecurity::toString($_POST['option_labels']);
            $optionLabels = join(',', array_map(function ($value) {
                return trim(cSecurity::toString($value));
            }, explode(',', $optionLabels)));
            $optionLabels = substr($optionLabels, 0, 1023);
            if ($optionLabels !== $this->_pifaField->get('option_labels')) {
                $this->_pifaField->set('option_labels', $optionLabels);
            }
        }

        if ($this->_pifaField->showField('option_values')) {
            $optionValues = cSecurity::toString($_POST['option_values']);
            $optionValues = join(',', array_map(function ($value) {
                return trim(cSecurity::toString($value));
            }, explode(',', $optionValues)));
            $optionValues = substr($optionValues, 0, 1023);
            if ($optionValues !== $this->_pifaField->get('option_values')) {
                $this->_pifaField->set('option_values', $optionValues);
            }
        }

        if ($this->_pifaField->showField('help_text')) {
            $helpText = cSecurity::toString($_POST['help_text']);
            $helpText = trim($helpText);
            // no substr() cause help_text is of type TEXT!
            // $helpText = substr($helpText, 0, );
            if ($helpText !== $this->_pifaField->get('help_text')) {
                $this->_pifaField->set('help_text', $helpText);
            }
        }

        if ($this->_pifaField->showField('obligatory')) {
            $obligatory = cSecurity::toString($_POST['obligatory']);
            $obligatory = trim($obligatory);
            $obligatory = 'on' === $obligatory? 1 : 0;
            if ($obligatory !== $this->_pifaField->get('obligatory')) {
                $this->_pifaField->set('obligatory', $obligatory);
            }
        }

        if ($this->_pifaField->showField('rule')) {
            $rule = cSecurity::toString($_POST['rule']);
            $rule = trim($rule);
            $rule = substr($rule, 0, 255);

            // == handle CONTENIDO bug
            // remove this line if bug is fixed!!!
            $rule = str_replace('\\\\', '\\', $rule);
            // == /handle CONTENIDO bug

            if ($rule !== $this->_pifaField->get('rule')) {
                $this->_pifaField->set('rule', $rule);
            }
        }

        if ($this->_pifaField->showField('error_message')) {
            $errorMessage = cSecurity::toString($_POST['error_message']);
            $errorMessage = trim($errorMessage);
            $errorMessage = substr($errorMessage, 0, 255);
            if ($errorMessage !== $this->_pifaField->get('error_message')) {
                $this->_pifaField->set('error_message', $errorMessage);
            }
        }

        // store item
        if (false === $this->_pifaField->store()) {
            throw new Exception('could not store field: ' . $this->_pifaField->getLastError());
        }

        // rename column if name has changed
        $this->_pifaForm->renameColumn($oldColumnName, $this->_pifaField);

    }

    // /**
    // * Just a test to see how an objects values can smartly be updated by an
    // * array with arbitrary keys so that only a set of predefined keys are
    // * considered.
    // *
    // * @return array
    // */
    // function test() {

    // // Would be clever to take the original objects array of values But I'm
    // // not sure how it looks like if the object has to be created newly.
    // //$defaultData = $this->_pifaField->values;

    // $defaultData = array(
    // 'column_name' => NULL,
    // 'label' => NULL,
    // 'default_value' => NULL,
    // 'help_text' => NULL,
    // 'obligatory' => NULL,
    // 'rule' => NULL,
    // 'error_message' => NULL
    // );

    // return array_merge($defaultData, array_intersect_key($_POST,
    // $defaultData));

    // }

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

    /**
     * Returns a multidimensional array with available field types.
     * This array contains values for the label and icon of the field type.
     *
     * @return array
     */
    private function _getMethodTypes() {

        $methodTypes = array();
        foreach (PifaField::getMethodTypeIds() as $methodTypeId) {
            $methodTypes[$methodTypeId] = array();
            $methodTypes[$methodTypeId]['id'] = $methodTypeId;
            $methodTypes[$methodTypeId]['label'] = PifaField::getMethodTypeName($methodTypeId);
            // icon is not used atm
            $methodTypes[$methodTypeId]['icon'] = PifaField::getMethodTypeIcon($methodTypeId);
        }

        return $methodTypes;

    }
}

?>