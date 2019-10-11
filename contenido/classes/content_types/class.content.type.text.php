<?php

/**
 * This file contains the cContentTypeText class.
 *
 * @package Core
 * @subpackage ContentType
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Content type CMS_TEXT which lets the editor enter a single-line text.
 *
 * @package Core
 * @subpackage ContentType
 */
class cContentTypeText extends cContentTypeAbstract {

    /**
     * Name of the content type.
     *
     * @var string
     */
    const CONTENT_TYPE = 'CMS_TEXT';

    /**
     * Prefix used for posted data.
     * Replaces the property $this->>_prefix.
     *
     * @var string
     */
    const PREFIX = 'text';

    /**
     * Constructor to create an instance of this class.
     *
     * Initialises class attributes and handles store events.
     *
     * @param string $rawSettings
     *         the raw settings in an XML structure or as plaintext
     * @param int    $id
     *         ID of the content type, e.g. 3 if CMS_DATE[3] is used
     * @param array  $contentTypes
     *         array containing the values of all content types
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function __construct($rawSettings, $id, array $contentTypes) {

        $rawSettings = conHtmlSpecialChars($rawSettings);

        // call parent constructor
        parent::__construct($rawSettings, $id, $contentTypes);

        // if form is submitted, store the current text
        // notice: also check the ID of the content type (there could be more
        // than one content type of the same type on the same page!)
        if (isset($_POST[static::PREFIX . '_action']) && $_POST[static::PREFIX . '_action'] === 'store' && isset($_POST[static::PREFIX . '_id']) && (int) $_POST[static::PREFIX . '_id'] == $this->_id) {
            $this->_settings = $_POST[static::PREFIX . '_text_' . $this->_id];
            $this->_rawSettings = $this->_settings;
            $this->_storeSettings();

            // make sure to escape variables before any output on page
            $this->_settings = stripslashes($this->_settings);
            $this->_settings = conHtmlSpecialChars($this->_settings);
            $this->_rawSettings = stripslashes($this->_rawSettings);
            $this->_rawSettings = conHtmlSpecialChars($this->_rawSettings);

            // input of text with paragraphs should print text with line breaks
            $this->_rawSettings = nl2br($this->_rawSettings);
        }
    }

    /**
     * Generates the code which should be shown if this content type is edited.
     *
     * @return string
     *         escaped HTML code which should be shown if content type is edited
     * @throws cInvalidArgumentException
     */
    public function generateEditCode() {
        $script = $this->_getEditJavaScript();

        $div = new cHTMLDiv($this->_rawSettings);
        $div->setID(static::PREFIX . '_text_' . $this->_id);
        $div->appendStyleDefinition('display', 'inline');

        $editButton = new cHTMLImage($this->_cfg['path']['contenido_fullhtml'] . $this->_cfg['path']['images'] . 'but_edithead.gif');
        $editButton->setID(static::PREFIX . '_editbutton_' . $this->_id);
        $editButton->appendStyleDefinitions(array(
            'margin-left' => '5px',
            'cursor' => 'pointer'
        ));

        return $this->_encodeForOutput($script . $div->render() . $editButton->render());
    }

    /**
     * Generates the JS code for this content type.
     *
     * @return string
     *         the JS code for the content type
     * @throws cInvalidArgumentException
     */
    protected function _getEditJavaScript() {
        $textbox = new cHTMLTextarea(static::PREFIX . '_text_' . $this->_id, '', '', '', static::PREFIX . '_text_' . $this->_id, false, NULL, '', 'edit-textfield edit-' . static::PREFIX . '-textfield');
        $textbox->setClass("$this->_id");

        $saveButton = new cHTMLImage($this->_cfg['path']['contenido_fullhtml'] . 'images/but_ok.gif');
        $saveButton->setID(static::PREFIX . '_savebutton_' . $this->_id);
        $saveButton->appendStyleDefinitions(array(
            'margin-left' => '5px',
            'cursor' => 'pointer'
        ));

        $template = new cTemplate();
        $template->set('s', 'PREFIX', static::PREFIX);
        $template->set('s', 'ID', $this->_id);
        $template->set('s', 'TEXTBOX', $textbox->render());
        $template->set('s', 'SAVEBUTTON', $saveButton->render());
        $template->set('s', 'IDARTLANG', $this->_idArtLang);

        return $template->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_text_js.html', true);
    }

    /**
     * Generates the code which should be shown if this content type is shown in
     * the frontend.
     *
     * @return string
     *         escaped HTML code which should be shown if content type is shown in frontend
     */
    public function generateViewCode() {
        return $this->_encodeForOutput($this->_rawSettings);
    }

}
