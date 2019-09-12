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
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Select box with additional functionality for category and article selection
 *
 * @package Core
 * @subpackage Util
 */
class cHTMLInputSelectElement extends cHTMLSelectElement {

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML select field (aka 'DropDown').
     *
     * @param string $sName
     *         Name of the select element
     * @param string $iWidth [optional]
     *         Width of the select element
     * @param string $sID [optional]
     *         ID of the select element
     * @param bool $bDisabled [optional]
     *         Item disabled flag (non-empty to set disabled)
     * @param int $iTabIndex [optional]
     *         Tab index for form elements
     * @param string $sAccessKey [optional]
     *         Key to access the field
     */
    public function __construct($sName, $iWidth = '', $sID = '', $bDisabled = false, $iTabIndex = NULL, $sAccessKey = '') {
        parent::__construct($sName, $iWidth, $sID, $bDisabled, $iTabIndex, $sAccessKey);
    }

    /**
     * Adds articles to select options.
     *
     * @param int    $iIDCat
     *         idcat of the category to be listed
     * @param bool   $bColored
     *         Add color information to option elements
     * @param bool   $bArtOnline
     *         If true, only online articles will be added
     * @param string $sSpaces
     *         Just some '&nbsp;' to show data hierarchically
     *         (used in conjunction with addCategories)
     *
     * @return int
     *         Number of items added
     * 
     * @throws cDbException
     */
    public function addArticles($iIDCat, $bColored = false, $bArtOnline = true, $sSpaces = '') {
        global $cfg, $lang;

        if (is_numeric($iIDCat) && $iIDCat > 0) {

            $sql = "SELECT al.title AS title
                        , al.idartlang AS idartlang
                        , ca.idcat AS idcat
                        , ca.idcatart AS idcatart
                        , ca.is_start AS isstart
                        , al.online AS online
                        , cl.startidartlang AS idstartartlang
                    FROM " . $cfg["tab"]["art_lang"] . " AS al
                        , " . $cfg["tab"]["cat_art"] . " AS ca
                        , " . $cfg["tab"]["cat_lang"] . " AS cl
                    WHERE ca.idcat = '" . cSecurity::toInteger($iIDCat) . "'
                        AND cl.idcat = ca.idcat
                        AND cl.idlang = al.idlang
                        ";

            if ($bArtOnline) {
                $sql .= " AND al.online = 1";
            }

            $sql .= " AND al.idart = ca.idart
                AND al.idlang = " . (int) $lang . "
                ORDER BY al.title";

            $oDB = cRegistry::getDb();
            $oDB->query($sql);

            $iCount = $oDB->numRows();
            if ($iCount == 0) {
                return 0;
            } else {
                $iCounter = count($this->_options);
                while ($oDB->nextRecord()) {
                    // Generate new option element
                    $oOption = new cHTMLOptionElement($sSpaces . '&nbsp;&nbsp;&nbsp;' . cString::getPartOfString($oDB->f('title'), 0, 32), $oDB->f('idcatart'));

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
     * Adds categories (optionally including articles) as options to select box.
     *
     * Note: Using 'with articles' also adds articles - but the categories
     * will get negative values cause otherwise there is no way to distinguish
     * between a category id and an article id.
     *
     * @param int  $iMaxLevel
     *         Max. level shown (to be exact: except this level)
     * @param bool $bColored
     *         Add color information to option elements
     * @param bool $bCatVisible
     *         If true, only add idcat as value, if cat is visible
     * @param bool $bCatPublic
     *         If true, only add idcat as value, if cat is public
     * @param bool $bWithArt
     *         Add also articles per category
     * @param bool $bArtOnline
     *         If true, show only online articles
     * 
     * @return int
     *         Number of items added
     * 
     * @throws cDbException
     */
    public function addCategories($iMaxLevel = 0, $bColored = false, $bCatVisible = true, $bCatPublic = true, $bWithArt = false, $bArtOnline = true) {
        global $cfg, $client, $lang;

        $sql = "SELECT
                    c.idcat
                    , cl.name
                    , cl.visible
                    , cl.public
                    , ct.level
                FROM
                    " . $cfg["tab"]["cat"] . " AS c
                    , " . $cfg["tab"]["cat_lang"] . " AS cl
                    , " . $cfg["tab"]["cat_tree"] . " AS ct
                WHERE
                    c.idclient = " . (int) $client . "
                    AND cl.idlang = " . (int) $lang . "
                    AND cl.idcat = c.idcat
                    AND ct.idcat = c.idcat";
        if ($iMaxLevel > 0) {
            $sql .= " AND ct.level < " . (int) $iMaxLevel;
        }
        $sql .= " ORDER BY ct.idtree";

        $oDB = cRegistry::getDb();
        $oDB->query($sql);

        $iCount = $oDB->numRows();
        if ($iCount == 0) {
            return false;
        } else {
            $iCounter = count($this->_options);
            while ($oDB->nextRecord()) {
                $sSpaces = '';
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
     * @param int    $iIDCatArt
     *         Article id
     * @param string $sTypeRange
     *         Comma separated list of CONTENIDO type ids
     *         which may be in the resulting list (e.g. '1', '17', '28')
     * 
     * @return int
     *         Number of items added
     * 
     * @throws cDbException
     */
    public function addTypesFromArt($iIDCatArt, $sTypeRange = '') {
        global $cfg, $lang;

        if (is_numeric($iIDCatArt) && $iIDCatArt > 0) {
            $oDB = cRegistry::getDb();

            $sql = "SELECT
                        t.typeid AS typeid
                        , t.idtype AS idtype
                        , t.type AS type
                        , t.description AS description
                        , t.value AS value
                    FROM " . $cfg["tab"]["content"] . " AS t
                        , " . $cfg["tab"]["art_lang"] . " AS al
                        , " . $cfg["tab"]["cat_art"] . " AS ca
                        , " . $cfg["tab"]["type"] . " AS t
                    WHERE
                        t.idtype = t.idtype
                        AND t.idartlang = al.idartlang
                        AND al.idart = ca.idart
                        AND al.idlang = " . (int) $lang . "
                        AND ca.idcatart = " . (int) $iIDCatArt;
            if ($sTypeRange != "") {
                $sql .= " AND t.idtype IN (" . $oDB->escape($sTypeRange) . ")";
            }
            $sql .= " ORDER BY t.idtype, t.typeid";

            $oDB = cRegistry::getDb();
            $oDB->query($sql);

            $iCount = $oDB->numRows();
            if ($iCount == 0) {
                return false;
            } else {
                while ($oDB->nextRecord()) {
                    $sTypeIdentifier = "tblData.idtype = '" . $oDB->f('idtype') . "' AND tblData.typeid = '" . $oDB->f('typeid') . "'";

                    // Generate new option element
                    $oOption = new cHTMLOptionElement($oDB->f('type') . "[" . $oDB->f('typeid') . "]: " . cString::getPartOfString(strip_tags($oDB->f("value")), 0, 50), $sTypeIdentifier);

                    // Add option element to the list
                    $this->addOptionElement($sTypeIdentifier, $oOption);
                }
                return $iCount;
            }
        } else {
            return false;
        }
    }
}

/**
 * Config table class.
 *
 * @package Core
 * @subpackage Util
 */
class UI_Config_Table
{
    /**
     * @var string
     */
    protected $_tplCellCode = '';

    /**
     * @var string
     */
    protected $_tplTableFile = '';

    /**
     * @var string
     */
    protected $_width = '';

    /**
     * @var int
     */
    protected $_border = 0;

    /**
     * @var string
     */
    protected $_borderColor = '';

    /**
     * @var string
     */
    protected $_solidBorder = '';

    /**
     * @var int
     */
    protected $_padding = 0;

    /**
     * @var array
     */
    protected $_cells = [];

    /**
     * @var array
     */
    protected $_cellAlignment = [];

    /**
     * @var array
     */
    protected $_cellVAlignment = [];

    /**
     * @var string
     */
    protected $_cellColSpan;

    /**
     * @var array
     */
    protected $_cellClass = [];

    /**
     * @var string
     */
    protected $_rowBgColor;

    /**
     * @var string
     */
    protected $_rowExtra;

    /**
     * @var bool
     */
    protected $_addMultiSelJS = null;

    /**
     * @var string
     */
    protected $_colorLight = '';

    /**
     * @var string
     */
    protected $_colorDark = '';

    /**
     * Constructor to create an instance of this class.
     */
    public function __construct()
    {
        $cfg         = cRegistry::getConfig();
        $backendPath = cRegistry::getBackendPath();

        $this->_padding      = 2;
        $this->_border       = 0;
        $this->_tplTableFile = $backendPath . $cfg['path']['templates'] . $cfg['templates']['input_helper'];
        $this->_tplCellCode  = $backendPath . $cfg['path']['templates'] . $cfg['templates']['input_helper_row'];
    }

    /**
     * @param string $code
     */
    public function setCellTemplate($code)
    {
        $this->_tplCellCode = $code;
    }

    /**
     * @param bool $bEnabled
     */
    function setAddMultiSelJS($bEnabled = true) {
        $this->_bAddMultiSelJS = (bool) $bEnabled;
    }

    /**
     * @param string $path
     */
    public function setTableTemplateFile($path)
    {
        $this->_tplTableFile = $path;
    }

    /**
     * Set method for cells
     *
     * @param string $row
     * @param string $cell
     * @param string $content
     */
    public function setCell($row, $cell, $content)
    {
        $this->_cells[$row][$cell]         = $content;
        $this->_cellAlignment[$row][$cell] = "";
    }

    /**
     * Set method for cell alignment
     *
     * @param string       $row
     * @param string       $cell
     * @param string $alignment
     */
    protected function setCellAlignment($row, $cell, $alignment)
    {
        $this->_cellAlignment[$row][$cell] = $alignment;
    }

    /**
     * Set method for cell vertical alignment
     *
     * @param string       $row
     * @param string       $cell
     * @param string $alignment
     */
    public function setCellVAlignment($row, $cell, $alignment)
    {
        $this->_cellVAlignment[$row][$cell] = $alignment;
    }

    /**
     * Set method for cell class
     *
     * @param string       $row
     * @param string       $cell
     * @param string $class
     */
    public function setCellClass($row, $cell, $class)
    {
        $this->_cellClass[$row][$cell] = $class;
    }

    /**
     */
    public function addMultiSelJS()
    {
        $this->_addMultiSelJS = true;
    }

    /**
     * Add inline javascript
     *
     * @internal Trick: To save multiple selections in <select>-Element,
     * add some JS which saves the selection, comma separated
     * in a hidden input field on change.
     * Try ... catch prevents error messages, if function is added
     * more than once if (!fncUpdateSel) in JS has not worked ...
     *
     * @return string
     */
    protected function _getMultiSelJS()
    {
        $script = '
<script type="text/javascript"><!--
try {
    function fncUpdateSel(selectBox, storage) {
        var sSelection = "";
        var oSelectBox = document.getElementsByName(selectBox)[0];
        var oStorage   = document.getElementsByName(storage)[0];
        if (oSelectBox && oStorage) {
            for (var i = 0; i < oSelectBox.length; i++) {
                if (oSelectBox.options[i].selected === true) {
                    if (sSelection !== "") {
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

        return $script;
    }

    /**
     * Rendering function
     *
     * @param bool $print [optional]
     *
     * @return string|null
     *         Complete template string or nothing
     *
     * @throws cInvalidArgumentException
     */
    public function render($print = false)
    {
        $template = new cTemplate();
        $template->reset();

        $ColCount = 0;
        if (is_array($this->_cells)) {
            foreach ($this->_cells as $row => $cells) {
                $ColCount++;
                // $dark = !$dark;
                $line  = '';
                $count = 0;

                foreach ($cells as $cell => $data) {
                    $count++;
                    $tplCell = new cTemplate();
                    $tplCell->reset();

                    if ($this->_cellClass[$row][$cell] != '') {
                        $tplCell->set('s', 'CLASS', $this->_cellClass[$row][$cell]);
                    } else {
                        $tplCell->set('s', 'CLASS', '');
                    }

                    if ($this->_cellAlignment[$row][$cell] != '') {
                        $tplCell->set('s', 'ALIGN', $this->_cellAlignment[$row][$cell]);
                    } else {
                        $tplCell->set('s', 'ALIGN', 'left');
                    }

                    if ($this->_cellVAlignment[$row][$cell] != '') {
                        $tplCell->set('s', 'VALIGN', $this->_cellAlignment[$row][$cell]);
                    } else {
                        $tplCell->set('s', 'VALIGN', 'top');
                    }

                    // Multi selection javascript
                    if ($this->_addMultiSelJS) {
                        $data                 = $this->_getMultiSelJS() . $data;
                        $this->_addMultiSelJS = false;
                    }

                    $tplCell->set('s', 'CONTENT', $data);
                    $line .= $tplCell->generate($this->_tplCellCode, true, false);

                    error_log($line);
                }

                // Row
                $template->set('d', 'ROWS', $line);
                $template->next();
            }
        }
        $rendered = $template->generate($this->_tplTableFile, true, false);

        if ($print == true) {
            echo $rendered;

            return null;
        } else {
            return $rendered;
        }
    }
}