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

// update con_pifa_field set field_type=1;
// update con_pifa_field set field_type=6 where idfield IN (49, 54, 55, 56);
// update con_pifa_field set field_type=12 where idfield IN (58);
// update con_pifa_field set field_type=4 where idfield IN (52);

// INSERT INTO con_pifa_form
// (`idform`, `idclient`, `idlang`, `name`, `data_table`, `method`)
// VALUES
// ('3', '1', '1', 'form_test', 'con_pifo_form_test', 'post');

// INSERT INTO con_pifa_field
// (idform, field_rank, field_type, column_name, label, default_value,
// option_labels, option_values, help_text, obligatory, rule, error_message)
// VALUES
// ('3', '1', '1', 'cnINPUTTEXT', 'lbINPUTTEXT', 'dvINPUTTEXT',
// 'olINPUTTEXT1,olINPUTTEXT2', 'ovINPUTTEXT1,ovINPUTTEXT2', 'htINPUTTEXT', '1',
// NULL, 'emINPUTTEXT'),
// ('3', '2', '2', 'cnTEXTAREA', 'lbTEXTAREA', 'dvTEXTAREA',
// 'olTEXTAREA1,olTEXTAREA2', 'ovTEXTAREA1,ovTEXTAREA2', 'htTEXTAREA', '1',
// NULL, 'emTEXTAREA'),
// ('3', '3', '3', 'cnINPUTPASSWORD', 'lbINPUTPASSWORD', 'dvINPUTPASSWORD',
// 'olINPUTPASSWORD1,olINPUTPASSWORD2', 'ovINPUTPASSWORD1,ovINPUTPASSWORD2',
// 'htINPUTPASSWORD', '1', NULL, 'emINPUTPASSWORD'),
// ('3', '4', '4', 'cnINPUTRADIO', 'lbINPUTRADIO', 'dvINPUTRADIO',
// 'olINPUTRADIO1,olINPUTRADIO2', 'ovINPUTRADIO1,ovINPUTRADIO2', 'htINPUTRADIO',
// '1', NULL, 'emINPUTRADIO'),
// ('3', '5', '5', 'cnINPUTCHECKBOX', 'lbINPUTCHECKBOX', 'dvINPUTCHECKBOX',
// 'olINPUTCHECKBOX1,olINPUTCHECKBOX2', 'ovINPUTCHECKBOX1,ovINPUTCHECKBOX2',
// 'htINPUTCHECKBOX', '1', NULL, 'emINPUTCHECKBOX'),
// ('3', '6', '6', 'cnSELECT', 'lbSELECT', 'dvSELECT', 'olSELECT1,olSELECT2',
// 'ovSELECT1,ovSELECT2', 'htSELECT', '1', NULL, 'emSELECT'),
// ('3', '7', '7', 'cnSELECTMULTI', 'lbSELECTMULTI', 'dvSELECTMULTI',
// 'olSELECTMULTI1,olSELECTMULTI2', 'ovSELECTMULTI1,ovSELECTMULTI2',
// 'htSELECTMULTI', '1', NULL, 'emSELECTMULTI'),
// ('3', '8', '8', 'cnDATEPICKER', 'lbDATEPICKER', 'dvDATEPICKER',
// 'olDATEPICKER1,olDATEPICKER2', 'ovDATEPICKER1,ovDATEPICKER2', 'htDATEPICKER',
// '1', NULL, 'emDATEPICKER'),
// ('3', '9', '9', 'cnINPUTFILE', 'lbINPUTFILE', 'dvINPUTFILE',
// 'olINPUTFILE1,olINPUTFILE2', 'ovINPUTFILE1,ovINPUTFILE2', 'htINPUTFILE', '1',
// NULL, 'emINPUTFILE'),
// ('3', '10', '10', 'cnPROCESSBAR', 'lbPROCESSBAR', 'dvPROCESSBAR',
// 'olPROCESSBAR1,olPROCESSBAR2', 'ovPROCESSBAR1,ovPROCESSBAR2', 'htPROCESSBAR',
// '1', NULL, 'emPROCESSBAR'),
// ('3', '11', '11', 'cnSLIDER', 'lbSLIDER', 'dvSLIDER', 'olSLIDER1,olSLIDER2',
// 'ovSLIDER1,ovSLIDER2', 'htSLIDER', '1', NULL, 'emSLIDER'),
// ('3', '12', '12', 'cnCAPTCHA', 'lbCAPTCHA', 'dvCAPTCHA',
// 'olCAPTCHA1,olCAPTCHA2', 'ovCAPTCHA1,ovCAPTCHA2', 'htCAPTCHA', '1', NULL,
// 'emCAPTCHA'),
// ('3', '13', '13', 'cnBUTTONSUBMIT', 'lbBUTTONSUBMIT', 'dvBUTTONSUBMIT',
// 'olBUTTONSUBMIT1,olBUTTONSUBMIT2', 'ovBUTTONSUBMIT1,ovBUTTONSUBMIT2',
// 'htBUTTONSUBMIT', '1', NULL, 'emBUTTONSUBMIT'),
// ('3', '14', '14', 'cnBUTTONRESET', 'lbBUTTONRESET', 'dvBUTTONRESET',
// 'olBUTTONRESET1,olBUTTONRESET2', 'ovBUTTONRESET1,ovBUTTONRESET2',
// 'htBUTTONRESET', '1', NULL, 'emBUTTONRESET'),
// ('3', '15', '15', 'cnBUTTONBACK', 'lbBUTTONBACK', 'dvBUTTONBACK',
// 'olBUTTONBACK1,olBUTTONBACK2', 'ovBUTTONBACK1,ovBUTTONBACK2', 'htBUTTONBACK',
// '1', NULL, 'emBUTTONBACK'),
// ('3', '16', '16', 'cnMATRIX', 'lbMATRIX', 'dvMATRIX', 'olMATRIX1,olMATRIX2',
// 'ovMATRIX1,ovMATRIX2', 'htMATRIX', '1', NULL, 'emMATRIX');

