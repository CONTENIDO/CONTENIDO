<?php
/**
 * This file contains the system integrity backend page.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Thomas Stauer
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

class cTinymce4Configuration {
    public function __construct() {
        // decide whether user is allowed to change values
        $this->_perm = ('sysadmin' === cRegistry::getAuth()->getPerms());
    }
    
    private function _checkType($type, $value) {
        if (true === empty($value)) {
            return true;
        }
        if (true === isset($value)) {
            // parameter is k   nown, check it using type expression
            return preg_match($type, $value);
        }
        
        return false;
    }

    private function _checkIsset(array $haystack, array $needles) {
        if (count($haystack) !== count($needles)) {
            return false;
        }
        foreach ($needles as $needle) {
            if (false === isset($haystack[$needle])) {
                return false;
            }
        }

        return true;
    }

    private function _validateToolbarN($toolbarData) {
        // do not use cRequestValidator instance because it does not support multi-dimensional arrays
        if (false === $this->_checkType('/^[a-zA-Z0-9 \-\|_]*$/', $toolbarData)
        || false !== strpos($toolbarData, '||')) {
            return false;
        }

        return true;
    }
    
    /**
     * Function to validate form from showConfigurationForm() 
     * @param array $config The post parameters of submitted form
     * @return multitype:string |boolean
     */
    public function validateForm($config) {
        // Checks for cross site requests and cross site scripting are omitted due to time constraints
        echo "validate";
        // User must be system administrator to change the settings
        if ('sysadmin' !== cRegistry::getAuth()->getPerms()) {
            return false;
        }

        // remove not used area field
        unset($config['area']);
        // remove not used frame field
        unset($config['frame']);
        // remove not used contenido field
        unset($config['contenido']);
        
        // remove x and y values from image submit button in in form
        unset($config['submit_x']);
        unset($config['submit_y']);


        // check if all array entries actually exist
        // abort if too many values are encountered
        $shouldArrayStructure =  array (
            'tinymce4_full' =>
            array (
                    'toolbar1',
                    'toolbar2',
                    'toolbar3',
                    'plugins'
            ),
            'tinymce4_fullscreen' =>
            array (
                    'toolbar1',
                    'toolbar2',
                    'toolbar3',
                    'plugins'
            ),
            'tinymce4',
            'externalplugins'
        );

        if (false === $this->_checkIsset($config['tinymce4_full'], $shouldArrayStructure['tinymce4_full'])) {
            return false;
        }
        if (false === $this->_checkIsset($config['tinymce4_fullscreen'], $shouldArrayStructure['tinymce4_fullscreen'])) {
            return false;
        }
        if (false === isset($config['tinymce4'])) {
            return false;
        }
        if (count($shouldArrayStructure) !== count($config)) {
            return false;
        }

        // do not use cRequestValidator instance because it does not support multi-dimensional arrays
        if (false === $this->_validateToolbarN($config['tinymce4_full']['toolbar1'])
        || false === $this->_validateToolbarN($config['tinymce4_full']['toolbar2'])
        || false === $this->_validateToolbarN($config['tinymce4_full']['toolbar3'])
        || false === $this->_validateToolbarN($config['tinymce4_fullscreen']['toolbar1'])
        || false === $this->_validateToolbarN($config['tinymce4_fullscreen']['toolbar2'])
        || false === $this->_validateToolbarN($config['tinymce4_fullscreen']['toolbar3'])) {
            return false;
        }

        // $config contains only valid content
        var_dump($config);
        return $config;
    }
    
    /**
     * Do not load external plugin if user has permission to request that 
     * @param array $form get parameters from deletion link
     */
    public function removeExternalPluginLoad($form) {
        if (false === $this->_perm) {
            return;
        }
        echo "requested external plugin removal from loading list";
        // TODO: remove the external plugin from the list of plugins to load
    }

    private function _addLabelWithTextbox($description, $name, $width = 150) {
        $label = new cHTMLLabel($description, $name);
        $label->setClass("sys_config_txt_lbl");
        $label->setStyle('width:' . $width . 'px;');
        
        $textbox = new cHTMLTextbox($name);
        if (false === $this->_perm) {
            $textbox->updateAttribute('disabled', 'disabled');
        }
        $div = new cHTMLDiv($label .  $textbox, 'systemSetting');

        return $div;
    }
    
    /**
     * Generates a cHTMLCheckbox based on function arguments and sets its disabled state based on permission check
     * @param string $description Description that will be displayed in checkbox label
     * @param string $name Name of checkbox, this is important for fetching values from sent form
     * @param string $value The value that will be sent as content in the name key
     * @param bool $checked Whether this checkbox is setup as checked
     * @return cHTMLCheckbox Checkbox with label
     */
    private function _addLabelWithCheckbox($description, $name, $value, $checked) {
        $checkBox = new cHTMLCheckbox($name, $value, str_replace('[]', '_', $name . $value), (true === $checked));
        $checkBox->setLabelText($description);
        
        if (false === $this->_perm) {
            $checkBox->updateAttribute('disabled', 'disabled');
        }
        
        return $checkBox;
    }
    
    /**
     * This function lists all external plugins that should be loaded in a table
     * @return string
     */
    private function _listExternalPlugins() {
        /// TODO: use a preference loading function for plugins to list
        $externalPlugins = array(array('name' => 'test', 'url' => 'path/to/test/plugin/plugins.js'));
        
        // build a table
        $table = new cHTMLTable();
        $table->setClass('generic');
        
        // table row
        $headrow = new cHTMLTableRow();
        
        // table column 1 (plugin name)
        $col = new cHTMLTableHead();
        $col->appendContent(i18n('name'));
        $headrow->appendContent($col);
        
        // table column 2 (plugin url)
        $col = new cHTMLTableHead();
        $col->appendContent(i18n('url'));
        $headrow->appendContent($col);
        
        // table column 3 (user actions)
        $col = new cHTMLTableHead();
        $col->appendContent(i18n('Action'));
        $headrow->appendContent($col);
        
        // add columns to table
        $table->appendContent($headrow);
        
        // build table body
        $tbody = new cHTMLTableBody();
        $i = 0;
        $n = count($externalPlugins);
        for ($i; $i < $n; $i++) {
            // new tr
            $row = new cHTMLTableRow();
            
            // create new td
            $td = new cHTMLTableData();
            $td->appendContent($externalPlugins[$i]['name']);
            
            // insert hidden input field
            $input = new cHTMLFormElement();
            $input->setAttribute('type', 'hidden');
            $input->setAttribute('name', 'externalplugins[' . $i . '][name]');
            $input->setAttribute('value', $externalPlugins[$i]['name']);
            $td->appendContent($input);
            
            // add td to tr
            $row->appendContent($td);

            // create new td
            $td = new cHTMLTableData();
            $td->appendContent($externalPlugins[$i]['url']);
            
            // insert hidden input field
            $input = new cHTMLFormElement();
            $input->setAttribute('type', 'hidden');
            $input->setAttribute('name', 'externalplugins[' . $i . '][url]');
            $input->setAttribute('value', $externalPlugins[$i]['url']);
            $td->appendContent($input);
            
            // add td to tr
            $row->appendContent($td);
            
            // create new td
            $td = new cHTMLTableData();
            if (true === $this->_perm) {
                // Edit/delete links only for sysadmin
                $oLinkDelete = new cHTMLLink();
                $oLinkDelete->setCLink(cRegistry::getArea(), cRegistry::getFrame(), "system_wysiwyg_tinymce4_delete_item");
                $oLinkDelete->setCustom("external_plugin_idx", urlencode($i));
                $img = new cHTMLImage(cRegistry::getBackendUrl() . cRegistry::getConfigValue('path', 'images') . 'delete.gif');
                $img->setAttribute('alt', i18n("Delete"));
                $img->setAttribute('title', i18n("Delete"));
                $oLinkDelete->appendContent($img);
                $td->appendContent($oLinkDelete);
            }
            
            // add td to tr
            $row->appendContent($td);
            
            // insert row into table body
            $tbody->appendContent($row);
        }
        // append empty row to let user enter new plugins
        $row = new cHTMLTableRow();
        
        // create new td for plugin name
        $td = new cHTMLTableData();
        $input = new cHTMLFormElement('externalplugins[' . $i . '][name]');
        $td->appendContent($input);
        $row->appendContent($td);
        
        // create new td for plugin url
        $td = new cHTMLTableData();
        $input = new cHTMLFormElement('externalplugins[' . $i . '][url]');
        $td->appendContent($input);
        $row->appendContent($td);
        
        // empty action column
        $td = new cHTMLTableData();
        $row->appendContent($td);
        
        // append row to table body
        $tbody->appendContent($row);


        // insert table body into table
        $table->appendContent($tbody);

        // return table as string
        return $table->render();
    }
    
    public function showConfigurationForm() {
        $page = new cGuiPage('system_wysiwyg_tinymce4', '', '5');
        $auth = cRegistry::getAuth();
        $frame = cRegistry::getFrame();
        $area = cRegistry::getArea();

        // validate if user has permission to edit this area
        if (false === cRegistry::getPerm()->have_perm_area_action($area, 'edit_system_wysiwyg_tinymce4')) {
            $page->displayCriticalError(i18n('Access denied'));
            $page->render();
            return;
        }
        
        $curWysiwygEditor = getEffectiveSetting('wysiwyg', 'editor', 'tinymce3');
        $tmpl = new cTemplate();
        
        //if (false === cRegistry::getPerm())
        
        $page->displayInfo(i18n('Currently active WYSIWYG editor: ' . cWYSIWYGEditor::getCurrentWysiwygEditorName()));
        $form = new cGuiTableForm('system_wysiwyg_tinymce4');
        $form->addHeader(i18n('Tinymce 4 configuration'));

        $form->setVar('area', $area);
        $form->setVar('frame', $frame);
        $form->setVar('action', 'edit_tinymce4');


        $containerDiv = new cHTMLDiv();
        $containerDiv->appendContent($this->_addLabelWithTextbox('Toolbar 1:', 'tinymce4_full[toolbar1]'));
        $containerDiv->appendContent($this->_addLabelWithTextbox('Toolbar 2:', 'tinymce4_full[toolbar2]'));
        $containerDiv->appendContent($this->_addLabelWithTextbox('Toolbar 3:', 'tinymce4_full[toolbar3]'));
        $containerDiv->appendContent($this->_addLabelWithTextbox('Plugins:', 'tinymce4_full[plugins]'));
        $form->add(i18n('Settings of inline editor in fullscreen mode'), $containerDiv->render());
        
        $containerDiv = new cHTMLDiv();
        $containerDiv->appendContent($this->_addLabelWithTextbox('Toolbar 1:', 'tinymce4_fullscreen[toolbar1]'));
        $containerDiv->appendContent($this->_addLabelWithTextbox('Toolbar 2:', 'tinymce4_fullscreen[toolbar2]'));
        $containerDiv->appendContent($this->_addLabelWithTextbox('Toolbar 3:', 'tinymce4_fullscreen[toolbar3]'));
        $containerDiv->appendContent($this->_addLabelWithTextbox('Plugins:', 'tinymce4_fullscreen[plugins]'));
        $form->add(i18n('Settings of editor in separate editor page'), $containerDiv->render());
        
        // GZIP editor over HTTP using tinymce's library
        $containerDiv = new cHTMLDiv();
        $containerDiv->appendContent($this->_addLabelWithCheckbox('GZIP Tinymce (only activate if server does not compress content already)', 'contenido_gzip', 'contenido_gzip', false, false));
        $form->add(i18n('contenido_gzip'), $containerDiv->render());
        
        // Add jump lists to tinymce's dialogs
        $containerDiv = new cHTMLDiv();
        $containerDiv->appendContent($this->_addLabelWithCheckbox('Provide jump lists in image insertion dialog', 'contenido_lists[]', 'image', true));
        $containerDiv->appendContent($this->_addLabelWithCheckbox('Provide jump lists in link insertion dialog', 'contenido_lists[]', 'link', true));
        $form->add(i18n('contenido_lists'), $containerDiv->render());
        
        // external plugins
        $containerDiv = new cHTMLDiv();
        $containerDiv->appendContent($this->_listExternalPlugins());
        $form->add(i18n('External plugins to load'), $containerDiv);
        
        //add textarea for custom tinymce 4 settings
        $textarea = new cHTMLTextarea('tinymce4');
        $textarea->setAttribute('style', 'width: 99%;');
        $form->add(i18n('Additional parameters (JSON passed to tinymce constructor)'), $textarea->render());
        
        // check permission to save system wysiwyg editor settings
        if (false === $this->_perm) {
            $form->setActionButton('submit', cRegistry::getBackendUrl() . 'images/but_ok_off.gif', i18n("You are not sysadmin. You can't change these settings."), 's');
        }

        $page->set('s', 'FORM', $form->render());
        $page->set('s', 'RELOAD_HEADER', (false) ? 'true' : 'false');
        $page->render();
    }
}
