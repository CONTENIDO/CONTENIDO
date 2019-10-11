<?php

/**
 * This file contains the cContentTypeHtml class.
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
 * Content type CMS_HTML which lets the editor enter HTML with the help of a
 * WYSIWYG editor.
 *
 * @package Core
 * @subpackage ContentType
 */
class cContentTypeHtml extends cContentTypeAbstract {

    /**
     * Name of the content type.
     *
     * @var string
     */
    const CONTENT_TYPE = 'CMS_HTML';

    /**
     * Prefix used for posted data.
     * Replaces the property $this->>_prefix.
     *
     * @var string
     */
    const PREFIX = 'html';

    /**
     * Generates the code which should be shown if this content type is shown in
     * the frontend.
     *
     * @return string
     *         escaped HTML code which sould be shown if content type is shown in frontend
     */
    public function generateViewCode() {
        return $this->_encodeForOutput($this->_rawSettings);
    }

    /**
     * Generates the code which should be shown if this content type is edited.
     *
     * @return string
     *         escaped HTML code which should be shown if content type is edited
     * @throws cDbException
     * @throws cException
     */
    public function generateEditCode() {
        $wysiwygDiv = new cHTMLDiv();

        // generate the div ID - format: TYPEWITHOUTCMS_TYPEID_ID
        // important because it is used to save the content accordingly
        $id = str_replace('CMS_', '', static::CONTENT_TYPE) . '_';
        $db = cRegistry::getDb();
        $sql = 'SELECT `idtype` FROM `' . $this->_cfg['tab']['type'] . '` WHERE `type`=\'' . static::CONTENT_TYPE . '\'';
        $db->query($sql);
        $db->nextRecord();
        $id .= $db->f('idtype') . '_' . $this->_id;
        $wysiwygDiv->setID($id);
        $wysiwygDiv->setClass(htmlentities(static::CONTENT_TYPE));

        $wysiwygDiv->setEvent('Focus', "this.style.border='1px solid #bb5577';");
        $wysiwygDiv->setEvent('Blur', "this.style.border='1px dashed #bfbfbf';");
        $wysiwygDiv->appendStyleDefinitions(array(
            'border' => '1px dashed #bfbfbf',
            'direction' => langGetTextDirection($this->_lang),
            'min-height' => '20px'
        ));
        $wysiwygDiv->updateAttribute('contentEditable', 'true');
        if (cString::getStringLength($this->_rawSettings) == 0) {
            $wysiwygDiv->setContent('&nbsp;');
        } else {
            $wysiwygDiv->setContent($this->_rawSettings);
        }


        // construct edit button
        $editLink = $this->_session->url($this->_cfg['path']['contenido_fullhtml'] . 'external/backendedit/' . 'front_content.php?action=10&idcat=' . $this->_idCat . '&idart=' . $this->_idArt . '&idartlang=' . $this->_idArtLang . '&type=' . static::CONTENT_TYPE . '&typenr=' . $this->_id. '&client=' . $this->_client);
        $editAnchor = new cHTMLLink('#');
        $editAnchor->setAttribute('onclick', "javascript:Con.Tiny.setContent('" . $this->_idArtLang . "','" . $editLink . "'); return false;");
        $editButton = new cHTMLImage($this->_cfg['path']['contenido_fullhtml'] . $this->_cfg['path']['images'] . 'but_edithtml.gif');
        $editButton->appendStyleDefinition('margin-right', '2px');
        $editButton->setClass('content_type_zindex');
        $editAnchor->setContent($editButton);

        // construct save button
        $saveAnchor = new cHTMLLink('#');
        $saveAnchor->setAttribute('onclick', "javascript:Con.Tiny.setContent('" . $this->_idArtLang . "', '0'); return false;");
        $saveButton = new cHTMLImage($this->_cfg['path']['contenido_fullhtml'] . $this->_cfg['path']['images'] . 'but_ok.gif');
        $saveAnchor->setContent($saveButton);

        return $this->_encodeForOutput($wysiwygDiv->render() . $editAnchor->render() . $saveAnchor->render());
    }

    /**
     * This content type and its derived types can be edited by a WYSIWYG editor
     *
     * @return bool
     */
    public function isWysiwygCompatible() {
        return true;
    }

}
