<?php
/**
 * This file contains the the input helper classes.
 * Various derived HTML class elements especially useful
 * in the input area of modules
 * Simple table generation class especially useful to generate
 * backend configuration table. May be used also in Frontend,
 * but note the globally used variables ($cfg)
 *
 * @package    Core
 * @subpackage Util
 * @version    SVN Revision $Rev:$
 *
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Select box with additional functions for category and article selection
 *
 * @package Core
 * @subpackage Util
 */
class cHTMLInputSelectElement extends cHTMLSelectElement {

    /**
     * Constructor.
     * Creates an HTML select field (aka 'DropDown').
     *
     * @param string $sName Name of the element
     * @param int $iWidth Width of the select element
     * @param string $sID ID of the element
     * @param string $bDisabled Item disabled flag (non-empty to set disabled)
     * @param int $iTabIndex Tab index for form elements
     * @param string $sAccessKey Key to access the field
     */
    public function __construct($sName, $iWidth = '', $sID = '', $bDisabled = false, $iTabIndex = NULL, $sAccessKey = '') {
        parent::__construct($sName, $iWidth, $sID, $bDisabled, $iTabIndex, $sAccessKey);
    }

    /**
     * Function addArticles.
     * Adds articles to select box values.
     *
     * @param int $iIDCat idcat of the category to be listed
     * @param bool $bColored Add color information to option elements
     * @param bool $bArtOnline If true, only online articles will be added
     * @param string $sSpaces Just some '&nbsp;' to show data hierarchically
     *        (used in conjunction with addCategories)
     *
     * @return int
     *         Number of items added
     */
    public function addArticles($iIDCat, $bColored = false, $bArtOnline = true, $sSpaces = '') {
        global $cfg, $lang;

        $oDB = cRegistry::getDb();

        if (is_numeric($iIDCat) && $iIDCat > 0) {
            $sql = "SELECT al.title AS title, al.idartlang AS idartlang, ca.idcat AS idcat,
                        ca.idcatart AS idcatart, ca.is_start AS isstart, al.online AS online,
                        cl.startidartlang AS idstartartlang
                    FROM " . $cfg["tab"]["art_lang"] . " AS al, " . $cfg["tab"]["cat_art"] . " AS ca,
                        " . $cfg["tab"]["cat_lang"] . " AS cl
                    WHERE ca.idcat = '" . cSecurity::toInteger($iIDCat) . "' AND cl.idcat = ca.idcat
                        AND cl.idlang = al.idlang AND ";

            if ($bArtOnline) {
                $sql .= "al.online = 1 AND ";
            }

            $sql .= "al.idart = ca.idart AND al.idlang = " . (int) $lang . " ORDER BY al.title";

            $oDB->query($sql);

            $iCount = $oDB->numRows();
            if ($iCount == 0) {
                return 0;
            } else {
                $iCounter = count($this->_options);
                while ($oDB->nextRecord()) {
                    // Generate new option element
                    $oOption = new cHTMLOptionElement($sSpaces . '&nbsp;&nbsp;&nbsp;' . substr($oDB->f('title'), 0, 32), $oDB->f('idcatart'));

                    if ($bColored) {
                        if ($oDB->f('idstartartlang') == $oDB->f('idartlang')) {
                            if ($oDB->f('online') == 0) {
                                // Start article, but offline -> red
                                $oOption->setStyle('color: #ff0000;');
                            } else {
                                // Start article -> blue
                                $oOption->setStyle('color: #0000ff;');
                            }
                        } else if ($oDB->f('online') == 0) {
                            // Offline article -> grey
                            $oOption->setStyle('color: #666666;');
                        }
                    }

                    // Add option element to the list
                    $this->addOptionElement($iCounter, $oOption);
                    $iCounter++;
                }
                return $iCount;
            }
        } else {
            return 0;
        }
    }

