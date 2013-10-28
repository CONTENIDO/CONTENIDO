<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Class for the dynamic Contenido backend navigation
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend classes
 * @version    1.0.3
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2008-07-08  Thorsten Granz, added option to disable menu hover effect. clicking is now possible again
 *   modified 2009-12-17, Dominik Ziegler, added support for username fallback and fixed double quote
 *   modified 2009-12-16  Corrected rendering of multiple apostrophes in anchors
 *   modified 2010-01-15, Dominik Ziegler, added frontend url to client name
 *   modified 2011-01-28, Dominik Ziegler, added check for client existance for link to frontend [#CON-378]
 *
 *   $Id: class.navigation.php 1264 2011-01-28 10:41:52Z dominik.ziegler $:
* }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.api.string.php');
cInclude('includes', 'functions.api.images.php');


/**
 * Backend navigaton class. Renders the header navigation document containing the navigtion structure.
 *
 * @category    Contenido
 * @package     Backend
 * @subpackage  Navigation
 */
class Contenido_Navigation {

    /**
     * Flag to debug this vlass
     * @var  bool
     */
    var $debug = 0;

    /**
     * Array storing all data
     * @var  array
     */
    var $data = array();


    /**
     * Constructor. Loads the XML language file using XML_Doc.
     */
    function Contenido_Navigation() {
        global $cfg, $belang;

        $this->xml = new XML_Doc();
        $this->plugxml = new XML_Doc();

        // Load language file
        if ($this->xml->load($cfg['path']['xml'] . $cfg['lang'][$belang]) == false) {
            if ($this->xml->load($cfg['path']['xml'] . 'lang_en_US.xml') == false) {
                die('Unable to load any XML language file');
            }
        }
    }


    /**
     * Extracts caption from the XML language file including plugins extended multilang version.
     *
     * @param  string  $location  The location of navigation item caption. Feasible values are
     *                            - "{xmlFilePath};{XPath}": Path to XML File and the XPath value
     *                                                       separated by semicolon. This type is used
     *                                                       to extract caption from a plugin XML file.
     *                            - "{XPath}": XPath value to extract caption from Contenido XML file
     * @return  string  The found caption
     */
    function getName($location) {
        global $cfg, $belang;

        # If a ";" is found entry is from a plugin -> explode location, first is xml file path,
        # second is xpath location in xml file
        if (strstr($location, ';')) {

            $locs  = explode(';', $location);
            $file  = trim($locs[0]);
            $xpath = trim($locs[1]);

            $filepath = explode('/', $file);
            $counter = count($filepath)-1;

            if ($filepath[$counter] == '') {
                unset($filepath[$counter]);
                $counter--;
            }

            if(strstr($filepath[$counter], '.xml')) {
                $filename = $filepath[$counter];
                unset($filepath[$counter]);
                $counter--;
            }

            $filepath[($counter+1)] = '';

            $filepath = implode('/', $filepath);

            if ($this->plugxml->load($cfg['path']['plugins'] . $filepath . $cfg['lang'][$belang]) == false) {
                if (!isset($filename)) {
                    $filename = 'lang_en_US.xml';
                }
                if ($this->plugxml->load($cfg['path']['plugins'] . $filepath . $filename) == false) {
                    die("Unable to load $filepath XML language file");
                }
            }
            $caption = $this->plugxml->valueOf($xpath);

        } else {
            $caption = $this->xml->valueOf($location);
        }

        return $caption;
    }


   /**
    * Reads and fills the navigation structure data
    *
    * @return  void
    */
    function _buildHeaderData() {
        global $cfg, $perm, $belang;

        $db  = new DB_Contenido();
        $db2 = new DB_Contenido();

        # Load main items
        $sql = "SELECT idnavm, location FROM ".$cfg['tab']['nav_main']." ORDER BY idnavm";

        $db->query($sql);

        # Loop result and build array
        while ($db->next_record()) {

            # Extract names from the XML document.
            $main = $this->getName($db->f('location'));

            # Build data array
            $this->data[$db->f('idnavm')] = array($main);

            $sql = "SELECT
                        a.location AS location, b.name AS area, b.relevant
                    FROM
                        ".$cfg['tab']['nav_sub']." AS a, ".$cfg['tab']['area']." AS b
                    WHERE
                        a.idnavm = '".$db->f('idnavm')."' AND
                        a.level  = '0' AND
                        b.idarea = a.idarea AND
                        a.online = '1' AND
                        b.online = '1'
                    ORDER BY
                        a.idnavs";

            $db2->query($sql);

            while ($db2->next_record()) {
                $area = $db2->f('area');
                if ($perm->have_perm_area_action($area) || $db2->f('relevant') == 0){
                    # Extract names from the XML document.
                    $name = $this->getName($db2->f('location'));
                    $this->data[$db->f('idnavm')][] = array($name, $area);
                }
            }

        }

        # debugging information
        if ($this->debug) {
            echo '<pre>' . print_r($this->data, true) . '</pre>';
        }
    }


