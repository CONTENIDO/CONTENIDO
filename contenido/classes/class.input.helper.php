<?php

/**
 * This file contains the input helper classes.
 * Various derived HTML class elements especially useful
 * in the input area of modules.
 * Simple table generation class especially useful to generate
 * backend configuration table. May be used also in Frontend,
 * but note the globally used variables ($cfg).
 *
 * @package    Core
 * @subpackage Util
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Select box with additional functionality for category and article selection
 *
 * @package    Core
 * @subpackage Util
 */
class cHTMLInputSelectElement extends cHTMLSelectElement
{

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML select field (aka 'DropDown').
     *
     * @param string $name Name of the select element
     * @param string $width [optional] Width of the select element
     * @param string $id [optional] ID of the select element
     * @param bool $disabled [optional] Item disabled flag (non-empty to set disabled)
     * @param int $tabindex [optional] Tab index for form elements
     * @param string $accessKey [optional] Key to access the field
     */
    public function __construct($name, $width = '', $id = '', $disabled = false, $tabindex = NULL, $accessKey = '')
    {
        parent::__construct($name, $width, $id, $disabled, $tabindex, $accessKey);
    }

    /**
     * Adds articles to select options.
     *
     * @param int $idcat Id of the category to be listed
     * @param bool $colored Add color information to option elements
     * @param bool $artOnline If true, only online articles will be added
     * @param string $spaces Just some '&nbsp;' to show data hierarchically
     *      (used in conjunction with addCategories)
     * @return int Number of items added
     * @throws cDbException
     */
    public function addArticles($idcat, $colored = false, $artOnline = true, $spaces = ''): int
    {
        $idcat = cSecurity::toInteger($idcat ?? '0');
        if ($idcat <= 0) {
            return 0;
        }

        $sql = "SELECT
                    al.title AS title
                    , al.idartlang AS idartlang
                    , ca.idcat AS idcat
                    , ca.idcatart AS idcatart
                    , ca.is_start AS isstart
                    , al.online AS online
                    , cl.startidartlang AS idstartartlang
                FROM
                    " . cDb::getTableName('art_lang') . " AS al
                    , " . cDb::getTableName('cat_art') . " AS ca
                    , " . cDb::getTableName('cat_lang') . " AS cl
                WHERE
                    ca.idcat = " . $idcat . "
                    AND cl.idcat = ca.idcat
                    AND cl.idlang = al.idlang
                    ";

        if ($artOnline) {
            $sql .= " AND al.online = 1";
        }

        $sql .= " AND al.idart = ca.idart
            AND al.idlang = " . cRegistry::getLanguageId() . "
            ORDER BY al.title";

        $db = cRegistry::getDb();
        $db->query($sql);

        $iCount = $db->numRows();
        if ($iCount == 0) {
            return 0;
        }

        $iCounter = count($this->_options);
        while ($db->nextRecord()) {
            // Generate new option element
            $oOption = new cHTMLOptionElement(
                $spaces . '&nbsp;&nbsp;&nbsp;' . cString::getPartOfString($db->f('title'), 0, 32),
                $db->f('idcatart')
            );

            if ($colored) {
                if ($db->f('idstartartlang') == $db->f('idartlang')) {
                    if ($db->f('online') == 0) {
                        // Start article, but offline -> red
                        $oOption->setStyle('color: #ff0000;');
                    } else {
                        // Start article -> blue
                        $oOption->setStyle('color: #0000ff;');
                    }
                } elseif ($db->f('online') == 0) {
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

    /**
     * Adds categories (optionally including articles) as options to select box.
     *
     * Note: Using 'with articles' also adds articles - but the categories
     * will get negative values cause otherwise there is no way to distinguish
     * between a category id and an article id.
     *
     * @param int $maxLevel Max. level shown (to be exact: except this level)
     * @param bool $colored Add color information to option elements
     * @param bool $catVisible If true, only add idcat as value, if cat is visible
     * @param bool $catPublic If true, only add idcat as value, if cat is public
     * @param bool $withArt Add also articles per category
     * @param bool $artOnline If true, show only online articles
     * @return int Number of items added
     * @throws cDbException
     */
    public function addCategories(
        $maxLevel = 0,
        $colored = false,
        $catVisible = true,
        $catPublic = true,
        $withArt = false,
        $artOnline = true
    ): int
    {
        $maxLevel = cSecurity::toInteger($maxLevel ?? '0');
        $sql = "SELECT
                    c.idcat
                    , cl.name
                    , cl.visible
                    , cl.public
                    , ct.level
                FROM
                    " . cDb::getTableName('cat') . " AS c
                    , " . cDb::getTableName('cat_lang') . " AS cl
                    , " . cDb::getTableName('cat_tree') . " AS ct
                WHERE
                    c.idclient = " . cRegistry::getClientId() . "
                    AND cl.idlang = " . cRegistry::getLanguageId() . "
                    AND cl.idcat = c.idcat
                    AND ct.idcat = c.idcat";
        if ($maxLevel > 0) {
            $sql .= " AND ct.level < " . $maxLevel;
        }
        $sql .= " ORDER BY ct.idtree";

        $db = cRegistry::getDb();
        $db->query($sql);

        $iCount = $db->numRows();
        if ($iCount == 0) {
            return 0;
        }

        $iCounter = count($this->_options);
        while ($db->nextRecord()) {
            $spaces = '';
            $iID = $db->f('idcat');

            for ($i = 0; $i < $db->f('level'); $i++) {
                $spaces .= '&nbsp;&nbsp;&nbsp;';
            }

            // Generate new option element
            if (($catVisible && $db->f('visible') == 0) || ($catPublic && $db->f('public') == 0)) {
                // If category has to be visible or public and it isn't,
                // don't add value
                $sValue = '';
            } elseif ($withArt) {
                // If article will be added, set negative idcat as value
                $sValue = '-' . $iID;
            } else {
                // Show only categories - and everything is fine...
                $sValue = $iID;
            }
            $oOption = new cHTMLOptionElement($spaces . '>&nbsp;' . $db->f('name'), $sValue);

            // Coloring option element, restricted shows grey color
            $oOption->setStyle('background-color: #EFEFEF');
            if ($colored && ($db->f('visible') == 0 || $db->f('public') == 0)) {
                $oOption->setStyle('color: #666666;');
            }

            // Add option element to the list
            $this->addOptionElement($iCounter, $oOption);

            if ($withArt) {
                $iArticles = $this->addArticles($iID, $colored, $artOnline, $spaces);
                $iCount += $iArticles;
            }
            $iCounter = count($this->_options);
        }

        return $iCount;
    }

    /**
     * Function addTypesFromArt.
     * Adds types and type ids which are available for the specified article
     *
     * @param int $idCatArt Article id
     * @param string $typeRange Comma separated list of CONTENIDO type ids which may be
     *      in the resulting list (e.g. '1', '17', '28')
     * @return int Number of items added
     * @throws cDbException
     */
    public function addTypesFromArt($idCatArt, $typeRange = ''): int
    {
        $idCatArt = cSecurity::toInteger($idCatArt ?? '0');

        if ($idCatArt <= 0) {
            return 0;
        }

        $db = cRegistry::getDb();

        $sql = "SELECT
                    t.typeid AS typeid
                    , t.idtype AS idtype
                    , t.type AS type
                    , t.description AS description
                    , t.value AS value
                FROM " . cDb::getTableName('content') . " AS c
                    , " . cDb::getTableName('art_lang') . " AS al
                    , " . cDb::getTableName('cat_art') . " AS ca
                    , " . cDb::getTableName('type') . " AS t
                WHERE
                    t.idtype = c.idtype
                    AND c.idartlang = al.idartlang
                    AND al.idart = ca.idart
                    AND al.idlang = " . cRegistry::getClientId() . "
                    AND ca.idcatart = " . $idCatArt;
        if ($typeRange != '') {
            $sql .= " AND t.idtype IN (" . $db->escape($typeRange) . ")";
        }
        $sql .= " ORDER BY t.idtype, t.typeid";

        $db = cRegistry::getDb();
        $db->query($sql);

        $iCount = $db->numRows();
        if ($iCount == 0) {
            return 0;
        }

        while ($db->nextRecord()) {
            $sTypeIdentifier = "tblData.idtype = '" . $db->f('idtype') . "' AND tblData.typeid = '" . $db->f('typeid') . "'";

            // Generate new option element
            $oOption = new cHTMLOptionElement($db->f('type') . "[" . $db->f('typeid') . "]: " . cString::getPartOfString(strip_tags($db->f('value')), 0, 50), $sTypeIdentifier);

            // Add option element to the list
            $this->addOptionElement($sTypeIdentifier, $oOption);
        }
        return $iCount;
    }
}

/**
 * Config table class.
 *
 * @package    Core
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
    protected $_addMultiSelJS = false;

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
        $cfg = cRegistry::getConfig();
        $backendPath = cRegistry::getBackendPath();

        $this->_padding = 2;
        $this->_border = 0;
        $this->_tplTableFile = $backendPath . $cfg['path']['templates'] . $cfg['templates']['input_helper'];
        $this->_tplCellCode = $backendPath . $cfg['path']['templates'] . $cfg['templates']['input_helper_row'];
    }

    /**
     * @param string $code
     */
    public function setCellTemplate($code)
    {
        $this->_tplCellCode = $code;
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
     * @param int|string $row
     * @param int|string $cell
     * @param string $content
     */
    public function setCell($row, $cell, $content)
    {
        $this->_cells[$row][$cell] = $content;
        $this->_cellAlignment[$row][$cell] = '';
    }

    /**
     * Set method for cell alignment
     *
     * @param int|string $row
     * @param int|string $cell
     * @param string $alignment
     */
    protected function setCellAlignment($row, $cell, $alignment)
    {
        $this->_cellAlignment[$row][$cell] = $alignment;
    }

    /**
     * Set method for cell vertical alignment
     *
     * @param int|string $row
     * @param int|string $cell
     * @param string $alignment
     */
    public function setCellVAlignment($row, $cell, $alignment)
    {
        $this->_cellVAlignment[$row][$cell] = $alignment;
    }

    /**
     * Set method for cell class
     *
     * @param int|string $row
     * @param int|string $cell
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
     * @internal Trick: To save multiple selections in <select>-Element, add some JS which saves
     *      the selection, comma separated in a hidden input field on change.
     *      Try ... catch prevents error messages, if function is added more than once if
     *      (!fncUpdateSel) in JS has not worked ...
     */
    protected function _getMultiSelJS(): string
    {
        $script = '
<script type="text/javascript">
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
</script>
';

        return $script;
    }

    /**
     * Rendering function
     *
     * @param bool $print [optional]
     * @return ?string Complete template string or nothing
     * @throws cInvalidArgumentException
     */
    public function render(bool $print = false): ?string
    {
        $template = new cTemplate();
        $template->reset();

        $ColCount = 0;
        if (is_array($this->_cells)) {
            foreach ($this->_cells as $row => $cells) {
                $ColCount++;
                // $dark = !$dark;
                $line = '';
                $count = 0;

                foreach ($cells as $cell => $data) {
                    $count++;
                    $tplCell = new cTemplate();
                    $tplCell->reset();

                    $tplCell->set('s', 'CLASS', $this->_cellClass[$row][$cell] ?? '');
                    $tplCell->set('s', 'ALIGN', $this->_cellAlignment[$row][$cell] ?? 'left');
                    $tplCell->set('s', 'VALIGN', $this->_cellVAlignment[$row][$cell] ?? 'top');

                    // add multi selection javascript
                    if ($this->_addMultiSelJS) {
                        $data = $this->_getMultiSelJS() . $data;
                    }

                    $tplCell->set('s', 'CONTENT', $data);
                    $line .= $tplCell->generate($this->_tplCellCode, true, false);
                }

                // Row
                $template->set('d', 'ROWS', $line);
                $template->next();
            }
        }
        $rendered = $template->generate($this->_tplTableFile, true, false);

        if ($print) {
            echo $rendered;
            return null;
        } else {
            return $rendered;
        }
    }

}