    /**
     * Function addCategories.
     * Adds category elements (optionally including articles) to select box
     * values.
     * Note: Using 'with articles' adds the articles also - but the categories
     * will get a negative value!
     * There is no way to distinguish between a category id and an article id...
     *
     * @param int $iMaxLevel Max. level shown (to be exact: except this level)
     * @param bool $bColored Add color information to option elements
     * @param bool $bCatVisible If true, only add idcat as value, if cat is
     *        visible
     * @param bool $bCatPublic If true, only add idcat as value, if cat is
     *        public
     * @param bool $bWithArt Add also articles per category
     * @param bool $bArtOnline If true, show only online articles
     *
     * @return int
     *         Number of items added
     */
    public function addCategories($iMaxLevel = 0, $bColored = false, $bCatVisible = true, $bCatPublic = true, $bWithArt = false, $bArtOnline = true) {
        global $cfg, $client, $lang;

        $oDB = cRegistry::getDb();

        $sql = "SELECT c.idcat AS idcat, cl.name AS name, cl.visible AS visible, cl.public AS public, ct.level AS level
                FROM " . $cfg["tab"]["cat"] . " AS c, " . $cfg["tab"]["cat_lang"] . " AS cl, " . $cfg["tab"]["cat_tree"] . " AS ct
                WHERE c.idclient = " . (int) $client . " AND cl.idlang = " . (int) $lang . " AND cl.idcat = c.idcat AND ct.idcat = c.idcat ";
        if ($iMaxLevel > 0) {
            $sql .= "AND ct.level < " . (int) $iMaxLevel . " ";
        }
        $sql .= "ORDER BY ct.idtree";

        $oDB->query($sql);

        $iCount = $oDB->numRows();
        if ($iCount == 0) {
            return false;
        } else {
            $iCounter = count($this->_options);
            while ($oDB->nextRecord()) {
                $sSpaces = '';
                $sStyle = '';
                $iID = $oDB->f('idcat');

                for ($i = 0; $i < $oDB->f('level'); $i++) {
                    $sSpaces .= '&nbsp;&nbsp;&nbsp;';
                }

                // Generate new option element
                if (($bCatVisible && $oDB->f('visible') == 0) || ($bCatPublic && $oDB->f('public') == 0)) {
                    // If category has to be visible or public and it isn't,
                    // don't add value
                    $sValue = '';
                } else if ($bWithArt) {
                    // If article will be added, set negative idcat as value
                    $sValue = '-' . $iID;
                } else {
                    // Show only categories - and everything is fine...
                    $sValue = $iID;
                }
                $oOption = new cHTMLOptionElement($sSpaces . '>&nbsp;' . $oDB->f('name'), $sValue);

                // Coloring option element, restricted shows grey color
                $oOption->setStyle('background-color: #EFEFEF');
                if ($bColored && ($oDB->f('visible') == 0 || $oDB->f('public') == 0)) {
                    $oOption->setStyle('color: #666666;');
                }

                // Add option element to the list
                $this->addOptionElement($iCounter, $oOption);

                if ($bWithArt) {
                    $iArticles = $this->addArticles($iID, $bColored, $bArtOnline, $sSpaces);
                    $iCount += $iArticles;
                }
                $iCounter = count($this->_options);
            }
        }
        return $iCount;
    }

    /**
     * Function addTypesFromArt.
     * Adds types and type ids which are available for the specified article
     *
     * @param int $iIDCatArt Article id
     * @param string $sTypeRange Komma separated list of CONTENIDO type ids
     *        which may be in the resulting list (e.g. '1', '17', '28')
     *
     * @return int
     *         Number of items added
     */
    public function addTypesFromArt($iIDCatArt, $sTypeRange = '') {
        global $cfg, $lang;

        $oDB = cRegistry::getDb();

        if (is_numeric($iIDCatArt) && $iIDCatArt > 0) {
            $sql = "SELECT t.typeid AS typeid, t.idtype AS idtype, t.type AS type, t.description AS description, t.value AS value
                    FROM " . $cfg["tab"]["content"] . " AS t, " . $cfg["tab"]["art_lang"] . " AS al,
                         " . $cfg["tab"]["cat_art"] . " AS ca, " . $cfg["tab"]["type"] . " AS t
                    WHERE t.idtype = t.idtype AND t.idartlang = al.idartlang AND al.idart = ca.idart
                        AND al.idlang = " . (int) $lang . " AND ca.idcatart = " . (int) $iIDCatArt . " ";

            if ($sTypeRange != "") {
                $sql .= "AND t.idtype IN (" . $oDB->escape($sTypeRange) . ") ";
            }

            $sql .= "ORDER BY t.idtype, t.typeid";

            $oDB->query($sql);

            $iCount = $oDB->numRows();
            if ($iCount == 0) {
                return false;
            } else {
                while ($oDB->nextRecord()) {
                    $sTypeIdentifier = "tblData.idtype = '" . $oDB->f('idtype') . "' AND tblData.typeid = '" . $oDB->f('typeid') . "'";

                    // Generate new option element
                    $oOption = new cHTMLOptionElement($oDB->f('type') . "[" . $oDB->f('typeid') . "]: " . substr(strip_tags($oDB->f("value")), 0, 50), $sTypeIdentifier);

                    // Add option element to the list
                    $this->addOptionElement($sTypeIdentifier, $oOption);
                }
                return $iCount;
            }
        } else {
            return false;
        }
    }

