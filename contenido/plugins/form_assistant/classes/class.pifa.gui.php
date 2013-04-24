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

        // $formIcon = Pifa::getUrl() . 'images/icon_form.png';

        // create menu
        $menu = new cGuiMenu();
        while (false !== $form = $forms->next()) {

            $idform = $form->get('idform');
            $formName = $form->get('name');

            $menu->setTitle($idform, $formName);
            // $menu->setImage($idform, $formIcon);

            // add 'show form' link
            $link = new cHTMLLink();
            // main.php?area=form&frame=4&action=show_form&idform=6&contenido=75ijqvmgbgc2dt61gt8pfcmg01
            $link->setCLink($area, 4, 'show_form');
            $link->setTargetFrame('right_bottom');
            $link->setCustom('idform', $idform);
            $link->setAlt('idform ' . $idform);
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

        // dispatch actions
        // @todo dont use actions here anymore
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

            default:
                throw new InvalidArgumentException('unknown action ' . $action);
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
        $formAction->setCLink($area, 4, 'store_form');
        $formAction = $formAction->getHref();

        // get current or default values for form
        if ($this->_pifaForm->isLoaded()) {
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
            // remember old table values
            $oldDataTable = $this->_pifaForm->get('data_table');
            $oldWithTimestamp = (bool) $this->_pifaForm->get('with_timestamp');
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
        if ($withTimestamp !== (bool) $this->_pifaForm->get('with_timestamp')) {
            $this->_pifaForm->set('with_timestamp', $withTimestamp);
        }

        // store item
        if (false === $this->_pifaForm->store()) {
            throw new Exception('could not store form: ' . $this->_pifaForm->getLastError());
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
