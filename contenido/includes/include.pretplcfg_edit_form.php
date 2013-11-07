<?php
/**
 * This file contains the backend page for the form of template pre configuration.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$tpl->reset();

$sql = "SELECT * FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg=" . (int) $idtplcfg;
$db->query($sql);

$a_c = array();

while ($db->nextRecord()) {
    $a_c[$db->f("number")] = $db->f("container"); // 'varstring' is safed in $a_c
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

$tpl->set('s', 'FORMACTION', $formaction);
$tpl->set('s', 'HIDDEN', $hidden);

$sql = "SELECT
            idtpl, name, description
        FROM
            ".$cfg["tab"]["tpl"]."
        WHERE
            idclient = " . (int) $client . " AND idtpl = " . (int) $idtpl;

$db->query($sql);
$db->nextRecord();
$description = $db->f('description');

$tpl->set('s', 'TEMPLATECAPTION', i18n("Template"). ": ");
$tpl->set('s', 'TEMPLATESELECTBOX', $db->f("name"));

// For all Containers list module input
$sql = "SELECT * FROM " . $cfg["tab"]["container"] . " WHERE idtpl=" . (int) $idtpl . " ORDER BY idcontainer ASC";
$db->query($sql);
while ($db->nextRecord()) {
    $a_d[$db->f("number")] = $db->f("idmod");  // 'list of used modules' is safed in $a_d
}

if (isset($a_d) && is_array($a_d)) {
    foreach ($a_d as $cnumber => $value) {
        // nur die Container anzeigen, in denen auch ein Modul enthalten ist
        if ($value != 0) {
            $sql = "SELECT * FROM " . $cfg["tab"]["mod"] . " WHERE idmod = " . (int) $a_d[$cnumber];
            $db->query($sql);
            $db->nextRecord();

            $input = "\n";

            // Read the input for the editing in Backend from file
            $contenidoModuleHandler = new cModuleHandler($db->f("idmod"));
            if ($contenidoModuleHandler->modulePathExists() == true) {
                $input = stripslashes($contenidoModuleHandler->readInput()) . "\n";
            }

            global $cCurrentModule;
            $cCurrentModule = $db->f("idmod");

            $modulecaption = sprintf(i18n("Module in Container %s"), $cnumber);
            $modulename    = $db->f("name");

// ############ @FIXME Same code as in contenido/includes/include.tplcfg_edit_form.php
            $varstring = array();

            if (isset($a_c[$cnumber])) {
                $a_c[$cnumber] = preg_replace("/&$/", "", $a_c[$cnumber]);
                $tmp1 = preg_split("/&/", $a_c[$cnumber]);

                foreach ($tmp1 as $key1 => $value1) {
                    $tmp2 = explode("=", $value1);
                    foreach ($tmp2 as $key2 => $value2) {
                        $varstring[$tmp2[0]] = urldecode($tmp2[1]);
                    }
                }
            }

            $CiCMS_Var = '$C' . $cnumber . 'CMS_VALUE';
            $CiCMS_VALUE = '';

            foreach ($varstring as $key3 => $value3) {
                // Convert special characters and escape backslashes!
                $tmp = conHtmlSpecialChars($value3);
                $tmp = str_replace('\\', '\\\\', $tmp);

                $CiCMS_VALUE .= $CiCMS_Var . '[' . $key3 . '] = "' . $tmp . '"; ';
                $input = str_replace("\$CMS_VALUE[$key3]", $tmp, $input);
                $input = str_replace("CMS_VALUE[$key3]", $tmp, $input);
            }

            $input = str_replace("CMS_VALUE", $CiCMS_Var, $input);
            $input = str_replace("\$" . $CiCMS_Var, $CiCMS_Var, $input);
            $input = str_replace("CMS_VAR", "C" . $cnumber . "CMS_VAR", $input);

            ob_start();
            eval($CiCMS_VALUE . "\n" . $input);
            $modulecode = ob_get_contents();
            ob_end_clean();
// ###### END FIXME

            $tpl->set('d', 'MODULECAPTION', $modulecaption);
            $tpl->set('d', 'MODULENAME', $modulename);
            $tpl->set('d', 'MODULECODE', $modulecode);
            $tpl->next();
        }
    }
}

$tpl->set('s', 'SCRIPT', '');
$tpl->set('s', 'MARKSUBMENU', '');
$tpl->set('s', 'CATEGORY', '');

$tpl->set('s', 'HEADER', i18n('Template preconfiguration'));
$tpl->set('s', 'DISPLAY_HEADER', 'block');

$buttons = '<a href="javascript:history.back()"><img src="images/but_cancel.gif" border="0"></a>&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="image" src="images/but_ok.gif">';

$tpl->set('s', 'BUTTONS', $buttons);

$tpl->set('s', 'LABLE_DESCRIPTION', i18n('Description'));
$tpl->set('s', 'DESCRIPTION', nl2br($description));

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['tplcfg_edit_form']);

?>