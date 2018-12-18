<?php
/**
 * This file contains the system integrity backend page.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Thomas Stauer
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 *
 * @package    Core
 * @subpackage Backend
 */
class cTinymce4Configuration {

    /**
     *
     * @var bool
     */
    public $successfully = false;

    /**
     *
     * @var bool
     */
    private $_perm = false;

    /**
     *
     * @var array
     */
    private $_configErrors = array();

    /**
     * Constructor function.
     * Inits permission.
     */
    public function __construct() {

        // decide whether user is allowed to change values
        $perms = cRegistry::getAuth()->getPerms();
        if (cString::findFirstPos($perms, 'sysadmin') !== false) {
            $this->_perm = true;
        }
    }

    /**
     * Generate a div containing a label and a textbox.
     *
     * @param string $description
     *        Label text before the textbox
     * @param string $name
     *        Name of textbox form element
     * @param string $value
     *        Default value of textbox
     * @param number $width
     *        Width of label in px
     * @return cHTMLDiv
     *        The div element containing label and textbox
     */
    private function _addLabelWithTextarea($description, $name, $value = '', $width = 75) {
        $label = new cHTMLLabel($description, $name);
        $label->setClass("sys_config_txt_lbl");
        $label->setStyle('width:' . $width . 'px; vertical-align: top;');

        $textarea = new cHTMLTextarea($name);
        $textarea->setValue($value);
        $textarea->setAttribute('style', 'box-sizing: border-box; width: 600px;');
        if (true !== $this->_perm) {
            $textarea->updateAttribute('disabled', 'disabled');
        }
        $div = new cHTMLDiv($label .  $textarea, 'systemSetting');

        return $div;
    }

    /**
     * Generates a cHTMLCheckbox based on function arguments and sets
     * its disabled state based on permission check.
     *
     * @param string $description
     *        Description that will be displayed in checkbox label
     * @param string $name
     *        Name of checkbox, this is important for fetching values
     *        from sent form
     * @param string $value
     *        The value that will be sent as content in the name key
     * @param bool $checked
     *        Whether this checkbox is setup as checked
     * @return cHTMLCheckbox
     *        Checkbox with label
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
     * Check if a type pattern matches value.
     *
     * @param string $type
     *        Pattern that is applied to value
     * @param string $value
     *        Value that is checked for pattern
     * @return boolean
     *        Whether type matches value
     */
    private function _checkType($type, $value) {
        if (true === empty($value)) {
            return true;
        }
        if (true === isset($value)) {
            // parameter is known, check it using type expression
            // preg match returns 1 if match occurs
            return (1 === preg_match($type, $value));
        }

        return false;
    }

    /**
     *
     * @param array haystack
     * @param array $needles
     * @return bool
     */
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