    /**
     * Selects specified elements as selected
     *
     * @param array $aElements Array with "values" of the cHTMLOptionElement to
     *        set
     * @return cHTMLSelectElement
     *         $this for chaining
     */
    public function setSelected($aElements) {
        if (is_array($this->_options) && is_array($aElements)) {
            foreach ($this->_options as $sKey => $oOption) {
                if (in_array($oOption->getAttribute("value"), $aElements)) {
                    $oOption->setSelected(true);
                    $this->_options[$sKey] = $oOption;
                } else {
                    $oOption->setSelected(false);
                    $this->_options[$sKey] = $oOption;
                }
            }
        }
        return $this;
    }

}

/**
 * Config table class.
 *
 * @package Core
 * @subpackage Util
 */
class UI_Config_Table {

    /**
     *
     * @var string
     */
    var $_sTplCellCode;

    /**
     *
     * @var string
     */
    var $_sTplTableFile;

    /**
     *
     * @var string
     */
    var $_sWidth;

    /**
     *
     * @var int
     */
    var $_sBorder;

    /**
     *
     * @var string
     */
    var $_sBorderColor;

    /**
     *
     * @var string
     */
    var $_bSolidBorder;

    /**
     *
     * @var int
     */
    var $_sPadding;

    /**
     *
     * @var array
     */
    var $_aCells;

    /**
     *
     * @var array
     */
    var $_aCellAlignment;

    /**
     *
     * @var array
     */
    var $_aCellVAlignment;

    /**
     *
     * @var unknown_type
     */
    var $_aCellColSpan;

    /**
     *
     * @var array
     */
    var $_aCellClass;

    /**
     *
     * @var unknown_type
     */
    var $_aRowBgColor;

    /**
     *
     * @var unknown_type
     */
    var $_aRowExtra;

    /**
     *
     * @var bool
     */
    var $_bAddMultiSelJS;

    /**
     *
     * @var unknown_type
     */
    var $_sColorLight;

    /**
     *
     * @var unknown_type
     */
    var $_sColorDark;

    /**
     * Create a config table instance.
     */
    function UI_Config_Table() {
        global $cfg;
        $backendPath = cRegistry::getBackendPath();

        $this->_sPadding = 2;
        $this->_sBorder = 0;
        $this->_sTplCellCode = '        <td align="{ALIGN}" valign="{VALIGN}" class="{CLASS}" colspan="{COLSPAN}" style="{EXTRA}white-space:nowrap;" nowrap="nowrap">{CONTENT}</td>' . "\n";
        $this->_sTplTableFile = $backendPath . $cfg['path']['templates'] . $cfg['templates']['input_helper'];
        $this->_sTplCellCode =  $backendPath . $cfg['path']['templates'] . $cfg['templates']['input_helper_row'];
    }

    /**
     *
     * @param string $sCode
     */
    function setCellTemplate($sCode) {
        $this->_sTplCellCode = $sCode;
    }

    /**
     *
     * @param string $sPath
     */
    function setTableTemplateFile($sPath) {
        $this->_sTplTableFile = $sPath;
    }

    /**
     *
     * @param unknown_type $sRow
     * @param unknown_type $sCell
     * @param unknown_type $sContent
     */
    function setCell($sRow, $sCell, $sContent) {
        $this->_aCells[$sRow][$sCell] = $sContent;
        $this->_aCellAlignment[$sRow][$sCell] = "";
    }

