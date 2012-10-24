<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Stringlist for module translation
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$contenidoTranslateFromFile = new cModuleFileTranslation($idmod);
$translationsArray = $contenidoTranslateFromFile->getTranslationArray();

$translations = new cApiModuleTranslationCollection;
$translations->select("idmod = " . (int) $idmod . " AND idlang = " . (int) $lang);

$page = new cGuiPage("mod_translate_stringlist");

$v = '<table class="borderless" cellspacing="0" cellpadding="0" width="600">';

$link = new cHTMLLink;
$link->setCLink("mod_translate", 4, "");

$mylink = new cHTMLLink;

$rowCount = 0;

foreach ($translationsArray as $key => $value) {
//while ($translation = $translations->next())
    $string = $key; // $translation->get("original");
    $tstring = $value; // $translation->get("translation");

    $link->setCustom("idmod", $idmod);
    //$link->setCustom("idmodtranslation", $translation->get("idmodtranslation"));
    $link->setCustom("row", $rowCount);

    $href = $link->getHREF();
    $mylink->setLink(htmlspecialchars('javascript:parent.location="' . $href . '"'));
    $mylink->setContent($string);
    if ($rowCount == $row) {// $translation->get("idmodtranslation"))
        $style = "active";
    } else {
        $style = "";
    }
    //$v .= '<tr bgcolor="'.$bgcol.'"><td style="padding-left: 2px; padding-top:2px; padding-bottom: 2px;" width="50%"><a name="'.$translation->get("idmodtranslation").'"></a>'.$mylink->render().'</td><td style="padding-left: 2px;">'.$tstring.'</td></tr>';

    $v .= '<tr class="' . $style . '"><td style="padding-left: 2px; padding-top:2px; padding-bottom: 2px;" width="50%"><a name="' . $rowCount . '"></a>' . $mylink->render() . '</td><td style="padding-left: 2px;">' . $tstring . '</td></tr>';
    $rowCount++;
}

$v .= '</table>';

$page->set("s", "FORM", $v);

$clang = new cApiLanguage($lang);

$page->setEncoding($clang->get("encoding"));

$page->render();

?>