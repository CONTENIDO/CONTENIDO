<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Displays form for configuring a template
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.1
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2002
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *   modified 2011-01-11, Rusmir Jusufovic, load input of moduls from files
 *   modified 2012-02-13, Rusmir Jusufovic, show message at success
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.pathresolver.php');
$message = '';
$description = '';
if (isset($idart)) {
    if ($idart > 0) {
        $idartlang = getArtLang($idart, $lang);

        // Remove all own marks
        $col = new cApiInUseCollection();
        $col->removeSessionMarks($sess->id);

        if (($obj = $col->checkMark('article', $idartlang)) === false || $obj->get("userid") == $auth->auth['uid']) {
            $col->markInUse('article', $idartlang, $sess->id, $auth->auth['uid']);
            $inUse = false;
            $disabled = '';
        } else {
            $vuser = new cApiUser($obj->get('userid'));
            $inUseUser = $vuser->getField('username');
            $inUseUserRealName = $vuser->getField('realname');

            $message = sprintf(i18n("Article is in use by %s (%s)"), $inUseUser, $inUseUserRealName);
            $notification->displayNotification('warning', $message);
            $inUse = true;
            $disabled = 'disabled="disabled"';
        }
    } else {
        // Remove all own marks
        $col = new cApiInUseCollection();
        $col->removeSessionMarks($sess->id);
        if (($obj = $col->checkMark('categorytpl', $idcat)) === false || $obj->get("userid") == $auth->auth['uid']) {
            $col->markInUse('categorytpl', $idcat, $sess->id, $auth->auth['uid']);
            $inUse = false;
            $disabled = '';
        } else {
            $vuser = new cApiUser($obj->get('userid'));
            $inUseUser = $vuser->getField('username');
            $inUseUserRealName = $vuser->getField('realname');

            $message = sprintf(i18n("Category template configuration is in use by %s (%s)"), $inUseUser, $inUseUserRealName);
            $notification->displayNotification('warning', $message);
            $inUse = true;
            $disabled = 'disabled="disabled"';
        }
    }
}

if (!isset($idart)) {
    $idart = 0;
}
if (!isset($idlay)) {
    $idlay = 0;
}
if (!isset($db2) || !is_object($db2)) {
    $db2 = new DB_Contenido();
}
if (!isset($db3) || !is_object($db3)) {
    $db3 = new DB_Contenido();
}

$tpl->reset();

if ($idart) {
    if ($perm->have_perm_area_action('con', 'con_tplcfg_edit') ||
        $perm->have_perm_area_action_item('con', 'con_tplcfg_edit', $idcat))
    {

        // Article is configured
        $sql = "SELECT
                    c.idtpl AS idtpl,
                    c.description,
                    b.idtplcfg AS idtplcfg,
                    b.locked AS locked
                FROM
                    ".$cfg['tab']['tpl_conf']." AS a,
                    ".$cfg['tab']['art_lang']." AS b,
                    ".$cfg['tab']['tpl']." AS c
                WHERE
                    b.idart     = ".(int) $idart." AND
                    b.idlang    = ".(int) $lang." AND
                    b.idtplcfg  = a.idtplcfg AND
                    c.idtpl     = a.idtpl";

        $db->query($sql);

        if ($db->next_record()) {
            // template configuration found
            $idtplcfg = $db->f('idtplcfg');
            $idtpl    = $db->f('idtpl');
            $description = $db->f('description');
            
            if ($db->f('locked') == 1) {
                $inUse = true;
                $disabled = 'disabled="disabled"';
            }

        } else {
            if ($idtpl) {
                // create new configuration entry
                //$nextid = $db3->nextid($cfg['tab']['tpl_conf']);

                $sql = "INSERT INTO ".$cfg['tab']['tpl_conf']." (idtpl) VALUES (".(int) $idtpl.")";
                $db->query($sql);
                $idtplcfg = $db->getLastInsertedId($cfg['tab']['tpl_conf']);

                // update art_lang
                $sql = "UPDATE ".$cfg['tab']['art_lang']." SET idtplcfg=".(int) $idtplcfg." WHERE idart=".(int) $idart." AND idlang=".(int) $lang;
                $db->query($sql);

            }


        }

    } else {
        $notification->displayNotification('error', i18n("Permission denied"));
        exit;
    }

} elseif ($idcat) {

    // Category is configured
    $sql = "SELECT
                c.idtpl AS idtpl,
                c.description,
                b.idtplcfg AS idtplcfg
            FROM
                ".$cfg['tab']['tpl_conf']." AS a,
                ".$cfg['tab']['cat_lang']." AS b,
                ".$cfg['tab']['tpl']." AS c
            WHERE
                b.idcat     = ".(int) $idcat." AND
                b.idlang    = ".(int) $lang." AND
                b.idtplcfg  = a.idtplcfg AND
                c.idtpl     = a.idtpl AND
                c.idclient  = ".(int) $client;
    $db->query($sql);

    if ($db->next_record()) {
        // template configuration found
        $idtplcfg = $db->f('idtplcfg');
        $idtpl    = $db->f('idtpl');
        $description = $db->f('description');
    } else {
        if ($idtpl) {
            // create new configuration entry
            //$nextid = $db3->nextid($cfg['tab']['tpl_conf']);

            $sql = "INSERT INTO ".$cfg['tab']['tpl_conf']." (idtpl) VALUES (".(int) $idtpl.")";
            $db->query($sql);
            $idtplcfg = $db->getLastInsertedId($cfg['tab']['tpl_conf']);

            // update cat_lang
            $sql = "UPDATE ".$cfg['tab']['cat_lang']." SET idtplcfg=".(int) $idtplcfg ." WHERE idcat=".(int) $idcat." AND idlang=".(int) $lang;
            $db->query($sql);
        }
    }
}

