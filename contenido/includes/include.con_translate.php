<?php

/**
 * This file contains the mass module translation backend page in content area.
 *
 * @package Core
 * @subpackage Backend
 * @author Ingo van Peeren
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Extend cGuiScrollList for some special features like CSS class for table data
 */
class cGuiScrollListAlltranslations extends cGuiScrollList {

    /**
     * Constructor to create an instance of this class.
     */
    function __construct() {
        parent::__construct(false);
        $this->objTable->setClass("generic alltranslations");
        $this->objTable->updateAttributes([
            "cellpadding" => "2"
        ]);
    }

    /**
     * Is called when a new row is rendered
     *
     * @param int $row
     *         The current row which is being rendered
     */
    public function onRenderRow($row) {
        // Add module name to the table row, we need it for the "inused_module" action
        $this->objRow->setAttribute('data-name', $this->data[$row - 1][1] ?? '');
    }

    /**
     * Is called when a new column is rendered
     *
     * @param int $column
     *         The current column which is being rendered
     */
    public function onRenderColumn($column) {
        $iColumns = count($this->data[0]);

        switch ($column) {
            case 1:
                $sClass = 'module';
                break;
            case 2:
                $sClass = 'inuse';
                break;
            case 3:
                $sClass = 'keyword';
                break;
            case $iColumns:
                $sClass = 'actions';
                break;

            default:
                $sClass = 'translation';
                break;
        }

        $this->objItem->setClass($sClass);
    }

    /**
     * Sorts the list by a given field and a given order.
     *
     * @param int $field
     *         Field index
     * @param string $order
     *         Sort order (see php's sort documentation)
     */
    public function sort($field, $order) {
        $this->sortkey = $field;
        $this->sortmode = ($order === 'DESC') ? SORT_DESC : SORT_ASC;

        $field = $field + 1;

        if ($field > 3) {
            $sortby = [];
            foreach ($this->data as $row => $cols) {
                $sortby[$row] = trim(cString::toLowerCase(conHtmlentities($cols[$field])));
            }
            $this->data = cArray::csort($this->data, $sortby, $this->sortmode);
        } else {
            $this->data = cArray::csort($this->data, "$field", $this->sortmode);
        }
    }

}

/**
 * Adds sorting images to string
 *
 * @param int    $index
 * @param string $text
 *
 * @return string
 *
 * @throws cException
 */
function addSortImages($index, $text) {
    $cfg = cRegistry::getConfig();
    $sortUp = '<img src="' . $cfg["path"]["contenido_fullhtml"] . $cfg['path']['images'] . 'sort_up.gif" alt="' . i18n("Sort") . '" title="' . i18n("Sort") . '">';
    $sortDown = '<img src="' . $cfg["path"]["contenido_fullhtml"] . $cfg['path']['images'] . 'sort_down.gif" alt="' . i18n("Sort") . '" title="' . i18n("Sort") . '">';

    if ($_REQUEST["sortby"] == $index) {
        if ($_REQUEST["sortmode"] == 'ASC') {
            $sortString = $text . $sortUp;
        } else {
            $sortString = $text . $sortDown;
        }
    } else {
        $sortString = $text . $sortUp . $sortDown;
    }
    return $sortString;
}

global $elemperpage;

$auth = cRegistry::getAuth();
$perm = cRegistry::getPerm();
$sess = cRegistry::getSession();
$cfg = cRegistry::getConfig();
$area = cRegistry::getArea();
$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cRegistry::getLanguageId();
$frame = cRegistry::getFrame();
$action = cRegistry::getAction() ?? 'con_translate_view';

$page = new cGuiPage("con_translate");

// Display critical error if no valid client is selected
if ($client < 1) {
    $page->displayCriticalError(i18n("No Client selected"));
    $page->render();
    return;
}

// Check permission for current user
if (!$perm->have_perm_area_action($area, $action)) {
    $page->displayCriticalError(i18n('Permission denied'));
    $page->render();
    return;
}