    /**
     * Function to build the Contenido header document for backend
     *
     * @param  int  $lang  The language to use for header doc creation
     */
    function buildHeader($lang) {

        global $cfg, $sess, $client, $changelang, $auth, $cfgClient;

        $this->_buildHeaderData();

        $main = new Template();
        $sub  = new Template();

        $cnt = 0;
        $t_sub = '';
        $numSubMenus = 0;

        $properties = new PropertyCollection();
        $clientImage = $properties->getValue('idclient', $client, 'backend', 'clientimage', false);

        $sJsEvents = '';
        foreach ($this->data as $id => $item) {
            $sub->reset();
            $genSubMenu = false;

            foreach ($item as $key => $value) {
                if (is_array($value)) {
                    $sub->set('s', 'SUBID', 'sub_'.$id);

                    // create sub menu link
                    $link = new cHTMLLink();
                    $link->disableAutomaticParameterAppend();
                    $link->setClass('sub');
                    $link->setID('sub_' . $value[1]);
                    $link->setLink($sess->url('frameset.php?area=' . $value[1]));
                    $link->setTargetFrame('content');
                    $link->setContent($value[0]);

                    if ($cfg['help'] == true) {
                        $sJsEvents .= "\n\t" . '$("#sub_' . $value[1] . '").click(function(){ $("#help").attr("data", "'.$value[0].'"); })';
                    }
                    $sub->set('d', 'CAPTION', $link->render());

                    $sub->next();
                    $genSubMenu = true;
                }
            }

            if ($genSubMenu == true) {
                $link = new cHTMLLink();
                $link->setClass('main');
                $link->setID('main_' . $id);
                $link->setLink('javascript://');
                $link->setAttribute('ident', 'sub_' . $id);
                $link->setContent($item[0]);

                $main->set('d', 'CAPTION', $link->render());
                $main->next();

                $numSubMenus++;

            } else {
                # first entry in array is a main menu item
            }

            # generate a sub menu item.
            $t_sub .= $sub->generate($cfg['path']['templates'] . $cfg['templates']['submenu'], true);
            $cnt ++;
        }

        if ($numSubMenus == 0) {
            $main->set('d', 'CAPTION', '&nbsp;');
            $main->next();
        }

        $main->set('s', 'SUBMENUS', $t_sub);

        // my contenido link
        $link = new cHTMLLink();
        $link->setClass('main');
        $link->setTargetFrame('content');
        $link->setLink($sess->url("frameset.php?area=mycontenido&frame=4"));
        $link->setContent('<img src="'.$cfg['path']['contenido_fullhtml'].$cfg['path']['images'].'my_contenido.gif" border="0" alt="MyContenido" id="imgMyContenido" title="MyContenido">');
        $main->set('s', 'MYCONTENIDO', $link->render());

        // info link
        $link = new cHTMLLink();
        $link->setClass('main');
        $link->setTargetFrame('content');
        $link->setLink($sess->url('frameset.php?area=info&frame=4'));
        $link->setContent('<img src="'.$cfg['path']['contenido_fullhtml'].$cfg['path']['images'].'info.gif" border="0" alt="Info" title="Info" id="imgInfo">');
        $main->set('s', 'INFO', $link->render());

        $main->set('s', 'LOGOUT', $sess->url('logout.php'));

        if ($cfg['help'] == true) {
            // help link
            $link = new cHTMLLink();
            $link->setID('help');
            $link->setClass('main');
            $link->setLink('javascript://');
            $link->setEvent('click', 'callHelp($(\'#help\').attr(\'data\'));');
            $link->setContent('<img src="'.$cfg['path']['contenido_fullhtml'].$cfg['path']['images'].'but_help.gif" border="0" alt="Hilfe" title="Hilfe">');
            $main->set('s', 'HELP', $link->render());
        } else {
            $main->set('s', 'HELP', '');
        }

/*
@TODO: is nowhere used
        // kill perms link
        $link = new cHTMLLink();
        $link->setClass('main');
        $link->setTargetFrame('header');
        $link->setLink($sess->url("header.php?killperms=1"));
        $link->setContent('<img src="'.$cfg['path']['contenido_fullhtml'].$cfg['path']['images'].'mycon.gif" border="0" alt="Reload Permission" title="Reload Permissions">');
        $main->set('s', 'KILLPERMS', $link->render());
*/

        $classuser = new User();
        $classclient = new Client();

        if (getEffectiveSetting('system', 'clickmenu') == 'true') {
            // set click menu
            $main->set('s', 'HEADER_MENU_OBJ', 'HeaderClickMenu');
            $main->set('s', 'HEADER_MENU_OPTIONS', '{menuId: "main_0", subMenuId: "sub_0"}');
        } else {
            // set delay menu
            $mouseOver = getEffectiveSetting('system', 'delaymenu_mouseover', 300);
            $mouseOot  = getEffectiveSetting('system', 'delaymenu_mouseout', 1000);
            $main->set('s', 'HEADER_MENU_OBJ', 'HeaderDelayMenu');
            $main->set('s', 'HEADER_MENU_OPTIONS', '{menuId: "main_0", subMenuId: "sub_0", mouseOverDelay: '.$mouseOver.', mouseOutDelay: '.$mouseOot.'}');
        }

        $main->set('s', 'ACTION', $sess->url('index.php'));
        $main->set('s', 'LANG', $this->_renderLanguageSelect());
        $main->set('s', 'WIDTH', $itemWidth);####

        $sClientName = $classclient->getClientName($client);
        if (strlen($sClientName) > 25) {
            $sClientName = capiStrTrimHard($sClientName, 25);
        }

		$client = Contenido_Security::toInteger($client);
		if ( $client == 0 ) {
			$sClientNameTemplate = '<b>' . i18n("Client") . ':</b> %s';
			$main->set('s', 'CHOSENCLIENT', sprintf($sClientNameTemplate, $sClientName));
		} else {
			$sClientNameTemplate = '<b>' . i18n("Client") . ':</b> <a href="%s" target="_blank">%s</a>';

			$sClientName 	= $classclient->getClientName($client).' ('.$client.')';
			$sClientUrl 	= $cfgClient[$client]["path"]["htmlpath"];

			if ($clientImage !== false && $clientImage != "" && file_exists($cfgClient[$client]['path']['frontend'].$clientImage)) {
				$sClientImageTemplate = '<img src="%s" alt="%s" title="%s" />';

				$sThumbnailPath 	= capiImgScale($cfgClient[$client]['path']['frontend'].$clientImage, 80, 25, 0, 1);
				$sClientImageTag 	= sprintf($sClientImageTemplate, $sThumbnailPath, $sClientName, $sClientName);

				$main->set('s', 'CHOSENCLIENT', sprintf($sClientNameTemplate, $sClientUrl, $sClientImageTag));
			} else {
				$main->set('s', 'CHOSENCLIENT', sprintf($sClientNameTemplate, $sClientUrl, $sClientName));
			}
		}

		$main->set('s', 'CHOSENUSER', "<b>".i18n("User").":</b> ".$classuser->getRealname($auth->auth["uid"], true));
		$main->set('s', 'SID', $sess->id);
        $main->set('s', 'MAINLOGINLINK', $sess->url("frameset.php?area=mycontenido&frame=4"));

        // additional footer javascript
        $footerJs = '';
        if ($sJsEvents !== '') {
            $footerJs = '$(document).ready(function(){' . $sJsEvents . '});';
        }
        $main->set('s', 'FOOTER_JS', $footerJs);

        $main->generate($cfg['path']['templates'] . $cfg['templates']['header']);
    }


