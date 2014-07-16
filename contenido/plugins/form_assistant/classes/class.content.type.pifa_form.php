<?php

/**
 * This file contains the cContentTypePifaForm class.
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.upl.php');

/**
 * Content type CMS_PIFAFORM which lets the editor configure a PIFA form to be
 * displayed in frontend.
 *
 * This content type offers several choices:
 * - select a form of those created in the PIFA backend for the current client
 * - select a module class located in plugins/form_assistant/extensions
 * - select a processor class located in plugins/form_assistant/extensions
 * - DOCME several client & system mail settings
 */
class cContentTypePifaForm extends cContentTypeAbstractTabbed {

    /**
     * Callback function that is capable of sorting items that are arrays by
     * comparing their value for the key 'label'.
     *
     * @param array $a
     * @param array $b
     * @return number as expected for a comparision function used for sorting
     */
    public static function sortByLabel($a, $b) {
        return ($a['label'] == $b['label']) ? 0 : (($a['label'] < $b['label']) ? -1 : 1);
    }

    /**
     * Initialize class attributes and handles store events.
     *
     * @param string $rawSettings the raw settings in an XML structure or as
     *        plaintext
     * @param int $id ID of the content type, e.g. 3 if CMS_DATE[3] is
     *        used
     * @param array $contentTypes array containing the values of all content
     *        types
     */
    function __construct($rawSettings, $id, array $contentTypes) {

        // set attributes of the parent class and call the parent constructor
        $this->_type = 'CMS_PIFAFORM';
        $this->_prefix = 'pifaform';
        $this->_settingsType = self::SETTINGS_TYPE_XML;
        $this->_formFields = array(
            'pifaform_idform',
            'pifaform_module',
            'pifaform_processor',
            'pifaform_template_get',
            'pifaform_template_post',
            'pifaform_mail_client_template',
            'pifaform_mail_client_from_email',
            'pifaform_mail_client_from_name',
            'pifaform_mail_client_subject',
            'pifaform_mail_system_template',
            'pifaform_mail_system_from_email',
            'pifaform_mail_system_from_name',
            'pifaform_mail_system_recipient_email',
            'pifaform_mail_system_subject'
        );

        // encoding conversions to avoid problems with umlauts
        $rawSettings = conHtmlEntityDecode($rawSettings);
        $rawSettings = utf8_encode($rawSettings);

        parent::__construct($rawSettings, $id, $contentTypes);

        // if form is submitted, store the current settings
        // notice: also check the ID of the content type (there could be more
        // than one content type of the same type on the same page!)
        $action = isset($_POST['pifaform_action']) ? $_POST['pifaform_action'] : NULL;
        $id = isset($_POST['pifaform_id']) ? $_POST['pifaform_id'] : NULL;
        if ('store' === $action && $this->_id == $id) {
            $this->_storeSettings();
        }
    }

    /**
     * Generate the escaped HTML code for editor.
     *
     * @return string escaped HTML code for editor
     */
    public function generateEditCode() {

        // build top code
        $tplTop = new cTemplate();
        $tplTop->set('s', 'ICON', 'plugins/form_assistant/images/icon_form.png');
        $tplTop->set('s', 'ID', $this->_id);
        $tplTop->set('s', 'PREFIX', $this->_prefix);
        $tplTop->set('s', 'HEADLINE', Pifa::i18n('form'));
        $codeTop = $tplTop->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_top.html', true);

        // available tabs
        $tabMenu = array(
            'base' => Pifa::i18n('form')
        );

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
        $tplBottom->set('s', 'PATH_FRONTEND', $this->_cfgClient[$this->_client]['path']['htmlpath']);
        $tplBottom->set('s', 'ID', $this->_id);
        $tplBottom->set('s', 'PREFIX', $this->_prefix);
        $tplBottom->set('s', 'IDARTLANG', $this->_idArtLang);
        $tplBottom->set('s', 'FIELDS', "'" . implode("','", $this->_formFields) . "'");
        // encode dollar sign so that contained PHP style variable will not be
        // interpreted
        $tplBottom->set('s', 'SETTINGS', json_encode(str_replace('$', '&#36;', $this->_settings)));
        $tplBottom->set('s', 'JS_CLASS_SCRIPT', Pifa::getUrl() . 'scripts/cmsPifaform.js');
        $tplBottom->set('s', 'JS_CLASS_NAME', get_class($this));

        $codeBottom = $tplBottom->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_bottom.html', true);

        // build template code
        $code = $this->_encodeForOutput($codeTop);
        // $code .= $this->_generateTabMenuCode($tabMenu);
        $code .= $this->_encodeForOutput($codePanel);
        $code .= $this->_generateActionCode();
        $code .= $this->_encodeForOutput($codeBottom);
        $code .= $this->generateViewCode();

        return $code;
    }