// change template to '--- Nothing ---'
if ($idtpl == 0) {
    $idtplcfg = 0;
}

// Check if a configuration for this $idtplcfg exists
$sql = "SELECT idcontainerc FROM ".$cfg['tab']['container_conf']." WHERE idtplcfg=".(int) $idtplcfg;
$db->query($sql);

if (!$db->next_record()) {
    // There is no configuration for this $idtplcfg, check if template has a pre-configuration
    $sql = "SELECT idtplcfg, description FROM ".$cfg['tab']['tpl']." WHERE idtpl=".(int) $idtpl;

    $db->query($sql);
    $db->next_record();
	$description = $db->f('description');
    if (0 != $db->f('idtplcfg')) {
        // Template has a pre-configuration, copy pre-configuration data to
        // category configuration with the $idtplcfg from the category
        $sql = "SELECT * FROM ".$cfg['tab']['container_conf']." WHERE idtplcfg=".(int) $db->f('idtplcfg')." ORDER BY number DESC";
        $db->query($sql);

        while ($db->next_record()) {
            // get data
            //$nextid    = $db3->nextid($cfg['tab']['container_conf']);
            $number    = $db->f('number');
            $container = $db->f('container');
            // write new entry
            $sql = "INSERT INTO
                        ".$cfg['tab']['container_conf']."
                        (idtplcfg, number, container)
                    VALUES
                        (".(int) $idtplcfg.", ".(int) $number.", '".$db2->escape($container)."')";

            $db2->query($sql);
        }
    }
}

if(count($_POST) > 0 && $message == '') {

     $notification->displayNotification(Contenido_Notification::LEVEL_INFO, i18n("Save change successfully!"));
}

// Get template configuration from 'con_container_conf' and create configuration data array
$sql = "SELECT * FROM ".$cfg['tab']['container_conf']." WHERE idtplcfg=".(int) $idtplcfg." ORDER BY number";
$db->query($sql);

$a_c = array();

while ($db->next_record()) {
    // varstring is stored in array $a_c
    $a_c[$db->f('number')] = $db->f('container');
}

$tmp_area = 'tplcfg';

//Form
$formaction = $sess->url('main.php');
$hidden     = '<input type="hidden" name="area" value="'.$area.'">
               <input type="hidden" name="frame" value="'.$frame.'">
               <input type="hidden" name="idcat" value="'.$idcat.'">
               <input type="hidden" name="idart" value="'.$idart.'">
               <input type="hidden" name="idtpl" value="'.$idtpl.'">
               <input type="hidden" name="lang" value="'.$lang.'">
               <input type="hidden" name="idtplcfg" value="'.$idtplcfg.'">
               <input type="hidden" name="changetemplate" value="0">
               <input type="hidden" name="send" value="1">';

$tpl->set('s', 'FORMACTION', $formaction);
$tpl->set('s', 'HIDDEN', $hidden);

// Category Path for user
$oArticle = new cApiArticleLanguage();
$oArticle->loadByArticleAndLanguageId($idart, $lang);

$sArticleTitle = $oArticle->getField('title');
$catString = '';
prCreateURLNameLocationString($idcat, '/', $catString);
$tpl->set('s', 'CATEGORY', $catString.'/'.$sArticleTitle);

