<?php

/**
 * This file contains the navigation GUI class.
 *
 * @package          Core
 * @subpackage       GUI
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.api.string.php');
cInclude('includes', 'functions.api.images.php');

/**
 * Backend navigation class.
 *
 * Renders the header navigation document containing the navigation structure.
 *
 * @package    Core
 * @subpackage GUI
 */
class cGuiNavigation {

    /**
     * Array storing all data.
     *
     * @var  array
     */
    public $data = [];

    /**
     * Array storing all errors.
     *
     * @var  array
     */
    protected $errors = [];

    /**
     * @var cXmlReader
     */
    protected $_xml;

    /**
     * @var cXmlReader
     */
    protected $_plugXml;

    /**
     * Constructor to create an instance of this class.
     *
     * Loads the XML language file using cXmlReader.
     *
     * @throws cException if XML language files could not be loaded
     */
    public function __construct() {
        $cfg = cRegistry::getConfig();

        $this->_xml = new cXmlReader();
        $this->_plugXml = new cXmlReader();

        // Load language file
        if (!$this->_xml->load($cfg['path']['xml'] . "navigation.xml")) {
            throw new cException('Unable to load any XML language file');
        }
    }

    /**
     * Magic getter function for outdated variable names.
     *
     * @param string $name
     *         Name of the variable
     * @return int|string|void
     * @throws cInvalidArgumentException
     */
    public function __get($name) {
        if ($name === 'xml' || $name == 'plugxml') {
            cDeprecated("The property `' . $name . '` is deprecated since CONTENIDO 4.10.2, it isd not meant for public usage.");
            return $this->{$name};
        }
    }

    /**
     * Extracts caption from the XML language file including plugins
     * extended multilang version.
     *
     * @param string $location
     *         The location of navigation item caption. Feasible values are
     *         - "{xmlFilePath};{XPath}": Path to XML File and the XPath
     *         value separated by semicolon. This type is used to extract
     *         caption from a plugin XML file.
     *         - "{XPath}": XPath value to extract caption from CONTENIDO
     *         XML file
     * @throws cException
     *         if XML language files could not be loaded
     * @return string
     *         The found caption
     */
    public function getName($location) {
        $cfg = cRegistry::getConfig();
        $belang = cRegistry::getBackendLanguage();

        // If a ";" is found entry is from a plugin -> explode location,
        // first is xml file path, second is xpath location in xml file.
        if (strstr($location, ';')) {
            $locs = explode(';', $location);
            $file = trim($locs[0]);
            $xpath = trim($locs[1]);

            $filepath = explode('/', $file);
            $counter = count($filepath) - 1;

            if ($filepath[$counter] == '') {
                unset($filepath[$counter]);
                $counter--;
            }

            if (strstr($filepath[$counter], '.xml')) {
                $filename = $filepath[$counter];
                unset($filepath[$counter]);
                $counter--;
            }

            $filepath[($counter + 1)] = '';

            $filepath = implode('/', $filepath);

            if (!$this->_plugXml->load($cfg['path']['plugins'] . $filepath . $cfg['lang'][$belang])) {
                if (!isset($filename)) {
                    $filename = 'lang_en_US.xml';
                }
                if (!$this->_plugXml->load($cfg['path']['plugins'] . $filepath . $filename)) {
                    throw new cException("Unable to load $filepath XML language file");
                }
            }
            $caption = $this->_plugXml->getXpathValue('/language/' . $xpath);
        } else {
            $caption = $this->_xml->getXpathValue('/language/' . $location);
        }

        return i18n($caption);
    }

