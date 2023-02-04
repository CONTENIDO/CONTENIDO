<?php

/**
 * This file contains several page classes.
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Page to be displayed in the left bottom frame.
 * It provides a navigation for all forms defined for the current client and the
 * current language.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaLeftBottomPage extends cGuiPage {

    /**
     * id of the PIFA content type
     *
     * @var int
     */
    protected $typeId;

    /**
     * id of form to be edited
     *
     * @var int
     */
    protected $idform;

    /**
     * Create an instance.
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct() {
        /**
         * @param int $idform id of form to be edited
         */
        global $idform;

        $this->idform = !empty($idform) ? cSecurity::toInteger($idform) : 0;
        $area = cRegistry::getArea();

        parent::__construct('left_bottom', Pifa::getName());

        // get the id of the content type
        $typeCollection = new cApiTypeCollection();
        $typeCollection->select("`type` = 'CMS_PIFAFORM'");
        $type = $typeCollection->next();
        $this->typeId = $type->get('idtype');

        $this->addScript('parameterCollector.js?v=4ff97ee40f1ac052f634e7e8c2f3e37e');
        $this->addScript('form_assistant.js');
        $this->addScript('left_bottom.js');

        $this->set('s', 'dialog_title', Pifa::i18n('INUSE_DIALOG_TITLE'));
        $this->set('s', 'menu', $this->_getMenu());

        // add translations to template
        $this->set('s', 'I18N', json_encode(['confirm_delete_form' => Pifa::i18n('CONFIRM_DELETE_FORM')]));
        $this->set('s', 'AREA', $area);
        $this->set('s', 'ACTION_EDIT', PifaRightBottomFormPage::SHOW_FORM);
        $this->set('s', 'ACTION_DELETE', PifaRightBottomFormPage::DELETE_FORM);
        $this->set('s', 'DELETE_MESSAGE', conHtmlEntityDecode(Pifa::i18n("CONFIRM_DELETE_FORM")));
    }

    /**
     * Get menu with all forms of current client in current language.
     *
     * @return string
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    private function _getMenu() {
        $cfg = cRegistry::getConfig();
        $client = cSecurity::toInteger(cRegistry::getClientId());
        $lang = cSecurity::toInteger(cRegistry::getLanguageId());

        // get all forms of current client in current language
        $forms = PifaFormCollection::getByClientAndLang($client, $lang);
        if (false === $forms) {
            return '<!-- no forms for current client/language -->';
        }

        // $formIcon = Pifa::getUrl() . 'images/icon_form.png';

        // collect usage information from the content table
        $contentCollection = new cApiContentCollection();
        // select all entries about the pifa content type
        $formContent = $contentCollection->getFieldsByWhereClause([
            'idartlang',
            'value',
        ], 'idtype = "' . $this->typeId . '"');
        // get the idform and the related cApiArticleLanguage object and save them in an array
        $assignedForms = [];
        foreach ($formContent as $formRow) {
            // read settings
            $formRow['value'] = conHtmlEntityDecode($formRow['value']);
            $formRow['value'] = utf8_encode($formRow['value']);
            $settings = cXmlBase::xmlStringToArray($formRow['value']);
            // if it was successful append the array of articles using this form
            if ($settings['idform'] != '') {
                if (isset($assignedForms[$settings['idform']]) && is_array($assignedForms[$settings['idform']])) {
                    $assignedForms[$settings['idform']][] = new cApiArticleLanguage($formRow['idartlang']);
                } else {
                    $assignedForms[$settings['idform']] = [
                        new cApiArticleLanguage($formRow['idartlang']),
                    ];
                }
            }
        }

        // create menu
        $menu = new cGuiMenu();
        while (false !== $form = $forms->next()) {
            $formIsInUse = false;
            $idform = cSecurity::toInteger($form->get('idform'));
            $formName = $form->get('name');

            if ($this->idform == $idform) {
                $menu->setMarked($idform);
            }

            $menu->setTitle($idform, $formName);

            // create link to show/edit the form
            $link = new cHTMLLink();
            $link->setClass('show_item')
                ->setLink('javascript:;')
                ->setAttribute('data-action', 'show_form');
            $menu->setLink($idform, $link);
            $menu->setId($idform, $idform);

            // if this is true, then the form is maybe in use
            if (isset($assignedForms[$idform])) {
                $dialogText = Pifa::i18n("FOLLOWING_LIST_USES_FORM") . "<br><br>";
                /** @var cApiArticleLanguage $article */
                foreach ($assignedForms[$idform] as $article) {
                    if ($article->isLoaded()) {
                        $formIsInUse = true;
                        $dialogText .= '<b>' . $article->get('title') . '</b> - (' . $article->get('idart') . ')<br>';
                    }
                }

                if ($formIsInUse) {
                    // create a link for the action
                    $inUseDescr = i18n("Click for more information about usage");
                    $link = new cHTMLLink();
                    $link->setLink('javascript://')
                        ->setAttribute("data-dialog-text", $dialogText)
                        ->setAttribute('data-action', 'inused_form')
                        ->setContent('<img class="vAlignMiddle" src="' . $cfg['path']['images'] . 'exclamation.gif" title="' . $inUseDescr . '" alt="' . $inUseDescr . '">');
                    $menu->setActions($idform, 'inuse', $link);
                }
            }

            // create link to delete the form
            if (!$formIsInUse && cRegistry::getPerm()->have_perm_area_action('form', PifaRightBottomFormPage::DELETE_FORM)) {
                $deleteForm = Pifa::i18n('DELETE_FORM');
                $link = new cHTMLLink();
                $link->setLink('javascript:;')
                    ->setAttribute('data-action', 'delete_form')
                    ->setContent('<img class="vAlignMiddle" src="' . $cfg['path']['images'] . 'delete.gif" title="' . $deleteForm . '" alt="' . $deleteForm . '">');
                $menu->setActions($idform, 'delete', $link);
            } else {
                $menu->setActions($idform, 'delete', '<img class="vAlignMiddle" src="' . $cfg['path']['images'] . 'delete_inact.gif" title="" alt="">');
            }
        }

        return $menu->render(false);
    }

}

