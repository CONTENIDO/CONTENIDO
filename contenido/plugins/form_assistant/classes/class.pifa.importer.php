<?php

/**
 * PIFA form importer.
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
 * This class allows for exporting a PIFA form as XML.
 * The exported file contains the structure of the form and its fields.
 * Optionally the export file may contain the forms gathered data.
 *
 * Example usage:
 * <code>plugin_include('form_assistant', 'classes/class.pifa.importer.php');
 * $imp = new PifaImporter();
 * $imp->import($xml);</code>
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaImporter {

    /**
     * The XML reader that is used to import a XML file.
     *
     * @var cXmlReader
     */
    private $_reader;

    /**
     * PIFA form collection used to create new item.
     *
     * @var PifaFormCollection
     */
    private $_pifaFormColl;

    /**
     * PIFA field collection used to create new item.
     *
     * @var PifaFieldCollection
     */
    private $_pifaFieldColl;

    /**
     * Name of data table to create
     *
     * @var string
     */
    private $_tableName;

    /**
     * Create an instance.
     * Creates XML reader member instances.
     */
    public function __construct() {
        $this->_reader = new cXmlReader();
        $this->_pifaFormColl = new PifaFormCollection();
        $this->_pifaFieldColl = new PifaFieldCollection();
    }

    /**
     * @param string $_tableName
     */
    public function setTableName($_tableName) {
        $this->_tableName = $_tableName;
    }

    /**
     * Import the given XML.
     *
     * @param string $xml to import
     * @throws PifaException if XML could not be loaded
     * @throws PifaException if existance of table could not be determined
     * @throws PifaException if table already exists
     * @throws PifaException if table could not be created
     */
    public function import($xml) {

        // load XML
        if (!$this->_reader->loadXML($xml)) {
            throw new PifaException('XML could not be loaded');
        }

        // import form
        $formElem = $this->_reader->getXpathNode('/pifa/form');
        if (is_null($this->_tableName)) {
            $this->_tableName = $formElem->getAttribute('table');
        }
        $this->_checkTableName();
        $pifaForm = $this->_createPifaForm($formElem);

        // import fields
        $fieldElems = $this->_reader->getXpathNodeList('/pifa/form/field');
        foreach ($fieldElems as $fieldElem) {
            $pifaField = $this->_createPifaField($fieldElem, $pifaForm);
        }

        // create table
        $pifaForm->loadFields();
        $pifaForm->createTable('true' === $formElem->getAttribute('timestamp'));

        // import data
        $rowElems = $this->_reader->getXpathNodeList('/pifa/data/row');
        $db = cRegistry::getDb();
        foreach ($rowElems as $rowElem) {
            $rowPath = $rowElem->getNodePath();
            $fields = array();
            if ('true' == $formElem->getAttribute('timestamp')) {
                $fields['pifa_timestamp'] = $rowElem->getAttribute('timestamp');
            }
            $colElems = $this->_reader->getXpathNodeList($rowPath . '/col');
            foreach ($colElems as $colElem) {
                $fields[$colElem->getAttribute('name')] = $colElem->nodeValue;
            }
            $sql = $db->buildInsert($this->_tableName, $fields);
            $db->query($sql);
        }
    }

    /**
     * Create new PIFA form for current client and language.
     *
     * @param DOMElement $formElem to get data from
     * @return PifaForm
     */
    private function _createPifaForm(DOMElement $formElem) {
        return $this->_pifaFormColl->createNewItem(array(
            'idclient' => cRegistry::getClientId(),
            'idlang' => cRegistry::getLanguageId(),
            'name' => $formElem->getAttribute('name'),
            'data_table' => $this->_tableName,
            'method' => $formElem->getAttribute('method'),
            'with_timestamp' => (int) ('true' === $formElem->getAttribute('timestamp'))
        ));
    }

    /**
     * Create PIFA field for given PIFA form..
     *
     * @param DOMElement $formElem to create field for
     * @return PifaForm
     */
    private function _createPifaField(DOMElement $fieldElem, PifaForm $pifaForm) {

        // get XPATH of this element to access children
        $fieldPath = $fieldElem->getNodePath();

        // create PIFA field
        $data = array(
            'idform' => $pifaForm->get('idform'),
            'field_rank' => $fieldElem->getAttribute('rank'),
            'field_type' => $this->_getPifaFieldTypeId($fieldElem->getAttribute('type')),
            'column_name' => $fieldElem->getAttribute('column'),
            'obligatory' => (int) ('true' === $fieldElem->getAttribute('obligatory'))
        );

        // import default (optional)
        if ($fieldElem->hasAttribute('default')) {
            $data['default_value'] = $fieldElem->getAttribute('default');
        }

        // import label
        $label = $this->_reader->getXpathValue($fieldPath . '/label');
        $data['label'] = strip_tags($label);
        $labelElem = $this->_reader->getXpathNode($fieldPath . '/label');
        if ($labelElem) {
            $display = (int) ('true' === $labelElem->getAttribute('display'));
            $data['display_label'] = $display;
        }

        // import help (optional)
        if (0 < $this->_reader->countXpathNodes($fieldPath . '/help')) {
            $help = $this->_reader->getXpathValue($fieldPath . '/help');
            $help = $this->_unCdata($help);
            $data['help_text'] = $help;
        }

        // import error (optional)
        if (0 < $this->_reader->countXpathNodes($fieldPath . '/error')) {
            $error = $this->_reader->getXpathValue($fieldPath . '/error');
            $error = $this->_unCdata($error);
            $data['error_message'] = $error;
        }

        // import rule (optional)
        if (0 < $this->_reader->countXpathNodes($fieldPath . '/rule')) {
            $rule = $this->_reader->getXpathValue($fieldPath . '/rule');
            $rule = $this->_unCdata($rule);
            $data['rule'] = $rule;
        }

        // import classes
        $classElems = $this->_reader->getXpathNodeList($fieldPath . '/classes/class');
        $cssClass = array();
        foreach ($classElems as $classElem) {
            array_push($cssClass, $classElem->nodeValue);
        }
        $data['css_class'] = implode(',', $cssClass);

        // import options
        $optionsElem = $this->_reader->getXpathNode($fieldPath . '/options');
        if ($optionsElem) {
            if ($optionsElem->hasAttribute('source')) {
                $data['option_class'] = $optionsElem->getAttribute('source');
            }
            $optionElems = $this->_reader->getXpathNodeList($fieldPath . '/options/option');
            $optionLabels = $optionValues = array();
            foreach ($optionElems as $optionElem) {
                array_push($optionLabels, $optionElem->nodeValue);
                array_push($optionValues, $optionElem->getAttribute('value'));
            }
            $data['option_labels'] = implode(',', $optionLabels);
            $data['option_values'] = implode(',', $optionValues);
        }

        return $this->_pifaFieldColl->createNewItem($data);
    }

    /**
     */
    private function _checkTableName() {
        $db = cRegistry::getDb();
        $sql = '-- _checkTableName()
            show tables
                like "' . $db->escape($this->_tableName) . '"
            ;';
        $db->query($sql);
        if (0 < $db->numRows()) {
            throw new PifaDatabaseException("table $this->_tableName already exists");
        }
    }

    /**
     * Map a PIFA field name to a numeric ID that is used to identify the
     * appropriate database record.
     *
     * @param string $fieldTypeName to map
     */
    private function _getPifaFieldTypeId($fieldTypeName) {
        $fieldTypeName = strtoupper($fieldTypeName);
        $fieldTypeIds = array(
            'INPUTTEXT' => PifaField::INPUTTEXT,
            'TEXTAREA' => PifaField::TEXTAREA,
            'INPUTPASSWORD' => PifaField::INPUTPASSWORD,
            'INPUTRADIO' => PifaField::INPUTRADIO,
            'INPUTCHECKBOX' => PifaField::INPUTCHECKBOX,
            'SELECT' => PifaField::SELECT,
            'SELECTMULTI' => PifaField::SELECTMULTI,
            'DATEPICKER' => PifaField::DATEPICKER,
            'INPUTFILE' => PifaField::INPUTFILE,
            'PROCESSBAR' => PifaField::PROCESSBAR,
            'SLIDER' => PifaField::SLIDER,
            // 'CAPTCHA' => PifaField::CAPTCHA,
            'BUTTONSUBMIT' => PifaField::BUTTONSUBMIT,
            'BUTTONRESET' => PifaField::BUTTONRESET,
            // @deprecated use PifaField::BUTTON instead
            'BUTTONBACK' => PifaField::BUTTONBACK,
            'BUTTON' => PifaField::BUTTON,
            'MATRIX' => PifaField::MATRIX,
            'PARAGRAPH' => PifaField::PARA,
            'INPUTHIDDEN' => PifaField::INPUTHIDDEN,
            'FIELDSET_BEGIN' => PifaField::FIELDSET_BEGIN,
            'FIELDSET_END' => PifaField::FIELDSET_END,
            'BUTTONIMAGE' => PifaField::BUTTONIMAGE
        );
        $fieldTypeId = $fieldTypeIds[$fieldTypeName];
        return $fieldTypeId;
    }

    /**
     * Remove CDATA syntax from a string using a regular expression.
     *
     * @param string $str to handle
     * @throws PifaException
     * @return string
     */
    private function _unCdata($str) {
        $regex = '/<\!\[CDATA\[(.*)\]\]>/is';
        $match = preg_replace($regex, '$1', $str);
        if (is_null($match)) {
            throw new PifaException("could not _unCdata() '$str'");
        }
        return (string) $match;
    }
}