$inUseCollection = new cApiInUseCollection();
list($inUse, $message) = $inUseCollection->checkAndMark("translations", $client, true, i18n("Translations are used by %s (%s)"), true, "main.php?area=$area&frame=$frame");
unset($inUseCollection);
if ($inUse == true) {
    $message .= "<br>";
}

// Initialize
$elemPerPage = [
    25 => "25",
    50 => "50"
];

// Set noResults variable standard to false (= results available)
$noResults = false;

$db = cRegistry::getDb();

$langobj = new cApiLanguage($lang);

$langstring = $langobj->get('name') . ' (' . $lang . ')';

// Initialize $_REQUEST with common used keys to prevent PHP 'Undefined array key' warnings
foreach (['dellang', 'editlang', 'editstring', 'elemperpage', 'extralang', 'filter', 'modtrans', 'page', 'search', 'sortby', 'sortmode'] as $_key) {
    if (!isset($_REQUEST[$_key])) {
        $_REQUEST[$_key] = '';
    }
}

$aTmpExtraLanguages = $_REQUEST["extralang"];
$extraLanguages = [];
if (is_array($aTmpExtraLanguages)) {
    foreach ($aTmpExtraLanguages as $idlang) {
        if ($idlang != $_REQUEST["dellang"]) {
            $extraLanguages[] = $idlang;
        }
    }
}
$allLanguages = array_merge([
    $lang
], $extraLanguages);

$editstring = $_REQUEST["editstring"];
$editlang = $_REQUEST["editlang"];
if ($editlang != 'all') {
    $editlang = cSecurity::toInteger($editlang);
}

$search = cString::toLowerCase(trim($_REQUEST["search"]));
$filter = $_REQUEST["filter"];

$cApiModuleCollection = new cApiModuleCollection();
$modulesInUse = $cApiModuleCollection->getModulesInUse();

$iNextPage = cSecurity::toInteger($_GET['nextpage'] ?? 0);
if ($iNextPage <= 0) {
    $iNextPage = 1;
}

if ($_REQUEST["sortmode"] !== "DESC") {
    $_REQUEST["sortmode"] = "ASC";
}

// no value found in request for items per page -> get it from db or set default
$oUser = new cApiUser($auth->auth["uid"]);
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST['elemperpage']) || $_REQUEST['elemperpage'] < 0) {
    $_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}
if (!is_numeric($_REQUEST["elemperpage"])) {
    $_REQUEST["elemperpage"] = 25;
}
if ($_REQUEST["elemperpage"] > 0) {
    // -- All -- will not be stored, as it may be impossible to change this back
    // to something more useful
    $oUser->setProperty("itemsperpage", $area, $_REQUEST["elemperpage"]);
}
unset($oUser);

if (!isset($_REQUEST["page"]) || !is_numeric($_REQUEST['page']) || $_REQUEST['page'] <= 0 || $_REQUEST["elemperpage"] == 0) {
    $_REQUEST["page"] = 1;
}

// Save translations
if ($action == 'con_translate_edit') {
    $error = false;

    $savetranslations = $_REQUEST['modtrans'];
    if (is_array($savetranslations)) {
        foreach ($savetranslations as $idmod => $savemodtranslations) {

            // get translation keywords from module
            $module = new cApiModule($idmod);
            $moduleKeywords = $module->parseModuleForStringsLoadFromFile($cfg, $client, $lang);
            $moduleKeywordsHashes = [];
            foreach ($moduleKeywords as $keyword) {
                $moduleKeywordsHashes[md5($keyword)] = $keyword;
            }

            foreach ($savemodtranslations as $hash => $stringtranslations) {
                foreach ($stringtranslations as $idlang => $modlangtranslation) {
                    $contenidoTranslateFromFile = new cModuleFileTranslation($module, false, $idlang);
                    $fileTranslations = $contenidoTranslateFromFile->getTranslationArray();

                    $hashparts = explode('_', $hash);
                    $translationKey = $moduleKeywordsHashes[$hashparts[1]];
                    $fileTranslations[stripslashes($translationKey)] = stripslashes($modlangtranslation);
                    $thislangerror = $contenidoTranslateFromFile->saveTranslationArray($fileTranslations);
                    if (!$thislangerror) {
                        $error = true;
                    }
                }
            }
        }
    }

    if (!empty($_POST['modtrans'])) {
        if (!$error) {
            $page->displayOk(i18n('Saved translation successfully!'));
        } else {
            $page->displayError(i18n("Can't save translation!"));
        }
    }
}

