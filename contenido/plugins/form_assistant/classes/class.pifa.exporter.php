<?php

/**
 * This file contains the PifaExporter class.
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
 * Optionally the export file may contain the forms gathered data from its data
 * table.
 *
 * Example usage:
 * <code>plugin_include('form_assistant', 'classes/class.pifa.exporter.php');
 * $exp = new PifaExporter(new PifaForm($idform));
 * $xml = $exp->export(false);
 * Util::logDump($xml);</code>
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaExporter {

    /**
     * The PIFA form to export.
     *
     * @var PifaForm
     */
    private $_form;

    /**
     * The XML writer that is used to create the export XML file.
     *
     * @var cXmlWriter
     */
    private $_writer;

    /**
     * Create an instance.
     * Creates PIFA form and XML writer member instances.
     *
     * @param PifaForm $pifaForm to export
     */
    public function __construct(PifaForm $pifaForm) {

        // aggregate PIFA form
        $this->_form = $pifaForm;

        // build XML writer
        $this->_writer = new cXmlWriter();
    }

    /**
     * Create and return export XML of PIFA form and its fields.
     * Optionally includes gathered form data.
     *
     * @param bool $addData if form data should be included in export
     * @return string created XML
     */
    public function export($addData) {

        // add pifa (root) element
        $pifa = $this->_writer->addElement('pifa');

        // add form & fields structure
        $this->_addForm($pifa, $this->_form);

        // optionally add gathered data
        if ($addData) {
            $this->_addData($pifa, $this->_form);
        }

        // create XML
        $xml = $this->_writer->saveToString();

        // return XML
        return $xml;
    }

    /**
     * Adds a "form" element containing one "field" elements for each defined
     * PIFA field.
     *
     * @param DOMElement $parent to add element to
     * @param PifaForm $pifaForm to create XML for
     */
    private function _addForm(DOMElement $parent, PifaForm $pifaForm) {

        // build attributes
        $attr = [];
        $attr['name'] = $pifaForm->get('name');
        $attr['table'] = $pifaForm->get('data_table');
        $attr['method'] = cString::toLowerCase($pifaForm->get('method'));
        if ($pifaForm->get('with_timestamp')) {
            $attr['timestamp'] = 'true';
        } else {
            $attr['timestamp'] = 'false';
        }
        $attr = array_filter($attr);

        // add element
        $formElem = $this->_writer->addElement('form', '', $parent, $attr);

        // add child elements
        foreach ($this->_form->getFields() as $pifaField) {
            $this->_addField($formElem, $pifaField);
        }
    }

    /**
     * Adds a "field" element optionally containing "label", "help", "error",
     * "rule", "classes" and "options" elements.
     *
     * @param DOMElement $parent to add element to
     * @param PifaField $pifaField to create XML for
     */
    private function _addField(DOMElement $parent, PifaField $pifaField) {

        // build attributes
        $attr = [];
        $attr['rank'] = $pifaField->get('field_rank');
        $attr['type'] = $this->_getFieldTypeName($pifaField->get('field_type'));
        $attr['column'] = $pifaField->get('column_name');
        $attr['default'] = $pifaField->get('default_value');
        if ($pifaField->get('obligatory')) {
            $attr['obligatory'] = 'true';
        } else {
            $attr['obligatory'] = 'false';
        }
        $attr = array_filter($attr);

        // add element
        $fieldElem = $this->_writer->addElement('field', '', $parent, $attr);

        // add child elements
        $this->_addLabel($fieldElem, $pifaField);
        $this->_addHelp($fieldElem, $pifaField);
        $this->_addError($fieldElem, $pifaField);
        $this->_addRule($fieldElem, $pifaField);
        $this->_addClasses($fieldElem, $pifaField);
        $this->_addOptions($fieldElem, $pifaField);
    }

    /**
     * Adds an optional "label" element.
     *
     * @param DOMElement $parent to add element to
     * @param PifaField $pifaField to create XML for
     */
    private function _addLabel(DOMElement $parent, PifaField $pifaField) {

        // get value
        $value = strip_tags($pifaField->get('label'));
        if (0 === cString::getStringLength(trim($value))) {
            return;
        }

        // build attributes
        $attr = [];
        if ($pifaField->get('display_label')) {
            $attr['display'] = 'true';
        } else {
            $attr['display'] = 'false';
        }
        $attr = array_filter($attr);

        // add element
        $this->_writer->addElement('label', $value, $parent, $attr);
    }

    /**
     * Adds an optional "help" element.
     * As the help text is free text it will be stored as CDATA.
     *
     * @param DOMElement $parent to add element to
     * @param PifaField $pifaField to create XML for
     */
    private function _addHelp(DOMElement $parent, PifaField $pifaField) {

        // get value
        $value = $pifaField->get('help_text');
        if (0 === cString::getStringLength(trim($value))) {
            return;
        }

        // add element
        $this->_writer->addElement('help', $value, $parent, [], true);
    }

    /**
     * Adds an optional "error" element.
     * As the error message is free text it will be stored as CDATA.
     *
     * @param DOMElement $parent to add element to
     * @param PifaField $pifaField to create XML for
     */
    private function _addError(DOMElement $parent, PifaField $pifaField) {

        // get value
        $value = $pifaField->get('error_message');
        if (0 === cString::getStringLength(trim($value))) {
            return;
        }

        // add element
        $this->_writer->addElement('error', $value, $parent, [], true);
    }

    /**
     * Adds an optional "rule" element.
     * As the rule is a regular expression it will be stored as CDATA.
     *
     * @param DOMElement $parent to add element to
     * @param PifaField $pifaField to create XML for
     */
    private function _addRule(DOMElement $parent, PifaField $pifaField) {

        // get value
        $value = $pifaField->get('rule');
        if (0 === cString::getStringLength(trim($value))) {
            return;
        }

        // add element
        $this->_writer->addElement('rule', $value, $parent, [], true);
    }

    /**
     * Adds an optional "classes" element containing one "class" element for
     * each defined class.
     *
     * @param DOMElement $parent to add element to
     * @param PifaField $pifaField to create XML for
     */
    private function _addClasses(DOMElement $parent, PifaField $pifaField) {
        $cssClasses = $pifaField->get('css_class');
        $cssClasses = trim($cssClasses);
        $cssClasses = explode(',', $cssClasses);
        $cssClasses = array_filter($cssClasses);

        // skip classes element if no classes were defined
        if (empty($cssClasses)) {
            return;
        }

        // add element
        $classesElem = $this->_writer->addElement('classes', '', $parent);

        // add child elements
        foreach ($cssClasses as $value) {
            $this->_writer->addElement('class', $value, $classesElem);
        }
    }

    /**
     * Adds an optional "options" element containing one "option" element for
     * each defined option.
     *
     * @param DOMElement $parent to add element to
     * @param PifaField $pifaField to create XML for
     */
    private function _addOptions(DOMElement $parent, PifaField $pifaField) {

        // add child elements
        $optionLabels = $pifaField->get('option_labels');
        $optionLabels = trim($optionLabels);
        $optionLabels = explode(',', $optionLabels);
        $optionLabels = array_filter($optionLabels);

        $optionValues = $pifaField->get('option_values');
        $optionValues = trim($optionValues);
        $optionValues = explode(',', $optionValues);
        $optionValues = array_filter($optionValues);

        $count = min([
            count($optionLabels),
            count($optionValues),
        ]);

        // build attributes
        $attr = [];
        $attr['source'] = $pifaField->get('option_class');
        $attr = array_filter($attr);

        if (0 === $count + count($attr)) {
            return;
        }

        // add element
        $optionsElem = $this->_writer->addElement('options', $pifaField->get('rule'), $parent, $attr);

        for ($i = 0; $i < $count; $i++) {

            // build attributes
            $attr = [];
            $attr['value'] = $optionValues[$i];
            $attr = array_filter($attr);

            // add element
            $this->_writer->addElement('option', $optionLabels[$i], $optionsElem, $attr);
        }
    }

    /**
     * Adds an optional "data" element containing one "row" element for each
     * record in the forms data table (gathered data).
     * If the for has either no fields or its data table has no records, no
     * "data" element will be added.
     *
     * @param DOMElement $parent to add element to
     * @param PifaForm $pifaForm to create XML for
     */
    private function _addData(DOMElement $parent, PifaForm $pifaForm) {

        // get fields from form
        $fields = $pifaForm->getFields();
        if (empty($fields)) {
            return;
        }

        // get all column names as array
        $columns = [];
        /** @var PifaField $pifaField */
        foreach ($fields as $pifaField) {
            $columns[] = $pifaField->get('column_name');
        }
        $columns = array_filter($columns);

        // get data from form
        $data = $pifaForm->getData();
        if (empty($data)) {
            return;
        }

        // add element (if form has fields and data)
        $dataElem = $this->_writer->addElement('data', '', $parent);

        // add data rows
        foreach ($data as $row) {

            // build attributes
            $attr = [];
            if (true === (bool) $pifaForm->get('with_timestamp')) {
                $attr['timestamp'] = $row['pifa_timestamp'];
            }
            $attr = array_filter($attr);

            // add element
            $rowElem = $this->_writer->addElement('row', '', $dataElem, $attr);

            // append value
            foreach ($columns as $index => $columnName) {

                // build attributes
                $attr = [];
                $attr['name'] = $columnName;
                $attr = array_filter($attr);

                // add element
                $this->_writer->addElement('col', $row[$columnName], $rowElem, $attr, true);
            }
        }
    }

    /**
     * Map a numeric PIFA field ID to a name that may be used as translatable
     * token (i18n).
     *
     * @param int $fieldTypeId to map
     *
     * @return string
     */
    private function _getFieldTypeName($fieldTypeId) {
        $fieldTypeNames = [
            PifaField::INPUTTEXT => 'INPUTTEXT',
            PifaField::TEXTAREA => 'TEXTAREA',
            PifaField::INPUTPASSWORD => 'INPUTPASSWORD',
            PifaField::INPUTRADIO => 'INPUTRADIO',
            PifaField::INPUTCHECKBOX => 'INPUTCHECKBOX',
            PifaField::SELECT => 'SELECT',
            PifaField::SELECTMULTI => 'SELECTMULTI',
            PifaField::DATEPICKER => 'DATEPICKER',
            PifaField::INPUTFILE => 'INPUTFILE',
            PifaField::PROCESSBAR => 'PROCESSBAR',
            PifaField::SLIDER => 'SLIDER',
            // PifaField::CAPTCHA => 'CAPTCHA',
            PifaField::BUTTONSUBMIT => 'BUTTONSUBMIT',
            PifaField::BUTTONRESET => 'BUTTONRESET',
            PifaField::BUTTON => 'BUTTON',
            PifaField::MATRIX => 'MATRIX',
            PifaField::PARA => 'PARAGRAPH',
            PifaField::INPUTHIDDEN => 'INPUTHIDDEN',
            PifaField::FIELDSET_BEGIN => 'FIELDSET_BEGIN',
            PifaField::FIELDSET_END => 'FIELDSET_END',
            PifaField::BUTTONIMAGE => 'BUTTONIMAGE',
        ];
        $fieldTypeName = $fieldTypeNames[$fieldTypeId];
        return cString::toLowerCase($fieldTypeName);
    }
}
