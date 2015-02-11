<?php
/**
 * This file contains various function for the plugin content allocation.
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 * @version    SVN Revision $Rev:$
 *
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

function pica_RegisterCustomTab ()
{
    return array("con_contentallocation");
}

function pica_GetCustomTabProperties ($sIntName)
{
    if ($sIntName == "con_contentallocation")
    {
        return array("con_contentallocation", "con_edit", "");
    }
}

function pica_ArticleListActions ($aActions)
{
    $aTmpActions["con_contentallocation"] = "con_contentallocation";

    return $aTmpActions + $aActions;
}

function pica_RenderArticleAction ($idcat, $idart, $idartlang, $actionkey)
{
    global $sess;

    if ($actionkey == "con_contentallocation")
    {
         return '<a title="'.i18n("Tagging", 'content_allocation').'" alt="'.i18n("Tagging", 'content_allocation').'" href="'.$sess->url('main.php?area=con_contentallocation&action=con_edit&idart='.$idart.'&idartlang='.$idartlang.'&idcat='.$idcat.'&frame=4').'"><img src="plugins/content_allocation/images/call_contentallocation.gif" alt=""></a>';

    } else {
        return "";
    }
}
?>