// Get all modules and translations for current client
$moduleCollection = new cApiModuleCollection();
$moduleCollection->setWhere('idclient', $client);
$moduleCollection->setOrder('name');
$moduleCollection->query();
$moduleObjects = $moduleCollection->fetchTable([], ['obj' => 'cApiModuleCollection']);

$allModules = [];
$allTranslations = [];

foreach ($moduleObjects as $entry) {
    /** @var cApiModule $module */
    $module = $entry['obj'];
    $idmod = cSecurity::toInteger($module->get('idmod'));
    $allModules[$idmod] = $module->get('name');

    // Get the mi18n strings from modul input/output
    $strings = $module->parseModuleForStringsLoadFromFile($cfg, $client, $lang);

    foreach ($allLanguages as $idlang) {
        // Get the strings from translation file
        $contenidoTranslateFromFile = new cModuleFileTranslation($module, false, $idlang);
        $fileTranslations = $contenidoTranslateFromFile->getTranslationArray();

        $translations = [];
        foreach ($fileTranslations as $key => $value) {
            $hash = $idmod . '_' . md5($key);
            $translations[$hash] = $value;
        }

        $currentModuleTranslations = [];

        // Insert new strings
        foreach ($strings as $string) {
            $hash = $idmod . '_' . md5($string);
            $currentTranslation = $translations[$hash] ?? '';
            if (isset($allTranslations[$hash])) {
                $allTranslations[$hash]['translations'][$idlang] = $currentTranslation;
            } else {
                $allTranslations[$hash] = [
                    'string' => $string,
                    'translations' => [
                        $idlang => $currentTranslation
                    ],
                    'idmod' => $idmod
                ];
            }
        }
    }
}

// Get all templates for current client
$templateCollection = new cApiTemplateCollection();
$templateCollection->setWhere('idclient', $client);
$templateCollection->setOrder('name');
$templateCollection->addResultField('name');
$templateCollection->query();
$aAllTemplates = [];
foreach ($templateCollection->fetchTable(['idtpl' => 'idtpl', 'name' => 'name']) as $entry) {
    $aAllTemplates[cSecurity::toInteger($entry['idtpl'])] = $entry['name'];
}

// filter by module/template or by search string
if ($search != '' || ($filter != '' && $filter != -1)) {
    foreach ($allTranslations as $hash => $aTranslation) {
        // filter by search
        if ($search != '') {
            $bFoundSearch = false;
            if (cString::findFirstPosCI($aTranslation['string'], $search) !== false) {
                $bFoundSearch = true;
            }
            foreach ($aTranslation['translations'] as $idlang => $langTranslation) {
                if (cString::findFirstPosCI($langTranslation, $search) !== false) {
                    $bFoundSearch = true;
                }
            }
        } else {
            $bFoundSearch = true;
        }

        // filter by module or template
        if ($filter != '' && $filter != -1) {
            $bFoundFilter = false;
            $aFilterType = explode('_', $filter);
            if ($aFilterType[0] == 'module') {
                $iFilterModule = $aFilterType[1];
                if ($aTranslation['idmod'] == $iFilterModule) {
                    $bFoundFilter = true;
                }
            } else {
                $iFilterTemplate = $aFilterType[1];
                if (is_array($modulesInUse[$aTranslation['idmod']]) && array_key_exists($iFilterTemplate, $modulesInUse[$aTranslation['idmod']])) {
                    $bFoundFilter = true;
                }
            }
        } else {
            $bFoundFilter = true;
        }

        if (!$bFoundSearch || !$bFoundFilter) {
            unset($allTranslations[$hash]);
        }
    }
}

