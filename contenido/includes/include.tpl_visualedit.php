<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Visual Template Editor
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.1.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-12-15
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *   modified 2009-01-08, Timo Trautmann fixed bug: Changes in Head Containers in visualedit were not stored
 *   modified 2009-10-13, Murat Purc, Fixed bug in visualedit replacements (see [#CON-273]) and othe improvements
 *   modified 2011-06-20, Rusmir Jusufovic , load layout code from file and not from db
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


cInclude('includes', 'functions.tpl.php');

$idtpl  = cSecurity::toInteger($idtpl);
$client = cSecurity::toInteger($client);

$sql = "SELECT
        a.idtpl, a.name as name, a.description, a.idlay, b.description as laydescription, defaulttemplate
        FROM
        " . $cfg['tab']['tpl'] . " AS a
        LEFT JOIN
        " . $cfg['tab']['lay'] . " AS b
        ON a.idlay=b.idlay
        WHERE a.idtpl='" . $idtpl . "'
        ORDER BY name";

$db->query($sql);
$db->next_record();

$idtpl          = (int) $db->f('idtpl');
$tplname        = $db->f('name');
$description    = $db->f('description');
$idlay          = (int) $db->f('idlay');
$laydescription = nl2br($db->f('laydescription'));
$bIsDefault     = $db->f('defaulttemplate');


$sql = "SELECT number, idmod FROM " . $cfg['tab']['container'] . " WHERE idtpl='" . $idtpl . "'";
$db->query($sql);
while ($db->next_record()) {
    $a_c[$db->f('number')] = $db->f('idmod');
}

$modules = array();
$sql = "SELECT idmod, name, type FROM " . $cfg['tab']['mod'] . " WHERE idclient='" . $client . "' ORDER BY name";
$db->query($sql);
while ($db->next_record()) {
    $modules[$db->f('idmod')]['name'] = $db->f('name');
    $modules[$db->f('idmod')]['type'] = $db->f('type');
}



    #$code = $db->f('code');
    $layoutInFile = new LayoutInFile($idlay, "", $cfg, $lang);
    $code = $layoutInFile->getLayoutCode();

    // get document version (html or xhtml)
    $is_XHTML = getEffectiveSetting('generator', 'xhtml', 'false');
    $sElemClosing = ($is_XHTML == 'true') ? ' /' : '';

    $base = '<base href="' . $cfgClient[$client]['path']['htmlpath'] . '"' . $sElemClosing . '>';
    $tags = $base;
    $tags .= '
<style type="text/css">
#tpl_visedit {height:100%; padding:0; margin:0;}
#tpl_visedit .visedit_item {position:relative; height:26px; white-space:nowrap; font-size:12px;}
</style>
';

    $code = str_replace('<head>', "<head>\n".$tags ."\n", $code);

    tplPreparseLayout($idlay);
    $containers = tplBrowseLayoutForContainers($idlay);

    $a_container = explode('&',$containers);
    $sContainerInHead = '';

    foreach ($a_container as $key => $value) {

        if ($value != 0) {
            //*************** Loop through containers ****************
            $name = tplGetContainerName($idlay, $value);

            $modselect = new cHTMLSelectElement('c['.$value.']');
            $modselect->setAttribute('title', "Container $value ($name)");

            $mode = tplGetContainerMode($idlay, $value);

            if ($mode == 'fixed') {
                $default = tplGetContainerDefault($idlay, $value);

                foreach ($modules as $key => $val) {
                    if ($val['name'] == $default) {
                        if (strlen($val['name']) > 20) {
                            $short_name = cApiStrTrimHard($val['name'], 20);
                            $option = new cHTMLOptionElement($short_name, $key);
                            $option->setAttribute('title', "Container $value ($name) " . $val['name']);
                        } else {
                            $option = new cHTMLOptionElement($val['name'], $key);
                            $option->setAttribute('title', "Container $value ($name)");
                        }

                        if ($a_c[$value] == $key) {
                            $option->setSelected(true);
                        }

                        $modselect->addOptionElement($key, $option);
                    }
                }
            } else {

                $default = tplGetContainerDefault($idlay, $value);

                if ($mode == 'optional' || $mode == '') {
                    $option = new cHTMLOptionElement('-- ' . i18n("none") . ' --', 0);

                    if (isset($a_c[$value]) && $a_c[$value] != '0') {
                        $option->setSelected(false);
                    } else {
                        $option->setSelected(true);
                    }

                    $modselect->addOptionElement(0, $option);
                }

                $allowedtypes = tplGetContainerTypes($idlay, $value);

                foreach ($modules as $key => $val) {
                    $short_name = $val['name'];
                    if (strlen($val['name']) > 20) {
                        $short_name = cApiStrTrimHard($val['name'], 20);
                    }

                    $option = new cHTMLOptionElement($short_name, $key);

                    if (strlen($val['name']) > 20) {
                        $option->setAttribute('title', "Container $value ($name) " . $val['name']);
                    }

                    if ($a_c[$value] == $key || ($a_c[$value] == 0 && $val['name'] == $default)) {
                        $option->setSelected(true);
                    }

                    if (count($allowedtypes) > 0) {
                        if (in_array($val['type'], $allowedtypes) || $val['type'] == '') {
                            $modselect->addOptionElement($key, $option);
                        }
                    } else {
                        $modselect->addOptionElement($key, $option);
                    }
                }
            }

            // visual edit item container
            $sLabelAndSelect = '<label for="' . $modselect->getAttribute('id') . '">' . $value . ':</label>' . $modselect->render();
            $sVisualEditItem = '<div class="visedit_item" onmouseover="this.style.zIndex = \'20\'" onmouseout="this.style.zIndex = \'10\'">' . $sLabelAndSelect . '</div>';

            // collect containers in head for displaying them at the start of body
            if (is_array($containerinf) && isset($containerinf[$idlay]) && isset($containerinf[$idlay][$value]) &&
                isset($containerinf[$idlay][$value]['is_body']) && $containerinf[$idlay][$value]['is_body'] == false) {
                // replace container inside head with empty values and collect the container
                $code = preg_replace("/<container( +)id=\"$value\"(.*)>(.*)<\/container>/Uis", "CMS_CONTAINER[$value]", $code);
                $code = preg_replace("/<container( +)id=\"$value\"(.*)\/>/i", "CMS_CONTAINER[$value]", $code);
                $code = str_ireplace("CMS_CONTAINER[$value]", '', $code);
                $sContainerInHead .= $sVisualEditItem . "\n";
            } else {
                // replace other container
                $code = preg_replace("/<container( +)id=\"$value\"(.*)>(.*)<\/container>/Uis", "CMS_CONTAINER[$value]", $code);
                $code = preg_replace("/<container( +)id=\"$value\"(.*)\/>/i", "CMS_CONTAINER[$value]", $code);
                $code = str_ireplace("CMS_CONTAINER[$value]", $sVisualEditItem, $code);
            }
        }
    }

    // Get rid of any forms
    $code = preg_replace("/<form(.*)>/i", '', $code);
    $code = preg_replace("/<\/form(.*)>/i", '', $code);

    $form = '
    <form id="tpl_visedit" name="tpl_visedit" action="'.$cfg['path']['contenido_fullhtml'].'main.php">
    <input type="hidden" name="' . $sess->name . '" value="' . $sess->id . '"' . $sElemClosing . '>
    <input type="hidden" name="idtpl" value="'.$idtpl.'"' . $sElemClosing . '>
    <input type="hidden" name="frame" value="'.$frame.'"' . $sElemClosing . '>
    <input type="hidden" name="area" value="'.$area.'"' . $sElemClosing . '>
    <input type="hidden" name="description" value="'.$description.'"' . $sElemClosing . '>
    <input type="hidden" name="tplname" value="'.$tplname.'"' . $sElemClosing . '>
    <input type="hidden" name="idlay" value="'.$idlay.'"' . $sElemClosing . '>
    <input type="hidden" name="tplisdefault" value="'.$bIsDefault.'"' . $sElemClosing . '>
    <input type="hidden" name="action" value="tpl_visedit"' . $sElemClosing . '>';
    $form .= $sContainerInHead;

    $sInput = '<input type="image" src="' . $cfg['path']['contenido_fullhtml'] . $cfg['path']['images'] . 'but_ok.gif' . '"' . $sElemClosing . '>';
    $button = '<table border="0" width="100%"><tr><td align="right">' . $sInput . '</td></tr></table>';
    $code = preg_replace("/<body(.*)>/i", "<body\\1>" . $form . $button, $code);
    $code = preg_replace("/<\/body(.*)>/i", '</form></body>', $code);

    eval("?>\n".$code."\n<?php\n");


?>