    /**
     * This function lists all external plugins that should be shown in
     * a table.
     *
     * @return string
     */
    private function _listExternalPlugins() {
        /// TODO: use a preference loading function for plugins to list
        $externalPlugins = static::get(array(), 'raw', 'externalplugins');

        // build a table
        $table = new cHTMLTable();
        $table->setClass('generic');

        // table row
        $headrow = new cHTMLTableRow();

        // table column 1 (plugin name)
        $col = new cHTMLTableHead();
        $col->appendContent(i18n('Name'));
        $headrow->appendContent($col);

        // table column 2 (plugin url)
        $col = new cHTMLTableHead();
        $col->appendContent(i18n('URL'));
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
        $n = count($externalPlugins) -1;
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

    /**
     * Function to check if toolbar data contains valid input.
     *
     * @param string $toolbarData
     *        The toolbar data to check for validity
     * @return boolean
     *        True if toolbar data is valid, false otherwise
     */
    private function _validateToolbarN($toolbarData) {
        // do not use cRequestValidator instance
        // because it does not support multi-dimensional arrays
        if (false === $this->_checkType('/^[a-zA-Z0-9 \-\|_]*$/', $toolbarData)
        || false !== cString::findFirstPos($toolbarData, '||')) {
            return false;
        }

        return true;
    }

    /**
     * Variadic function to obtain config values using nested key values.
     *
     * @param mixed $default
     *        Default value to use in case no value is set
     * param string keys
     *        The keys to access values in configuration
     * @return string|array
     */
    public static function get($default) {
        $cfg = cRegistry::getConfig();

        if (false === isset($cfg['wysiwyg'])
        || false === isset($cfg['wysiwyg']['tinymce4'])) {
            $configPath = cRegistry::getConfigValue('path', 'contenido_config') . 'config.wysiwyg_tinymce4.php';
            // check if configuration file exists
            if (true !== cFileHandler::exists($configPath)) {
                return $default;
            }
            // check if file is reable
            if (true !== cFileHandler::readable($configPath)) {
                return $default;
            }
            // Include configuration file
            require $configPath;
        }

        // check number of keys passed to function
        $numargs = func_num_args();
        if (0 === $numargs) {
            return $default;
        }

        // walk through config
        $result = cRegistry::getConfig();

        // select ['wysiwyg']['tinymce4'] by default
        if (false === isset($result['wysiwyg'])) {
            return $default;
        }
        if (false === $result['wysiwyg']['tinymce4']) {
            return $default;
        }
        $result = $result['wysiwyg']['tinymce4'];
        // get values in key path that user requested
        for ($i = 0; $i < $numargs -1; $i++) {
            if (false === isset($result[func_get_arg(1 + $i)])) {
                return $default;
            }
            // jump one array level deeper into the result
            $result = $result[func_get_arg(1 + $i)];
        }

        return $result;
    }

    /**
     * Function to validate form from showConfigurationForm().
     *
     * @param array $config
     *        The post parameters of submitted form
     * @return boolean|array
     *        False if data should not be saved, otherwise data to save
     */
    public function validateForm($config) {
        // Checks for cross site requests and cross site scripting
        // are omitted due to time constraints

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

        // form action (added in showConfigurationForm() inside this
        // class) is not used for saving config
        if ('system_wysiwyg_tinymce4_delete_item' === $_GET['action']) {
            return $this->removeExternalPluginLoad($_GET);
        }
        unset($config['action']);

        // check if config should be deleted
        if (isset($_POST['reset'])) {
            $noti = new cGuiNotification();

            // try to delete configuration
            $configPath = cRegistry::getConfigValue('path', 'contenido_config');
            $configPath .= 'config.wysiwyg_tinymce4.php';
            if (cFileHandler::exists($configPath)
            && cFileHandler::writeable($configPath)) {
                cFileHandler::remove($configPath);
                $noti->displayNotification(cGuiNotification::LEVEL_INFO, i18n('TinyMCE 4 configuration got reset back to default'));
            } else {
                // can not delete config, display message
                $noti->displayNotification(cGuiNotification::LEVEL_ERROR, i18n('Can not delete config file'));
            }

            // do not save config
            return false;
        }

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
            'contenido_lists',
            'contenido_gzip',
            'custom',
        );

        // get name of first key
        reset($config);
        $key = key($config);

        if (false === isset($_POST['externalplugins']) && false === $this->_checkIsset($config[$key]['tinymce4_full'], $shouldArrayStructure['tinymce4_full'])) {
            $this->_configErrors[] = i18n('Fullscreen config of inline editor is erroneous.');
            return false;
        }
        if (false === isset($_POST['externalplugins']) && false === $this->_checkIsset($config[$key]['tinymce4_fullscreen'], $shouldArrayStructure['tinymce4_fullscreen'])) {
            $this->_configErrors[] = i18n('Config of editor on separate editor page is erroneous.');
            return false;
        }
        if (false === isset($_POST['externalplugins']) && false === isset($config[$key]['custom'])) {
            $this->_configErrors[] = i18n('Custom configuration of tinyMCE 4 is not set.');
            return false;
        }

        // do not use cRequestValidator instance because it does not support multi-dimensional arrays
        if (false === $this->_validateToolbarN($config[$key]['tinymce4_full']['toolbar1'])
        || false === $this->_validateToolbarN($config[$key]['tinymce4_full']['toolbar2'])
        || false === $this->_validateToolbarN($config[$key]['tinymce4_full']['toolbar3'])
        || false === $this->_validateToolbarN($config[$key]['tinymce4_fullscreen']['toolbar1'])
        || false === $this->_validateToolbarN($config[$key]['tinymce4_fullscreen']['toolbar2'])
        || false === $this->_validateToolbarN($config[$key]['tinymce4_fullscreen']['toolbar3'])) {
            $this->_configErrors[] = i18n('Toolbar(s) of editor contain erroneous data.');
            return false;
        }

        // remove last entry of external plugins if it is empty
        $lastExternalPlugin = $config[$key]['externalplugins'][count($config[$key]['externalplugins']) -1];
        if ('' === $lastExternalPlugin['name']
        && '' === $lastExternalPlugin['url']) {
            unset($config[$key]['externalplugins'][count($config[$key]['externalplugins']) -1]);
        }

        // custom tinymce 4 settings overwrite other fields
        if (cRegistry::getConfigValue('simulate_magic_quotes') === true) {
            $config[$key]['custom'] = stripslashes($config[$key]['custom']);
        }

        // unescape strings then build config
        $customConfig = (array) json_decode($config[$key]['custom'], true);
        switch(json_last_error()) {
            case JSON_ERROR_DEPTH:
                $this->_configErrors[] = i18n('Maximum stack depth exceeded while decoding json');
            return false;
            case JSON_ERROR_CTRL_CHAR:
                $this->_configErrors[] = i18n('Unexpected control character found');
            return false;
            case JSON_ERROR_SYNTAX:
                $this->_configErrors[] = i18n('Syntax error, malformed JSON');
            return false;
        }

        // append new config to old config
        $origConfig = static::get(array(), 'raw');
        $config['raw'] = array_merge($origConfig, $config);
        //$config['raw'] = $config;

        // use custom parameters if they are correct JSON
        if (JSON_ERROR_NONE === json_last_error()) {
            $config[$key] = array_merge($config[$key], $customConfig);
        }
        unset($config[$key]['custom']);

        // put all config values into ['tinymce4']['tinymce4']
        // put the raw settings of config page into ['tinymce4']['raw']
        $origConfig = static::get(array(), 'tinymce4');
        $config = array_merge($origConfig, $config);
        $res = array('tinymce4' => array('tinymce4' =>$config));
        //unset($res['tinymce4']['tinymce4']['custom']);
        $res['tinymce4']['raw'] = $res['tinymce4']['tinymce4']['raw'];
        unset($res['tinymce4']['tinymce4']['raw']);

        // $config contains only valid content, returned processed config
        return $res;
    }