    /**
     * Renders the language select box
     *
     * @return  string
     */
    function _renderLanguageSelect()
    {
        global $cfg, $client, $lang;

        $tpl = new Template();

        $tpl->set('s', 'NAME', 'changelang');
        $tpl->set('s', 'CLASS', 'text_medium');
        $tpl->set('s', 'ID', 'cLanguageSelect');
        $tpl->set('s', 'OPTIONS', 'onchange="changeContenidoLanguage(this.value)"');

        $availableLanguages = new Languages();

        if (getEffectiveSetting('system', 'languageorder', 'name') == 'name') {
            $availableLanguages->select('', '', 'name ASC');
        } else {
            $availableLanguages->select('', '', 'idlang ASC');
        }

        $db = new DB_Contenido();

        if ($availableLanguages->count() > 0) {
            while ($myLang = $availableLanguages->nextAccessible()) {
                $key   = $myLang->get('idlang');
                $value = $myLang->get('name');

                // I want to get rid of such silly constructs very soon :)

                $sql = "SELECT idclient FROM ".$cfg['tab']['clients_lang']." WHERE
                        idlang = '".Contenido_Security::toInteger($key)."'";

                $db->query($sql);

                if ($db->next_record()) {
                    if ($db->f('idclient') == $client) {
                        if ($key == $lang) {
                            $tpl->set('d', 'SELECTED', 'selected');
                        } else {
                            $tpl->set('d', 'SELECTED', '');
                        }

                        if (strlen($value) > 20) {
                            $value = capiStrTrimHard($value, 20);
                        }

                        $tpl->set('d', 'VALUE', $key);
                        $tpl->set('d', 'CAPTION', $value.' ('.$key.')');
                        $tpl->next();
                    }
                }
            }
        } else {
            $tpl->set('d', 'VALUE', 0);
            $tpl->set('d', 'CAPTION', '-- ' . i18n("No language available") . ' --');
            $tpl->next();
        }

        return $tpl->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'], true);
    }
}