if (empty($allTranslations)) {
	$page->displayInfo(i18n("Can not find some module translations for your selection."));
	$noResults = true;
}

unset($strings);
unset($fileTranslations);
unset($translations);

// Form for adding languages
$formExtraLangsString = '';
if (is_array($allLanguages)) {

    $formExtraLangs = new cHTMLForm('extralangs');
    $formExtraLangs->setVar('area', $area);
    $formExtraLangs->setVar('frame', $frame);
    $formExtraLangs->setVar("elemperpage", $_REQUEST["elemperpage"]);
    $formExtraLangs->setVar("sortby", $_REQUEST["sortby"]);
    $formExtraLangs->setVar("sortmode", $_REQUEST["sortmode"]);
    $formExtraLangs->setVar("search", $search);
    $formExtraLangs->setVar("filter", $filter);
    foreach ($extraLanguages as $idExtraLang) {
        $formExtraLangs->setVar('extralang[]', $idExtraLang);
    }

    $labelExtraLangs = new cHTMLSpan(i18n("New language for editing") . ': ', "vALignMiddle");
    $selectExtraLangs = new cHTMLSelectElement('extralang[]', "100px", 'newlang');
    $selectExtraLangs->setClass("vAlignTop");

    $sql = "SELECT
              A.name AS name, A.idlang AS idlang, B.idclientslang AS idclientslang
            FROM
              " . $cfg["tab"]["lang"] . " AS A,
              " . $cfg["tab"]["clients_lang"] . " AS B
            WHERE
              A.idlang = B.idlang AND
              B.idclient = '" . cSecurity::toInteger($client) . "'
            ORDER BY A.idlang";

    $db->query($sql);

    $langNames = [];
    $countExtraLangOptions = 0;
    while ($db->nextRecord()) {
        $idlang = $db->f("idlang");
        $langString = conHtmlSpecialChars($db->f("name")) . " (" . $db->f("idlang") . ")";
        $langNames[$idlang] = $langString;
        if (!in_array($idlang, $allLanguages)) {
            $option = new cHTMLOptionElement($langString, $idlang);
            $selectExtraLangs->addOptionElement($idlang, $option);
            $countExtraLangOptions++;
        }
    }
    $submitExtraLangs = new cHTMLButton('newlangsubmit', i18n("Add"), 'newlangsubmit', false, NULL, '', 'image', "vAlignTop tableElement");
    $submitExtraLangs->setImageSource('images/but_art_new.gif')
        ->setAlt(i18n("Add"));

    $formExtraLangs->setContent($labelExtraLangs->render() . $selectExtraLangs->render() . $submitExtraLangs->render());
    if ($countExtraLangOptions > 0) {
        $formExtraLangsString = $formExtraLangs->render();
    }
}

// Form for choosing elements per page
$formElementsPerPage = new cHTMLForm('elementsperpage');
$formElementsPerPage->setVar('area', $area);
$formElementsPerPage->setVar('frame', $frame);
$formElementsPerPage->setVar('idclient', $client);
$formElementsPerPage->setVar("sortby", $_REQUEST["sortby"]);
$formElementsPerPage->setVar("sortmode", $_REQUEST["sortmode"]);
$formElementsPerPage->setVar("search", $search);
$formElementsPerPage->setVar("filter", $filter);
foreach ($extraLanguages as $idExtraLang) {
    $formElementsPerPage->setVar('extralang[]', $idExtraLang);
}
$labelElementsPerPage = new cHTMLLabel(i18n("Items per page:") . ':', 'elemperpage');
$selectElementsPerPage = new cHTMLSelectElement('elemperpage');

