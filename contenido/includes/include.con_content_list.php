<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Content Type list for editing the content in an article
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.3
 * @author     Fulai zhang
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * @todo replace code generation by Contenido_CodeGenerator (see contenido/classes/CodeGenerator)
 *
 * {@internal
 *   created  2012
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

if (!isset($idcat)) {
    cRegistry::shutdown();
    return;
}

$edit = 'true';
$scripts = '';

//save / set value
if ($action == 'savecontype' || $action == 10) {
    if ($data != '') {
        $data = explode('||', substr($data, 0, -2));
//print_r($data);
        foreach ($data as $value) {
            $value = explode('|', $value);
            if ($value[3] == '%$%EMPTY%$%') {
                $value[3] = '';
            } else {
                $value[3] = str_replace('%$%SEPERATOR%$%', '|', $value[3]);
            }
            conSaveContentEntry($value[0], 'CMS_' . $value[1], $value[2], $value[3]);
        }

        conMakeArticleIndex($idartlang, $idart);

        // restore orginal values
        $data  = $_REQUEST['data'];
        $value = $_REQUEST['value'];
    }

    conGenerateCodeForArtInAllCategories($idart);
} else if($action == 'deletecontype'){
	 if(isset($_REQUEST['idcontent']) && is_numeric($_REQUEST['idcontent'])) {
		$sql = "DELETE FROM ".$cfg["tab"]["content"]." WHERE idcontent='".Contenido_Security::toInteger($_REQUEST['idcontent'])."' LIMIT 1";
		$db->query($sql);
	 }
	
}

//get value
$sql = "SELECT b.idtype as idtype, b.type as name, a.typeid as id, a.value as value FROM ".$cfg["tab"]["content"]." as a, ".$cfg["tab"]["type"]." as b WHERE a.idartlang=".$_REQUEST["idartlang"]." AND a.idtype=b.idtype ORDER BY a.idartlang, a.idtype, a.typeid";
$db->query($sql);
$result = array();
$aList = array();
while ( $db->next_record() ) {
		$result[$db->f("name")][$db->f("id")] = $db->f("value");
		if(!in_array($db->f("name"),$aList)){
			$aList[$db->f("idtype")] = $db->f("name");
		}
}
//show ContentTypeList
//if ($action == 'con_content') {
	//@fulai.zhang: Mark submenuitem 'Editor' in the CONTENIDO Backend (Area: Contenido --> Articles --> Editor)
    $markSubItem = markSubMenuItem(5, true);

    //Include tiny class
    include($cfg['path']['contenido'] . 'external/wysiwyg/tinymce3/editorclass.php');
    $oEditor = new cTinyMCEEditor('', '');
    $oEditor->setToolbar('inline_edit');

    //Get configuration for popup und inline tiny
    $sConfigInlineEdit = $oEditor->getConfigInlineEdit();
    $sConfigFullscreen = $oEditor->getConfigFullscreen();


    //Replace vars in Script
    $oScriptTpl = new Template();

    $oScriptTpl->set('s', 'CONTENIDO_FULLHTML', $cfg['path']['contenido_fullhtml']);

    //Set urls to file browsers
    $oScriptTpl->set('s', 'IMAGE', $cfg['path']['contenido_fullhtml'] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
    $oScriptTpl->set('s', 'FILE', $cfg['path']['contenido_fullhtml'] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=filebrowser');
    $oScriptTpl->set('s', 'FLASH', $cfg['path']['contenido_fullhtml'] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
    $oScriptTpl->set('s', 'MEDIA', $cfg['path']['contenido_fullhtml'] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
    $oScriptTpl->set('s', 'FRONTEND', $cfgClient[$client]['path']['htmlpath']);

    //Add tiny options and fill function leave_check()
    $oScriptTpl->set('s', 'TINY_OPTIONS', $sConfigInlineEdit);
    $oScriptTpl->set('s', 'TINY_FULLSCREEN', $sConfigFullscreen);
    $oScriptTpl->set('s', 'IDARTLANG', $idartlang);
    $oScriptTpl->set('s', 'CON_PATH', $cfg['path']['contenido_fullhtml']);
    $oScriptTpl->set('s', 'CLOSE', i18n('Close editor'));
    $oScriptTpl->set('s', 'SAVE', i18n('Close editor and save changes'));
    $oScriptTpl->set('s', 'QUESTION', i18n('Do you want to save changes?'));

    if (getEffectiveSetting('system', 'insight_editing_activated', 'true') == 'false') {
        $oScriptTpl->set('s', 'USE_TINY', '');
    } else {
        $oScriptTpl->set('s', 'USE_TINY', 'swapTiny(this);');
    }

    $scripts = $oScriptTpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['con_editcontent'], 1);

    $contentform  = '
	<form name="editcontent" method="post" action="' . $sess->url($cfg['path']['contenido_fullhtml'] . "main.php?area=con_content_list&action=savecontype&idart=$idart&idcat=$idcat&lang=$lang&idartlang=$idartlang&frame=4&client=$client") . '">
	    <input type="hidden" name="changeview" value="edit">
	    <input type="hidden" name="data" value="">
	</form>
	';

    //print_r($result);
	$layoutcode = '<html>
		<head>
		    <title></title>
		    <meta http-equiv="expires" content="0">
		    <meta http-equiv="cache-control" content="no-cache">
		    <meta http-equiv="pragma" content="no-cache">
		    <link rel="stylesheet" type="text/css" href="../contenido/styles/contenido.css">
		    <script type="text/javascript" src="../contenido/scripts/general.js"></script>
		</head>
		<body style="margin: 10px">';
	foreach($result as $key => $cmstype){
		foreach($cmstype as $index => $value){		
			$layoutcode .= '<div class="contypeList">
			<div class="headline">'.$key.' '.$index.':</div>'.$key.'['.$index.']</div><hr>';
		}
	}
	$layoutcode .= '</body></html>';

    // generate code
    $code = _processCmsTags($aList, $result, true, $layoutcode);

	if($code == "0601") {
		markSubMenuItem("1");
		$code = "<script type='text/javascript'>location.href = '".$cfg['path']['contenido_fullhtml']."main.php?frame=4&area=con_content_list&action=con_content&idart=".$idart."&idcat=".$idcat."&contenido=".$contenido."'; console.log(location.href);</script>";
	} else {
    	// inject some additional markup
    	$code = str_ireplace_once("</head>", "$markSubItem $scripts\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$encoding[$lang]\"></head>", $code);
    	$code = str_ireplace_once_reverse("</body>", "$contentform</body>", $code);
    	$code = str_ireplace_once("<head>", "<head>\n" . '<base href="' . $cfgClient[$client]["path"]["htmlpath"] . '">', $code);
	}

    if ($cfg["debug"]["codeoutput"]) {
    	cDebug(htmlspecialchars($code));
    }

    chdir($cfgClient[$client]["path"]["frontend"]);
    eval("?>\n".$code."\n<?php\n");
//}

cRegistry::shutdown();

	function _processCmsTags($aList, $contentList, $saveKeywords = true, $layoutCode) {
        // #####################################################################
        // NOTE: Variables below are required in included/evaluated content type codes!
        global $db, $db2, $sess, $cfg, $code, $cfgClient, $encoding;

        // NOTE: Variables below are additionally required in included/evaluated
        //       content type codes within backend edit mode!
        global $edit, $editLink, $belang;
        
		$idcat = $_REQUEST['idcat'];
        $idart = $_REQUEST['idart'];
        $lang = $_REQUEST['lang'];
        $client = $_REQUEST['client'];
        $idartlang = $_REQUEST['idartlang'];
		$contenido = $_REQUEST['contenido'];

        if (!is_object($db2)) {
            $db2 = new DB_Contenido();
        }
        // End: Variables required in content type codes
        // #####################################################################

        $match = array();
        $keycode = array();

        // $a_content is used by included/evaluated content type codes below
        $a_content = $contentList;

        // Select all cms_type entries
        $sql = 'SELECT idtype, type, code FROM ' . $cfg['tab']['type'];
        $db->query($sql);
        $_typeList = array();
        while ($db->next_record()) {
            $_typeList[] = $db->toObject();
        }
        
		$html = '';
        // Replace all CMS_TAGS[]
        foreach($_typeList as $_typeItem) {
            $key = strtolower($_typeItem->type);
            $type = $_typeItem->type;
           
        	if(in_array($type,$aList)){
            // Try to find all CMS_{type}[{number}] values, e. g. CMS_HTML[1]
            $tmp = preg_match_all('/(' . $type . '\[+(\d)+\])/i', $layoutCode, $match);

            $a_[$key] = $match[0];

            $success = array_walk($a_[$key], 'extractNumber');

            $search = array();
            $replacements = array();

            $typeCodeFile = $cfg['path']['contenido'] . 'includes/type/code/include.' . $type . '.code.php';

            foreach ($a_[$key] as $val) {
                if (file_exists($typeCodeFile)) {
                    // include CMS type code
                    include($typeCodeFile);
                } elseif (!empty($_typeItem->code)) {
                    // old version, evaluate CMS type code
                    cDeprecated("Move code for $type from table into file system (contenido/includes/cms/code/)");
                    eval($_typeItem->code);
                }
                
				$sql = "SELECT a.idcontent 
						FROM ".$cfg["tab"]["content"]." as a, ".$cfg["tab"]["type"]." as b 
						WHERE a.idartlang=".$_REQUEST["idartlang"].
						" AND a.idtype=b.idtype AND a.typeid = ".$val." AND b.type = '".$type."'
						ORDER BY a.idartlang, a.idtype, a.typeid";
				$db->query($sql);
		        while ($db->next_record()) {
		            $idcontent = $db->f("idcontent");
		        }
				
                $search[$val] = sprintf('%s[%s]', $type, $val);				
					
				$path = $cfg['path']['contenido_fullhtml'].'main.php?area=con_content_list&action=deletecontype&changeview=edit&idart='.$idart.'&idartlang='.$idartlang.
				'&idcat='.$idcat.'&client='.$client.'&lang='.$lang.'&frame=4&contenido='.$contenido.'&idcontent='.$idcontent;
                $replacements[$val] = $tmp.
	                '<a style="text-decoration:none;" href="javascript:setcontent(\'1\',\''.$path.'\');">
	                <img border="0" src="'.$cfg['path']['contenido_fullhtml'].'images/but_cancel.gif">
	                </a>';
                $keycode[$type][$val] = $tmp.                                                                                  
	                '<a style="text-decoration:none;" href="javascript:setcontent(\'1\',\''.$path.'\');">
	                <img border="0" src="'.$cfg['path']['contenido_fullhtml'].'images/but_cancel.gif">
	                </a>';
            }

			$code = str_ireplace($search, $replacements, $layoutCode);
			// execute CEC hook
			$code = CEC_Hook::executeAndReturn('Contenido.Content.conGenerateCode', $code);
			$layoutCode = stripslashes($code);

        	}
		}
        return $layoutCode;
    }
    
?>