/**
 * Page for area "form" to be displayed in the right bottom frame.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaRightBottomFormPage extends cGuiPage {

    /**
     * Action constant.
     *
     * @var string
     */
    const SHOW_FORM = 'pifa_show_form';

    /**
     * Action constant.
     *
     * @var string
     */
    const STORE_FORM = 'pifa_store_form';

    /**
     * Action constant.
     *
     * @var string
     */
    const DELETE_FORM = 'pifa_delete_form';

    /**
     * model for a single PIFA form
     *
     * @var PifaForm
     */
    private $_pifaForm;

    /**
     * Creates and aggregates a model for a collection of PIFA forms
     * and another for a single PIFA form.
     *
     * If an ID for an item is given this is loaded from database
     * and its values are stored in the appropriate model.
     *
     * @throws PifaException if form could not be loaded
     * @throws cDbException
     * @throws cException
     */
    public function __construct() {
        // Action to be performed
        $action = cRegistry::getAction();

        /**
         * @param int $idform id of form to be edited
         */
        global $idform;

        parent::__construct('right_bottom', Pifa::getName());

        $this->addStyle('smoothness/jquery-ui-1.8.20.custom.css');
        $this->addStyle('right_bottom.css');
        $this->addScript('form_assistant.js');
        $this->addScript('right_bottom.js');

        // create models
        $this->_pifaForm = new PifaForm();

        // load models
        $idform = cSecurity::toInteger($idform);
        if (0 < $idform) {
            if (false === $this->_pifaForm->loadByPrimaryKey($idform)) {
                $msg = Pifa::i18n('FORM_LOAD_ERROR');
                throw new PifaException($msg);
            }
        }

        // add translations to template
        $this->set('s', 'I18N', json_encode([
            'cancel' => Pifa::i18n('CANCEL'),
            'save' => Pifa::i18n('SAVE'),
        ]));

        // dispatch action
        try {
            $this->_dispatch($action);
        } catch (PifaException $e) {
            $cGuiNotification = new cGuiNotification();
            $notification = $cGuiNotification->returnNotification(cGuiNotification::LEVEL_ERROR, $e->getMessage());
            $this->set('s', 'notification', $notification);
            $this->set('s', 'content', '');
        }
    }

    /**
     * Dispatches the given action.
     *
     * @param string $action to be executed
     * @param string $notification
     *
     * @throws PifaException if the given action is unknown
     * @throws PifaIllegalStateException if permissions are missing
     * @throws cDbException
     * @throws cException|SmartyException
     */
    protected function _dispatch($action, $notification = '') {
        $area = cRegistry::getArea();

        // check for permission
        if (!cRegistry::getPerm()->have_perm_area_action($area, $action)) {
            $msg = Pifa::i18n('NO_PERMISSIONS');
            throw new PifaIllegalStateException($msg);
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
            case self::SHOW_FORM:
                $this->set('s', 'notification', $notification);
                try {
                    $this->set('s', 'content', $this->_showForm());
                } catch (SmartyCompilerException $e) {
                    $this->set('s', 'content', Pifa::notifyException($e));
                }
                // New form or form couldn't loaded
                if (is_null($this->_pifaForm) || !$this->_pifaForm->isLoaded()) {
                    $this->reloadLeftBottomFrame(['idform' => null]);
                }
                break;

            case self::STORE_FORM:
                $notification = '';
                try {
                    $this->_storeForm();
                    // reload left_bottom and right_top after saving of form
                    $idform = $this->_pifaForm->get('idform');
                    $this->reloadLeftBottomFrame(['idform' => $idform]);
                    $this->reloadRightTopFrame(['idform' => $idform]);
                } catch (Exception $e) {
                    $notification = Pifa::notifyException($e);
                }
                $this->_dispatch(self::SHOW_FORM, $notification);
                break;

            case self::DELETE_FORM:
                $notification = '';
                try {
                    $this->_deleteForm();
                    $cGuiNotification = new cGuiNotification();
                    $this->set('s', 'notification', $cGuiNotification->returnNotification(cGuiNotification::LEVEL_OK, Pifa::i18n('FORM_DELETED')));
                    $this->set('s', 'content', '');
                    $this->reloadLeftBottomFrame(['idform' => null]);
                } catch (Exception $e) {
                    $notification = Pifa::notifyException($e);
                }
                break;

            default:
                $msg = Pifa::i18n('UNKNOWN_ACTION');
                throw new PifaException($msg);
        }
    }

    /**
     * Build and return form for PIFA forms.
     *
     * @return string
     * @throws cDbException
     * @throws cException|SmartyException
     */
    private function _showForm() {
        $area = cRegistry::getArea();
        $cfg = cRegistry::getConfig();

        // get form action
        $formAction = '';
        if (cRegistry::getPerm()->have_perm_area_action('form', self::STORE_FORM)) {
            $formAction = new cHTMLLink();
            $formAction->setCLink($area, 4, self::STORE_FORM);
            $formAction = $formAction->getHref();
        }

        // get current or default values for form
        if (!is_null($this->_pifaForm) && $this->_pifaForm->isLoaded()) {
            $idform = $this->_pifaForm->get('idform');
            $nameValue = $this->_pifaForm->get('name');
            $dataTableValue = $this->_pifaForm->get('data_table');
            $methodValue = $this->_pifaForm->get('method');
            $withTimestampValue = (bool) $this->_pifaForm->get('with_timestamp');
        } else {
            $idform = NULL;

            // read item data from form
            $nameValue = empty($_POST['name'])? '' : $_POST['name'];
            $nameValue = cSecurity::unescapeDB($nameValue);
            $nameValue = cSecurity::toString($nameValue);
            $nameValue = trim($nameValue);

            $dataTableValue = empty($_POST['data_table'])? '' : $_POST['data_table'];
            $dataTableValue = trim($dataTableValue);
            $dataTableValue = cString::toLowerCase($dataTableValue);
            $dataTableValue = preg_replace('/[^a-z0-9_]/', '_', $dataTableValue);

            $methodValue = '';
            $withTimestampValue = true;
        }

        $tpl = cSmartyBackend::getInstance(true);
        $tpl->assign('formAction', $formAction);
        $tpl->assign('idform', $idform);
        $tpl->assign('nameValue', $nameValue);
        $tpl->assign('dataTableValue', $dataTableValue);
        $tpl->assign('methodValue', cString::toUpperCase($methodValue));
        $tpl->assign('withTimestampValue', $withTimestampValue);
        $tpl->assign('hasWithTimestamp', Pifa::TIMESTAMP_BYFORM === Pifa::getTimestampSetting());
        $tpl->assign('trans', [
            'legend' => Pifa::i18n('form'),
            'name' => Pifa::i18n('form name'),
            'dataTable' => Pifa::i18n('data table'),
            'method' => Pifa::i18n('method'),
            'withTimestamp' => Pifa::i18n('with timestamp'),
            'pleaseChoose' => Pifa::i18n('please choose'),
            'saveForm' => Pifa::i18n('save form'),
        ]);

        return $tpl->fetch($cfg['templates']['pifa_right_bottom_form']);
    }

    /**
     * Handles a POST request of the first form, showing a forms details.
     *
     * @throws PifaException
     */
    private function _storeForm() {
        // determine if item is loaded
        $isLoaded = $this->_pifaForm->isLoaded();

        // read item data from form
        $name = $_POST['name'];
        $name = cSecurity::unescapeDB($name);
        $name = cSecurity::toString($name);
        $name = trim($name);

        $dataTable = $_POST['data_table'] ?? '';
        $dataTable = trim($dataTable);
        $dataTable = cString::toLowerCase($dataTable);
        $dataTable = preg_replace('/[^a-z0-9_]/', '_', $dataTable);

        $method = $_POST['method'] ?? '';
        $method = trim($method);
        $method = cString::toUpperCase($method);

        $oldDataTable = null;
        $oldWithTimestamp = null;

        switch (Pifa::getTimestampSetting()) {
            case Pifa::TIMESTAMP_NEVER:
                $withTimestamp = false;
                break;
            case Pifa::TIMESTAMP_BYFORM:
                $withTimestamp = 'on' === $_POST['with_timestamp'];
                break;
            case Pifa::TIMESTAMP_ALWAYS:
            default:
                $withTimestamp = true;
                break;
        }

        // validate item data
        if (0 === cString::getStringLength($name)) {
            $msg = Pifa::i18n('EMPTY_FORMNAME_ERROR');
            throw new PifaException($msg);
        }
        if (0 === cString::getStringLength($dataTable)) {
            $msg = Pifa::i18n('EMPTY_DATETABLENAME_ERROR');
            throw new PifaException($msg);
        }
        if (!in_array($method, [
            'GET',
            'POST',
        ])) {
            $msg = Pifa::i18n('FORM_METHOD_ERROR');
            throw new PifaException($msg);
        }

        if ($isLoaded) {
            // remember old table values
            $oldDataTable = $this->_pifaForm->get('data_table');
            $oldWithTimestamp = (bool) $this->_pifaForm->get('with_timestamp');
        } else {
            // create new item for given client & language
            $pifaFormCollection = new PifaFormCollection();
            $this->_pifaForm = $pifaFormCollection->createNewItem([
                'idclient' => cRegistry::getClientId(),
                'idlang' => cRegistry::getLanguageId(),
            ]);
        }

        // set item data
        // Never, really never, call Item->set() if the value doesn't differ
        // from the previous one. Otherwise, the genericDb thinks that the item
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

        // store item
        if (false === $this->_pifaForm->store()) {
            $msg = Pifa::i18n('FORM_STORE_ERROR');
            $msg = sprintf($msg, $this->_pifaForm->getLastError());
            throw new PifaException($msg);
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
 * Page for area "form_fields" to be displayed in the right bottom frame.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaRightBottomFormFieldsPage extends cGuiPage {

    /**
     * Action constant.
     *
     * @var string
     */
    const SHOW_FIELDS = 'pifa_show_fields';

    /**
     * model for a single PIFA form
     *
     * @var PifaForm
     */
    private $_pifaForm;

    /**
     * Creates and aggregates a model for a collection of PIFA forms
     * and another for a single PIFA form.
     *
     * If an ID for an item is given this is loaded from database
     * and its values are stored in the appropriate model.
     *
     * @throws PifaException if form could not be loaded
     * @throws cDbException
     * @throws cException
     */
    public function __construct() {
        // Action to be performed
        $action = cRegistry::getAction();

        /**
         * @param int $idform id of form to be edited
         */
        global $idform;

        parent::__construct('right_bottom', Pifa::getName());

        $this->addStyle('smoothness/jquery-ui-1.8.20.custom.css');
        $this->addStyle('right_bottom.css');
        $this->addScript('form_assistant.js');
        $this->addScript('right_bottom.js');

        // create models
        $this->_pifaForm = new PifaForm();

        // load models
        $idform = cSecurity::toInteger($idform);
        if (0 < $idform) {
            $ret = $this->_pifaForm->loadByPrimaryKey($idform);
            if (false === $ret) {
                $msg = Pifa::i18n('FORM_LOAD_ERROR');
                throw new PifaException($msg);
            }
        }

        // add translations to template
        $this->set('s', 'I18N', json_encode([
            'cancel' => Pifa::i18n('CANCEL'),
            'save' => Pifa::i18n('SAVE'),
            'confirm_delete_field' => Pifa::i18n('CONFIRM_DELETE_FIELD'),
        ]));

        // dispatch action
        try {
            $this->_dispatch($action);
        } catch (PifaException $e) {
            $cGuiNotification = new cGuiNotification();
            $notification = $cGuiNotification->returnNotification(cGuiNotification::LEVEL_ERROR, $e->getMessage());
            $this->set('s', 'notification', $notification);
            $this->set('s', 'content', '');
        }
    }

    /**
     * Dispatches the given action.
     *
     * @param string $action to be executed
     * @param string $notification
     *
     * @throws PifaException if the given action is unknown
     * @throws PifaIllegalStateException if permissions are missing
     * @throws cDbException
     * @throws cException
     */
    protected function _dispatch($action, $notification = '') {
        $area = cRegistry::getArea();

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

            case self::SHOW_FIELDS:
                $this->set('s', 'notification', $notification);
                try {
                    $this->set('s', 'content', $this->_showFields());
                } catch (SmartyCompilerException $e) {
                    $this->set('s', 'content', Pifa::notifyException($e));
                }
                break;

            default:
                $msg = Pifa::i18n('UNKNOWN_ACTION');
                throw new PifaException($msg);
        }
    }

    /**
     * @return mixed|string
     * @throws cDbException
     * @throws cException|SmartyException
     */
    private function _showFields() {
        $cfg = cRegistry::getConfig();

        $idform = $idfield = NULL;
        $fields = NULL;
        $editField = $deleteField = NULL;
        if ($this->_pifaForm->isLoaded()) {

            $idform = $this->_pifaForm->get('idform');
            $idfield = $_GET['idfield'] ?? '';

            $fields = $this->_pifaForm->getFields();

            if (cRegistry::getPerm()->have_perm_area_action('form_ajax', PifaAjaxHandler::GET_FIELD_FORM)) {
                $editField = new cHTMLLink();
                $editField->setCLink('form_ajax', 4, PifaAjaxHandler::GET_FIELD_FORM);
                $editField->setCustom('idform', $idform);
                $editField = $editField->getHref();
            }

            if (cRegistry::getPerm()->have_perm_area_action('form_ajax', PifaAjaxHandler::DELETE_FIELD)) {
                $deleteField = new cHTMLLink();
                $deleteField->setCLink('form_ajax', 4, PifaAjaxHandler::DELETE_FIELD);
                $deleteField->setCustom('idform', $idform);
                $deleteField = $deleteField->getHref();
            }
        }

        // get and fill template
        $tpl = cSmartyBackend::getInstance(true);

        $columnNames = [];
        $includesCaptcha = false;

        /** @var PifaField $field */
        foreach ($this->_pifaForm->getFields() as $field) {
            $columnNames[] = $field->get('column_name');
            if ((int) $field->get('field_type') === PifaField::CAPTCHA) {
                $includesCaptcha = true;
            }
        }

        $cGuiNotification = new cGuiNotification();

        // check for required email column at this form
        if (!in_array('email', $columnNames)) {
            $email_notification = $cGuiNotification->returnNotification(
                cGuiNotification::LEVEL_WARNING,
                Pifa::i18n('Currently there is no field called "email" in this form. Sending mails - if configured - to the user which entered the form data may not work!')
            );
            $tpl->assign('email_notification', $email_notification);
        }

        // check for captcha usage
        if ($includesCaptcha && (cString::getStringLength(getEffectiveSetting('pifa-recaptcha', 'sitekey', '')) === 0
                || cString::getStringLength(getEffectiveSetting('pifa-recaptcha', 'secret', '')) === 0)) {
            $captcha_notification = $cGuiNotification->returnNotification(
                cGuiNotification::LEVEL_WARNING,
                Pifa::i18n('This form is configured with a captcha, but its settings were not defined.') . "<br>" .
                Pifa::i18n('The captcha will not work until you provide the missing information.') . "<br>" .
                Pifa::i18n('Please save the "sitekey" and the "secret" in the client settings for the type "pifa-recaptcha". You will get this data from https://www.google.com/recaptcha.')
            );
            $tpl->assign('captcha_notification', $captcha_notification);
        }

        // translations
        $tpl->assign('trans', [
            'legend' => Pifa::i18n('fields'),
            'pleaseSaveFirst' => Pifa::i18n('please save first'),
            'dialogTitle' => Pifa::i18n('edit field'),
            'edit' => Pifa::i18n('EDIT'),
            'delete' => Pifa::i18n('DELETE'),
            'obligatory' => Pifa::i18n('OBLIGATORY'),
        ]);

        // params
        $tpl->assign('ajaxParams', implode('&', [
            'area=form_ajax',
            'frame=4',
            'contenido=' . cRegistry::getBackendSessionId(),
        ]));
        if (cRegistry::getPerm()->have_perm_area_action('form_ajax', PifaAjaxHandler::GET_FIELD_FORM)) {
            $tpl->assign('dragParams', implode('&', [
                'area=form_ajax',
                'frame=4',
                'contenido=' . cRegistry::getBackendSessionId(),
                'action=' . PifaAjaxHandler::GET_FIELD_FORM,
                'idform=' . $idform,
            ]));
        }
        if (cRegistry::getPerm()->have_perm_area_action('form_ajax', PifaAjaxHandler::REORDER_FIELDS)) {
            $tpl->assign('sortParams', implode('&', [
                'area=form_ajax',
                'frame=4',
                'contenido=' . cRegistry::getBackendSessionId(),
                'action=' . PifaAjaxHandler::REORDER_FIELDS,
                'idform=' . $this->_pifaForm->get('idform'),
            ]));
        }

        // data
        $tpl->assign('idform', $idform);
        $tpl->assign('idfield', $idfield);

        $tpl->assign('fields', $fields);
        $tpl->assign('fieldTypes', PifaField::getFieldTypeNames());

        // for partial
        $tpl->assign('editField', $editField);
        $tpl->assign('deleteField', $deleteField);
        // define path to partial template for displaying a single field row
        $tpl->assign('partialFieldRow', $cfg['templates']['pifa_ajax_field_row']);

        return $tpl->fetch($cfg['templates']['pifa_right_bottom_fields']);
    }

}

/**
 * Page for area "form_data" to be displayed in the right bottom frame.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaRightBottomFormDataPage extends cGuiPage {

    /**
     * Action constant.
     *
     * @var string
     */
    const SHOW_DATA = 'pifa_show_data';

    /**
     * model for a single PIFA form
     *
     * @var PifaForm
     */
    private $_pifaForm;

    /**
     * Creates and aggregates a model for a collection of PIFA forms
     * and another for a single PIFA form.
     *
     * If an ID for an item is given this is loaded from database
     * and its values are stored in the appropriate model.
     *
     * @throws PifaException if form could not be loaded
     * @throws cDbException
     * @throws cException
     */
    public function __construct() {
        /**
         * @param int $idform id of form to be edited
         */
        global $idform;

        // Action to be performed
        $action = cRegistry::getAction();

        parent::__construct('right_bottom', Pifa::getName());

        $this->addStyle('smoothness/jquery-ui-1.8.20.custom.css');
        $this->addStyle('right_bottom.css');
        $this->addScript('form_assistant.js');
        $this->addScript('right_bottom.js');

        // create models
        $this->_pifaForm = new PifaForm();

        // load models
        $idform = cSecurity::toInteger($idform);
        if (0 < $idform) {
            if (false === $this->_pifaForm->loadByPrimaryKey($idform)) {
                $msg = Pifa::i18n('FORM_LOAD_ERROR');
                throw new PifaException($msg);
            }
        }

        // add translations to template
        $this->set('s', 'I18N', json_encode([
            'cancel' => Pifa::i18n('CANCEL'),
            'save' => Pifa::i18n('SAVE'),
        ]));

        // dispatch action
        try {
            $this->_dispatch($action);
        } catch (PifaException $e) {
            $cGuiNotification = new cGuiNotification();
            $notification = $cGuiNotification->returnNotification(cGuiNotification::LEVEL_ERROR, $e->getMessage());
            $this->set('s', 'notification', $notification);
            $this->set('s', 'content', '');
        }
    }

    /**
     * Dispatches the given action.
     *
     * @param string $action to be executed
     * @param string $notification
     *
     * @throws PifaException if the given action is unknown
     * @throws PifaIllegalStateException if permissions are missing
     * @throws cDbException
     * @throws cException
     */
    protected function _dispatch($action, $notification = '') {
        $area = cRegistry::getArea();

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

            case self::SHOW_DATA:
                $this->set('s', 'notification', $notification);
                try {
                    $this->set('s', 'content', $this->_showData());
                } catch (SmartyCompilerException $e) {
                    $this->set('s', 'content', Pifa::notifyException($e));
                }
                break;

            default:
                $msg = Pifa::i18n('UNKNOWN_ACTION');
                throw new PifaException($msg);
        }
    }

    /**
     * @return mixed|string
     * @throws cException|SmartyException
     */
    private function _showData() {
        $cfg = cRegistry::getConfig();

        $tpl = cSmartyBackend::getInstance(true);

        // translations
        $tpl->assign('trans', [
            'legend' => Pifa::i18n('data'),
        	'nodata' => Pifa::i18n('NODATA'),
            'pleaseSaveFirst' => Pifa::i18n('please save first'),
            'export' => Pifa::i18n('download data as CSV'),
            'delete' => Pifa::i18n('Delete'),
        ]);

        $tpl->assign('form', $this->_pifaForm);
        $tpl->assign('getFileUrl', 'main.php?' . implode('&', [
            'area=form_ajax',
            'frame=4',
            'contenido=' . cRegistry::getBackendSessionId(),
            'action=' . PifaAjaxHandler::GET_FILE,
            ]));

        try {
            $tpl->assign('fields', $this->_pifaForm->getFields());
        } catch (Exception $e) {
            $tpl->assign('fields', Pifa::notifyException($e));
        }

        $tpl->assign('withTimestamp', (bool) $this->_pifaForm->get('with_timestamp'));


        try {
            $hasPermExportData = cRegistry::getPerm()->have_perm_area_action('form_ajax', PifaAjaxHandler::EXPORT_DATA);
            $hasPermDeleteData = cRegistry::getPerm()->have_perm_area_action('form_ajax', PifaAjaxHandler::DELETE_DATA);
        } catch (Exception $e) {
            $hasPermExportData = false;
            $hasPermDeleteData = false;
        }

        // export data
        $data = $this->_pifaForm->getData();
        $tpl->assign('data', $data);

        if (!empty($data) && $hasPermExportData) {
            $tpl->assign('exportUrl', 'main.php?' . http_build_query([
                'area' => 'form_ajax',
                'frame' => '4',
                'contenido' => cRegistry::getBackendSessionId(),
                'action' => PifaAjaxHandler::EXPORT_DATA,
                'idform' => $this->_pifaForm->get('idform')
                ]));
        }

        // delete data
        if (!empty($data) && $hasPermDeleteData) {
            $tpl->assign('deleteUrl', 'main.php?' . http_build_query([
                'area' => 'form_ajax',
                'frame' => '4',
                'contenido' => cRegistry::getBackendSessionId(),
                'action' => PifaAjaxHandler::DELETE_DATA,
                'idform' => $this->_pifaForm->get('idform')
            ]));
        }

        // Mass deletion of form data
        $lnkDel = new cHTMLLink('javascript://');
        $lnkDel->setClass('flip_mark');
        $lnkDel->setContent(Pifa::i18n('Check all'));
        $tpl->assign('lnkDel', $lnkDel->render());

        return $tpl->fetch($cfg['templates']['pifa_right_bottom_data']);
    }

}

