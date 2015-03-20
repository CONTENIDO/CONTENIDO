<?php
/**
 * This file contains the cContentTypeHead class.
 *
 * @package Core
 * @subpackage ContentType
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Content type CMS_HEAD which lets the editor enter a single-line text.
 *
 * @package Core
 * @subpackage ContentType
 */
class cContentTypeHead extends cContentTypeText {

    /**
     * Initialises class attributes and handles store events.
     *
     * @param string $rawSettings the raw settings in an XML structure or as
     *        plaintext
     * @param int $id ID of the content type, e.g. 3 if CMS_DATE[3] is
     *        used
     * @param array $contentTypes array containing the values of all content
     *        types
     */
    public function __construct($rawSettings, $id, array $contentTypes) {
        // change attributes from the parent class and call the parent
        // constructor
        parent::__construct($rawSettings, $id, $contentTypes);
        $this->_type = 'CMS_HEAD';
        $this->_prefix = 'head';

        // if form is submitted, store the current text
        // notice: also check the ID of the content type (there could be more
        // than one content type of the same type on the same page!)
        if (isset($_POST[$this->_prefix . '_action']) && $_POST[$this->_prefix . '_action'] === 'store' && isset($_POST[$this->_prefix . '_id']) && (int) $_POST[$this->_prefix . '_id'] == $this->_id) {
            $this->_settings = $_POST[$this->_prefix . '_text_' . $this->_id];
            $this->_rawSettings = $this->_settings;
            $this->_storeSettings();

            // make sure to escape variables before any output on page
            $this->_settings = stripslashes($this->_settings);
            $this->_settings = conHtmlSpecialChars($this->_settings);
            $this->_rawSettings = stripslashes($this->_rawSettings);
            $this->_rawSettings = conHtmlSpecialChars($this->_rawSettings);
        }
    }
    
    /**
     * Generates the JS code for this content type.
     *
     * @return string the JS code for the content type
     */
    protected function _getEditJavaScript() {
        $textbox = new cHTMLTextbox($this->_prefix . '_text_' . $this->_id, '', '', '', $this->_prefix . '_text_' . $this->_id, false, NULL, '', 'edit-textfield edit-' . $this->_prefix . '-textfield');
        $textbox->setClass("$this->_id");

        $saveButton = new cHTMLImage($this->_cfg['path']['contenido_fullhtml'] . 'images/but_ok.gif');
        $saveButton->setID($this->_prefix . '_savebutton_' . $this->_id);
        $saveButton->appendStyleDefinitions(array(
                'margin-left' => '5px',
                'cursor' => 'pointer'
        ));

        $template = new cTemplate();
        $template->set('s', 'PREFIX', $this->_prefix);
        $template->set('s', 'ID', $this->_id);
        $template->set('s', 'TEXTBOX', $textbox->render());
        $template->set('s', 'SAVEBUTTON', $saveButton->render());
        $template->set('s', 'IDARTLANG', $this->_idArtLang);

        return $template->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_text_js.html', true);
    }

}