foreach ($elemPerPage as $value => $option) {
    $option = new cHTMLOptionElement($option, $value);
    if ($_REQUEST["elemperpage"] == $value) {
        $option->setSelected(true);
    }
    $selectElementsPerPage->addOptionElement($value, $option);
}
$selectElementsPerPage->setAttribute('class', 'elemperpage');
$submitElementsPerPage = new cHTMLButton('elemperpagesubmit', i18n("Submit"), 'elemperpagesubmit', false, NULL, '', 'image');
$submitElementsPerPage->setImageSource($cfg["path"]["contenido_fullhtml"] . $cfg['path']['images'] . 'but_ok.gif');

$formElementsPerPage->setContent($labelElementsPerPage->render() . $selectElementsPerPage->render() . $submitElementsPerPage->render());

// Form for filtering by module/template and searching by given strings
$formSearch = new cHTMLForm('searchfilter');
$formSearch->setVar('area', $area);
$formSearch->setVar('frame', $frame);
$formSearch->setVar("elemperpage", $elemperpage);
$formSearch->setVar("sortby", $_REQUEST["sortby"]);
$formSearch->setVar("sortmode", $_REQUEST["sortmode"]);
foreach ($extraLanguages as $idExtraLang) {
    $formSearch->setVar('extralang[]', $idExtraLang);
}
$filterSelect = '<select name="filter">';
$filterSelect .= '<option value="-1">' . i18n("-- filter by --") . '</option>';
if (is_array($allModules) && count($allModules) > 0) {
    $filterSelect .= '<optgroup label="' . i18n("Module name") . '">';
    foreach ($allModules as $idmod => $sModule) {
        if ($_REQUEST["filter"] == 'module_' . $idmod) {
            $sSelected = ' selected';
        } else {
            $sSelected = '';
        }
        $filterSelect .= '<option value="module_' . $idmod . '"' . $sSelected . '>' . $sModule . '</option>';
    }
    $filterSelect .= '</optgroup>';
}
if (is_array($aAllTemplates) && count($aAllTemplates) > 0) {
    $filterSelect .= '<optgroup label="' . i18n("Template") . '">';
    foreach ($aAllTemplates as $idtpl => $sTemplate) {
        if ($_REQUEST["filter"] == 'template_' . $idtpl) {
            $sSelected = ' selected';
        } else {
            $sSelected = '';
        }
        $filterSelect .= '<option value="template_' . $idtpl . '"' . $sSelected . '>' . $sTemplate . '</option>';
    }
    $filterSelect .= '</optgroup>';
}
$searchInput = new cHTMLTextbox('search', $search, 20);

$searchSubmit = ' <input type="image" name="searchsubmit" class="vAlignTop" value="submit" src="' . $cfg["path"]["contenido_fullhtml"] . $cfg['path']['images'] . 'but_preview.gif">';

$formSearch->setContent($filterSelect . $searchInput->render() . $searchSubmit);

// The list of translations
$list = new cGuiScrollListAlltranslations();
$i = 0;
// building parameter array
$tableHeaders = [
    addSortImages($i++, i18n('Module name')),
    addSortImages($i++, i18n('In use by')),
    addSortImages($i++, i18n('Translation ID')),
    addSortImages($i++, i18n('Current language') . ': ' . $langstring)
];
foreach ($extraLanguages as $idExtraLang) {
    $delImage = new cHTMLImage('images/but_cancel.gif');
    $delImage = $delImage->setAlt(i18n("Delete"))->render();

    $delLangLink = new cHTMLLink('javascript:void(0)');
    $delLangLink = $delLangLink->setClass('vAlignMiddle')
        ->setAttribute('data-action', 'dellang')
        ->setAttribute('data-id', $idExtraLang)
        ->setAlt(i18n("Delete"))
        ->disableAutomaticParameterAppend()
        ->setContent($delImage)->render();

    $tableHeaders[] = $delLangLink . ' ' . addSortImages($i++, i18n('Language') . ': ' . $langNames[$idExtraLang]);
}
$tableHeaders[] = i18n('Edit row');