/**
 * Page for area "form_export" to be displayed in the right bottom frame.
 * This page allows for exporting a form as XML.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaRightBottomFormExportPage extends cGuiPage {

    /**
     * Action constant.
     *
     * @var string
     */
    const EXPORT_FORM = 'pifa_export_form';

    /**
     * model for a single PIFA form
     *
     * @var PifaForm
     */
    private $_pifaForm;

    /**
     * Creates and aggregates a model for a single PIFA form.
     *
     * If an ID for an item is given this is loaded from database
     * and its values are stored in the appropriate model.
     *
     * @throws PifaException if form could not be loaded
     * @throws cDbException
     * @throws cException
     */
    public function __construct() {

        /**
         * @param string $action to be performed
         */
        $action = cRegistry::getAction();

        /**
         * @param int $idform id of form to be edited
         */
        global $idform;

        parent::__construct('right_bottom', Pifa::getName());

        $this->addStyle('smoothness/jquery-ui-1.8.20.custom.css');
        $this->addStyle('right_bottom.css');
        $this->addScript('form_assistant.js');
        $this->addScript('right_bottom.js');

        // add translations to template
        $this->set('s', 'I18N', json_encode([
            'cancel' => Pifa::i18n('CANCEL'),
            'save' => Pifa::i18n('SAVE'),
        ]));

        // create models
        $this->_pifaForm = new PifaForm();

        // load models
        $idform = cSecurity::toInteger($idform);
        if (0 < $idform) {
            if (false === $this->_pifaForm->loadByPrimaryKey($idform)) {
                $msg = Pifa::i18n('FORM_LOAD_ERROR');
                throw new PifaException($msg);
            }
        }

        // dispatch action
        try {
            $this->_dispatch($action);
        } catch (PifaException $e) {
            $cGuiNotification = new cGuiNotification();
            $notification = $cGuiNotification->returnNotification(cGuiNotification::LEVEL_ERROR, $e->getMessage());
            $this->set('s', 'notification', $notification);
            $this->set('s', 'content', '');
        }
    }

    /**
     * Dispatches the given action.
     *
     * @param string $action to be executed
     * @param string $notification
     *
     * @throws PifaException if the given action is unknown
     * @throws PifaIllegalStateException if permissions are missing
     * @throws cDbException
     * @throws cException|SmartyException
     */
    protected function _dispatch($action, $notification = '') {
        // dispatch actions
        switch ($action) {
            case self::EXPORT_FORM:
                // check for permission
                if (!cRegistry::getPerm()->have_perm_area_action('form_ajax', $action)) {
                    $msg = Pifa::i18n('NO_PERMISSIONS');
                    throw new PifaIllegalStateException($msg);
                }

                $this->set('s', 'notification', $notification);
                try {
                    $this->set('s', 'content', $this->_exportForm());
                } catch (SmartyCompilerException $e) {
                    $this->set('s', 'content', Pifa::notifyException($e));
                }
                break;

            default:
                $msg = Pifa::i18n('UNKNOWN_ACTION');
                throw new PifaException($msg);
        }
    }

    /**
     * @return mixed|string
     * @throws cException|SmartyException
     */
    private function _exportForm() {
        $cfg = cRegistry::getConfig();

        $tpl = cSmartyBackend::getInstance(true);

        // translations
        $tpl->assign('trans', [
            'legend' => Pifa::i18n('pifa_export_form'),
            'withData' => Pifa::i18n('WITH_DATA'),
            'export' => Pifa::i18n('EXPORT'),
        ]);

        $tpl->assign('formAction', 'main.php?' . implode('&', [
            'area=form_ajax',
            'frame=4',
            'contenido=' . cRegistry::getBackendSessionId(),
            'action=' . PifaAjaxHandler::EXPORT_FORM,
            'idform=' . $this->_pifaForm->get('idform'),
            ]));

        $tpl->assign('idform', $this->_pifaForm->get('idform'));

        return $tpl->fetch($cfg['templates']['pifa_right_bottom_export']);
    }

}