    /**
     * Do not load external plugin if user has permission to request that.
     *
     * @param array $form
     *        get parameters from deletion link
     * @return boolean|array
     *        False if data should not be saved, otherwise data to save
     */
    public function removeExternalPluginLoad($form) {
        // abort if user has not sufficient permissions
        if (false === $this->_perm) {
            return;
        }

        $pluginToRemoveIdx = (int) $form['external_plugin_idx'];

        // load config through usage of get function
        $settings = static::get(false);

        // no config or no external plugins or no plugin with that index
        // means nothing to remove
        if (false === $settings
        || false === isset($settings['raw'])
        || false === isset($settings['raw']['externalplugins'])
        || false === isset($settings['raw']['externalplugins'][$pluginToRemoveIdx])) {
            return false;
        }

        // remove value from raw settings
        unset($settings['raw']['externalplugins'][$pluginToRemoveIdx]);
        // remove stray custom setting
        unset($settings['raw']['externalplugins']['custom']);

        // re-index array
        $settings['raw']['externalplugins'] = array_values($settings['raw']['externalplugins']);

        // apply raw settings to computed settings
        $settings['tinymce4']['externalplugins'] = $settings['raw']['externalplugins'];

        return array('tinymce4' => $settings);
    }

    /**
     * Generates an HTML form to configure tinymce 4.
     */
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

