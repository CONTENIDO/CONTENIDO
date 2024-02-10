<?php

/**
 * This file contains the class for contenttype CMS_USERFORUM
 *
 * @package    Plugin
 * @subpackage UserForum
 * @author     Claus Schunk
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains the features of the contenttype CMS_USERFORUM.
 *
 *
 * @package    Plugin
 * @subpackage UserForum
 */
class cContentTypeUserForum extends cContentTypeAbstractTabbed
{

    /**
     * Initialize class attributes and handles store events.
     *
     * @param string $rawSettings the raw settings in an XML structure or as
     *                             plaintext
     * @param int $id ID of the content type, e.g. 3 if CMS_DATE[3] is
     *                             used
     * @param array $contentTypes array containing the values of all content
     *                             types
     *
     * @throws cDbException
     */
    function __construct($rawSettings, $id, array $contentTypes)
    {
        // set attributes of the parent class and call the parent constructor
        $this->_type = 'CMS_USERFORUM';
        $this->_prefix = 'userforum';
        $this->_settingsType = self::SETTINGS_TYPE_XML;
        $this->_formFields = [
            'userforum_email',
            'userforum_subcomments',
            'userforum_modactive'
        ];

        // encoding conversions to avoid problems with umlauts
        $rawSettings = conHtmlEntityDecode($rawSettings ?? '');
        $rawSettings = @utf8_encode($rawSettings);

        parent::__construct($rawSettings, $id, $contentTypes);

        // if form is submitted, store the current settings
        // notice: also check the ID of the content type (there could be more
        // than one content type of the same type on the same page!)
        $action = $_POST['userforum_action'] ?? NULL;
        $id = $_POST['userforum_id'] ?? NULL;
        if ('store' === $action && $this->_id == $id) {
            $this->_storeSettings();
        }
    }

    /**
     * @inheritDoc
     */
    public function generateEditCode(): string
    {
        $cfg = cRegistry::getConfig();

        // build top code
        $tplTop = new cTemplate();
        $tplTop->set('s', 'ICON', 'plugins/user_forum/images/con_button.gif');
        $tplTop->set('s', 'ID', $this->_id);
        $tplTop->set('s', 'PREFIX', $this->_prefix);
        $tplTop->set('s', 'HEADLINE', UserForum::i18n('ADMINISTRATION'));
        $codeTop = $tplTop->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_top.html', true);

        // available tabs
        // $tabMenu = array('base' => Pifa::i18n('form'));

        // build tab code
        $tplPanel = new cTemplate();
        $tplPanel->set('s', 'PREFIX', $this->_prefix);
        $tplPanel->set('d', 'TAB_ID', 'base');
        $tplPanel->set('d', 'TAB_CLASS', 'base');
        $tplPanel->set('d', 'TAB_CONTENT', $this->_getPanel());
        $tplPanel->next();
        $codePanel = $tplPanel->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_tabs.html', true);

        // build bottom code
        $tplBottom = new cTemplate();
        $tplBottom->set('s', 'PATH_FRONTEND', cRegistry::getFrontendUrl());
        $tplBottom->set('s', 'ID', $this->_id);
        $tplBottom->set('s', 'PREFIX', $this->_prefix);
        $tplBottom->set('s', 'IDARTLANG', $this->_idArtLang);
        $tplBottom->set('s', 'FIELDS', "'" . implode("','", $this->_formFields) . "'");
        $tplBottom->set('s', 'SETTINGS', json_encode($this->_settings));
        $tplBottom->set('s', 'JS_CLASS_SCRIPT', UserForum::getUrl() . cAsset::backend('scripts/cmsUserforum.js'));
        $tplBottom->set('s', 'JS_CLASS_NAME', 'Con.' . get_class($this));
        $codeBottom = $tplBottom->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_bottom.html', true);

        // build template code
        $code = $this->_encodeForOutput($codeTop);
        // $code .= $this->_generateTabMenuCode($tabMenu);
        $code .= $this->_encodeForOutput($codePanel);
        $code .= $this->_generateActionCode();
        $code .= $this->_encodeForOutput($codeBottom);
        $code .= $this->generateViewCode();

        $code = "\n\n<!-- CODE (class.content.type.user_forum.php) -->
$code
<!-- /CODE -->\n\n";

        return $code;
    }

    /**
     * Generates code for the base panel in which all data can be specified.
     *
     * @return string  The code for the base panel
     */
    private function _getPanel(): string
    {
        $wrapper = new cHTMLDiv([
            $this->_getModEmail(),
            $this->_getModMode(),
            $this->_getEditMode()
        ], $this->_prefix . '_panel_base', $this->_prefix . '_panel_base_' . $this->_id);
        $wrapper->setStyle('clear:both');

        return $wrapper->render();
    }

    /**
     * @return cHTMLDiv
     */
    private function _getModMode(): cHTMLDiv
    {
        $id = 'userforum_modactive_' . $this->_id;

        // build html elements
        $labelModMode = new cHTMLLabel(UserForum::i18n('ACTIVATEMOD'), $id);
        $checkBoxMod = new cHTMLCheckbox($id, '', $id);
        $checkBoxMod->setID($id);

        // check state
        $checkBoxMod->setChecked($this->getSetting('userforum_modactive', 'false') === 'false');

        // build div element as wrapper
        $div = new cHTMLDiv([
            '<br />',
            $labelModMode,
            $checkBoxMod
        ]);
        $div->setClass('modMode');

        // return div element
        return $div;
    }

    /**
     * @return cHTMLDiv
     */
    private function _getEditMode(): cHTMLDiv
    {
        $id = 'userforum_subcomments_' . $this->_id;

        // build html elements
        $labelModMode = new cHTMLLabel(UserForum::i18n('EDITABLE'), $id);
        $checkBoxMod = new cHTMLCheckbox($id, '', $id);
        $checkBoxMod->setID($id);

        // check state
        $checkBoxMod->setChecked($this->getSetting('userforum_subcomments', 'false') === 'false');

        // build div element as wrapper
        $div = new cHTMLDiv([
            $labelModMode,
            $checkBoxMod
        ]);
        $div->setClass('editMode');

        // return div element
        return $div;
    }

    /**
     * Builds a select element allowing to choose a single form that was created
     * for the current client.
     *
     * @return cHTMLDiv
     */
    private function _getModEmail(): cHTMLDiv
    {
        $id = 'userforum_email_' . $this->_id;

        // build html elements
        $infoLabel = new cHTMLLabel(UserForum::i18n('MODSETTINGS'), $id);
        $labelEmail = new cHTMLLabel(UserForum::i18n('MODEMAIL'), $id);

        $inputEmail = new cHTMLTextbox($id);
        $inputEmail->setID($id);
        $inputEmail->setValue($this->getSetting('userforum_email', ''));

        // build div element as wrapper
        $div = new cHTMLDiv([
            $labelEmail,
            $inputEmail
        ]);
        $div->setClass('mail');

        // return div element
        return $div;
    }

    /**
     * @inheritDoc
     */
    public function generateViewCode(): string
    {
        $code = '<?php
            $form = new %s(\'%s\', %s, %s);
            echo $form->buildCode();
        ?>';

        $code = $this->_wrapPhpViewCode($code);

        return sprintf($code, get_class($this), $this->_rawSettings, $this->_id, '[]');
    }

    /**
     * Get code of form (either GET or POST request).
     *
     * @return string escaped HTML code which should be shown if content type is
     *         shown in frontend
     */
    public function buildCode(): string
    {
        return '';
    }

}