    /**
     * Reads and fills the navigation structure data
     *
     * @throws cDbException
     * @throws cException
     */
    public function _buildHeaderData() {
        $cfg = cRegistry::getConfig();
        $perm = cRegistry::getPerm();
        $db = cRegistry::getDb();
        $db2 = cRegistry::getDb();

        // Load main items
        $sql = "SELECT `idnavm`, `location` FROM `%s` ORDER BY `idnavm`";
        $db->query($sql, cRegistry::getDbTableName('nav_main'));

        $tabNavSub = cRegistry::getDbTableName('nav_sub');
        $tabArea = cRegistry::getDbTableName('area');

        // Loop result and build array
        while ($db->nextRecord()) {

            // Extract names from the XML document.
            $main = $this->getName($db->f('location'));

            // Build data array
            $this->data[$db->f('idnavm')] = [$main];

            $sql = "SELECT
                        a.location AS location, b.name AS area, b.relevant
                    FROM
                        " . $tabNavSub . " AS a, " . $tabArea . " AS b
                    WHERE
                        a.idnavm = " . $db->f('idnavm') . " AND
                        a.level  = 0 AND
                        b.idarea = a.idarea AND
                        a.online = 1 AND
                        b.online = 1
                    ORDER BY
                        a.idnavs";

            $db2->query($sql);

            while ($db2->nextRecord()) {
                $area = $db2->f('area');
                if ($perm->have_perm_area_action($area) || $db2->f('relevant') == 0) {
                    // if this menu entry is a plugin and plugins are disabled, ignore it
                    if (cString::findFirstPos($db2->f('location'), ';') !== false && $cfg['debug']['disable_plugins']) {
                        continue;
                    }
                    // Extract names from the XML document.
                    try {
                        $name = $this->getName($db2->f('location'));
                    } catch(cException $e) {
                        $this->errors[] = i18n('Unable to load ' . $db2->f('location'));
                        continue;
                    }
                    $this->data[$db->f('idnavm')][] = [$name, $area];
                }
            }
        }

        // debugging information
        cDebug::out(print_r($this->data, true));
    }