//SELECT Box for Templates

$tpl->set('s', 'TEMPLATECAPTION', i18n("Template"));

$tpl2 = new Template();
$tpl2->set('s', 'NAME', 'idtpl');
$tpl2->set('s', 'CLASS', 'text_medium');

if (!$perm->have_perm_area_action_item('con', 'con_changetemplate', $idcat)) {
    $disabled2 = 'disabled="disabled"';
}

$tpl2->set('s', 'OPTIONS', $disabled.' '.$disabled2.' onchange="tplcfgform.changetemplate.value=1;tplcfgform.send.value=0;tplcfgform.submit();"');

$sql = "SELECT idtpl, name, description FROM ".$cfg['tab']['tpl']." WHERE idclient=".(int) $client." ORDER BY name";
$db->query($sql);

$tpl2->set('d', 'VALUE', 0);
$tpl2->set('d', 'CAPTION', '--- '.i18n("none"). ' ---');
$tpl2->set('d', 'SELECTED', '');
$tpl2->next();

while ($db->next_record()) {
    if ($db->f('idtpl') != "$idtpl") {
        $tpl2->set('d', 'VALUE',    $db->f('idtpl'));
        $tpl2->set('d', 'CAPTION',  $db->f('name'));
        $tpl2->set('d', 'SELECTED', '');
        $tpl2->next();
    } else {
        $tpl2->set('d', 'VALUE',    $db->f('idtpl'));
        $tpl2->set('d', 'CAPTION',  $db->f('name'));
        $tpl2->set('d', 'SELECTED', 'selected="selected"');
        $tpl2->next();
    }
}

$select = $tpl2->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'], true);
$tpl->set('s', 'TEMPLATESELECTBOX', $select);
// modul input bereich von allen container anzeigen
$sql = "SELECT * FROM ".$cfg['tab']['container']." WHERE idtpl=".(int) $idtpl." ORDER BY number ASC";
$db->query($sql);

$a_d = array();

while ($db->next_record()) {
    // liste der benutzten module generieren
    $a_d[$db->f('number')] = $db->f('idmod');
}

if (isset($a_d) && is_array($a_d)) {
    foreach ($a_d as $cnumber => $value) {
        // show only the containers which contain a module
        if (0 != $value) {
            $sql = "SELECT * FROM ".$cfg['tab']['mod']." WHERE idmod=".(int) $a_d[$cnumber];
            $db->query($sql);
            $db->next_record();

            global $cCurrentModule, $cCurrentContainer;
            $cCurrentModule = $db->f('idmod');
            $cCurrentContainer = $cnumber;
            $modulecaption = i18n("Module in container").' '.$cnumber.': ';
            $modulename    = $db->f('name');


            $input = "\n";
            $contenidoModuleHandler = new Contenido_Module_Handler($db->f('idmod'));

            // load data from file
            if ($contenidoModuleHandler->modulePathExists() == true) {
                $input = $contenidoModuleHandler->readInput()."\n";
            }
            $varstring = array();

            if (isset($a_c[$cnumber])) {
                $a_c[$cnumber] = preg_replace("/&$/", "", $a_c[$cnumber]);
                $tmp1 = preg_split("/&/", $a_c[$cnumber]);

                foreach ($tmp1 as $key1=>$value1) {
                    $tmp2 = explode("=", $value1);
                    foreach ($tmp2 as $key2=>$value2) {
                        $varstring[$tmp2[0]]=$tmp2[1];
                    }
                }
            }

            $CiCMS_Var = '$C'.$cnumber.'CMS_VALUE';
            $CiCMS_VALUE = '';

            foreach ($varstring as $key3=>$value3){
                $tmp = urldecode($value3);
                $tmp = str_replace("\'", "'", $tmp);
                $CiCMS_VALUE .= $CiCMS_Var.'['.$key3.']="'.$tmp.'"; ';
                $input = str_replace("\$CMS_VALUE[$key3]", $tmp, $input);
                $input = str_replace("CMS_VALUE[$key3]", $tmp, $input);
            }

            $input = str_replace("CMS_VALUE", $CiCMS_Var, $input);
            $input = str_replace("\$".$CiCMS_Var, $CiCMS_Var, $input);
            $input = str_replace("CMS_VAR", "C".$cnumber."CMS_VAR" , $input);

            ob_start();
            eval($CiCMS_VALUE."\n".$input);

            $modulecode = ob_get_contents();
            ob_end_clean();

            $tpl->set('d', 'MODULECAPTION', $modulecaption);
            $tpl->set('d', 'MODULENAME',    $modulename);
            if ($inUse == false) {
                $tpl->set('d', 'MODULECODE', $modulecode);
            } else {
                $tpl->set('d', 'MODULECODE', '&nbsp;');
            }
            $tpl->next();
        }
    }
}