/**
 * Page for area "form_import" to be displayed in the right bottom frame.
 * This page allows for importing a form that is available as XML.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaRightBottomFormImportPage extends cGuiPage {

    /**
     * Action constant.
     *
     * @var string
     */
    const IMPORT_FORM = 'pifa_import_form';

    /**
     * PifaRightBottomFormImportPage constructor.
     * Dispatches the current action and displays a notification.
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct() {

        /**
         * @param string $action to be performed
         */
        $action = cRegistry::getAction();

        parent::__construct('right_bottom', Pifa::getName());

        $this->addStyle('smoothness/jquery-ui-1.8.20.custom.css');
        $this->addStyle('right_bottom.css');
        $this->addScript('form_assistant.js');
        $this->addScript('right_bottom.js');

        // add translations to template
        $this->set('s', 'I18N', json_encode([
            'cancel' => Pifa::i18n('CANCEL'),
            'save' => Pifa::i18n('SAVE'),
        ]));

        // dispatch action
        try {
            $this->_dispatch($action);
        } catch (PifaException $e) {
            $cGuiNotification = new cGuiNotification();
            $notification = $cGuiNotification->returnNotification(cGuiNotification::LEVEL_ERROR, $e->getMessage());
            $this->set('s', 'notification', $notification);
            $this->set('s', 'content', '');
        }
    }

    /**
     * Dispatches the given action.
     *
     * @param string $action to be executed
     * @param string $notification
     *
     * @throws PifaException if the given action is unknown
     * @throws PifaIllegalStateException if permissions are missing
     * @throws cDbException
     * @throws cException|SmartyException
     */
    protected function _dispatch($action, $notification = '') {
        $area = cRegistry::getArea();

        // check for permission
        if (!cRegistry::getPerm()->have_perm_area_action($area, $action)) {
            $msg = Pifa::i18n('NO_PERMISSIONS');
            throw new PifaIllegalStateException($msg);
        }

        // dispatch actions
        switch ($action) {
            case self::IMPORT_FORM:
                $this->set('s', 'notification', $notification);
                try {
                    switch (cString::toUpperCase($_SERVER['REQUEST_METHOD'] ?? '')) {
                        case 'GET':
                            $this->set('s', 'content', $this->_importFormGet());
                            break;

                        case 'POST':
                            $this->set('s', 'content', $this->_importFormPost());
                            break;
                    }

                    $this->reloadLeftBottomFrame(['idform' => null]);
                } catch (SmartyCompilerException $e) {
                    $this->set('s', 'content', Pifa::notifyException($e));
                }
                break;

            default:
                $msg = Pifa::i18n('UNKNOWN_ACTION');
                throw new PifaException($msg);
        }
    }

    /**
     * Handles the GET & POST request for the form_import area.
     * On a GET request a form is displayed that allows for uploading an XML
     * export file and an additional checkbox to select if gathered data should
     * be imported too.
     * On a POST request the import of the uploaded file is performed via
     * PifaImporter. Eventually a notification is displayed.
     *
     * @param bool $showTableNameField
     *
     * @return string
     * @throws cException|SmartyException
     */
    private function _importFormGet($showTableNameField = false) {
        $cfg = cRegistry::getConfig();

        $tpl = cSmartyBackend::getInstance(true);

        // translations
        $tpl->assign('trans', [
            'legend' => Pifa::i18n('pifa_import_form'),
            'xml' => Pifa::i18n('XML'),
            'used_table_name_error' => Pifa::i18n('USED_TABLE_NAME_ERROR'),
            'table_name' => Pifa::i18n('data table'),
            'import' => Pifa::i18n('IMPORT'),
        ]);

        $tpl->assign('formAction', 'main.php?' . implode('&', [
            'area=form_import',
            'frame=4',
            'contenido=' . cRegistry::getBackendSessionId(),
            'action=' . self::IMPORT_FORM,
            ]));

        $tpl->assign('showTableNameField', $showTableNameField);

        return $tpl->fetch($cfg['templates']['pifa_right_bottom_import']);
    }

    /**
     * Handles the GET & POST request for the form_import area.
     * On a GET request a form is displayed that allows for uploading an XML
     * export file and an additional checkbox to select if gathered data should
     * be imported too.
     * On a POST request the import of the uploaded file is performed via
     * PifaImporter. Eventually a notification is displayed.
     *
     * @return string
     * @throws cException|SmartyException
     */
    private function _importFormPost() {
        $cGuiNotification = new cGuiNotification();

        // read XML from file
        $xml = file_get_contents($_FILES['xml']['tmp_name']);
        $tableName = $_POST['table_name'] ?? NULL;

        // check read operation
        if (false === $xml) {
            $note = Pifa::i18n('READ_XML_ERROR');
            return $cGuiNotification->returnNotification(cGuiNotification::LEVEL_ERROR, $note);
        }

        try {

            // perform import process
            $pifaImporter = new PifaImporter();
            $pifaImporter->setTableName($tableName);
            $pifaImporter->import($xml);

            // display success message
            $note = Pifa::i18n('IMPORT_SUCCESS');
            $out = $cGuiNotification->returnNotification(cGuiNotification::LEVEL_OK, $note);
        } catch (PifaDatabaseException $e) {
            $out = $this->_importFormGet(true);
        }

        return $out;
    }

}