    /**
     *
     * @param unknown_type $sRow
     * @param unknown_type $sCell
     * @param unknown_type $sAlignment
     */
    function setCellAlignment($sRow, $sCell, $sAlignment) {
        $this->_aCellAlignment[$sRow][$sCell] = $sAlignment;
    }

    /**
     *
     * @param unknown_type $sRow
     * @param unknown_type $sCell
     * @param unknown_type $sAlignment
     */
    function setCellVAlignment($sRow, $sCell, $sAlignment) {
        $this->_aCellVAlignment[$sRow][$sCell] = $sAlignment;
    }

    /**
     *
     * @param unknown_type $sRow
     * @param unknown_type $sCell
     * @param unknown_type $sClass
     */
    function setCellClass($sRow, $sCell, $sClass) {
        $this->_aCellClass[$sRow][$sCell] = $sClass;
    }

    /**
     *
     * @return string
     */
    function _addMultiSelJS() {
        // Trick: To save multiple selections in <select>-Element, add some JS
        // which saves the
        // selection, comma separated in a hidden input field on change.
        // Try ... catch prevents error messages, if function is added more than
        // once
        // if (!fncUpdateSel) in JS has not worked...
        $sSkript = '
<script type="text/javascript"><!--
try {
    function fncUpdateSel(sSelectBox, sStorage) {
        var sSelection = "";
        var oSelectBox = document.getElementsByName(sSelectBox)[0];
        var oStorage   = document.getElementsByName(sStorage)[0];
        if (oSelectBox && oStorage) {
            for (i = 0; i < oSelectBox.length; i++) {
                if (oSelectBox.options[i].selected == true) {
                    if (sSelection != "") {
                        sSelection = sSelection + ",";
                    }
                    sSelection = sSelection + oSelectBox.options[i].value;
                }
            }
            oStorage.value = sSelection;
        }
    }
} catch (e) { }
//--></script>
';

        return $sSkript;
    }

    /**
     *
     * @param unknown_type $bPrint
     * @return string|void
     *         Complete template string or nothing
     */
    function render($bPrint = false) {
        $oTable = new cTemplate();
        $oTable->reset();

        $iColCount = 0;
        $bDark = false;
        $sBgColor = "";
        $bMultiSelJSAdded = false;
        if (is_array($this->_aCells)) {
            foreach ($this->_aCells as $sRow => $aCells) {
                $iColCount++;
                // $bDark = !$bDark;
                $sLine = '';
                $iCount = 0;

                foreach ($aCells as $sCell => $sData) {
                    $iCount++;
                    $tplCell = new cTemplate();
                    $tplCell->reset();

                    if ($this->_aCellClass[$sRow][$sCell] != '') {
                        $tplCell->set('s', 'CLASS', $this->_aCellClass[$sRow][$sCell]);
                    } else {
                        $tplCell->set('s', 'CLASS', '');
                    }

                    if ($this->_aCellAlignment[$sRow][$sCell] != '') {
                        $tplCell->set('s', 'ALIGN', $this->_aCellAlignment[$sRow][$sCell]);
                    } else {
                        $tplCell->set('s', 'ALIGN', 'left');
                    }

                    if ($this->_aCellVAlignment[$sRow][$sCell] != '') {
                        $tplCell->set('s', 'VALIGN', $this->_aCellAlignment[$sRow][$sCell]);
                    } else {
                        $tplCell->set('s', 'VALIGN', 'top');
                    }

                    // Multi selection javascript
                    if ($this->_bAddMultiSelJS) {
                        $sData = $this->_addMultiSelJS() . $sData;
                        $this->_bAddMultiSelJS = false;
                    }

                    $tplCell->set('s', 'CONTENT', $sData);
                    $sLine .= $tplCell->generate($this->_sTplCellCode, true, false);
                }

                // Row
                $oTable->set('d', 'ROWS', $sLine);

                $oTable->next();
            }
        }
        $sRendered = $oTable->generate($this->_sTplTableFile, true, false);

        if ($bPrint == true) {
            echo $sRendered;
        } else {
            return $sRendered;
        }
    }

}

?>