$script = '
    var sid = "'.$sess->id.'";

    try {
        obj = parent.parent.frames["left"].frames["left_top"].cfg;
    } catch (e) {
        // catch error exception
    }

    if (obj) {
        /* Format of the data-string
            0 -> category id
            1 -> category template id
            2 -> category online
            3 -> category public
            4 -> has right for: template
            5 -> has right for: online
            6 -> has right for: public
            7 -> idstring not splitted */

        tmp_idtpl = ("'.$idtpl.'" == "") ? 0 : "'.$idtpl.'";

        changed = (obj.tplId != tmp_idtpl);

        sData = "'.$idcat.'-'.$idtpl.'-"+obj.isOnline+"-"+obj.isPublic+"-"+obj.hasRight["template"]+"-"+obj.hasRight["online"]+"-"+obj.hasRight["public"];

        if (changed) {
            obj.load( "'.$idcat.'", "'.$idtpl.'", obj.isOnline, obj.isPublic, obj.hasRight["template"], obj.hasRight["online"], obj.hasRight["public"], sData );
            parent.parent.frames["left"].frames["left_bottom"].location.href = "'.$sess->url("main.php?area=con&force=1&frame=2").'";
        }
    }

    // parent.parent.frames["right"].frames["right_top"].location.href = "main.php?area=con&frame=3&idcat=0&contenido='.$sess->id.'";
    artObj = parent.parent.frames["left"].frames["left_top"].artObj;
    artObj.disable();';

// Change template select only when configuring a category
if (!$idart && $area != "str_tplcfg") {
    $tpl->set('s', 'SCRIPT', $script);
} else {
    $tpl->set('s', 'SCRIPT', '');
}

if ($idart) {
    $markscript = markSubMenuItem(3, true);
    $tpl->set('s', 'MARKSUBMENU', $markscript);
} else {
    $tpl->set('s', 'MARKSUBMENU', "");
}

if ($idart || $area == 'con_tplcfg') {
    $buttons = '<a accesskey="c" href="'.$sess->url("main.php?area=con&frame=4&idcat=$idcat").'"><img src="images/but_cancel.gif" border="0"></a>&nbsp;&nbsp;&nbsp;&nbsp;
                <input accesskey="s" type="image" src="images/but_ok.gif" onclick="document.getElementById(\'tpl_form\').action = document.getElementById(\'tpl_form\').action+\'&back=true\'">';
} else {
    $buttons = '<a accesskey="c" href="'.$sess->url("main.php?area=str&frame=4&idcat=$idcat").'"><img src="images/but_cancel.gif" border="0"></a>&nbsp;&nbsp;&nbsp;&nbsp;
                <input accesskey="s" type="image" src="images/but_ok.gif" onclick="document.getElementById(\'tpl_form\').action = document.getElementById(\'tpl_form\').action+\'&back=true\'">';
}
if ($idtpl != 0 && $inUse == false) {
    $tpl->set('s', 'BUTTONS', $buttons);
} else {
    $tpl->set('s', 'BUTTONS', '');
}


//Display template description
if($idtpl) {
	
	$tpl->set('s', 'DESCRIPTION',  nl2br($description));
	$tpl->set('s', 'LABLE_DESCRIPTION', i18n("Description"));
}else {
	$tpl->set('s', 'DESCRIPTION', '');
	$tpl->set('s', 'LABLE_DESCRIPTION','');
}

if ($area == 'str_tplcfg' || $area == 'con_tplcfg' && (int) $idart == 0) {
    $tpl->set('s', 'HEADER', i18n('Category template configuration2'));
    $tpl->set('s', 'DISPLAY_HEADER', 'block');
   
} else if ($area == 'con_tplcfg' && (int) $idart > 0) {
    $tpl->set('s', 'HEADER', i18n('Article template configuration3'));
    $tpl->set('s', 'DISPLAY_HEADER', 'block');
   
} else {
    $tpl->set('s', 'HEADER', '');
    $tpl->set('s', 'DISPLAY_HEADER', 'none');
   
}

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['tplcfg_edit_form']);

?>