    /**
     * Generates code for the base panel in which all data can be specified.
     *
     * @return string - the code for the base panel
     */
    private function _getPanel() {
        $wrapper = new cHTMLDiv(array(

            // form
            $this->_getSelectForm(),

            // module & processor class and GET & POST templates
            new cHTMLFieldset(array(
                new cHTMLLegend(Pifa::i18n('classes & templates')),
                $this->_getSelectModule(),
                $this->_getSelectProcessor(),
                $this->_getSelectTemplateGet(),
                $this->_getSelectTemplatePost()
            )),

            // client mail settings
            new cHTMLFieldset(array(
                new cHTMLLegend(Pifa::i18n('client mail')),
                $this->_getSelectMailClientTemplate(),
                $this->_getInputMailClientFromEmail(),
                $this->_getInputMailClientFromName(),
                $this->_getInputMailClientSubject()
            )),

            // system mail settings
            new cHTMLFieldset(array(
                new cHTMLLegend(Pifa::i18n('system mail')),
                $this->_getSelectMailSystemTemplate(),
                $this->_getInputMailSystemFromEmail(),
                $this->_getInputMailSystemFromName(),
                $this->_getInputMailSystemRecipientEmail(),
                $this->_getInputMailSystemSubject()
            ))
        ), $this->_prefix . '_panel_base', $this->_prefix . '_panel_base_' . $this->_id);
        $wrapper->setStyle('clear:both');

        return $wrapper->render();
    }

    /**
     * Builds a select element allowing to choose a single form that was created
     * for the current client.
     *
     * @return cHTMLDiv
     */
    private function _getSelectForm() {

        // attributes of form field elements
        $id = 'pifaform_idform_' . $this->_id;

        // build label element
        $label = new cHTMLLabel(Pifa::i18n('form'), $id);

        // build select element
        $select = new cHTMLSelectElement($id, '', $id);
        $select->addOptionElement($index = 0, new cHTMLOptionElement(Pifa::i18n('none'), ''));

        // get all forms of current client & validate result
        $idclient = cRegistry::getClientId();
        $forms = PifaFormCollection::getByClient($idclient);
        if (false === $forms) {
            return $select;
        }

        // loop all forms
        while (false !== $form = $forms->next()) {

            // attributes of option element
            $title = $form->get('name');
            $value = $form->get('idform');
            $selected = $form->get('idform') == $this->_settings['pifaform_idform'];

            // build option element
            $option = new cHTMLOptionElement($title, $value, $selected);

            // append option element to select element
            $select->addOptionElement(++$index, $option);
        }

        // build div element as wrapper
        $div = new cHTMLDiv(array(
            $label,
            $select
        ));

        // return div element
        return $div;
    }

    /**
     * Builds a select element allowing to choose a single module that handles
     * the chosen form.
     *
     * @return cHTMLDiv
     */
    private function _getSelectModule() {

        // attributes of form field elements
        $id = 'pifaform_module_' . $this->_id;

        // build label element
        $label = new cHTMLLabel(Pifa::i18n('module'), $id);

        // build select element
        $select = new cHTMLSelectElement($id, '', $id);
        $select->addOptionElement($index = 0, new cHTMLOptionElement(Pifa::i18n('none'), ''));

        // get all modules from extensions & validate result
        $modules = Pifa::getExtensionClasses('PifaAbstractFormModule');

        // sort modules by their label
        usort($modules, 'cContentTypePifaForm::sortByLabel');

        // loop all forms
        foreach ($modules as $module) {

            // attributes of option element
            $title = $module['label'];
            $value = $module['value'];
            $selected = $module['value'] == $this->_settings['pifaform_module'];

            // build option element
            $option = new cHTMLOptionElement($title, $value, $selected);

            // append option element to select element
            $select->addOptionElement(++$index, $option);
        }

        // build div element as wrapper
        $div = new cHTMLDiv(array(
            $label,
            $select
        ));

        // return div element
        return $div;
    }