        if (count($this->_configErrors) > 0) {
            $errorMessage = i18n('The following errors occurred when trying to verify configuration:') . '<ul>';
            foreach ($this->_configErrors as $error) {
                $errorMessage .= '<li>' . $error . '</li>';
            }
            $errorMessage .= '</ul>';
            $page->displayError($errorMessage);
        }

        if ($this->successfully === true) $page->displayOk(i18n("Changes saved successfully!"));

        $page->displayInfo(sprintf(i18n('Currently active WYSIWYG editor: %s'), cWYSIWYGEditor::getCurrentWysiwygEditorName()));

        $oTypeColl = new cApiTypeCollection();
        $oTypeColl->select();
        $result = '';
        while (false !== ($typeEntry = $oTypeColl->next())) {
            // specify a shortcut for type field
            $curType = $typeEntry->get('type');

            $contentTypeClassName = cTypeGenerator::getContentTypeClassName($curType);
            if (false === class_exists($contentTypeClassName)) {
                continue;
            }
            $cContentType = new $contentTypeClassName(null, 0, array());
            if (false === $cContentType->isWysiwygCompatible()) {
                continue;
            }


            $form = new cGuiTableForm('system_wysiwyg_tinymce4_' . strtolower($curType));
            $form->setAcceptCharset('UTF-8');

            $form->addHeader(i18n('TinyMCE 4 configuration for ') . $curType);

            $form->setVar('area', $area);
            $form->setVar('frame', $frame);
            $form->setVar('action', 'edit_tinymce4');


            $containerDiv = new cHTMLDiv();
            if ('CMS_HTMLHEAD' === $curType) {
                $defaultToolbar1 = static::get('undo redo | consave conclose', 'raw', $curType, 'tinymce4_full', 'toolbar1');
                $defaultToolbar2 = static::get('', 'raw', $curType, 'tinymce4_full', 'toolbar2');
                $defaultToolbar3 = static::get('', 'raw', $curType, 'tinymce4_full', 'toolbar3');
                $defaultPlugins = static::get('conclose', 'raw', $curType, 'tinymce4_full', 'plugins');
            } else {
                $defaultToolbar1 = static::get('cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen', 'raw', $curType, 'tinymce4_full', 'toolbar1');
                $defaultToolbar2 = static::get('link unlink anchor image media hr | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code', 'raw', $curType, 'tinymce4_full', 'toolbar2');
                $defaultToolbar3 = static::get('table | formatselect fontselect fontsizeselect | consave conclose', 'raw', $curType, 'tinymce4_full', 'toolbar3');
                $defaultPlugins = static::get('charmap code table conclose hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor', 'raw', $curType, 'tinymce4_full', 'plugins');
            }
            $containerDiv->appendContent($this->_addLabelWithTextarea('Toolbar 1:', $curType . '[tinymce4_full][toolbar1]', $defaultToolbar1));
            $containerDiv->appendContent($this->_addLabelWithTextarea('Toolbar 2:', $curType . '[tinymce4_full][toolbar2]', $defaultToolbar2));
            $containerDiv->appendContent($this->_addLabelWithTextarea('Toolbar 3:', $curType . '[tinymce4_full][toolbar3]', $defaultToolbar3));
            $containerDiv->appendContent($this->_addLabelWithTextarea('Plugins:', $curType . '[tinymce4_full][plugins]', $defaultPlugins));
            $form->add(i18n('Settings of editor in separate editor page'), $containerDiv->render());

            $containerDiv = new cHTMLDiv();
            if ('CMS_HTMLHEAD' === $curType) {
                $defaultToolbar1 = static::get('undo redo | consave conclose', 'raw', $curType, 'tinymce4_inline', 'toolbar1');
                $defaultToolbar2 = static::get('', 'raw', $curType, 'tinymce4_inline', 'toolbar2');
                $defaultToolbar3 = static::get('', 'raw', $curType, 'tinymce4_inline', 'toolbar3');
                $defaultPlugins = static::get('conclose', 'raw', $curType, 'tinymce4_inline', 'plugins');
            } else {
                $defaultToolbar1 = static::get('bold italic underline strikethrough | undo redo | bullist numlist separator forecolor backcolor | alignleft aligncenter alignright | confullscreen | consave conclose', 'raw', $curType, 'tinymce4_inline', 'toolbar1');
                $defaultToolbar2 = static::get('', 'raw', $curType, 'tinymce4_inline', 'toolbar2');
                $defaultToolbar3 = static::get('', 'raw', $curType, 'tinymce4_inline', 'toolbar3');
                $defaultPlugins = static::get('conclose confullscreen media table textcolor', 'raw', $curType, 'tinymce4_inline', 'plugins');
            }
            $containerDiv->appendContent($this->_addLabelWithTextarea('Toolbar 1:', $curType . '[tinymce4_inline][toolbar1]', $defaultToolbar1));
            $containerDiv->appendContent($this->_addLabelWithTextarea('Toolbar 2:', $curType . '[tinymce4_inline][toolbar2]', $defaultToolbar2));
            $containerDiv->appendContent($this->_addLabelWithTextarea('Toolbar 3:', $curType . '[tinymce4_inline][toolbar3]', $defaultToolbar3));
            $containerDiv->appendContent($this->_addLabelWithTextarea('Plugins:', $curType . '[tinymce4_inline][plugins]', $defaultPlugins));
            $form->add(i18n('Settings of inline editor in inline mode'), $containerDiv->render());

            $containerDiv = new cHTMLDiv();
            if ('CMS_HTMLHEAD' === $curType) {
                $defaultToolbar1 = static::get('undo redo | consave conclose', 'raw', $curType, 'tinymce4_fullscreen', 'toolbar1');
                $defaultToolbar2 = static::get('', 'raw', $curType, 'tinymce4_fullscreen', 'toolbar2');
                $defaultToolbar3 = static::get('', 'raw', $curType, 'tinymce4_fullscreen', 'toolbar3');
                $defaultPlugins = static::get('conclose', 'raw', $curType, 'tinymce4_fullscreen', 'plugins');
            } else {
                $defaultToolbar1 = static::get('cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen', 'raw', $curType, 'tinymce4_fullscreen', 'toolbar1');
                $defaultToolbar2 = static::get('link unlink anchor image media | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code', 'raw', $curType, 'tinymce4_fullscreen', 'toolbar2');
                $defaultToolbar3 = static::get('table | formatselect fontselect fontsizeselect | consave conclose', 'raw', $curType, 'tinymce4_fullscreen', 'toolbar3');
                $defaultPlugins = static::get('charmap code conclose table hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor', 'raw', $curType, 'tinymce4_fullscreen', 'plugins');
            }
            $containerDiv->appendContent($this->_addLabelWithTextarea('Toolbar 1:', $curType . '[tinymce4_fullscreen][toolbar1]', $defaultToolbar1));
            $containerDiv->appendContent($this->_addLabelWithTextarea('Toolbar 2:', $curType . '[tinymce4_fullscreen][toolbar2]', $defaultToolbar2));
            $containerDiv->appendContent($this->_addLabelWithTextarea('Toolbar 3:', $curType . '[tinymce4_fullscreen][toolbar3]', $defaultToolbar3));
            $containerDiv->appendContent($this->_addLabelWithTextarea('Plugins:', $curType . '[tinymce4_fullscreen][plugins]', $defaultPlugins));
            $form->add(i18n('Settings of inline editor in fullscreen mode'), $containerDiv->render());

            // GZIP editor over HTTP using tinymce's library
            $containerDiv = new cHTMLDiv();
            $checked = 'contenido_gzip' === static::get(false, 'raw', $curType, 'contenido_gzip');
            $containerDiv->appendContent($this->_addLabelWithCheckbox(i18n('GZIP TinyMCE (only activate if server does not compress content already)'), $curType . '[contenido_gzip]', 'contenido_gzip', $checked));
            $form->add(i18n('contenido_gzip'), $containerDiv->render());

            // Add jump lists to tinymce's dialogs
            $containerDiv = new cHTMLDiv();
            $checked = true === ('image' === static::get(false, 'raw', $curType, 'contenido_lists', 'image'));
            $containerDiv->appendContent($this->_addLabelWithCheckbox(i18n('Provide jump lists in image insertion dialog'), $curType . '[contenido_lists][image]', 'image', $checked));
            $checked = true === ('link' === static::get(false, 'raw', $curType, 'contenido_lists', 'link'));
            $containerDiv->appendContent($this->_addLabelWithCheckbox(i18n('Provide jump lists in link insertion dialog'), $curType . '[contenido_lists][link]', 'link', $checked));
            $form->add(i18n('contenido_lists'), $containerDiv->render());

            //add textarea for custom tinymce 4 settings
            $textarea = new cHTMLTextarea($curType . '[custom]');
            $textarea->setAttribute('style', 'width: 99%;');
            $defaultParams = '';
            if ('CMS_HTMLHEAD' === $curType) {
                $defaultParams = '{' . PHP_EOL . '"inline": true,' . PHP_EOL . '"menubar": false' . PHP_EOL . '}';
            }
            $textarea->setValue(static::get($defaultParams, 'raw', $curType, 'custom'));
            $form->add(i18n('Additional parameters (JSON passed to tinymce constructor)'), $textarea->render());

            // check permission to save system wysiwyg editor settings
            if (false === $this->_perm) {
                $form->setActionButton('submit', cRegistry::getBackendUrl() . 'images/but_ok_off.gif', i18n("You are not sysadmin. You can't change these settings."), 's');
            }
            $result .= '<p>' . $form->render() . '</p>';
        }