    /**
     * Function to build the CONTENIDO header document for backend.
     *
     * @param int $lang
     *         The language to use for header doc creation
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function buildHeader($lang) {
        $this->_buildHeaderData();

        $sess = cRegistry::getSession();
        $cfg = cRegistry::getConfig();
        $client = cRegistry::getClientId();
        $main = new cTemplate();
        $sub = new cTemplate();

        $t_sub = '';
        $numSubMenus = 0;

        $properties = new cApiPropertyCollection();
        $clientImage = $properties->getValue('idclient', $client, 'backend', 'clientimage', false);

        $sJsEvents = '';
        foreach ($this->data as $id => $item) {
            $sub->reset();
            $genSubMenu = false;

            foreach ($item as $key => $value) {
                if (is_array($value)) {
                    $sub->set('s', 'SUBID', 'sub_' . $id);

                    // create sub menu link
                    $link = new cHTMLLink();
                    $link->disableAutomaticParameterAppend();
                    $link->setClass('sub');
                    $link->setID('sub_' . $value[1]);
                    $link->setLink($sess->url('frameset.php?area=' . $value[1]));
                    $link->setTargetFrame('content');
                    $link->setContent(i18n($value[0]));

                    if ($cfg['help'] == true) {
                        $sJsEvents .= "\n\t" . '$("#sub_' . $value[1] . '").click(function() { $("#help").attr("data", "' . $value[0] . '"); })';
                    }
                    $sub->set('d', 'CAPTION', $link->render());

                    $sub->next();
                    $genSubMenu = true;
                }
            }

            if ($genSubMenu) {
                $link = new cHTMLLink();
                $link->setClass('main');
                $link->setID('main_' . $id);
                $link->setLink('javascript://');
                $link->setAttribute('ident', 'sub_' . $id);
                $link->setContent(i18n($item[0]));

                $main->set('d', 'CAPTION', $link->render());
                $main->next();

                $numSubMenus++;
            } else {
                // first entry in array is a main menu item
            }

            // generate a sub menu item.
            $t_sub .= $sub->generate($cfg['path']['templates'] . $cfg['templates']['submenu'], true);
        }

        if ($numSubMenus == 0) {
            $main->set('d', 'CAPTION', '&nbsp;');
            $main->next();
        }

        $main->set('s', 'SUBMENUS', $t_sub);

        $backendUrl = cRegistry::getBackendUrl();

        // my CONTENIDO link
        $link = new cHTMLLink();
        $link->setClass('main');
        $link->setTargetFrame('content');
        $link->setLink($sess->url("frameset.php?area=mycontenido&frame=4"));
        $link->setContent('<img class="borderless vAlignMiddle" src="' . $backendUrl . $cfg['path']['images'] . 'my_contenido.gif" alt="My CONTENIDO" id="imgMyContenido" title="' . i18n("My CONTENIDO") . '">');
        $main->set('s', 'MYCONTENIDO', $link->render());

        // info link
        $link = new cHTMLLink();
        $link->setClass('main');
        $link->setTargetFrame('content');
        $link->setLink($sess->url('frameset.php?area=info&frame=4'));
        $link->setContent('<img alt="" class="borderless vAlignMiddle" src="' . $backendUrl . $cfg['path']['images'] . 'info.gif" alt="Info" title="Info" id="imgInfo">');
        $main->set('s', 'INFO', $link->render());

        $main->set('s', 'LOGOUT', $sess->url('logout.php'));

        if ($cfg['help'] == true) {
            // help link
            $link = new cHTMLLink();
            $link->setID('help');
            $link->setClass('main');
            $link->setLink('javascript://');
            $link->setEvent('click', 'Con.Help.show($(\'#help\').attr(\'data\'));');
            $link->setContent('<img class="borderless vAlignMiddle" src="' . $backendUrl . $cfg['path']['images'] . 'but_help.gif" alt="Hilfe" title="Hilfe">');
            $main->set('s', 'HELP', $link->render());
        } else {
            $main->set('s', 'HELP', '');
        }

        $auth = cRegistry::getAuth();
        $oUser = new cApiUser($auth->auth["uid"]);
        $clientCollection = new cApiClientCollection();

        if (getEffectiveSetting('system', 'clickmenu') == 'true') {
            // set click menu
            $main->set('s', 'HEADER_MENU_OBJ', 'Con.HeaderClickMenu');
            $main->set('s', 'HEADER_MENU_OPTIONS', '{menuId: "main_0", subMenuId: "sub_0"}');
        } else {
            // set delay menu
            $mouseOver = getEffectiveSetting('system', 'delaymenu_mouseover', 300);
            $mouseOot = getEffectiveSetting('system', 'delaymenu_mouseout', 1000);
            $main->set('s', 'HEADER_MENU_OBJ', 'Con.HeaderDelayMenu');
            $main->set('s', 'HEADER_MENU_OPTIONS', '{menuId: "main_0", subMenuId: "sub_0", mouseOverDelay: ' . $mouseOver . ', mouseOutDelay: ' . $mouseOot . '}');
        }

        $main->set('s', 'ACTION', $sess->url('index.php'));

        if ($this->hasErrors()) {
            $errors = $this->getErrors();
            $errorString = '';
            foreach ($errors as $error) {
                $errorString .= $error.'<br>';
            }
            $errorString .= '<br>' . i18n('Some plugin menus can not be shown because of these errors.');
            $helpBox = new cGuiBackendHelpbox($errorString, './images/but_warn.gif');
            $main->set('s', 'LANG', $helpBox->render(true) . $this->_renderLanguageSelect());
        } else {
            $main->set('s', 'LANG', $this->_renderLanguageSelect());
        }

        $sClientName = $clientCollection->getClientname($client);
        if (cString::getStringLength($sClientName) > 25) {
            $sClientName = cString::trimHard($sClientName, 25);
        }

        $client = cSecurity::toInteger($client);
        if ($client == 0) {
            $sClientNameTemplate = '<b>' . i18n("Client") . ':</b> %s';
            $main->set('s', 'CHOSENCLIENT', sprintf($sClientNameTemplate, $sClientName));
        } else {
            $sClientNameTemplate = '<b>' . i18n("Client") . ':</b> <a href="%s" target="_blank">%s</a>';

            $sClientName = $clientCollection->getClientname($client) . ' (' . $client . ')';
            $sClientNameWithHtml = '<span id="chosenclient">' .$sClientName . '</span>';

            $sClientUrl = cRegistry::getFrontendUrl();
            $frontendPath = cRegistry::getFrontendPath();

            if ($clientImage !== false && $clientImage != "" && cFileHandler::exists($frontendPath . $clientImage)) {
                $sClientImageTemplate = '<img src="%s" alt="%s" title="%s" style="height: 15px;">';

                $sThumbnailPath = cApiImgScale($frontendPath . $clientImage, 80, 25, 0, 1);
                $sClientImageTag = sprintf($sClientImageTemplate, $sThumbnailPath, $sClientName, $sClientName);

                $main->set('s', 'CHOSENCLIENT', sprintf($sClientNameTemplate, $sClientUrl, $sClientImageTag));
            } else {
                $html = sprintf($sClientNameTemplate, $sClientUrl, $sClientNameWithHtml);
                $html .= $this->_renderClientSelect();
                $main->set('s', 'CHOSENCLIENT', $html);
            }
        }

        $main->set('s', 'CHOSENUSER', "<b>" . i18n("User") . ":</b> " . $oUser->getEffectiveName());
        $main->set('s', 'MAINLOGINLINK', $sess->url("frameset.php?area=mycontenido&frame=4"));

        // additional footer javascript
        $footerJs = '';
        if ($sJsEvents !== '') {
            $footerJs = '$(function() {' . $sJsEvents . '});';
        }
        $main->set('s', 'FOOTER_JS', $footerJs);

        $main->generate($cfg['path']['templates'] . $cfg['templates']['header']);
    }

    /**
     * Renders the language select box.
     *
     * @return string
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function _renderLanguageSelect() {
        $cfg = cRegistry::getConfig();

        $tpl = new cTemplate();

        $tpl->set('s', 'NAME', 'changelang');
        $tpl->set('s', 'CLASS', 'vAlignMiddle text_medium');
        $tpl->set('s', 'ID', 'cLanguageSelect');
        $tpl->set('s', 'OPTIONS', 'onchange="Con.Header.changeContenidoLanguage(this.value)"');

        $availableLanguages = new cApiLanguageCollection();

        if (getEffectiveSetting('system', 'languageorder', 'name') == 'name') {
            $availableLanguages->select('', '', 'name ASC');
        } else {
            $availableLanguages->select('', '', 'idlang ASC');
        }

        if ($availableLanguages->count() > 0) {
            $client = cRegistry::getClientId();
            $lang = cRegistry::getLanguageId();

            while (($myLang = $availableLanguages->nextAccessible()) !== NULL) {
                $key = $myLang->get('idlang');
                $value = $myLang->get('name');

                $clientsLang = new cApiClientLanguage();
                $clientsLang->loadBy('idlang', cSecurity::toInteger($key));
                if ($clientsLang->isLoaded()) {
                    if ($clientsLang->get('idclient') == $client) {
                        $this->_renderSelectOption($tpl, $key, $value, $lang);
                    }
                }
            }
        } else {
            $tpl->set('d', 'VALUE', 0);
            $tpl->set('d', 'CAPTION', i18n('-- No Language available --'));
            $tpl->next();
        }

        return $tpl->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'], true);
    }

    /**
     * Renders a select box where the client can be selected as well as
     * an edit button.
     *
     * @return string
     *         rendered HTML
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    protected function _renderClientSelect() {
        $cfg = cRegistry::getConfig();
        $client = cRegistry::getClientId();
        // get all accessible clients
        $clientCollection = new cApiClientCollection();
        $clients = $clientCollection->getAccessibleClients();
        if (count($clients) === 1) {
            return '';
        }

        $tpl = new cTemplate();
        $tpl->set('s', 'NAME', 'changeclient');
        $tpl->set('s', 'CLASS', 'vAlignMiddle text_medium nodisplay');
        $tpl->set('s', 'ID', 'cClientSelect');
        $tpl->set('s', 'OPTIONS', 'onchange="Con.Header.changeContenidoClient(this.value)"');

        // add all accessible clients to the select
        foreach ($clients as $idclient => $clientInfo) {
            $name = $clientInfo['name'];
            $this->_renderSelectOption($tpl, $idclient, $name, $client);
        }

        $html = $tpl->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'], true);
        $editButton = new cHTMLImage(cRegistry::getBackendUrl() . $cfg['path']['images'] . 'but_edithead.gif');
        $editButton->setID('changeclient');
        $editButton->setClass("vAlignMiddle");
        $editButton->appendStyleDefinition('cursor', 'pointer');

        return $html . $editButton->render();
    }

    /**
     * Renders the options for the client and language select.
     * @param cTemplate $tpl
     * @param $key
     * @param $value
     * @param $selectedKey
     * @return void
     */
    protected function _renderSelectOption(cTemplate $tpl, $key, $value, $selectedKey) {
        if ($key == $selectedKey) {
            $tpl->set('d', 'SELECTED', 'selected');
        } else {
            $tpl->set('d', 'SELECTED', '');
        }

        if (cString::getStringLength($value) > 20) {
            $value = cString::trimHard($value, 20);
        }

        $tpl->set('d', 'VALUE', $key);
        $tpl->set('d', 'CAPTION', $value . ' (' . $key . ')');
        $tpl->next();
    }

    /**
     * Returns true if the class encountered errors while building the
     * navigation-
     *
     * @return bool
     */
    public function hasErrors() {
        return count($this->errors) > 0;
    }

    /**
     * Returns an array of localized error messages.
     *
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

}