    /**
     * Builds a select element allowing to choose a single class that
     * postprocesses the sent data.
     *
     * @return cHTMLDiv
     */
    private function _getSelectProcessor() {

        // attributes of form field elements
        $id = 'pifaform_processor_' . $this->_id;

        // build label element
        $label = new cHTMLLabel(Pifa::i18n('processor'), $id);

        // build select element
        $select = new cHTMLSelectElement($id, '', $id);
        $select->addOptionElement($index = 0, new cHTMLOptionElement(Pifa::i18n('none'), ''));

        // get all processors from extensions & validate result
        $processors = Pifa::getExtensionClasses('PifaAbstractFormProcessor');

        // sort processors by their label
        usort($processors, 'cContentTypePifaForm::sortByLabel');

        // loop all forms
        foreach ($processors as $processor) {

            // attributes of option element
            $title = $processor['label'];
            $value = $processor['value'];
            $selected = $processor['value'] == $this->_settings['pifaform_processor'];

            // build option element
            $option = new cHTMLOptionElement($title, $value, $selected);

            // append option element to select element
            $select->addOptionElement(++$index, $option);
        }

        // build div element as wrapper
        $div = new cHTMLDiv(array(
            $label,
            $select
        ));

        // return div element
        return $div;
    }

    /**
     * Builds a select element allowing to choose a single template to respond a
     * GET request.
     *
     * @return cHTMLDiv
     */
    private function _getSelectTemplateGet() {

        // attributes of form field elements
        $id = 'pifaform_template_get_' . $this->_id;

        // build label element
        $label = new cHTMLLabel(Pifa::i18n('template') . ' &ndash; ' . Pifa::i18n('GET'), $id);

        // build select element
        $select = new cHTMLSelectElement($id, '', $id);
        $select->addOptionElement($index = 0, new cHTMLOptionElement(Pifa::i18n('none'), ''));

        // get templates from client template folder
        $templates = Pifa::getTemplates('/cms_pifaform_[^\.]+_get\.tpl/');

        // sort templates by their label
        usort($templates, 'cContentTypePifaForm::sortByLabel');

        // loop all templates
        foreach ($templates as $template) {

            // attributes of option element
            $title = $template['label'];
            $value = $template['value'];
            $selected = $template['value'] == $this->_settings['pifaform_template_get'];

            // build option element
            $option = new cHTMLOptionElement($title, $value, $selected);

            // append option element to select element
            $select->addOptionElement(++$index, $option);
        }

        // build div element as wrapper
        $div = new cHTMLDiv(array(
            $label,
            $select
        ));

        // return div element
        return $div;
    }

    /**
     * Builds a select element allowing to choose a single template to respond a
     * POST request.
     *
     * @return cHTMLDiv
     */
    private function _getSelectTemplatePost() {

        // attributes of form field elements
        $id = 'pifaform_template_post_' . $this->_id;

        // build label element
        $label = new cHTMLLabel(Pifa::i18n('template') . ' &ndash; ' . Pifa::i18n('POST'), $id);

        // build select element
        $select = new cHTMLSelectElement($id, '', $id);
        $select->addOptionElement($index = 0, new cHTMLOptionElement(Pifa::i18n('none'), ''));

        // get templates from client template folder
        $templates = Pifa::getTemplates('/cms_pifaform_[^\.]+_post\.tpl/');

        // sort templates by their label
        usort($templates, 'cContentTypePifaForm::sortByLabel');

        // loop all templates
        foreach ($templates as $template) {

            // attributes of option element
            $title = $template['label'];
            $value = $template['value'];
            $selected = $template['value'] == $this->_settings['pifaform_template_post'];

            // build option element
            $option = new cHTMLOptionElement($title, $value, $selected);

            // append option element to select element
            $select->addOptionElement(++$index, $option);
        }

        // build div element as wrapper
        $div = new cHTMLDiv(array(
            $label,
            $select
        ));

        // return div element
        return $div;
    }