/**
 *
 * @author marcus.gnass
 */
class PifaFieldCollection extends ItemCollection {

    /**
     *
     * @param mixed $where clause to be used to load items or false
     */
    public function __construct($where = false) {

        $cfg = cRegistry::getConfig();

        parent::__construct($cfg['tab']['pifa_field'], 'idfield');

        $this->_setItemClass('PifaField');

        if (false !== $where) {
            $this->select($where);
        }

    }

    /**
     * Reorders a forms fields according to the given.
     *
     * @param int $idform
     * @param string $idfields containing a CSV list of idfield as integer
     */
    public static function reorder($idform, $idfields) {

        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();

        $sql = "-- PifaFieldCollection::reorder()
		    UPDATE
			    " . $cfg['tab']['pifa_field'] . "
		    SET
			    field_rank = FIND_IN_SET(idfield, '$idfields')
		    WHERE
		    	idform = $idform
		    ;";

        $db->query($sql);

    }

}

/**
 * contains meta data of a PIFA field
 *
 * @author marcus.gnass
 */
class PifaField extends Item {

    /**
     * size to use for VARCHAR fields
     * Remember: the maximum row size for the used table type, not counting
     * BLOBs, is 65535.
     * TODO PIFA should be able to calculate the size for one record by the size
     * of its fields and handle it accordingly.
     *
     * @var int
     */
    const VARCHAR_SIZE = 255;

    /**
     *
     * @var int
     */
    const INPUTTEXT = 1;

    /**
     *
     * @var int
     */
    const TEXTAREA = 2;

    /**
     *
     * @var int
     */
    const INPUTPASSWORD = 3;

    /**
     *
     * @var int
     */
    const INPUTRADIO = 4;

    /**
     *
     * @var int
     */
    const INPUTCHECKBOX = 5;

    /**
     *
     * @var int
     */
    const SELECT = 6;

    /**
     *
     * @var int
     */
    const SELECTMULTI = 7;

    /**
     *
     * @var int
     */
    const DATEPICKER = 8;

    /**
     *
     * @var int
     */
    const INPUTFILE = 9;

    /**
     *
     * @var int
     */
    const PROCESSBAR = 10;

    /**
     *
     * @var int
     */
    const SLIDER = 11;

    /**
     *
     * @var int
     */
    const CAPTCHA = 12;

    /**
     *
     * @var int
     */
    const BUTTONSUBMIT = 13;

    /**
     *
     * @var int
     */
    const BUTTONRESET = 14;

    /**
     *
     * @var int
     */
    const BUTTONBACK = 15;

    /**
     *
     * @var int
     */
    const MATRIX = 16;

    /**
     * A text to be displayed between form fields.
     * It's no form field on its own but should be handled like one.
     *
     * @var int
     */
    const PARA = 17;

    /**
     *
     * @var int
     */
    const INPUTHIDDEN = 18;

    /**
     *
     * @var int
     */
    const FIELDSET_BEGIN = 19;

    /**
     *
     * @var int
     */
    const FIELDSET_END = 20;

    /**
     *
     * @var mixed
     */
    private $_value = NULL;

    /**
     *
     * @var array
     */
    private $_file = NULL;