call_user_func_array([
    $list,
    "setHeader"
], $tableHeaders);

$iHeaders = count($tableHeaders);
for ($i = 0; $i < $iHeaders; $i++) {
    $list->setSortable($i, true);
}
$list->setCustom("nextpage", $iNextPage);
$list->setCustom("elemperpage", $_REQUEST["elemperpage"]);
$list->setCustom("sortby", $_REQUEST["sortby"]);
$list->setCustom("sortmode", $_REQUEST["sortmode"]);
$list->setCustom("search", $search);
$list->setCustom("filter", $filter);
foreach ($extraLanguages as $idExtraLang) {
    $list->setCustom("extralang[]", $idExtraLang);
}
$list->setResultsPerPage($_REQUEST["elemperpage"]);
$list->objHeaderItem->updateAttributes([
    'width' => 52
]);
$list->objRow->updateAttributes([
    'valign' => 'top'
]);

$submitButton = new cHTMLButton('submit', 'submit');
$submitButton = $submitButton->setMode('image')
    ->setAlt(i18n("Save"))
    ->setClass('vAlignTop')
    ->setImageSource('images/but_ok.gif')
    ->render();

$cancelButton = new cHTMLButton('reset', 'reset');
$cancelButton = $cancelButton->setMode('image')
    ->setAlt(i18n("Cancel"))
    ->setAttribute('data-action', 'cancel')
    ->setClass('vAlignTop')
    ->setImageSource('images/but_cancel.gif')
    ->render();

$editImage = new cHTMLImage('images/editieren.gif');
$editImage = $editImage->setAlt(i18n("Edit"))->render();

$counter = 0;