    /**
     * Builds a select element allowing to choose a single template for the
     * client
     * mail.
     *
     * @return cHTMLDiv
     */
    private function _getSelectMailClientTemplate() {

        // attributes of form field elements
        $id = 'pifaform_mail_client_template_' . $this->_id;

        // build label element
        $label = new cHTMLLabel(Pifa::i18n('template'), $id);

        // build select element
        $select = new cHTMLSelectElement($id, '', $id);
        $select->addOptionElement($index = 0, new cHTMLOptionElement(Pifa::i18n('none'), ''));

        // get templates from client template folder
        $templates = Pifa::getTemplates('/cms_pifaform_[^\.]+_mail_client\.tpl/');

        // sort templates by their label
        usort($templates, 'cContentTypePifaForm::sortByLabel');

        // loop all templates
        foreach ($templates as $template) {

            // attributes of option element
            $title = $template['label'];
            $value = $template['value'];
            $selected = $template['value'] == $this->_settings['pifaform_mail_client_template'];

            // build option element
            $option = new cHTMLOptionElement($title, $value, $selected);

            // append option element to select element
            $select->addOptionElement(++$index, $option);
        }

        // build div element as wrapper
        $div = new cHTMLDiv(array(
            $label,
            $select
        ));

        // return div element
        return $div;
    }

    /**
     * Builds an input element allowing to set the client mail sender address.
     *
     * @return cHTMLDiv
     */
    private function _getInputMailClientFromEmail() {

        // attributes of form field elements
        $label = Pifa::i18n('sender email');
        $id = 'pifaform_mail_client_from_email_' . $this->_id;
        $value = $this->_settings['pifaform_mail_client_from_email'];

        // build label element, input element & div element as wrapper
        $div = new cHTMLDiv(array(
            new cHTMLLabel($label, $id),
            new cHTMLTextbox($id, $value, '', '', $id)
        ));

        // return div element
        return $div;
    }

    /**
     * Builds an input element allowing to set the client mail sender name.
     *
     * @return cHTMLDiv
     */
    private function _getInputMailClientFromName() {

        // attributes of form field elements
        $label = Pifa::i18n('sender name');
        $id = 'pifaform_mail_client_from_name_' . $this->_id;
        $value = $this->_settings['pifaform_mail_client_from_name'];

        // build label element, input element & div element as wrapper
        $div = new cHTMLDiv(array(
            new cHTMLLabel($label, $id),
            new cHTMLTextbox($id, $value, '', '', $id)
        ));

        // return div element
        return $div;
    }

    /**
     * Builds an input element allowing to set the client mail subject.
     *
     * @return cHTMLDiv
     */
    private function _getInputMailClientSubject() {

        // attributes of form field elements
        $label = Pifa::i18n('subject');
        $id = 'pifaform_mail_client_subject_' . $this->_id;
        $value = $this->_settings['pifaform_mail_client_subject'];
        // encode dollar sign so that contained PHP style variable will not be
        // interpreted
        $value = str_replace('$', '&#36;', $value);

        // build label element, input element & div element as wrapper
        $div = new cHTMLDiv(array(
            new cHTMLLabel($label, $id),
            new cHTMLTextbox($id, $value, '', '', $id)
        ));

        // return div element
        return $div;
    }

    /**
     * Builds a select element allowing to choose a single template the system
     * mail.
     *
     * @return cHTMLSelectElement
     */
    private function _getSelectMailSystemTemplate() {

        // attributes of form field elements
        $id = 'pifaform_mail_system_template_' . $this->_id;

        // build label element
        $label = new cHTMLLabel(Pifa::i18n('template'), $id);

        // build select element
        $select = new cHTMLSelectElement($id, '', $id);
        $select->addOptionElement($index = 0, new cHTMLOptionElement(Pifa::i18n('none'), ''));

        // get templates from client template folder
        $templates = Pifa::getTemplates('/cms_pifaform_[^\.]+_mail_system\.tpl/');

        // sort templates by their label
        usort($templates, 'cContentTypePifaForm::sortByLabel');

        // loop all templates
        foreach ($templates as $template) {

            // attributes of option element
            $title = $template['label'];
            $value = $template['value'];
            $selected = $template['value'] == $this->_settings['pifaform_mail_system_template'];

            // build option element
            $option = new cHTMLOptionElement($title, $value, $selected);

            // append option element to select element
            $select->addOptionElement(++$index, $option);
        }

        // build div element as wrapper
        $div = new cHTMLDiv(array(
            $label,
            $select
        ));

        // return div element
        return $div;
    }

