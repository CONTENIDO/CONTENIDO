<?php
/**
 * This file contains the cContentTypeText class.
 *
 * @package Core
 * @subpackage Content Type
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Content type CMS_TEXT which lets the editor enter a single-line text.
 *
 * @package Core
 * @subpackage Content Type
 */
class cContentTypeText extends cContentTypeAbstract {

    /**
     * Initialises class attributes and handles store events.
     *
     * @param string $rawSettings the raw settings in an XML structure or as
     *        plaintext
     * @param integer $id ID of the content type, e.g. 3 if CMS_DATE[3] is
     *        used
     * @param array $contentTypes array containing the values of all content
     *        types
     * @return void
     */
    public function __construct($rawSettings, $id, array $contentTypes) {
        $rawSettings = htmlspecialchars($rawSettings);
        // change attributes from the parent class and call the parent
        // constructor
        parent::__construct($rawSettings, $id, $contentTypes);
        $this->_type = 'CMS_TEXT';
        $this->_prefix = 'text';

        // if form is submitted, store the current text
        // notice: also check the ID of the content type (there could be more
        // than one content type of the same type on the same page!)
        if (isset($_POST[$this->_prefix . '_action']) && $_POST[$this->_prefix . '_action'] === 'store' && isset($_POST[$this->_prefix . '_id']) && (int) $_POST[$this->_prefix . '_id'] == $this->_id) {
            $this->_settings = $_POST[$this->_prefix . '_text_' . $this->_id];
            $this->_rawSettings = $this->_settings;
            $this->_storeSettings();
        }
    }

    /**
     * Generates the code which should be shown if this content type is edited.
     *
     * @return string escaped HTML code which should be shown if content type is
     *         edited
     */
    public function generateEditCode() {
        $script = $this->_getEditJavaScript();

        $div = new cHTMLDiv($this->_rawSettings);
        $div->setID($this->_prefix . '_text_' . $this->_id);
        $div->attachStyleDefinition('inline', 'display: inline');

        $editButton = new cHTMLImage($this->_cfg['path']['contenido_fullhtml'] . $this->_cfg['path']['images'] . 'but_edithead.gif');
        $editButton->setID($this->_prefix . '_editbutton_' . $this->_id);
        $editButton->attachStyleDefinition('margin-left', 'margin-left: 5px');
        $editButton->attachStyleDefinition('cursor', 'cursor: pointer');

        return $this->_encodeForOutput($script . $div->render() . $editButton->render());
    }

    /**
     * Generates the JS code for this content type.
     *
     * @return string the JS code for the content type
     */
    protected function _getEditJavaScript() {
        $textbox = new cHTMLTextbox($this->_prefix . '_text_' . $this->_id, '', '', '', $this->_prefix . '_text_' . $this->_id);

        $saveButton = new cHTMLImage($this->_cfg['path']['contenido_fullhtml'] . 'images/but_ok.gif');
        $saveButton->setID($this->_prefix . '_savebutton_' . $this->_id);
        $saveButton->attachStyleDefinition('margin-left', 'margin-left: 5px');
        $saveButton->attachStyleDefinition('cursor', 'cursor: pointer');

        $template = new Template();
        $template->set('s', 'PREFIX', $this->_prefix);
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
     * @return string escaped HTML code which sould be shown if content type is
     *         shown in frontend
     */
    public function generateViewCode() {
        return $this->_encodeForOutput($this->_rawSettings);
    }

}