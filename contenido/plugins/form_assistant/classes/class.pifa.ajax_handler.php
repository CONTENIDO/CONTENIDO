<?php

/**
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
 * Class for area "form_ajax" handling all Ajax requests for the PIFA backend.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaAjaxHandler {

    /**
     * Action constant to display a form for editing a PIFA form field.
     *
     * @var string
     */
    const GET_FIELD_FORM = 'pifa_get_field_form';

    /**
     * Action constant to process a form for editing a PIFA form field.
     *
     * @var string
     */
    const POST_FIELD_FORM = 'pifa_post_field_form';

    /**
     * Action constant.
     *
     * @var string
     */
    const REORDER_FIELDS = 'pifa_reorder_fields';

    /**
     * Action constant.
     *
     * @var string
     */
    const EXPORT_DATA = 'pifa_export_data';

    /**
     * Action constant.
     *
     * @var string
     */
    const EXPORT_FORM = 'pifa_export_form';

    /**
     * Action constant.
     *
     * @var string
     */
    const IMPORT_FORM = 'pifa_import_form';

    /**
     * Action constant.
     *
     * @var string
     */
    const GET_FILE = 'pifa_get_file';

    /**
     * Action constant.
     *
     * @var string
     */
    const DELETE_FIELD = 'pifa_delete_field';

    /**
     * Action constant.
     *
     * @var string
     */
    const GET_OPTION_ROW = 'pifa_get_option_row';

    /**
     *
     * @throws PifaException if action is unknown
     */
    function dispatch($action) {
        global $area;

        // check for permission
        if (!cRegistry::getPerm()->have_perm_area_action($area, $action)) {
            $msg = Pifa::i18n('NO_PERMISSIONS');
            throw new PifaIllegalStateException($msg);
        }

        switch ($action) {

            case self::GET_FIELD_FORM:
                // display a form for editing a PIFA form field
                $idform = cSecurity::toInteger($_GET['idform']);
                $idfield = cSecurity::toInteger($_GET['idfield']);
                $fieldType = cSecurity::toInteger($_GET['field_type']);
                $this->_getFieldForm($idform, $idfield, $fieldType);
                break;

            case self::POST_FIELD_FORM:
                // process a form for editing a PIFA form field
                $idform = cSecurity::toInteger($_POST['idform']);
                $idfield = cSecurity::toInteger($_POST['idfield']);
                // $this->_editFieldForm($idform, $idfield);
                $this->_postFieldForm($idform, $idfield);
                break;

            case self::DELETE_FIELD:
                $idfield = cSecurity::toInteger($_GET['idfield']);
                $this->_deleteField($idfield);
                break;

            case self::REORDER_FIELDS:
                $idform = cSecurity::toInteger($_POST['idform']);
                $idfields = implode(',', array_map('cSecurity::toInteger', explode(',', $_POST['idfields'])));
                $this->_reorderFields($idform, $idfields);
                break;

            case self::EXPORT_DATA:
                $idform = cSecurity::toInteger($_GET['idform']);
                $this->_exportData($idform);
                break;

            case self::EXPORT_FORM:
                $idform = cSecurity::toInteger($_POST['idform']);
                $withData = 'on' === $_POST['with_data'];
                $this->_exportForm($idform, $withData);
                break;

            case self::IMPORT_FORM:
                $xml = $_FILES['xml'];
                $this->_importForm($xml);
                break;

            case self::GET_FILE:
                $name = cSecurity::toString($_GET['name']);
                $file = cSecurity::toString($_GET['file']);
                $this->_getFile($name, $file);
                break;

            case self::GET_OPTION_ROW:
                $index = cSecurity::toInteger($_GET['index']);
                $this->_getOptionRow($index);
                break;

            default:
                $msg = Pifa::i18n('UNKNOWN_ACTION');
                // Util::log(get_class($this) . ': ' . $msg . ': ' . $action);
                throw new PifaException($msg);
        }
    }

    /**
     * Displays a form for editing a PIFA form field.
     *
     * @param int $idform
     * @param int $idfield
     * @param int $fieldType
     * @throws PifaException
     */
    private function _getFieldForm($idform, $idfield, $fieldType) {
        $cfg = cRegistry::getConfig();

        // get field
        if (0 < $idfield) {
            // edit existing field
            $field = new PifaField();
            $field->loadByPrimaryKey($idfield);
        } elseif (0 < $fieldType) {
            // create new field by type
            $field = new PifaField();
            $field->loadByRecordSet(array(
                'field_type' => $fieldType
            ));
        } else {
            // bugger off
            // TODO check error message
            $msg = Pifa::i18n('FORM_CREATE_ERROR');
            throw new PifaException($msg);
        }

        // get option classes
        $optionClasses = Pifa::getExtensionClasses('PifaExternalOptionsDatasourceInterface');
        array_unshift($optionClasses, array(
            'value' => '',
            'label' => Pifa::i18n('none')
        ));

        // create form
        $tpl = cSmartyBackend::getInstance(true);

        // translations
        $tpl->assign('trans', array(
            'idfield' => Pifa::i18n('ID'),
            'fieldRank' => Pifa::i18n('RANK'),
            'fieldType' => Pifa::i18n('FIELD_TYPE'),
            'columnName' => Pifa::i18n('COLUMN_NAME'),
            'label' => Pifa::i18n('LABEL'),
            'displayLabel' => Pifa::i18n('DISPLAY_LABEL'),
            'defaultValue' => Pifa::i18n('DEFAULT_VALUE'),
            'helpText' => Pifa::i18n('HELP_TEXT'),
            'rule' => Pifa::i18n('VALIDATION_RULE'),
            'errorMessage' => Pifa::i18n('ERROR_MESSAGE'),
            'database' => Pifa::i18n('DATABASE'),
            'options' => Pifa::i18n('OPTIONS'),
            'general' => Pifa::i18n('GENERAL'),
            'obligatory' => Pifa::i18n('OBLIGATORY'),
            'value' => Pifa::i18n('VALUE'),
            'addOption' => Pifa::i18n('ADD_OPTION'),
            'submitValue' => Pifa::i18n('SAVE'),
            'styling' => Pifa::i18n('STYLING'),
            'cssClass' => Pifa::i18n('CSS_CLASS'),
            'uri' => Pifa::i18n('URI'),
            'externalOptionsDatasource' => Pifa::i18n('EXTERNAL_OPTIONS_DATASOURCE'),
            'deleteAll' => Pifa::i18n('DELETE_CSS_CLASSES')
        ));

        // hidden form values (requires right to store form field)
        if (cRegistry::getPerm()->have_perm_area_action('form_ajax', self::POST_FIELD_FORM)) {
            $tpl->assign('contenido', cRegistry::getBackendSessionId());
            $tpl->assign('action', self::POST_FIELD_FORM);
            $tpl->assign('idform', $idform);
        }

        // field
        $tpl->assign('field', $field);

        // CSS classes
        $tpl->assign('cssClasses', explode(',', getEffectiveSetting('pifa', 'field-css-classes', 'half-row,full-row,line-bottom,line-top')));

        // option classes (external options datasources)
        $tpl->assign('optionClasses', $optionClasses);

        // build href to add new option row (requires right to add option)
        if (cRegistry::getPerm()->have_perm_area_action('form_ajax', self::POST_FIELD_FORM) && cRegistry::getPerm()->have_perm_area_action('form_ajax', self::GET_OPTION_ROW)) {
            $tpl->assign('hrefAddOption', 'main.php?' . implode('&', array(
                'area=form_ajax',
                'frame=4',
                'contenido=' . cRegistry::getBackendSessionId(),
                'action=' . PifaAjaxHandler::GET_OPTION_ROW
            )));
        }

        // path to partial template for displaying a single option row
        $tpl->assign('partialOptionRow', $cfg['templates']['pifa_ajax_option_row']);

        $tpl->display($cfg['templates']['pifa_ajax_field_form']);
    }

    /**
     * Processes a form for editing a PIFA form field.
     *
     * @param int $idform
     * @param int $idfield
     * @throws PifaException
     */
    private function _postFieldForm($idform, $idfield) {
        $string_cast_deep = create_function('$value', '
            $value = cSecurity::unescapeDB($value);
            $value = cSecurity::toString($value);
            $value = trim($value);
            // replace comma by comma entity
            $value = str_replace(\',\', \'&#44;\', $value);
            return $value;
        ');

        global $area;
        $cfg = cRegistry::getConfig();

        // load or create field
        if (0 < $idfield) {
            // load field
            $pifaField = new PifaField($idfield);
            if (!$pifaField->isLoaded()) {
                $msg = Pifa::i18n('FIELD_LOAD_ERROR');
                throw new PifaException($msg);
            }
            $isFieldCreated = false;
        } else {
            // get field type for new form field
            $fieldType = $_POST['field_type'];
            $fieldType = cSecurity::toInteger($fieldType);
            // create field
            $collection = new PifaFieldCollection();
            $pifaField = $collection->createNewItem(array(
                'idform' => $idform,
                'field_type' => $fieldType
            ));
            $isFieldCreated = true;
        }

        // remember old column name
        // will be an empty string for new fields
        $oldColumnName = $pifaField->get('column_name');

        // set the new rank of the item
        $fieldRank = $_POST['field_rank'];
        $fieldRank = cSecurity::toInteger($fieldRank);
        if ($fieldRank !== $pifaField->get('field_rank')) {
            $pifaField->set('field_rank', $fieldRank);
        }

        /*
         * Read item data from form, validate item data and finally set item
         * data. Which data is editable depends upon the field type. So certain
         * data will only be stored if its field is shown in the form. Never,
         * really never, call Item->set() if the value doesn't differ from the
         * previous one. Otherwise the genericDb thinks that the item is
         * modified and tries to store it, resulting in a return value of false!
         */

        // According to the MySQL documentation table and column names
        // must not be longer than 64 charcters.
        if ($pifaField->showField('column_name')) {
            $columnName = $_POST['column_name'];
            $columnName = cSecurity::unescapeDB($columnName);
            $columnName = cSecurity::toString($columnName);
            $columnName = trim($columnName);
            $columnName = cString::toLowerCase($columnName);
            // does not seem to work
            // $columnName = cString::replaceDiacritics($columnName);
            $columnName = preg_replace('/[^a-z0-9_]/', '_', $columnName);
            $columnName = cString::getPartOfString($columnName, 0, 64);
            if ($columnName !== $pifaField->get('column_name')) {
                $pifaField->set('column_name', $columnName);
            }
        }

        if ($pifaField->showField('label')) {
            $label = $_POST['label'];
            $label = cSecurity::unescapeDB($label);
            $label = cSecurity::toString($label);
            $label = strip_tags($label);
            $label = trim($label);
            $label = cString::getPartOfString($label, 0, 1023);
            if ($label !== $pifaField->get('label')) {
                $pifaField->set('label', $label);
            }
        }

        if ($pifaField->showField('display_label')) {
            $displayLabel = $_POST['display_label'];
            $displayLabel = cSecurity::unescapeDB($displayLabel);
            $displayLabel = cSecurity::toString($displayLabel);
            $displayLabel = trim($displayLabel);
            $displayLabel = 'on' === $displayLabel? 1 : 0;
            if ($displayLabel !== $pifaField->get('display_label')) {
                $pifaField->set('display_label', $displayLabel);
            }
        }

        if ($pifaField->showField('uri')) {
            $uri = $_POST['uri'];
            $uri = cSecurity::unescapeDB($uri);
            $uri = cSecurity::toString($uri);
            $uri = trim($uri);
            $uri = cString::getPartOfString($uri, 0, 1023);
            if ($uri !== $pifaField->get('uri')) {
                $pifaField->set('uri', $uri);
            }
        }

        if ($pifaField->showField('default_value')) {
            $defaultValue = $_POST['default_value'];
            $defaultValue = cSecurity::unescapeDB($defaultValue);
            $defaultValue = cSecurity::toString($defaultValue);
            $defaultValue = trim($defaultValue);
            $defaultValue = cString::getPartOfString($defaultValue, 0, 1023);
            if ($defaultValue !== $pifaField->get('default_value')) {
                $pifaField->set('default_value', $defaultValue);
            }
        }

        if ($pifaField->showField('option_labels')) {
            if (array_key_exists('option_labels', $_POST) && is_array($_POST['option_labels'])) {
                $optionLabels = implode(',', array_map($string_cast_deep, $_POST['option_labels']));
                $optionLabels = cString::getPartOfString($optionLabels, 0, 1023);
            }
            if ($optionLabels !== $pifaField->get('option_labels')) {
                $pifaField->set('option_labels', $optionLabels);
            }
        }

        if ($pifaField->showField('option_values')) {
            if (array_key_exists('option_values', $_POST) && is_array($_POST['option_values'])) {
                $optionValues = implode(',', array_map($string_cast_deep, $_POST['option_values']));
                $optionValues = cString::getPartOfString($optionValues, 0, 1023);
            }
            if ($optionValues !== $pifaField->get('option_values')) {
                $pifaField->set('option_values', $optionValues);
            }
        }

        if ($pifaField->showField('help_text')) {
            $helpText = $_POST['help_text'];
            $helpText = cSecurity::unescapeDB($helpText);
            $helpText = cSecurity::toString($helpText);
            $helpText = trim($helpText);
            if ($helpText !== $pifaField->get('help_text')) {
                $pifaField->set('help_text', $helpText);
            }
        }

        if ($pifaField->showField('obligatory')) {
            $obligatory = $_POST['obligatory'];
            $obligatory = cSecurity::unescapeDB($obligatory);
            $obligatory = cSecurity::toString($obligatory);
            $obligatory = trim($obligatory);
            $obligatory = 'on' === $obligatory? 1 : 0;
            if ($obligatory !== $pifaField->get('obligatory')) {
                $pifaField->set('obligatory', $obligatory);
            }
        }

        if ($pifaField->showField('rule')) {
            $rule = $_POST['rule'];
            $rule = cSecurity::toString($rule, false);
            $rule = trim($rule);
            $rule = cString::getPartOfString($rule, 0, 1023);
            // check if rule is valid
            if (0 === cString::getStringLength($rule)) {
                $pifaField->set('rule', $rule);
            } else if (false === @preg_match($rule, 'And always remember: the world is an orange!')) {
                // PASS
            } else if ($rule === $pifaField->get('rule')) {
                // PASS
            } else {
                $pifaField->set('rule', $rule);
            }
        }

        if ($pifaField->showField('error_message')) {
            $errorMessage = $_POST['error_message'];
            $errorMessage = cSecurity::unescapeDB($errorMessage);
            $errorMessage = cSecurity::toString($errorMessage);
            $errorMessage = trim($errorMessage);
            $errorMessage = cString::getPartOfString($errorMessage, 0, 1023);
            if ($errorMessage !== $pifaField->get('error_message')) {
                $pifaField->set('error_message', $errorMessage);
            }
        }

        if ($pifaField->showField('css_class') && array_key_exists('css_class', $_POST) && is_array($_POST['css_class'])) {
            $cssClass = implode(',', array_map($string_cast_deep, $_POST['css_class']));
            $cssClass = cString::getPartOfString($cssClass, 0, 1023);
        }
        if ($cssClass !== $pifaField->get('css_class')) {
            $pifaField->set('css_class', $cssClass);
        }

        if ($pifaField->showField('option_class')) {
            $optionClass = $_POST['option_class'];
            $optionClass = cSecurity::unescapeDB($optionClass);
            $optionClass = cSecurity::toString($optionClass);
            $optionClass = trim($optionClass);
            $optionClass = cString::getPartOfString($optionClass, 0, 1023);
            if ($optionClass !== $pifaField->get('option_class')) {
                $pifaField->set('option_class', $optionClass);
            }
        }

        // store (add, drop or change) column in data table
        $pifaForm = new PifaForm($idform);
        try {
            $pifaForm->storeColumn($pifaField, $oldColumnName);
        } catch (PifaException $e) {
            // if column could not be created
            if ($isFieldCreated) {
                // the field has to be deleted if its newly created
                $pifaField->delete();
            } else {
                // the field has to keep its old column name
                $pifaField->set('column_name', $oldColumnName);
            }
            throw $e;
        }

        // store item
        if (false === $pifaField->store()) {
            $msg = Pifa::i18n('FIELD_STORE_ERROR');
            $msg = sprintf($msg, $pifaField->getLastError());
            throw new PifaException($msg);
        }

        // if a new field was created
        // younger siblings have to be moved
        if (true === $isFieldCreated) {

            // update ranks of younger siblings
            $sql = "-- PifaAjaxHandler->_postFieldForm()
                UPDATE
                    " . cRegistry::getDbTableName('pifa_field') . "
                SET
                    field_rank = field_rank + 1
                WHERE
                    idform = " . cSecurity::toInteger($idform) . "
                    AND field_rank >= " . cSecurity::toInteger($fieldRank) . "
                    AND idfield <> " . cSecurity::toInteger($pifaField->get('idfield')) . "
                ;";

            $db = cRegistry::getDb();
            if (false === $db->query($sql)) {
                // false is returned if no fields were updated
                // but that doesn't matter cause new field might
                // have no younger siblings
            }
        }

        // return new row to be displayed in list
        $editField = new cHTMLLink();
        $editField->setCLink($area, 4, self::GET_FIELD_FORM);
        $editField->setCustom('idform', $idform);
        $editField = $editField->getHref();

        $deleteField = new cHTMLLink();
        $deleteField->setCLink($area, 4, self::DELETE_FIELD);
        $deleteField->setCustom('idform', $idform);
        $deleteField = $deleteField->getHref();

        $tpl = cSmartyBackend::getInstance(true);

        // translations
        $tpl->assign('trans', array(
            'edit' => Pifa::i18n('EDIT'),
            'delete' => Pifa::i18n('DELETE'),
            'obligatory' => Pifa::i18n('OBLIGATORY')
        ));

        // the field
        $tpl->assign('field', $pifaField);

        $tpl->assign('editField', $editField);
        $tpl->assign('deleteField', $deleteField);

        $tpl->display($cfg['templates']['pifa_ajax_field_row']);
    }

    /**
     *
     * @param int $idfield
     * @throws PifaException
     */
    private function _deleteField($idfield) {
        if (0 == $idfield) {
            $msg = Pifa::i18n('MISSING_IDFIELD');
            throw new PifaException($msg);
        }

        $pifaField = new PifaField($idfield);
        $pifaField->delete();
    }

    /**
     * reorder fields
     *
     * @param int $idform
     * @param string $idfields CSV of integers
     */
    private function _reorderFields($idform, $idfields) {
        PifaFieldCollection::reorder($idform, $idfields);
    }

    /**
     *
     * @param int $idform
     */
    private function _exportData($idform) {

        // read and echo data
        $pifaForm = new PifaForm($idform);
        $filename = $pifaForm->get('data_table') . date('_Y_m_d_H_i_s') . '.csv';
        $data = $pifaForm->getDataAsCsv();

        // prevent caching
        session_cache_limiter('private');
        session_cache_limiter('must-revalidate');

        // set header
        header('Pragma: cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private');
        header('Content-Type: text/csv');
        header('Content-Length: ' . cString::getStringLength($data));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');

        // echo payload
        echo $data;
    }

    /**
     * Create an PIFA form export file as XML and displays this as attachment
     * for download.
     *
     * @param int $idform of form to be exported
     * @param bool $withData if form data should be included
     */
    private function _exportForm($idform, $withData) {

        // read and echo data
        $pifaForm = new PifaForm($idform);
        $filename = $pifaForm->get('data_table') . date('_Y_m_d_H_i_s') . '.xml';

        $pifaExporter = new PifaExporter($pifaForm);
        $xml = $pifaExporter->export($withData);

        // prevent caching
        session_cache_limiter('private');
        session_cache_limiter('must-revalidate');

        // set header
        header('Pragma: cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private');
        header('Content-Type: text/xml');
        // header('Content-Type: application/xml');
        header('Content-Length: ' . cString::getStringLength($xml));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');

        // echo payload
        echo $xml;
    }

    /**
     *
     * @param string $name
     * @param string $file
     */
    private function _getFile($name, $file) {
        $cfg = cRegistry::getConfig();

        $path = $cfg['path']['contenido_cache'] . 'form_assistant/';

        $file = basename($file);

        header('Pragma: cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private');

        /*
         * TODO find solution application/zip works on Ubuntu 12.04 but has
         * problems on XP/IE7/IE8 application/octet-stream works on XP/IE7/IE8
         * but has problems on Ubuntu 12.04
         */
        header('Content-Type: application/octet-stream');

        header('Content-Length: ' . filesize($path . $file));
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Transfer-Encoding: binary');

        $buffer = '';
        $handle = fopen($path . $file, 'rb');
        if (false === $handle) {
            return false;
        }
        while (!feof($handle)) {
            print fread($handle, 1 * (1024 * 1024));
            ob_flush();
            flush();
        }
        fclose($handle);
    }

    /**
     *
     * @param int $index
     */
    private function _getOptionRow($index) {
        $cfg = cRegistry::getConfig();

        $tpl = cSmartyBackend::getInstance(true);

        // translations
        $tpl->assign('trans', array(
            'label' => Pifa::i18n('LABEL'),
            'value' => Pifa::i18n('VALUE')
        ));

        $tpl->assign('i', $index);

        // option
        $tpl->assign('option', array(
            'label' => '',
            'value' => ''
        ));

        $tpl->display($cfg['templates']['pifa_ajax_option_row']);
    }
}

?>