    /**
     * Builds an input element allowing to set the system mail sender address.
     *
     * @return cHTMLDiv
     */
    private function _getInputMailSystemFromEmail() {

        // attributes of form field elements
        $label = Pifa::i18n('sender email');
        $id = 'pifaform_mail_system_from_email_' . $this->_id;
        $value = $this->_settings['pifaform_mail_system_from_email'];

        // build label element, input element & div element as wrapper
        $div = new cHTMLDiv(array(
            new cHTMLLabel($label, $id),
            new cHTMLTextbox($id, $value, '', '', $id)
        ));

        // return div element
        return $div;
    }

    /**
     * Builds an input element allowing to set the system mail sender name.
     *
     * @return cHTMLDiv
     */
    private function _getInputMailSystemFromName() {

        // attributes of form field elements
        $label = Pifa::i18n('sender name');
        $id = 'pifaform_mail_system_from_name_' . $this->_id;
        $value = $this->_settings['pifaform_mail_system_from_name'];

        // build label element, input element & div element as wrapper
        $div = new cHTMLDiv(array(
            new cHTMLLabel($label, $id),
            new cHTMLTextbox($id, $value, '', '', $id)
        ));

        // return div element
        return $div;
    }

    /**
     * Builds an input element allowing to set the system mail recipient
     * address.
     *
     * @return cHTMLDiv
     */
    private function _getInputMailSystemRecipientEmail() {

        // attributes of form field elements
        $label = Pifa::i18n('Recipient email');
        $id = 'pifaform_mail_system_recipient_email_' . $this->_id;
        $value = $this->_settings['pifaform_mail_system_recipient_email'];

        // build label element, input element & div element as wrapper
        $div = new cHTMLDiv(array(
            new cHTMLLabel($label, $id),
            new cHTMLTextbox($id, $value, '', '', $id)
        ));

        // return div element
        return $div;
    }

    /**
     * Builds an input element allowing to set the system mail subject.
     *
     * @return cHTMLDiv
     */
    private function _getInputMailSystemSubject() {

        // attributes of form field elements
        $label = Pifa::i18n('subject');
        $id = 'pifaform_mail_system_subject_' . $this->_id;
        $value = $this->_settings['pifaform_mail_system_subject'];
        // encode dollar sign so that contained PHP style variable will not be
        // interpreted
        $value = str_replace('$', '&#36;', $value);

        // build label element, input element & div element as wrapper
        $div = new cHTMLDiv(array(
            new cHTMLLabel($label, $id),
            new cHTMLTextbox($id, $value, '', '', $id)
        ));

        // return div element
        return $div;
    }

    /**
     * Generates the code which should be shown if this content type is shown in
     * the frontend.
     * This code is cached. Thatfor ist no more than the initialisation of this
     * class and the call of its method buildCode(). Otherwise the generated
     * HTML would have been cached.
     *
     * @return string escaped HTML code which sould be shown if content type is
     *         shown in frontend
     */
    public function generateViewCode() {
        $code = '";?' . '><' . '?php $form = new %s(\'%s\', %s, %s); echo $form->buildCode(); ?' . '><' . '?php echo "';
        $code = sprintf($code, get_class($this), $this->_rawSettings, $this->_id, 'array()');

        return $code;
    }

    /**
     * Get code of form (either GET or POST request).
     *
     * @return string escaped HTML code which sould be shown if content type is
     *         shown in frontend
     */
    public function buildCode() {
        $out = '';
        if (0 === cSecurity::toInteger($this->_settings['pifaform_idform'])) {
            // no form was selected
        } else if (0 === strlen(trim($this->_settings['pifaform_module']))) {
            // no module was selected
        } else {
            $moduleClass = trim($this->_settings['pifaform_module']);
            try {
                $filename = Pifa::fromCamelCase($moduleClass);
                $filename = "extensions/class.pifa.$filename.php";
                if (false === file_exists(Pifa::getPath() . $filename)) {
                    $msg = sprintf(Pifa::i18n('MISSING_MODULE_FILE'), $filename);
                    throw new PifaException($msg);
                }
                plugin_include(Pifa::getName(), $filename);
                if (false === class_exists($moduleClass)) {
                    $msg = sprintf(Pifa::i18n('MISSING_MODULE_CLASS'), $moduleClass);
                    throw new PifaException($msg);
                }
                $mod = new $moduleClass($this->_settings);
                $out = $mod->render(true);
            } catch (Exception $e) {
                Pifa::logException($e);
                // log but don't display exception
            }
        }

        // don't encode cached code for output
        // $out = $this->_encodeForOutput($out);

        return $out;
    }
}