    /**
     *
     * @param mixed $id ID of item to be loaded or false
     */
    public function __construct($id = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct($cfg['tab']['pifa_field'], 'idfield');
        $this->setFilters(array(), array());
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Rule has to be stripslashed to allow regular expressions with
     * backslashes.
     *
     * @see Item::getField()
     */
    function getField($field) {
        $value = parent::getField($field);
        if ('rule' === $field) {
            $value = stripslashes($value);
        }
        return $value;
    }

    /**
     * Getter for protected prop.
     */
    public function getLastError() {
        return $this->lasterror;
    }

    /**
     * Returns this fields stored value.
     *
     * @return mixed the $_value
     */
    public function getValue() {
        return $this->_value;
    }

    /**
     *
     * @param mixed $_value
     */
    public function setValue($value) {
        $this->_value = $value;
    }

    /**
     * keys: name, tmp_name
     *
     * @return array the $_file
     */
    public function getFile() {
        return $this->_file;
    }

    /**
     *
     * @param array $_file
     */
    public function setFile(array $_file) {
        $this->_file = $_file;
    }

    /**
     *
     * @throws PifaValidationException if an error occured
     */
    public function validate() {

        // get value
        $values = $this->getValue();
        if (NULL === $values) {
            $values = $this->get('default_value');
        }

        // value could be an array (for form fields with suffix '[]')
        // so make it an array anyway ...
        if (!is_array($values)) {
            $values = array(
                $values
            );
        }

        foreach ($values as $value) {

            if (self::CAPTCHA == $this->get('field_type')) {
                // check for captcha
                $securimage = new Securimage(array(
                    'session_name' => cRegistry::getClientId() . 'frontend'
                ));
                $isValid = $securimage->check($value);

            } else if (1 === cSecurity::toInteger($this->get('obligatory')) && 0 === strlen($value)) {
                // check for obligatory & rule
                $isValid = false;

            } else if (0 < strlen($this->get('rule')) && in_array(preg_match($this->get('rule'), $value), array(
                false,
                0
            ))) {
                // check for rule
                $isValid = false;

            } else {
                $isValid = true;

            }

            // throw error
            if (true !== $isValid) {
                $error_message = $this->get('error_message');
                if (NULL === $error_message) {
                    $error_message = 'invalid data';
                }
                throw new PifaValidationException(array(
                    $this->get('column_name') => $error_message
                ));
            }

        }

    }

    /**
     * Returns HTML for this form that should be displayed in frontend.
     *
     * @param array $errors to be displayed for form field
     * @return string
     */
    public function toHtml(array $errors = NULL) {

        switch (cSecurity::toInteger($this->get('field_type'))) {

            case self::FIELDSET_BEGIN:

                $label = $this->get('label');
                return "\n\t<fieldset><legend>$label</legend>";
                break;

            case self::FIELDSET_END:

                return "\n\t</fieldset>";
                break;

            default:

                $error = NULL;
                if (array_key_exists($this->get('column_name'), $errors)) {
                    $error = $errors[$this->get('column_name')];
                }

                // build HTML content
                $content = array();
                try {
                    $content[] = $this->_getElemLabel();
                    $content[] = $this->_getElemField();
                    $content[] = $this->_getElemHelp();
                    $content[] = $this->_getElemScript();
                    if (NULL !== $error) {
                        $content[] = new cHTMLParagraph($error, 'pifa-error-message');
                    }
                } catch (NotImplementedException $e) {
                    return NULL; // PASS // warning?
                }

                $content = array_filter($content);
                if (empty($content)) {
                    return NULL; // PASS // warning?
                }

                // CSS class for surrounding division
                $class = 'pifa-field-' . $this->get('field_type');
                // optional obligatory class for field
                if (0 < strlen(trim($this->get('css_class')))) {
                    $class .= ' ' . implode(' ', explode(',', $this->get('css_class')));
                }
                // optional obligatory class for field
                if (true === (bool) $this->get('obligatory')) {
                    $class .= ' pifa-obligatory';
                }
                // optional error class for field
                if (NULL !== $error) {
                    $class .= ' pifa-error';
                }

                // ID for surrounding division
                $id = 'pifa-field-' . $this->get('idfield');

                // surrounding division
                $div = new cHTMLDiv($content, $class, $id);

                return "\n\t" . $div->render();
                break;

        }

    }

    /**
     */
    public function _getElemLabel() {

        // get field data
        $idfield = cSecurity::toInteger($this->get('idfield'));
        $fieldType = cSecurity::toInteger($this->get('field_type'));
        $label = $this->get('label');

        if (NULL === $label) {
            return NULL;
        }

        // buttons have no external label
        // the label is instead used as element value (see _getElemField())
        if (in_array($fieldType, array(
            self::BUTTONSUBMIT,
            self::BUTTONRESET,
            self::BUTTONBACK
        ))) {
            return NULL;
        }

        // obligatory fields have an additional ' *'
        if (true === (bool) $this->get('obligatory')) {
            $label .= ' *';
        }

        // add span to request new captcha code
        // if (self::CAPTCHA===$fieldType) {
        // $label .= '<br><br><span style="cursor: pointer;">New Captcha
        // Code</span>';
        // }

        $elemLabel = new cHTMLLabel($label, 'pifa-field-elm-' . $idfield, 'pifa-field-lbl');
        // set ID (workaround: remove ID first!)
        $elemLabel->removeAttribute('id');

        return $elemLabel;

    }

    /**
     * Creates the HTML for a form field.
     *
     * The displayed value of a form field can be set via a GET param whose name
     * has to be the fields column name which is set if the form is displayed
     * for the first time and user hasn't entered another value.
     *
     * @param boolean $error if field elem should be displayed as erroneous
     * @throws NotImplementedException if field type is not implemented
     * @return cHTMLTextbox cHTMLTextarea cHTMLPasswordbox cHTMLSpan
     *         cHTMLSelectElement NULL cHTMLButton
     */
    public function _getElemField() {

        // get field data

        $idfield = cSecurity::toInteger($this->get('idfield'));

        $fieldType = cSecurity::toInteger($this->get('field_type'));

        $columnName = $this->get('column_name');

        $label = $this->get('label');

        // get option labels & values
        // either from field or from external data source class
        $optionClass = $this->get('option_class');
        if (0 === strlen(trim($optionClass))) {
            $optionLabels = $this->get('option_labels');
            if (NULL !== $optionLabels) {
                $optionLabels = explode(',', $optionLabels);
            }
            $optionValues = $this->get('option_values');
            if (NULL !== $optionValues) {
                $optionValues = explode(',', $optionValues);
            }
        } else {
            $filename = Pifa::fromCamelCase($optionClass);
            $filename = "extensions/class.pifa.$filename.php";
            if (false === file_exists(Pifa::getPath() . $filename)) {
                throw new PifaException('missing external options datasource file ' . $filename);
            }
            plugin_include(Pifa::getName(), $filename);
            if (false === class_exists($optionClass)) {
                throw new PifaException('missing external options datasource class ' . $optionClass);
            }
            $dataSource = new $optionClass();
            $optionLabels = $dataSource->getOptionLabels();
            $optionValues = $dataSource->getOptionValues();
        }

        // ID for field & FOR for label
        $id = 'pifa-field-elm-' . $idfield;

        // get current value
        $value = $this->getValue();

        // if no current value is given
        if (NULL === $value) {
            // the fields default value is used
            $value = $this->get('default_value');
            // which could be overwritten by a GET param
            if (array_key_exists($columnName, $_GET)) {
                $value = $_GET[$columnName];
                // try to prevent XSS ... the lazy way ...
                $value = htmlentities($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
            }
        }

        switch ($fieldType) {

            case self::INPUTTEXT:

                $elemField = new cHTMLTextbox($columnName);
                // set ID (workaround: remove ID first!)
                $elemField->removeAttribute('id')->setID($id);
                if (NULL !== $value) {
                    $elemField->setValue($value);
                }
                break;

            case self::TEXTAREA:

                $elemField = new cHTMLTextarea($columnName);
                // set ID (workaround: remove ID first!)
                $elemField->removeAttribute('id')->setID($id);
                if (NULL !== $value) {
                    $elemField->setValue($value);
                }
                break;

            case self::INPUTPASSWORD:

                $elemField = new cHTMLPasswordbox($columnName);
                // set ID (workaround: remove ID first!)
                $elemField->removeAttribute('id')->setID($id);
                if (NULL !== $value) {
                    $elemField->setValue($value);
                }
                break;

            case self::INPUTRADIO:
            case self::INPUTCHECKBOX:

                $count = min(array(
                    count($optionLabels),
                    count($optionValues)
                ));
                $tmpHtml = '';
                for ($i = 0; $i < $count; $i++) {
                    if (self::INPUTRADIO === $fieldType) {
                        $elemField = new cHTMLRadiobutton($columnName, $optionValues[$i]);
                    } else if (self::INPUTCHECKBOX === $fieldType) {
                        $elemField = new cHTMLCheckbox($columnName . '[]', $optionValues[$i]);
                    }
                    if (!is_array($value)) {
                        $value = explode(',', $value);
                    }
                    $elemField->setChecked(in_array($optionValues[$i], $value));
                    $elemField->setLabelText($optionLabels[$i]);
                    $tmpHtml .= $elemField->render();
                }
                $elemField = new cHTMLSpan($tmpHtml);
                break;

            case self::SELECT:
            case self::SELECTMULTI:

                $elemField = new cHTMLSelectElement($columnName);

                // set ID (workaround: remove ID first!)
                $elemField->removeAttribute('id')->setID($id);
                $autofill = array();
                $count = min(array(
                    count($optionLabels),
                    count($optionValues)
                ));

                for ($i = 0; $i < $count; $i++) {
                    $autofill[$optionValues[$i]] = $optionLabels[$i];
                }

                $elemField->autoFill($autofill);
                $elemField->setDefault($value);

                break;

            case self::DATEPICKER:

                // hidden field to post date in generic date format
                $hiddenField = new cHTMLHiddenField($columnName);
                $hiddenField->removeAttribute('id')->setID($id . '-hidden');
                if (NULL !== $value) {
                    $hiddenField->setValue($value);
                }

                // textbox to display date in localized date format
                $textbox = new cHTMLTextbox($columnName . '_visible');
                // set ID (workaround: remove ID first!)
                $textbox->removeAttribute('id')->setID($id);

                // surrounding div
                $elemField = new cHTMLDiv(array(
                    $hiddenField,
                    $textbox
                ));

                break;

            case self::INPUTFILE:

                $elemField = new cHTMLUpload($columnName);
                // set ID (workaround: remove ID first!)
                $elemField->removeAttribute('id')->setID($id);
                break;

            case self::PROCESSBAR:

                $elemField = NULL;
                // TODO PROCESSBAR is NYI
                // $elemField = new cHTML();
                break;

            case self::SLIDER:

                $elemField = NULL;
                // TODO SLIDER is NYI
                // $elemField = new cHTML();
                break;

            case self::CAPTCHA:

                // input
                $elemField = new cHTMLTextbox($columnName);
                // set ID (workaround: remove ID first!)
                $elemField->removeAttribute('id')->setID($id);
                if (NULL !== $value) {
                    $elemField->setValue($value);
                }

                // surrounding div
                // img src (front_content.php?securimage) will be caught by
                // Pifa::afterLoadPlugins
                $elemField = new cHTMLDiv(array(
                    new cHTMLImage('front_content.php?securimage'),
                    $elemField
                ));

                break;

            case self::BUTTONSUBMIT:
            case self::BUTTONRESET:
            case self::BUTTONBACK:

                $modes = array(
                    self::BUTTONSUBMIT => 'submit',
                    self::BUTTONRESET => 'reset',
                    self::BUTTONBACK => 'button'
                );
                $elemField = new cHTMLButton($columnName);
                // set ID (workaround: remove ID first!)
                $elemField->removeAttribute('id')->setID($id);
                if (NULL !== $label) {
                    $elemField->setTitle($label);
                } else {
                    $elemField->setTitle($modes[$fieldType]);
                }
                $elemField->setMode($modes[$fieldType]);
                break;

            case self::MATRIX:

                $elemField = NULL;
                // TODO MATRIX is NYI
                // $elemField = new cHTML();
                break;

            case self::PARA:
                $elemField = NULL;
                // TODO PARA is NYI
                // $elemField = new cHTML();
                break;

            case self::INPUTHIDDEN:
                $elemField = new cHTMLHiddenField($columnName);
                // set ID (workaround: remove ID first!)
                $elemField->removeAttribute('id')->setID($id);
                if (NULL !== $value) {
                    $elemField->setValue($value);
                }
                break;

            default:

                throw new NotImplementedException('field type ' . $fieldType . ' is not implemented');

        }

        return $elemField;

    }

    /**
     */
    public function _getElemHelp() {

        $helpText = $this->get('help_text');

        $p = NULL;
        if (0 < strlen($helpText)) {
            $p = new cHTMLParagraph($helpText, 'pifa-field-help');
        }

        return $p;

    }

    /**
     */
    public function _getElemScript() {

        // ID for field & FOR for label
        $idfield = cSecurity::toInteger($this->get('idfield'));
        $fieldType = cSecurity::toInteger($this->get('field_type'));

        switch ($fieldType) {
            case self::DATEPICKER:
                $sel = '#pifa-field-elm-' . $idfield;
                // dateFormat: 'yy-mm-dd', // could be different
                // altFormat as ISO_8601
                $script = "jQuery(function(){jQuery('$sel').datepicker({
	                altFormat: 'yy-mm-dd',
	                altField: '$sel-hidden'
                });});";
                break;
            case self::CAPTCHA:
                $sel = '#pifa-field-' . $idfield . ' label';
                $script = "jQuery(function(){\n";
                // implement captcha reload on click
                $script .= "jQuery('$sel').click(function (e) {\n";
                $script .= "e.preventDefault();\n";
                $script .= "var url = 'front_content.php?securimage&' + Math.random();\n";
                $script .= "jQuery(this).parent().find('img').attr('src', url);\n";
                $script .= "});\n";
                // append 'New Captcha Code' to label
                $script .= "jQuery('$sel').append('<br/><br/><span style=\"cursor:pointer\">New Captcha Code</span>');";
                $script .= "});\n";
                break;
            default:
                $script = '';
        }

        $elemScript = NULL;
        if (0 < strlen($script)) {
            $elemScript = new cHTMLScript();
            $elemScript->setContent($script);
        }

        return $elemScript;

    }

    /**
     * Returns an array containing all field type ids.
     *
     * @return array
     */
    public static function getFieldTypeIds() {

        return array(
            self::INPUTTEXT,
            self::TEXTAREA,
            self::INPUTPASSWORD,
            self::INPUTRADIO,
            self::INPUTCHECKBOX,
            self::SELECT,
            self::SELECTMULTI,
            self::DATEPICKER,
            self::INPUTFILE,
            self::PROCESSBAR,
            self::SLIDER,
            self::CAPTCHA,
            self::BUTTONSUBMIT,
            self::BUTTONRESET,
            self::BUTTONBACK,
            // self::MATRIX,
            self::PARA,
            self::INPUTHIDDEN,
            self::FIELDSET_BEGIN,
            self::FIELDSET_END
        );

    }

    /**
     * Return this fields type name.
     *
     * @param int $fieldType
     * @return string
     */
    public function getMyFieldTypeName() {

        return self::getFieldTypeName($this->get('field_type'));

    }

    /**
     * Return the field type name for the given field type id.
     *
     * @param int $fieldType
     * @return string
     */
    public static function getFieldTypeName($fieldTypeId) {

        $fieldTypeId = cSecurity::toInteger($fieldTypeId);

        // TODO add proper translations
        $fieldTypeName = array(
            self::INPUTTEXT => Pifa::i18n('INPUTTEXT'),
            self::TEXTAREA => Pifa::i18n('TEXTAREA'),
            self::INPUTPASSWORD => Pifa::i18n('INPUTPASSWORD'),
            self::INPUTRADIO => Pifa::i18n('INPUTRADIO'),
            self::INPUTCHECKBOX => Pifa::i18n('INPUTCHECKBOX'),
            self::SELECT => Pifa::i18n('SELECT'),
            self::SELECTMULTI => Pifa::i18n('SELECTMULTI'),
            self::DATEPICKER => Pifa::i18n('DATEPICKER'),
            self::INPUTFILE => Pifa::i18n('INPUTFILE'),
            self::PROCESSBAR => Pifa::i18n('PROCESSBAR'),
            self::SLIDER => Pifa::i18n('SLIDER'),
            self::CAPTCHA => Pifa::i18n('CAPTCHA'),
            self::BUTTONSUBMIT => Pifa::i18n('BUTTONSUBMIT'),
            self::BUTTONRESET => Pifa::i18n('BUTTONRESET'),
            self::BUTTONBACK => Pifa::i18n('BUTTONBACK'),
            self::MATRIX => Pifa::i18n('MATRIX'),
            self::PARA => Pifa::i18n('PARAGRAPH'),
            self::INPUTHIDDEN => Pifa::i18n('INPUTHIDDEN'),
            self::FIELDSET_BEGIN => Pifa::i18n('FIELDSET_BEGIN'),
            self::FIELDSET_END => Pifa::i18n('FIELDSET_END')
        );

        if (array_key_exists($fieldTypeId, $fieldTypeName)) {
            return $fieldTypeName[$fieldTypeId];
        }

        return Pifa::i18n('UNKNOWN');

    }

    /**
     * TODO add different icons for different form field types
     *
     * @param int $fieldType
     */
    public static function getFieldTypeIcon($fieldType) {

        switch ($fieldType) {
            case self::INPUTTEXT:
            case self::TEXTAREA:
            case self::INPUTPASSWORD:
            case self::INPUTRADIO:
            case self::INPUTCHECKBOX:
            case self::SELECT:
            case self::SELECTMULTI:
            case self::DATEPICKER:
            case self::INPUTFILE:
            case self::PROCESSBAR:
            case self::SLIDER:
            case self::CAPTCHA:
            case self::BUTTONSUBMIT:
            case self::BUTTONRESET:
            case self::BUTTONBACK:
            case self::MATRIX:
            case self::PARA:
            case self::INPUTHIDDEN:
            case self::FIELDSET_BEGIN:
            case self::FIELDSET_END:
                return 'icon.png';
        }

    }

    /**
     * Returns a string describing this fields database data type as used in
     * MySQL CREATE and ALTER TABLE statements.
     *
     * @throws Exception if field is not loaded
     * @throws Exception if field type is not implemented
     */
    public function getDbDataType() {

        if (!$this->isLoaded()) {
            throw new Exception('field is not loaded');
        }

        $fieldType = cSecurity::toInteger($this->get('field_type'));

        switch ($fieldType) {

            // Text and password input fields can store a string of
            // arbitrary length. Cause they are single lined it does not
            // make sense to enable them storing more than 1023 characters
            // though.
            case self::INPUTTEXT:
            case self::INPUTPASSWORD:
            case self::INPUTHIDDEN:
            case self::INPUTFILE:

                return 'VARCHAR(' . self::VARCHAR_SIZE . ')';

            // A group of checkboxes can store multiple values. So this has
            // to be implemented as a CSV string of max. 1023 characters.
            case self::INPUTCHECKBOX:

                return 'VARCHAR(' . self::VARCHAR_SIZE . ')';

            // Textareas are designed to store longer texts so I chose the
            // TEXT data type to enable them storing up to 65535 characters.
            case self::TEXTAREA:

                return 'TEXT';

            // A group of radiobuttons or a select box can store a single
            // value of a given set of options. This can be implemented
            // as an enumeration.
            case self::INPUTRADIO:
            case self::SELECT:
            case self::SELECTMULTI:

                // $optionValues = $this->get('option_values');
                // $optionValues = explode(',', $optionValues);
                // array_map(function ($val) {
                // return "'$val'";
                // }, $optionValues);
                // $optionValues = join(',', $optionValues);

                // return "ENUM($optionValues)";

                // as long as the GUI does not offer an entry to define option
                // values when creating a new field assume VARCHAR as data type
                return 'VARCHAR(' . self::VARCHAR_SIZE . ')';

            // The datepicker can store a date having an optional time
            // portion. I chose DATETIME as data type over TIMESTAMP due to
            // its limitation of storing dates before 1970-01-01 although
            // even DATETIME can't store dates before 1000-01-01.
            case self::DATEPICKER:

                return 'DATETIME';

            // Buttons don't have any values that should be stored.
            case self::BUTTONSUBMIT:
            case self::BUTTONRESET:
            case self::BUTTONBACK:

                return NULL;

            // TODO For some filed types I havn't yet decided which data
            // type to use.
            case self::PROCESSBAR:
            case self::SLIDER:
            case self::CAPTCHA:
            case self::MATRIX:
            case self::PARA:
            case self::FIELDSET_BEGIN:
            case self::FIELDSET_END:

                return NULL;

            default:
                throw new Exception('field type ' . $fieldType . ' is not implemented');

        }

    }

    /**
     * Deletes this form with all its fields and stored data.
     * The forms data table is also dropped.
     */
    public function delete() {

        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();

        if (!$this->isLoaded()) {
            throw new Exception('field is not loaded');
        }

        // update ranks of younger siblings
        $sql = "-- PifaField->delete()
			UPDATE
				" . $cfg['tab']['pifa_field'] . "
			SET
				field_rank = field_rank - 1
			WHERE
				idform = " . cSecurity::toInteger($this->get('idform')) . "
				AND field_rank > " . cSecurity::toInteger($this->get('field_rank')) . "
			;";
        if (false === $db->query($sql)) {
            // false is returned if no fields were updated
            // but that doesn't matter ...
        }

        // delete field
        $sql = "-- PifaField->delete()
			DELETE FROM
				" . $cfg['tab']['pifa_field'] . "
			WHERE
				idfield = " . cSecurity::toInteger($this->get('idfield')) . "
			;";
        if (false === $db->query($sql)) {
            throw new Exception('field could not be deleted');
        }

        // drop column of data table
        if (0 < strlen(trim($this->get('column_name')))) {
            $pifaForm = new PifaForm($this->get('idform'));
            if (0 < strlen(trim($pifaForm->get('data_table')))) {
                $sql = "-- PifaField->delete()
    	            ALTER TABLE
    					`" . cSecurity::toString($pifaForm->get('data_table')) . "`
    	        	DROP COLUMN
    					`" . cSecurity::toString($this->get('column_name')) . "`
    				;";
                if (false === $db->query($sql)) {
                    throw new Exception('column could not be dropped');
                }
            }
        }

    }

    /**
     * Determines for which form field types which data should be editable in
     * backend.
     *
     * @param string $columnName for data to edit
     */
    public function showField($columnName) {

        $fieldType = cSecurity::toInteger($this->get('field_type'));

        switch ($columnName) {

            case 'idfield':
            case 'idform':
            case 'field_rank':
            case 'field_type':
                // will never be editable directly
                return false;

            case 'column_name':
                return in_array($fieldType, array(
                    self::INPUTTEXT,
                    self::TEXTAREA,
                    self::INPUTPASSWORD,
                    self::INPUTRADIO,
                    self::INPUTCHECKBOX,
                    self::SELECT,
                    self::SELECTMULTI,
                    self::DATEPICKER,
                    self::INPUTFILE,
                    self::PROCESSBAR,
                    self::SLIDER,
                    self::CAPTCHA,
                    self::BUTTONSUBMIT,
                    self::BUTTONRESET,
                    self::BUTTONBACK,
                    self::MATRIX,
                    self::INPUTHIDDEN
                ));

            case 'label':
                return in_array($fieldType, array(
                    self::INPUTTEXT,
                    self::TEXTAREA,
                    self::INPUTPASSWORD,
                    self::INPUTRADIO,
                    self::INPUTCHECKBOX,
                    self::SELECT,
                    self::SELECTMULTI,
                    self::DATEPICKER,
                    self::INPUTFILE,
                    self::PROCESSBAR,
                    self::SLIDER,
                    self::CAPTCHA,
                    self::BUTTONSUBMIT,
                    self::BUTTONRESET,
                    self::BUTTONBACK,
                    self::MATRIX,
                    self::PARA,
                    self::FIELDSET_BEGIN
                ));

            case 'default_value':
                return in_array($fieldType, array(
                    self::INPUTTEXT,
                    self::TEXTAREA,
                    self::INPUTPASSWORD,
                    self::INPUTRADIO,
                    self::INPUTCHECKBOX,
                    self::SELECT,
                    self::SELECTMULTI,
                    self::DATEPICKER,
                    self::INPUTFILE,
                    self::PROCESSBAR,
                    self::SLIDER,
                    self::CAPTCHA,
                    self::BUTTONSUBMIT,
                    self::BUTTONRESET,
                    self::BUTTONBACK,
                    self::MATRIX,
                    self::INPUTHIDDEN
                ));

            case 'option_labels':
            case 'option_values':
            case 'option_class':
                return in_array($fieldType, array(
                    self::INPUTRADIO,
                    self::INPUTCHECKBOX,
                    self::SELECT,
                    self::SELECTMULTI
                ));

            case 'help_text':
                return in_array($fieldType, array(
                    self::INPUTTEXT,
                    self::TEXTAREA,
                    self::INPUTPASSWORD,
                    self::INPUTRADIO,
                    self::INPUTCHECKBOX,
                    self::SELECT,
                    self::SELECTMULTI,
                    self::DATEPICKER,
                    self::INPUTFILE,
                    self::PROCESSBAR,
                    self::SLIDER,
                    self::CAPTCHA,
                    self::BUTTONSUBMIT,
                    self::BUTTONRESET,
                    self::BUTTONBACK,
                    self::MATRIX,
                    self::PARA,
                    self::FIELDSET_BEGIN
                ));

            case 'obligatory':
                return in_array($fieldType, array(
                    self::INPUTTEXT,
                    self::TEXTAREA,
                    self::INPUTPASSWORD,
                    self::INPUTRADIO,
                    self::INPUTCHECKBOX,
                    self::SELECT,
                    self::SELECTMULTI,
                    self::DATEPICKER,
                    self::INPUTFILE,
                    self::PROCESSBAR,
                    self::SLIDER,
                    self::CAPTCHA,
                    self::BUTTONSUBMIT,
                    self::BUTTONRESET,
                    self::BUTTONBACK,
                    self::MATRIX,
                    self::INPUTHIDDEN
                ));

            case 'rule':
                return in_array($fieldType, array(
                    self::INPUTTEXT,
                    self::TEXTAREA,
                    self::INPUTPASSWORD,
                    self::INPUTRADIO,
                    self::INPUTCHECKBOX,
                    self::SELECT,
                    self::SELECTMULTI,
                    self::DATEPICKER,
                    self::INPUTFILE,
                    self::PROCESSBAR,
                    self::SLIDER,
                    self::CAPTCHA,
                    self::BUTTONSUBMIT,
                    self::BUTTONRESET,
                    self::BUTTONBACK,
                    self::MATRIX,
                    self::INPUTHIDDEN
                ));

            case 'error_message':
                return in_array($fieldType, array(
                    self::INPUTTEXT,
                    self::TEXTAREA,
                    self::INPUTPASSWORD,
                    self::INPUTRADIO,
                    self::INPUTCHECKBOX,
                    self::SELECT,
                    self::SELECTMULTI,
                    self::DATEPICKER,
                    self::INPUTFILE,
                    self::PROCESSBAR,
                    self::SLIDER,
                    self::CAPTCHA,
                    self::BUTTONSUBMIT,
                    self::BUTTONRESET,
                    self::BUTTONBACK,
                    self::MATRIX,
                    self::INPUTHIDDEN
                ));

            case 'css_class':
                return in_array($fieldType, array(
                    self::INPUTTEXT,
                    self::TEXTAREA,
                    self::INPUTPASSWORD,
                    self::INPUTRADIO,
                    self::INPUTCHECKBOX,
                    self::SELECT,
                    self::SELECTMULTI,
                    self::DATEPICKER,
                    self::INPUTFILE,
                    self::PROCESSBAR,
                    self::SLIDER,
                    self::CAPTCHA,
                    self::BUTTONSUBMIT,
                    self::BUTTONRESET,
                    self::BUTTONBACK,
                    self::MATRIX,
                    self::PARA,
                    self::INPUTHIDDEN
                ));

            default:
                throw new Exception('field type ' . $fieldType . ' is not implemented');

        }

    }

    /**
     *
     * @return array
     */
    public function getOptions() {

        $option_labels = $this->get('option_labels');
        $option_values = $this->get('option_values');

        $out = array();
        if (0 < strlen($option_labels . $option_values)) {
            $option_labels = explode(',', $option_labels);
            $option_values = explode(',', $option_values);
            foreach (array_keys($option_labels) as $key) {
                $out[] = array(
                    'label' => $option_labels[$key],
                    'value' => $option_values[$key]
                );
            }
        }

        return $out;

    }

}

?>