foreach ($allTranslations as $hash => $translationArray) {

    if (!$inUse && $perm->have_perm_area_action($area, 'con_translate_edit') && $action == 'con_translate_edit' && ($editstring == 'all' || $editstring == $hash) && ($editlang == 'all' || $editlang == $lang)) {
        $oTranslation = new cHTMLTextarea('modtrans[' . $translationArray['idmod'] . '][' . $hash . '][' . $lang . ']', conHtmlSpecialChars($translationArray['translations'][$lang]));
        $oTranslation->setWidth(30);
        $sTranslationFirstLang = $oTranslation->render();
        if ($editstring == $hash && $editlang == $lang) {
            $sTranslationFirstLang = $sTranslationFirstLang . '<br>' . $submitButton . '&nbsp;&nbsp;' . $cancelButton;
        }
    } else {
        if (!$inUse && $perm->have_perm_area_action($area, 'con_translate_edit') && $editstring != 'all') {
            $linkEdit = new cHTMLLink();
            $linkEdit->setCLink($area, $frame, "con_translate_edit");
            $linkEdit->setContent($editImage);
            $linkEdit->setCustom("editstring", $hash);
            $linkEdit->setCustom("editlang", $lang);
            $linkEdit->setCustom("elemperpage", $_REQUEST["elemperpage"]);
            $linkEdit->setCustom("page", $_REQUEST["page"]);
            $linkEdit->setCustom("sortby", $_REQUEST["sortby"]);
            $linkEdit->setCustom("sortmode", $_REQUEST["sortmode"]);
            $linkEdit->setCustom("search", $search);
            $linkEdit->setCustom("filter", $filter);

            $idExtraLangCount = 0;
            foreach ($extraLanguages as $idExtraLangTemp) {
                $linkEdit->setCustom("extralang[$idExtraLangCount]", $idExtraLangTemp);
                $idExtraLangCount++;
            }

            $sLinkEdit = ' ' . $linkEdit->render();
        } else {
            $sLinkEdit = '';
        }
        $sTranslationFirstLang = trim(conHtmlentities($translationArray['translations'][$lang])) . $sLinkEdit;
    }
    // building parameter array
    $countCurrentModuleInUse = isset($modulesInUse[$translationArray['idmod']]) && is_array($modulesInUse[$translationArray['idmod']]) ? count($modulesInUse[$translationArray['idmod']]) : 0;
    if ($countCurrentModuleInUse == 0) {
        $inUseString = '';
        $currentModuleInUse = i18n('No template');
    } else {
        $inUseString = i18n("Click for more information about usage");
        $currentModuleInUse = '<a href="javascript:void(0)" rel="' . $translationArray['idmod'] . '" class="inused_module" data-action="inused_module" data-id="' . $translationArray['idmod'] . '"><img src="' . $cfg['path']['images'] . 'info.gif" title="' . $inUseString . '" alt="' . $inUseString . '">' . $countCurrentModuleInUse . ' ' . ($countCurrentModuleInUse == 1? i18n('Template') : i18n('Templates')) . ' </a>';
    }
    $fields = [
        $counter,
        $allModules[$translationArray['idmod']],
        $currentModuleInUse,
        $translationArray['string'],
        $sTranslationFirstLang
    ];
    foreach ($extraLanguages as $idExtraLang) {
        if (!$inUse && $perm->have_perm_area_action($area, 'con_translate_edit') && $action == 'con_translate_edit' && ($editstring == 'all' || $editstring == $hash) && ($editlang == 'all' || $editlang == $idExtraLang)) {

            $oExtraTranslation = new cHTMLTextarea('modtrans[' . $translationArray['idmod'] . '][' . $hash . '][' . $idExtraLang . ']', conHtmlSpecialChars($translationArray['translations'][$idExtraLang]));
            $oExtraTranslation->setWidth(30);

            if ($editstring == $hash && $editlang == $idExtraLang) {
                $submitTranslation = $submitButton . '&nbsp;&nbsp;' . $cancelButton;
            } else {
                $submitTranslation = '';
            }
            $fields[] = $oExtraTranslation->render() . $submitTranslation;
        } else {
            if (!$inUse && $perm->have_perm_area_action($area, 'con_translate_edit') && $editstring != 'all') {
                $linkEdit = new cHTMLLink();
                $linkEdit->setCLink($area, $frame, "con_translate_edit");
                $linkEdit->setContent($editImage);
                $linkEdit->setCustom("editstring", $hash);
                $linkEdit->setCustom("editlang", $idExtraLang);
                $linkEdit->setCustom("elemperpage", $_REQUEST["elemperpage"]);
                $linkEdit->setCustom("page", $_REQUEST["page"]);
                $linkEdit->setCustom("sortby", $_REQUEST["sortby"]);
                $linkEdit->setCustom("sortmode", $_REQUEST["sortmode"]);
                $linkEdit->setCustom("search", $search);
                $linkEdit->setCustom("filter", $filter);

                $idExtraLangCount = 0;
                foreach ($extraLanguages as $idExtraLangTemp) {
                    $linkEdit->setCustom("extralang[$idExtraLangCount]", $idExtraLangTemp);
                    $idExtraLangCount++;
                }

                $sLinkEdit = ' ' . $linkEdit->render();
            } else {
                $sLinkEdit = '';
            }
            $fields[] = trim(conHtmlentities($translationArray['translations'][$idExtraLang])) . $sLinkEdit;
        }
    }

    // Edit all languages
    if ($action == 'con_translate_edit' && $editstring == $hash && $editlang == 'all') {
        $fields[] = $submitButton . '&nbsp;&nbsp;' . $cancelButton;
    } else {
        if (!$inUse && $perm->have_perm_area_action($area, 'con_translate_edit')) {
            $linkEditRow = new cHTMLLink();
            $linkEditRow->setCLink($area, $frame, "con_translate_edit");
            $linkEditRow->setContent($editImage);
            $linkEditRow->setCustom("editstring", $hash);
            $linkEditRow->setCustom("editlang", 'all');
            $linkEditRow->setCustom("elemperpage", $_REQUEST["elemperpage"]);
            $linkEditRow->setCustom("page", $_REQUEST["page"]);
            $linkEditRow->setCustom("sortby", $_REQUEST["sortby"]);
            $linkEditRow->setCustom("sortmode", $_REQUEST["sortmode"]);
            $linkEditRow->setCustom("search", $search);
            $linkEditRow->setCustom("filter", $filter);

            $idExtraLangCount = 0;
            foreach ($extraLanguages as $idExtraLangTemp) {
                $linkEditRow->setCustom("extralang[$idExtraLangCount]", $idExtraLangTemp);
                $idExtraLangCount++;
            }

            $sLinkEditRow = ' ' . $linkEditRow->render();
        } else {
            $sLinkEditRow = '&nbsp;';
        }
        $fields[] = $sLinkEditRow;
    }

    call_user_func_array([
        $list,
        "setData"
    ], $fields);
    $counter++;
}