        // external plugins (can not be configured per CMS-type)
        $form = new cGuiTableForm('system_wysiwyg_tinymce4_external_plugins');
        $form->setAcceptCharset('UTF-8');
        $form->addHeader(i18n('TinyMCE 4 configuration for external plugins'));

        $form->setVar('area', $area);
        $form->setVar('frame', $frame);
        $form->setVar('action', 'edit_tinymce4');
        $containerDiv = new cHTMLDiv();
        $containerDiv->appendContent($this->_listExternalPlugins());
        $form->add(i18n('External plugins to load'), $containerDiv);
        $result .= '<p>' . $form->render() . '</p>';

        $configPath = cRegistry::getConfigValue('path', 'contenido_config');
        $configPath .= 'config.wysiwyg_tinymce4.php';
        if (cFileHandler::exists($configPath)
        && cFileHandler::writeable($configPath)) {
            $resetForm = new cHTMLForm('system_wysiwyg_tinymce4_general_options', 'main.php', 'post');
            $resetForm->setVar('area', $area);
            $resetForm->setVar('frame', $frame);
            $resetForm->setVar('action', 'edit_tinymce4');
            $oResetButton = new cHTMLButton('reset', i18n('Reset configuration back to default'));
            $oResetButton->setAttribute('value', i18n('Reset Configuration'));

            $resetForm = $resetForm->appendContent($oResetButton);
            $result .= $resetForm->render();
        }


        $page->set('s', 'FORM', $result);
        $page->set('s', 'RELOAD_HEADER', (false) ? 'true' : 'false');
        $page->render();
    }
}
