<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Displays form for configuring a template
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created  2002
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id: include.pretplcfg_edit_form.php 1157 2010-05-20 14:10:43Z xmurrix $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


$tpl->reset();

$sql = "SELECT * FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg='".Contenido_Security::toInteger($idtplcfg)."'";
$db->query($sql);

$a_c = array();

while ($db->next_record()) {
    $a_c[$db->f("number")] = $db->f("container");                // 'varstring' is safed in $a_c
}

//Form
$formaction = $sess->url("main.php");
#<input type="hidden" name="action" value="tplcfg_edit">
$hidden     = '<input type="hidden" name="area" value="tpl_cfg">
               <input type="hidden" name="frame" value="'.$frame.'">
               <input type="hidden" name="idcat" value="'.$idcat.'">
               <input type="hidden" name="idart" value="'.$idart.'">
               <input type="hidden" name="idtpl" value="'.$idtpl.'">
               <input type="hidden" name="lang" value="'.$lang.'">
               <input type="hidden" name="idtplcfg" value="'.$idtplcfg.'">
               <input type="hidden" name="changetemplate" value="0">';

$tpl->set('s', 'FORMACTION', $formaction );
$tpl->set('s', 'HIDDEN', $hidden );


$sql = "SELECT
            idtpl,
            name,
            description
        FROM
            ".$cfg["tab"]["tpl"]."
        WHERE
            idclient = '".Contenido_Security::toInteger($client)."' AND
            idtpl    = '".Contenido_Security::toInteger($idtpl)."'";

$db->query($sql);
$db->next_record();

$tpl->set('s', 'TEMPLATECAPTION', i18n("Template"). ": ");
$tpl->set('s', 'TEMPLATESELECTBOX', $db->f("name"));
$tpl->set('s', 'DESCRIPTIONCAPTION', i18n("Description"). ": ");
$tpl->set('s', 'TEMPLATEDESCRIPTION', $db->f("description"));

//************** For all Containers list module input
$sql = "SELECT
            *
        FROM
            ".$cfg["tab"]["container"]."
        WHERE
            idtpl='".Contenido_Security::toInteger($idtpl)."' ORDER BY idcontainer ASC";

$db->query($sql);
while ($db->next_record()) {
        $a_d[$db->f("number")] = $db->f("idmod");                // 'list of used modules' is safed in $a_d
}

if (isset($a_d) && is_array($a_d)) {
    foreach ($a_d as $cnumber=>$value) {
        // only show containers which contain a module
        if ($value != 0) {

                $sql = "SELECT
                            *
                        FROM
                            ".$cfg["tab"]["mod"]."
                        WHERE
                            idmod = '".Contenido_Security::toInteger($a_d[$cnumber])."'";

                $db->query($sql);
                $db->next_record();

                $input = $db->f("input")."\n";

				global $cCurrentModule;
				$cCurrentModule = $db->f("idmod");

                $modulecaption = sprintf(i18n("Module in container %s"), $cnumber);
                $modulename    = $db->f("name");

//              echo "$a_c[$cnumber]<br><br>";

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
                    $input  = str_replace("CMS_VAR", "C".$cnumber."CMS_VAR" , $input);

                    ob_start();
                    eval($CiCMS_VALUE." \r\n ".$input);
                    $modulecode = ob_get_contents();
                    ob_end_clean();

                    $tpl->set('d', 'MODULECAPTION', $modulecaption);
                    $tpl->set('d', 'MODULENAME',    $modulename);
                    $tpl->set('d', 'MODULECODE',    $modulecode);
                    $tpl->next();

        }
    }
}

$tpl->set('s', 'SCRIPT',        '');
$tpl->set('s', 'MARKSUBMENU',   '');
$tpl->set('s', 'CATEGORY',      '');

$tpl->set('s', 'HEADER', i18n("Template preconfiguration"));
$tpl->set('s', 'DISPLAY_HEADER', 'block');

$buttons = '<a href="javascript:history.back()"><img src="images/but_cancel.gif" border="0"></a>&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="image" src="images/but_ok.gif">';

$tpl->set('s', 'BUTTONS', $buttons);

# Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['tplcfg_edit_form']);
?>