// Count all found translations
// Important to calculate needed pages
$counter = count($allTranslations);

$list->sort(cSecurity::toInteger($_REQUEST["sortby"]), $_REQUEST["sortmode"]);
$list->setListStart($_REQUEST["page"]);
$form = new cHTMLForm('all_mod_translations');
$form->setVar('area', $area);
$form->setVar('action', 'con_translate_edit');
$form->setVar('frame', 4);
$form->setVar("elemperpage", $_REQUEST["elemperpage"]);
$form->setVar("page", $_REQUEST["page"]);
$form->setVar("sortby", $_REQUEST["sortby"]);
$form->setVar("sortmode", $_REQUEST["sortmode"]);
$form->setVar("search", $search);
$form->setVar("filter", $filter);

$idExtraLangCount = 0;
foreach ($extraLanguages as $idExtraLangTemp) {
    $form->setVar("extralang[$idExtraLangCount]", $idExtraLangTemp);
    $idExtraLangCount++;
}

$form->setVar('contenido', cRegistry::getBackendSessionId());
$form->setContent($list->render());

// Generate current content for Object Pager
$pagerLink = new cHTMLLink();
$pagerl = "pagerlink";
$pagerLink->setTargetFrame('right_bottom');
$pagerLink->setLink("main.php");
$pagerLink->setCustom("elemperpage", $elemperpage);
$pagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$pagerLink->setCustom("sortmode", $_REQUEST["sortmode"]);
$pagerLink->setCustom("search", $search);
$pagerLink->setCustom("frame", $frame);
$pagerLink->setCustom("area", $area);
$pagerLink->setCustom("search", $search);
$pagerLink->setCustom("filter", $filter);

$idExtraLangCount = 0;
foreach ($extraLanguages as $idExtraLangTemp) {
    $pagerLink->setCustom("extralang[$idExtraLangCount]", $idExtraLangTemp);
    $idExtraLangCount++;
}

$pagerLink->setCustom("contenido", $sess->id);
$pager = new cGuiObjectPager("02420d6b-a77e-4a97-9395-7f6be480f471", $counter, $_REQUEST["elemperpage"], $_REQUEST["page"], $pagerLink, "page", $pagerl);

$page->set("s", "NEWLANG", $formExtraLangsString);
$page->set("s", "SEARCH", $formSearch->render());
$page->set("s", "ELEMPERPAGE", $formElementsPerPage->render());
$page->set("s", "FORM", $form->render());
$page->set("s", "FORM_NAME", $form->getAttribute('name'));
$page->set("s", "PAGER", $pager->render(true));
$page->set("s", "MODULEINUSETEXT", i18n("The module &quot;%s&quot; is used for following templates"));

if (!$noResults) {
    $page->set("s", "INFO", $message . '<p class="notify_general notify_warning">' . i18n("WARNING: Translations have effects on every article that uses the module!") . '</p>');
} else {
    $page->set("s", "INFO", "");
}

$page->setMarkScript(2);
$page->setEncoding($langobj->get('encoding'));
$page->render();
