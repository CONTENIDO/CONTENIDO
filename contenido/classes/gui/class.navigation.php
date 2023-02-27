<?php

/**
 * This file contains the navigation GUI class.
 *
 * @package    Core
 * @subpackage GUI
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
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
class cGuiNavigation
{

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
     * @var string
     */
    protected $_imagesPath;

    /**
     * @var int
     */
    protected $_clientId;

    /**
     * Constructor to create an instance of this class.
     *
     * Loads the XML language file using cXmlReader.
     *
     * @throws cException if XML language files could not be loaded
     */
    public function __construct()
    {
        $cfg = cRegistry::getConfig();

        $this->_xml = new cXmlReader();
        $this->_plugXml = new cXmlReader();
        $this->_imagesPath = $cfg['path']['images'];
        $this->_clientId = cSecurity::toInteger(cRegistry::getClientId());

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
    public function __get($name)
    {
        if (in_array($name, ['xml', 'plugxml', 'data'])) {
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
    public function getName($location)
    {
        $cfg = cRegistry::getConfig();

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

            $belang = cRegistry::getBackendLanguage();
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
     * @deprecated Since 4.10.2, Function `_buildHeaderData` is not meant for public usage!
     */
    public function _buildHeaderData()
    {
        cDeprecated("The function _buildHeaderData() is deprecated since CONTENIDO 4.10.2, is not meant for public usage and there is no replacement!.");
        $this->buildHeaderData();
    }

    /**
     * Reads and fills the navigation structure data
     *
     * @throws cDbException
     * @throws cException
     */
    protected function buildHeaderData()
    {
        $cfg = cRegistry::getConfig();
        $perm = cRegistry::getPerm();
        $db = cRegistry::getDb();

        // First, load main items
        $sql = "SELECT `idnavm`, `location` FROM `%s` ORDER BY `idnavm`";
        $db->query($sql, cRegistry::getDbTableName('nav_main'));
        while ($db->nextRecord()) {
            $idNavM = cSecurity::toInteger($db->f('idnavm'));
            $this->data[$idNavM] = [$this->getName($db->f('location'))];
        }

        // Then load them all with second query
        $inSql = implode(', ', array_keys($this->data));
        $sql = "SELECT
                    a.idnavm AS idnavm, a.location AS location, b.name AS area, b.relevant
                FROM
                    `" .  cRegistry::getDbTableName('nav_sub') . "` AS a, 
                    `" . cRegistry::getDbTableName('area') . "` AS b
                WHERE
                    a.idnavm IN (" . $inSql . ") AND
                    a.level  = 0 AND
                    b.idarea = a.idarea AND
                    a.online = 1 AND
                    b.online = 1
                ORDER BY
                    a.idnavs";

        $db->query($sql);

        while ($db->nextRecord()) {
            $idNavM = cSecurity::toInteger($db->f('idnavm'));
            $area = $db->f('area');
            $location = $db->f('location');
            if ($perm->have_perm_area_action($area) || $db->f('relevant') == 0) {
                // if this menu entry is a plugin and plugins are disabled, ignore it
                if (cString::findFirstPos($location, ';') !== false && $cfg['debug']['disable_plugins']) {
                    continue;
                }
                // Extract names from the XML document.
                try {
                    $name = $this->getName($location);
                } catch(cException $e) {
                    $this->errors[] = i18n('Unable to load ' . $location);
                    continue;
                }
                $this->data[$idNavM][] = [$name, $area];
            }
        }

        // debugging information
        cDebug::out('cGuiNavigation: ' . print_r($this->data, true));
    }

    /**
     * Function to build the CONTENIDO header document for backend.
     *
     * @param int $lang
     *         The language id
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function buildHeader($lang)
    {
        $lang = cSecurity::toInteger($lang);
        $this->buildHeaderData();

        $sess = cRegistry::getSession();
        $cfg = cRegistry::getConfig();
        $main = new cTemplate();
        $sub = new cTemplate();

        $t_sub = '';
        $numSubMenus = 0;

        $properties = new cApiPropertyCollection();
        $clientImage = $properties->getValue('idclient', $this->_clientId , 'backend', 'clientimage', false);

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

                    // NOTE: Help system is currently not used!
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
                $link->setLink('javascript:void(0)');
                $link->disableAutomaticParameterAppend();
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

        // my CONTENIDO link
        $link = new cHTMLLink();
        $link->setClass('con_img_button');
        $link->setTargetFrame('content');
        $link->setLink($sess->url("frameset.php?area=mycontenido&frame=4"));
        $link->disableAutomaticParameterAppend();
        $link->setContent(
            cHTMLImage::img($this->_imagesPath . 'my_contenido.gif', i18n('My CONTENIDO'), ['class' => 'align_middle'])
        );
        $main->set('s', 'MYCONTENIDO', $link->render());

        // info link
        $link = new cHTMLLink();
        $link->setClass('con_img_button');
        $link->setTargetFrame('content');
        $link->setLink($sess->url('frameset.php?area=info&frame=4'));
        $link->disableAutomaticParameterAppend();
        $link->setContent(
            cHTMLImage::img($this->_imagesPath . 'info.gif', i18n('Info'), ['class' => 'align_middle'])
        );
        $main->set('s', 'INFO', $link->render());

        $main->set('s', 'LOGOUT', $sess->url('logout.php'));

        // NOTE: Help system is currently not used!
        if ($cfg['help'] == true) {
            // help link
            $link = new cHTMLLink();
            $link->setID('help');
            $link->setClass('con_img_button');
            $link->setLink('javascript:void(0)');
            $link->disableAutomaticParameterAppend();
            $link->setAttribute('data-action', 'show_help');
            $link->setContent(
                cHTMLImage::img($this->_imagesPath . 'but_help.gif', i18n('Help'), ['class' => 'align_middle'])
            );
            $main->set('s', 'HELP', $link->render());
        } else {
            $main->set('s', 'HELP', '');
        }

        $auth = cRegistry::getAuth();
        $oUser = new cApiUser($auth->auth["uid"]);

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
            $helpBox = new cGuiBackendHelpbox($errorString, $this->_imagesPath . 'but_warn.gif');
            $main->set('s', 'LANG', $helpBox->render(true) . $this->_renderLanguageSelect($lang));
        } else {
            $main->set('s', 'LANG', $this->_renderLanguageSelect($lang));
        }

        if ($this->_clientId > 0) {
            $oClient = new cApiClient($this->_clientId );
            if ($oClient->isLoaded()) {
                $sClientName = $oClient->get('name');
            } else {
                $this->_clientId = 0;
                $sClientName = i18n("No client");
            }
        } else {
            $sClientName = i18n("No client");
        }

        if ($this->_clientId === 0) {
            $sClientNameTemplate = '<b>' . i18n("Client") . ':</b> %s';
            if (cString::getStringLength($sClientName) > 25) {
                $sClientName = cString::trimHard($sClientName, 25);
            }
            $main->set('s', 'CHOSENCLIENT', sprintf($sClientNameTemplate, $sClientName));
        } else {
            $sClientNameTemplate = '<b>' . i18n("Client") . ':</b> <a href="%s" target="_blank">%s</a>';

            $sClientName = $sClientName . ' (' . $this->_clientId . ')';
            $sClientNameWithHtml = '<span id="chosen_client">' .$sClientName . '</span>';

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
     * @param int $lang
     *         Language id
     * @return string
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function _renderLanguageSelect($lang)
    {
        $availableLanguages = new cApiLanguageCollection();

        if (getEffectiveSetting('system', 'languageorder', 'name') == 'name') {
            $availableLanguages->select('', '', 'name ASC');
        } else {
            $availableLanguages->select('', '', 'idlang ASC');
        }

        $select = new cHTMLSelectElement('changelang', '', 'language_select');
        $select->setAttribute('data-action-change', 'select_language');
        $select->setDefault($lang);
        $counter = 0;

        if ($availableLanguages->count() > 0) {
            while (($myLang = $availableLanguages->nextAccessible()) !== NULL) {
                $languageId = cSecurity::toInteger($myLang->get('idlang'));
                $languageName = $this->_truncateSelectOption($myLang->get('name'));

                $clientsLang = new cApiClientLanguage();
                $clientsLang->loadBy('idlang', $languageId);
                if ($clientsLang->isLoaded()) {
                    if (cSecurity::toInteger($clientsLang->get('idclient')) === $this->_clientId) {
                        $selected = $languageId == $lang;
                        $option = new cHTMLOptionElement($languageName, $languageId, $selected);
                        $select->addOptionElement(++$counter, $option);
                    }
                }
            }

            return $select->toHtml();
        } else {
            $template = '<span class="textg_medium pdr5">%s</span>';
            $text = trim(trim(i18n('-- No Language available --'), ' -'));
            return sprintf($template, $text);
        }
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
    protected function _renderClientSelect()
    {
        // Get all accessible clients
        $clientCollection = new cApiClientCollection();
        $clients = $clientCollection->getAccessibleClients();
        if (count($clients) <= 1) {
            return '';
        }

        $select = new cHTMLSelectElement('select_client', '', 'select_client');
        $select->setStyle('display: none;');
        $select->setAttribute('data-action-change', 'select_client');
        $select->setDefault($this->_clientId);
        $counter = 0;
        foreach ($clients as $idclient => $clientInfo) {
            $name = $this->_truncateSelectOption($clientInfo['name']);
            $selected = $idclient == $this->_clientId;
            $option = new cHTMLOptionElement($name, $idclient, $selected);
            $select->addOptionElement(++$counter, $option);
        }
        $html = $select->toHtml();

        $editButton = new cHTMLImage($this->_imagesPath . 'but_edithead.gif');
        $editButton->setAttribute('data-action', 'change_client');
        $editButton->setClass("con_img_button align_middle mgl3");
        $editButton->appendStyleDefinition('cursor', 'pointer');

        return $html . $editButton->render();
    }

    /**
     * Truncates the option text for client and language select.
     * Any text longer than 20 characters will be truncated with ellipsis (...).
     *
     * @param string $value
     * @return string
     */
    protected function _truncateSelectOption(string $value): string
    {
        if (cString::getStringLength($value) > 20) {
            $value = cString::trimHard($value, 20);
        }
        return $value;
    }

    /**
     * Returns true if the class encountered errors while building the
     * navigation-
     *
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * Returns an array of localized error messages.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

}
