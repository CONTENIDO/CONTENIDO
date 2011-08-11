<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Contenido Content Functions
 *
 * Requirements:
 * @con_php_req 5.0
 * @con_notice Please add only stuff which is relevant for the frontend
 *             AND the backend. This file should NOT contain any backend editing
 *             functions to improve frontend performance:
 *
 *
 * @package    Contenido Backend includes
 * @version    1.3.8
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created 2003-12-15
 *   modified 2008-06-25, Timo Trautmann, user meta tags and system meta tags were merged, not replaced
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *   modified 2008-08-29, Murat Purc, add new chain execution
 *   modified 2009-03-27, Andreas Lindner, Add title tag generation via chain
 *   modified 2009-10-29, Murat Purc, removed deprecated functions (PHP 5.3 ready)
 *   modified 2009-12-18, Murat Purc, fixed meta tag generation, see [#CON-272]
 *   modified 2009-10-27, Murat Purc, fixed/modified CEC_Hook, see [#CON-256]
 *   modified 2010-10-11, Dominik Ziegler, display only major and minor version of version number
 *   modified 2010-12-09, Dominik Ziegler, fixed multiple replacements of title tags [#CON-373]
 *   modified 2011-01-11, Rusmir Jusufovic
 *       - load input and output of moduls from files
 *   modified 2011-06-24, Rusmir Jusufovic , load layout code from file
 *   modified 2011-07-20, Murat Purc, partly refactored function conGenerateCode(), cleanup, documenting and formatting.
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Generates the code for one article
 *
 * @param int $idcat Id of category
 * @param int $idart Id of article
 * @param int $lang Id of language
 * @param int $client Id of client
 * @param int $layout Layout-ID of alternate Layout (if false, use associated layout)
 * @return string The generated code or "0601" if neither article nor category configuration 
 *                was found
 */
function conGenerateCode($idcat, $idart, $lang, $client, $layout = false)
{
    global $frontend_debug, $_cecRegistry, $cfgClient;
    global $db, $db2, $cfg, $code, $client;

    $cssData = '';
    $jsData = '';
    $tplName = '';

    $debug = 0;

    if ($debug)
        echo "conGenerateCode($idcat, $idart, $lang, $client, $layout);<br>";

    #set contenido vars for module concepts
    Contenido_Vars::setVar('db', $db);
    Contenido_Vars::setVar('lang', $lang);
    Contenido_Vars::setVar('cfg', $cfg);
    Contenido_Vars::setEncoding($db,$cfg,$lang);
    Contenido_Vars::setVar('cfgClient', $cfgClient);
    Contenido_Vars::setVar('client', $client);
    Contenido_Vars::setVar('fileEncoding', getEffectiveSetting('encoding', 'file_encoding','UTF-8'));

    if (!is_object($db2))
        $db2 = new DB_Contenido();

    // extract IDCATART
    $sql = "SELECT idcatart FROM " . $cfg["tab"]["cat_art"] . "
            WHERE idcat=" . (int) $idcat . " AND idart = " . (int) $idart;
    $db->query($sql);
    $db->next_record();
    $idcatart = $db->f("idcatart");

    // If neither the article or the category is configured, no code will be
    // created and an error occurs.
    $sql = "SELECT a.idtplcfg AS idtplcfg
            FROM " . $cfg["tab"]["art_lang"] . " AS a, " . $cfg["tab"]["art"] . " AS b
            WHERE a.idart = " . (int) $idart . " AND a.idlang = " . (int) $lang . " AND
                  b.idart = a.idart AND b.idclient = " . (int) $client;

    $db->query($sql);
    $db->next_record();

    if ($db->f("idtplcfg") != 0) {
        // Article is configured
        $idtplcfg = $db->f("idtplcfg");

        if ($debug)
            echo "configuration for article found: $idtplcfg<br><br>";

        $a_c = array();

        $sql2 = "SELECT * FROM " . $cfg["tab"]["container_conf"] . "
                 WHERE idtplcfg=" . (int) $idtplcfg . " ORDER BY number ASC";
        $db2->query($sql2);
        while ($db2->next_record()) {
            $a_c[$db2->f("number")] = $db2->f("container");
        }
    } else {
        // Check whether category is configured
        $sql = "SELECT a.idtplcfg AS idtplcfg
                FROM " . $cfg["tab"]["cat_lang"] . " AS a, " . $cfg["tab"]["cat"] . " AS b
                WHERE a.idcat = " . (int) $idcat . " AND a.idlang = " . (int) $lang . " AND
                    b.idcat = a.idcat AND b.idclient = " . (int) $client;

        $db->query($sql);
        $db->next_record();

        if ($db->f("idtplcfg") != 0) {
            // Category is configured, extract varstring
            $idtplcfg = $db->f("idtplcfg");

            if ($debug)
                echo "configuration for category found: $idtplcfg<br><br>";

            $a_c = array();

            $sql2 = "SELECT * FROM " . $cfg["tab"]["container_conf"] . "
                     WHERE idtplcfg=" . (int) $idtplcfg . " ORDER BY number ASC";
            $db2->query($sql2);
            while ($db2->next_record()) {
                $a_c[$db2->f("number")] = $db2->f("container");
            }
        } else {
            // Article nor Category is configured. Creation of Code is not possible.
            // Write Errormsg to DB.

            if ($debug)
                echo "Neither CAT or ART are configured!<br><br>";

            $code = '<html><body>No code was created for this art in this category.</body><html>';

            $oCodeColl = new cApiCodeCollection();
            $oCode = $oCodeColl->selectByCatArtAndLang($idcatart, $lang);
            if (!is_object($oCode)) {
                $oCode = $oCodeColl->create($idcatart, $lang, $client, $code);
            } else {
                $oCode->set('code', $code, false);
                $oCode->store();
            }

            return "0601";
        }
    }

    // Get IDLAY and IDMOD array
    $sql = "SELECT a.idlay AS idlay, a.idtpl AS idtpl, a.name AS name
            FROM ".$cfg["tab"]["tpl"]." AS a, ".$cfg["tab"]["tpl_conf"]." AS b
            WHERE b.idtplcfg = " . (int) $idtplcfg . " AND b.idtpl = a.idtpl";
    $db->query($sql);
    $db->next_record();

    $idlay = $db->f("idlay");
    if ($layout != false) {
        $idlay = $layout;
    }
    $idtpl = $db->f("idtpl");
    $tplName = $db->f("name");
    if ($debug)
        echo "Usging Layout: $idlay and Template: $idtpl for generation of code.<br><br>";

    // List of used modules
    $a_d = array();
    $sql = "SELECT number, idmod FROM " . $cfg["tab"]["container"] . "
            WHERE idtpl=" . (int) $idtpl . " ORDER BY number ASC";
    $db->query($sql);
    while ($db->next_record()) {
        $a_d[(int) $db->f("number")] = (int) $db->f("idmod");
    }

    //Load layout code from file
    $layoutInFile = new LayoutInFile($idlay,"", $cfg, $lang);
    $code = $layoutInFile->getLayoutCode();
    $code = capiStrNormalizeLineEndings($code, "\n");

    // Create code for all containers
    if ($idlay) {
        tplPreparseLayout($idlay);
        $tmp_returnstring = tplBrowseLayoutForContainers($idlay);
        $a_container = explode('&', $tmp_returnstring);

        foreach ($a_container as $key => $value) {
            $sFeDebug = '';

            $sql = "SELECT * FROM " . $cfg["tab"]["mod"] . " WHERE idmod=" . $a_d[$value];
            $db->query($sql);
            $db->next_record();

            $thisModule = '<?php $cCurrentModule = ' . $a_d[$value] . '; ?>';
            $thisContainer = '<?php $cCurrentContainer = ' . $value . '; ?>';

            $contenidoModuleHandler = new Contenido_Module_Handler($db->f("idmod"));
            $output = $thisModule.$thisContainer;
            $input = $thisModule.$thisContainer;

            // get the contents of input and output from files and not from db-table
            if ($contenidoModuleHandler->existModul() == true) {
                $output = $thisModule.$thisContainer.$contenidoModuleHandler->readOutput();
                //load css and js content of the js/css files
                $cssData .= $contenidoModuleHandler->getFilesContent("css","css");
                $jsData .= $contenidoModuleHandler->getFilesContent("js","js");

                $input = $thisModule.$thisContainer.$contenidoModuleHandler->readInput();
            } else {

            }
            $output = $output . "\n";

            $template = $db->f("template");

            $a_c[$value] = preg_replace('/(&\$)/', '', $a_c[$value]);

            $tmp1 = preg_split('/&/', $a_c[$value]);

            $varstring = array();

            foreach ($tmp1 as $key1 => $value1) {
                $tmp2 = explode('=', $value1);
                foreach ($tmp2 as $key2 => $value2) {
                    $varstring["$tmp2[0]"] = $tmp2[1];
                }
            }

            $CiCMS_Var = '$C' . $value . 'CMS_VALUE';
            $CiCMS_VALUE = '';

            foreach ($varstring as $key3 => $value3) {
                $tmp = urldecode($value3);
                $tmp = str_replace("\'", "'", $tmp);
                $CiCMS_VALUE .= $CiCMS_Var . '[' . $key3 . ']="' . $tmp . '"; ';
                $output = str_replace("\$CMS_VALUE[$key3]", $tmp, $output);
                $output = str_replace("CMS_VALUE[$key3]", $tmp, $output);
            }

            $output = str_replace("CMS_VALUE", $CiCMS_Var, $output);
            $output = str_replace("\$" . $CiCMS_Var, $CiCMS_Var, $output);

            $output = preg_replace("/(CMS_VALUE\[)([0-9]*)(\])/i", "", $output);

            if ($frontend_debug["container_display"] == true) {
                $sFeDebug .= "Container: CMS_CONTAINER[$value]\\n";
            }
            if ($frontend_debug["module_display"] == true) {
                $sFeDebug .= "Modul: " . $db->f("name") . "\\n";
            }
            if ($frontend_debug["module_timing_summary"] == true || $frontend_debug["module_timing"] == true) {
                $sFeDebug .= 'Eval-Time: $modtime' . $value . "\\n";
                $output = '<?php $modstart' . $value . '=getmicrotime();?>' . $output . '<?php $modend' . $value . '=getmicrotime()+0.001; $modtime' . $value . '=$modend' . $value . ' - $modstart' . $value . ';?>';
            }

            if ($sFeDebug != '') {
                $output = '<?php echo \'<img onclick="javascript:showmod'.$value.'();" src="'.$cfg['path']['contenido_fullhtml'].'images/but_preview.gif">\'; ?>' . "<br>" . $output;
                $output = $output . '<?php echo \'<script language="javascript">function showmod'.$value.'(){window.alert(\\\'\'. "'.addslashes($sFeDebug).'".\'\\\');} </script>\';?>';
            }

            if ($frontend_debug["module_timing_summary"] == true) {
                $output .= '<?php $cModuleTimes["' . $value . '"] = $modtime' . $value . ';?>';
                $output .= '<?php $cModuleNames["' . $value . '"] = "' . addslashes($db->f("name")) . '";?>';
            }

            // Replace new containers
            $code = preg_replace("/<container( +)id=\\\"$value\\\"(.*)>(.*)<\/container>/Uis", "CMS_CONTAINER[$value]", $code);
            $code = preg_replace("/<container( +)id=\\\"$value\\\"(.*)\/>/i", "CMS_CONTAINER[$value]", $code);
            $code = str_ireplace("CMS_CONTAINER[$value]", "<?php $CiCMS_VALUE ?>\n".$output, $code);
        }
    }

    // Find out what kind of CMS_... Vars are in use
    $sql = "SELECT * FROM " . $cfg["tab"]["content"] . " AS A, " . $cfg["tab"]["art_lang"] . " AS B,
                " . $cfg["tab"]["type"]." AS C
            WHERE A.idtype = C.idtype AND A.idartlang = B.idartlang AND
                B.idart = " . (int) $idart . " AND B.idlang = " . (int) $lang;
    $db->query($sql);
    while ($db->next_record()) {
        $a_content[$db->f("type")][$db->f("typeid")] = $db->f("value");
    }

    $sql = "SELECT idartlang, pagetitle FROM " . $cfg["tab"]["art_lang"] 
         . " WHERE idart=" . (int) $idart . " AND idlang=" . (int) $lang;
    $db->query($sql);
    $db->next_record();

    $idartlang = $db->f("idartlang");
    $pagetitle = stripslashes($db->f("pagetitle"));

    // replace all CMS_TAGS[]
    $sql = "SELECT type, code FROM ".$cfg["tab"]["type"];

    $db->query($sql);

    $match = array();
    while ($db->next_record()) {
        $tmp = preg_match_all("/(".$db->f("type")."\[+\d+\])/i", $code, $match);
        $a_[strtolower($db->f("type"))] = $match[0];

        $success = array_walk($a_[strtolower($db->f("type"))], 'extractNumber');

        $search = array();
        $replacements = array();

        foreach ($a_[strtolower($db->f("type"))] as $val) {
            eval ($db->f("code"));

            $search[$val] = $db->f("type")."[$val]";
            $replacements[$val] = $tmp;
            $keycode[$db->f("type")][$val] = $tmp;
        }

        $code = str_ireplace($search, $replacements, $code);
    }

    if (is_array($keycode)) {
        saveKeywordsForArt($keycode, $idart, "auto", $lang);
    }

    // add/replace title tag
    $code = conProcessCodeTitleTag($code, $pagetitle);

    // add/replace meta tags
    $code = conProcessCodeMetaTags($code, $idartlang);

    //save the collected css/js data and save it undter the template name ([templatename].css , [templatename].js in cache dir
    $cssDatei = '';
    if(($myFileCss = Contenido_Module_Handler::saveContentToFile($cfgClient[$client], $tplName,"css", $cssData))== false) {

     $cssDatei = "error error culd not generate css file";
    } else {
       //generate link for html-head only if data exist
	  if($cssData != '')	
	  	$cssDatei = '<link rel="stylesheet" type="text/css" href="'.$myFileCss.'"/>';
    }
    $jsDatei = '';
    if( ($myFileJs =Contenido_Module_Handler::saveContentToFile($cfgClient[$client], $tplName,"js", $jsData))== false) {

      $jsDatei = "error error error culd not generate js file";
    } else {

     	//generate js link for html-head only if data exist
		if($jsData != '')
	  		$jsDatei = '<script src="'.$myFileJs.'" type="text/javascript"></script>';
    }

    // Add meta tags
    $code = str_ireplace_once("</head>", $cssDatei.$jsDatei."</head>", $code);

    // write code into the database
    if ($layout == false) {
        $oCodeColl = new cApiCodeCollection();
        $oCode = $oCodeColl->selectByCatArtAndLang($idcatart, $lang);
        if (!is_object($oCode)) {
            $oCode = $oCodeColl->create($idcatart, $lang, $client, $code);
        } else {
            $oCode->set('code', $code, false);
            $oCode->store();
        }

        $db->update($cfg['tab']['cat_art'], array('createcode' => 0), array('idcatart' => (int) $idcatart));
    }

    // execute CEC hook
    $code = CEC_Hook::executeAndReturn('Contenido.Content.conGenerateCode', $code);

    return $code;
}


/**
 * Returns the idartlang for a given article and language
 *
 * @param  int  $idart ID of the article
 * @param  int  $idlang ID of the language
 * @return mixed idartlang of the article or false if nothing was found
 *
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG 2003
 */
function getArtLang($idart, $idlang)
{
    global $cfg;

    static $oDB;
    if (!isset($oDB)) {
        $oDB = new DB_Contenido();
    }

    $sql = 'SELECT idartlang FROM ' . $cfg['tab']['art_lang'] . '
            WHERE idart=' . (int) $idart . ' AND idlang=' . (int) $idlang;

    $oDB->query($sql);
    if ($oDB->next_record()) {
        return $oDB->f('idartlang');
    } else {
        return false;
    }
}


/**
 * Returns all available meta tag types
 *
 * @return  array  Assoziative meta tags list
 */
function conGetAvailableMetaTagTypes()
{
    global $cfg;

    static $oDB;
    if (!isset($oDB)) {
        $oDB = new DB_Contenido();
    }

    $sql = 'SELECT idmetatype, metatype, fieldtype, maxlength, fieldname
            FROM ' . $cfg['tab']['meta_type'];

    $oDB->query($sql);

    $aMetaTypes = array();

    while ($oDB->next_record()) {
        $newentry['name'] = $oDB->f('metatype');
        $newentry['fieldtype'] = $oDB->f('fieldtype');
        $newentry['maxlength'] = $oDB->f('maxlength');
        $newentry['fieldname'] = $oDB->f('fieldname');
        $aMetaTypes[$oDB->f('idmetatype')] = $newentry;
    }

    return $aMetaTypes;
}


/**
 * Get the meta tag value for a specific article
 *
 * @param int $idartlang ID of the article
 * @param int $idmetatype Metatype-ID
 * @return  string
 */
function conGetMetaValue($idartlang, $idmetatype)
{
    static $oMetaTagColl;
    if (!isset($oMetaTagColl)) {
        $oMetaTagColl = new cApiMetaTagCollection();
    }

    if ((int) $idartlang <= 0) {
        return '';
    }

    $oMetaTag = $oMetaTagColl->selectByArtLangAndMetaType($idartlang, $idmetatype);
    if (is_object($oMetaTag)) {
        return stripslashes($oMetaTag->get('metavalue'));
    } else {
        return '';
    }
}


/**
 * Set the meta tag value for a specific article.
 *
 * @param  int  $idartlang ID of the article
 * @param  int  $idmetatype Metatype-ID
 * @param  string  $value Value of the meta tag
 */
function conSetMetaValue($idartlang, $idmetatype, $value)
{
    static $oMetaTagColl;
    if (!isset($oMetaTagColl)) {
        $oMetaTagColl = new cApiMetaTagCollection();
    }
    
    $oMetaTag = $oMetaTagColl->selectByArtLangAndMetaType($idartlang, $idmetatype);
    if (is_object($oMetaTag)) {
        $oMetaTag->updateMetaValue($value);
    } else {
        $oMetaTagColl->create($idartlang, $idmetatype, $value);
    }
}


/**
 * (re)generate keywords for all articles of a given client (with specified language)
 * @param int $client Client
 * @param int $lang Language of a client
 * @return void
 *
 * @author Willi Man
 * Created   :   12.05.2004
 * Modified  :   13.05.2004
 * @copyright four for business AG 2003
 */
function conGenerateKeywords($client, $lang)
{
    global $cfg;

    static $oDB;
    if (!isset($oDB)) {
        $oDB = new DB_Contenido();
    }

    $options = array('img', 'link', 'linktarget', 'swf'); // cms types to be excluded from indexing

    $sql = 'SELECT a.idart, b.idartlang FROM ' . $cfg['tab']['art'] . ' AS a, ' . $cfg['tab']['art_lang'] . ' AS b
            WHERE a.idart=b.idart AND a.idclient=' . (int) $client . ' AND b.idlang=' . (int) $lang;

    $oDB->query($sql);

    $aArticles = array();
    while ($oDB->next_record()) {
        $aArticles[$oDB->f('idart')] = $oDB->f('idartlang');
    }

    foreach ($aArticles as $artid => $artlangid) {
        $aContent = conGetContentFromArticle($artlangid);
        if (count($aContent) > 0) {
            $oIndex = new Index($oDB);
            $oIndex->lang = $lang;
            $oIndex->start($artid, $aContent, 'auto', $options);
        }
    }
}


/**
 * Get content from article by article language.
 * @param int $iIdArtLang ArticleLanguageId of an article (idartlang)
 * @return array Array with content of an article indexed by content-types as follows:
 *               - $arr[type][typeid] = value;
 *
 * @author Willi Man
 * Created   :   12.05.2004
 * Modified  :   13.05.2004
 * @copyright four for business AG 2003
 */
function conGetContentFromArticle($iIdArtLang)
{
    global $cfg;

    static $oDB;
    if (!isset($oDB)) {
        $oDB = new DB_Contenido();
    }

    $aContent = array();

    $sql = 'SELECT * FROM ' . $cfg['tab']['content'] . ' AS A, ' . $cfg['tab']['art_lang'] . ' AS B, ' . $cfg['tab']['type'] . ' AS C
            WHERE A.idtype=C.idtype AND A.idartlang=B.idartlang AND A.idartlang='. (int) $iIdArtLang;
    $oDB->query($sql);
    while ($oDB->next_record()) {
        $aContent[$oDB->f('type')][$oDB->f('typeid')] = urldecode($oDB->f('value'));
    }

    return $aContent;
}


/**
 * Processes and adds or replaces title tag for an article
 *
 * @param   string  $sCode  Layout code
 * @param   string  $sPageTitle  Pagetitle from article language entry
 * @return  string
 */
function conProcessCodeTitleTag($sCode, $sPageTitle)
{
    if ($sPageTitle == '') {
        CEC_Hook::setDefaultReturnValue($sPageTitle);
        $sPageTitle = CEC_Hook::executeAndReturn('Contenido.Content.CreateTitletag');
    }

    // add or replace title
    if ($sPageTitle != '') {
        $sCode = preg_replace('/<title>.*?<\/title>/is', '{TITLE}', $sCode, 1);
        if (strstr($sCode, '{TITLE}')) {
            $sCode = str_ireplace('{TITLE}', '<title>' . $sPageTitle . '</title>', $sCode);
        } else {
            $sCode = str_ireplace_once('</head>', '<title>' . $sPageTitle . "</title>\n</head>", $sCode);
        }
    } else {
        $sCode = str_replace('<title></title>', '', $sCode);
    }
    return $sCode;
}


/**
 * Processes and adds or replaces all meta tags for an article
 *
 * @param   string  $sCode  Layout code
 * @param   int     $iIdArtLang
 * @return  string
 */
function conProcessCodeMetaTags($sCode, $iIdArtLang)
{
    global $cfg, $encoding, $lang, $_cecRegistry;

    // Collect all available meta tag entries with non empty values
    $aMetaTags = array();
    $aAvailableTags = conGetAvailableMetaTagTypes();
    foreach ($aAvailableTags as $key => $value) {
        $sMetaValue = conGetMetaValue($iIdArtLang, $key);
        if (strlen($sMetaValue) > 0) {
            //$aMetaTags[$value['name']] = array(array('attribute' => $value['fieldname'], 'value' => $sMetaValue), ...);
            $aMetaTags[] = array($value['fieldname'] => $value['name'], 'content' => $sMetaValue);
        }
    }

    // Add CONTENIDO meta tag
    $aVersion = explode('.', $cfg['version']);
    $sContenidoVersion = $aVersion[0] . '.' . $aVersion[1];
    $aMetaTags[] = array('name' => 'generator', 'content' => 'CMS CONTENIDO ' . $sContenidoVersion);

    // Add content type meta tag
    if (getEffectiveSetting('generator', 'xhtml', 'false') == 'true') {
        $aMetaTags[] = array('http-equiv' => 'Content-Type', 'content' => 'application/xhtml+xml; charset='.$encoding[$lang]);
    } else {
        $aMetaTags[] = array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset='.$encoding[$lang]);
    }

    // Process chain to update meta tags
    $_cecIterator = $_cecRegistry->getIterator('Contenido.Content.CreateMetatags');

    if ($_cecIterator->count() > 0) {
        $aTmpMetaTags = $aMetaTags;

        while ($chainEntry = $_cecIterator->next()) {
            $aTmpMetaTags = $chainEntry->execute($aTmpMetaTags);
        }

        // added 2008-06-25 Timo Trautmann -- system metatags were merged to user meta 
        // tags and user meta tags were not longer replaced by system meta tags
        if (is_array($aTmpMetaTags)) {
            //check for all system meta tags if there is already a user meta tag
            foreach ($aTmpMetaTags as $aAutValue) {
                $bExists = false;

                //get name of meta tag for search
                $sSearch = '';
                if (array_key_exists('name', $aAutValue)) {
                    $sSearch = $aAutValue['name'];
                } else if (array_key_exists('http-equiv', $aAutValue)) {
                    $sSearch = $aAutValue['http-equiv'];
                }

                //check if meta tag is already in list of user meta tags
                if (strlen($sSearch) > 0) {
                    foreach ($aMetaTags as $aValue) {
                        if (array_key_exists('name', $aValue)) {
                            if ($sSearch == $aValue['name']) {
                                $bExists = true;
                                break;
                            }
                        } else if (array_key_exists('http-equiv', $aAutValue)) {
                            if ($sSearch == $aValue['http-equiv']) {
                                $bExists = true;
                                break;
                            }
                        }
                    }
                }

                //add system meta tag if there is no user meta tag
                if ($bExists == false && strlen($aAutValue['content']) > 0) {
                    array_push($aMetaTags, $aAutValue);
                }
            }
        }
    }

    $sMetatags = '';

    foreach ($aMetaTags as $value) {
        // decode entities and htmlspecialchars, content will be converted later using htmlspecialchars()
        // by render() function
        $value['content'] = html_entity_decode($value['content'], ENT_QUOTES, strtoupper($encoding[$lang]));
        $value['content'] = htmlspecialchars_decode($value['content'], ENT_QUOTES);

        // build up metatag string
        $oMetaTagGen = new cHTML();
        $oMetaTagGen->_tag = 'meta';
        $oMetaTagGen->updateAttributes($value);

        // HTML does not allow ID for meta tags
        $oMetaTagGen->removeAttribute('id');

        // Check if metatag already exists
        $sPattern = '/(<meta(?:\s+)name(?:\s*)=(?:\s*)(?:\\"|\\\')(?:\s*)' . $value['name'] . '(?:\s*)(?:\\"|\\\')(?:[^>]+)>\n?)/i';
        if (preg_match($sPattern, $sCode, $aMatch)) {
            $sCode = str_replace($aMatch[1], $oMetaTagGen->render() . "\n", $sCode);
        } else {
            $sMetatags .= $oMetaTagGen->render() . "\n";
        }
    }

    // Add meta tags
    $sCode = str_ireplace_once('</head>', $sMetatags . '</head>', $sCode);

    return $sCode;
}

?>