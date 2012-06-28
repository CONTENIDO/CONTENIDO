<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Group Rights
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
 *   created  unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


if (!isset($actionarea)) {
    $actionarea = 'area';
}

if (!isset($rights_clientslang)) {
    $rights_clientslang = $firstclientslang;
}

if (!is_object($db2)) {
    $db2 = new DB_Contenido();
}

if (!is_object($oTpl)) {
    $oTpl = new Template();
}
$oTpl->reset();

// Set new right_list (=all possible rights)
if (!is_array($right_list)) {
    // Select all rights, actions an their locations without area login
    $sql = "SELECT A.idarea, A.parent_id, B.location, A.name "
         . "FROM ".$cfg["tab"]["area"]." AS A LEFT JOIN ".$cfg["tab"]["nav_sub"]." AS B ON  A.idarea = B.idarea "
         . "WHERE A.name!='login' AND A.relevant='1' AND A.online='1' GROUP BY A.name ORDER BY A.idarea";
    $db->query($sql);

    while ($db->next_record()) {
        if ($db->f('parent_id') == '0') {
            $right_list[$db->f('name')][$db->f('name')]['perm'] = $db->f('name');
            $right_list[$db->f('name')][$db->f('name')]['location'] = $db->f('location');
        } else {
            $right_list[$db->f('parent_id')][$db->f('name')]['perm'] = $db->f('name');
            $right_list[$db->f('parent_id')][$db->f('name')]['location'] = $db->f('location');
        }

        $sql = "SELECT * FROM ".$cfg["tab"]["actions"]." WHERE idarea=" . (int) $db->f("idarea") . " AND relevant='1'";
        $db2->query($sql);
        while ($db2->next_record()) {
            if ($db->f('parent_id') == '0') {
                $right_list[$db->f('name')][$db->f('name')]['action'][] = $db2->f('name');
            } else {
                $right_list[$db->f('parent_id')][$db->f('name')]['action'][] = $db2->f('name');
            }
        }
    }
}

$oTpl->set('s', 'SESS_ID', $sess->id);
$oTpl->set('s', 'ACTION_URL', $sess->url('main.php'));
$oTpl->set('s', 'TYPE_ID', 'groupid');
$oTpl->set('s', 'USER_ID', $groupid);
$oTpl->set('s', 'AREA', $area);

$mgroup = new cApiGroup($groupid);
$userperms = $mgroup->getField('perms');

$oTpl->set('s', 'RIGHTS_PERMS', $rights_perms);

// Selectbox for clients
$oHtmlSelect = new cHTMLSelectElement('rights_clientslang', '', 'rights_clientslang');

$clientclass = new cApiClientCollection;
$clientList = $clientclass->getAccessibleClients();
$firstsel = false;
$i = 0;

foreach ($clientList as $key => $value) {
    $sql = "SELECT * FROM ".$cfg["tab"]["lang"]." AS A, ".$cfg["tab"]["clients_lang"]." AS B WHERE B.idclient=" . (int) $key . " AND A.idlang=B.idlang";
    $db->query($sql);

    while ($db->next_record()) {
        if ((strpos($userperms, "client[$key]") !== false) &&
            (strpos($userperms, "lang[".$db->f("idlang")."]") !== false) &&
            ($perm->have_perm("lang[".$db->f("idlang")."]")))
        {
            if ($firstsel == false) {
                $firstsel = true;
                $firstclientslang = $db->f('idclientslang');
            }

            if ($rights_clientslang == $db->f('idclientslang')) {
                $oHtmlSelectOption = new cHTMLOptionElement($value['name'] . ' -> '.$db->f('name'), $db->f('idclientslang'), true);
                $oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
                $i++;
                if (!isset($rights_client)) {
                    $firstclientslang = $db->f('idclientslang');
                }
            } else {
                $oHtmlSelectOption = new cHTMLOptionElement($value['name'] . ' -> '.$db->f('name'), $db->f('idclientslang'), false);
                $oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
                $i++;
            }
        }
    }
}

// Render Select Box
$oTpl->set('s', 'INPUT_SELECT_CLIENT', $oHtmlSelect->render());

if ($area != 'groups_content') {
    $oTpl->set('s', 'INPUT_SELECT_RIGHTS', '');
    $oTpl->set('s', 'DISPLAY_RIGHTS', 'none');
} else {
    // Filter for displaying rights
    $oHtmlSelect = new cHTMLSelectElement('filter_rights', '', 'filter_rights');
    $oHtmlSelectOption = new cHTMLOptionElement('--- '.i18n('All').' ---', '', false);
    $oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);
    $oHtmlSelectOption = new cHTMLOptionElement(i18n('Article rights'), 'article', false);
    $oHtmlSelect->addOptionElement(1, $oHtmlSelectOption);
    $oHtmlSelectOption = new cHTMLOptionElement(i18n('Category rights'), 'category', false);
    $oHtmlSelect->addOptionElement(2, $oHtmlSelectOption);
    $oHtmlSelectOption = new cHTMLOptionElement(i18n('Template rights'), 'template', false);
    $oHtmlSelect->addOptionElement(3, $oHtmlSelectOption);
    $oHtmlSelectOption = new cHTMLOptionElement(i18n('Plugin/Other rights'), 'other', false);
    $oHtmlSelect->addOptionElement(4, $oHtmlSelectOption);
    $oHtmlSelect->setEvent('change', 'document.rightsform.submit();');
    $oHtmlSelect->setDefault($_POST['filter_rights']);

    // Set global array which defines rights to display
    $aArticleRights = array('con_syncarticle', 'con_lock', 'con_deleteart', 'con_makeonline', 'con_makestart', 'con_duplicate', 'con_editart', 'con_newart', 'con_edit');
    $aCategoryRights = array('con_synccat', 'con_makecatonline', 'con_makepublic');
    $aTempalteRights = array('con_changetemplate', 'con_tplcfg_edit');

    $aViewRights = array();
    $bExclusive = false;
    if (isset($_POST['filter_rights'])) {
        switch ($_POST['filter_rights']) {
            case 'article':
                $aViewRights = $aArticleRights;
                break;
            case 'category':
                $aViewRights = $aCategoryRights;
                break;
            case 'template':
                $aViewRights = $aTempalteRights;
                break;
            case 'other':
                $aViewRights = array_merge($aArticleRights, $aCategoryRights, $aTempalteRights);
                $bExclusive = true;
                break;
            default:
                break;
        }
    }
    $oTpl->set('s', 'INPUT_SELECT_RIGHTS', $oHtmlSelect->render());
    $oTpl->set('s', 'DISPLAY_RIGHTS', 'block');
}


$oClientLang = new cApiClientLanguage((int) $rights_clientslang);
if ($oClientLang->isLoaded()) {
    $rights_client = $oClientLang->get('idclient');
    $rights_lang = $oClientLang->get('idlang');
} else {
    $notification->displayNotification('error', i18n("Current group doesn't have any rights to any client/language."));
    die();
}

// current set it on null
$oTpl->set('s', 'NOTIFICATION', '');

$oTpl->set('s', 'OB_CONTENT', '');

function saverightsarea()
{
    global $db, $cfg, $groupid;
    global $rights_client, $rights_lang, $rights_admin, $rights_sysadmin, $rights_perms, $rights_list;

    $oGroup = new cApiGroup($groupid);
    if (!$oGroup->isLoaded()) {
        return;
    }

    if (!isset($rights_perms)) {
        // Get permissions of this user
        $rights_perms = $oGroup->get('perms');
    }

    // If there are no permissions, delete permissions for lan and client
    if (!is_array($rights_list)) {
        $rights_perms = preg_replace("/,+client\[$rights_client\]/", '', $rights_perms);
        $rights_perms = preg_replace("/,+lang\[$rights_lang\]/", '', $rights_perms);
    } else {
        if (!strstr($rights_perms, "client[$rights_client]")) {
            $rights_perms .= ",client[$rights_client]";
        }
        if (!strstr($rights_perms, "lang[$rights_lang]")) {
            $rights_perms .= ",lang[$rights_lang]";
        }
    }

    // If admin is checked
    if ($rights_admin == 1) {
        // If admin is not set
        if (!strstr($rights_perms, "admin[$rights_client]")) {
            $rights_perms .= ",admin[$rights_client]";
        }
    } else {
        // Cut admin from the string
        $rights_perms = preg_replace("/,*admin\[$rights_client\]/", '', $rights_perms);
    }

    // If sysadmin is checked
    if ($rights_sysadmin == 1) {
        // If sysadmin is not set
        if (!strstr($rights_perms, 'sysadmin')) {
            $rights_perms .= ',sysadmin';
        }
    } else {
        // Cat sysadmin from string
        $rights_perms = preg_replace('/,*sysadmin/', '', $rights_perms);
    }

    // Cut ',' in front of the string
    $rights_perms = preg_replace('/^,/', '', $rights_perms);

    // Update table
    $oGroup->set('perms', $db->escape($rights_perms));
    $oGroup->store();

    // Save the other rights
    saverights();
}

function saverights()
{
    global $rights_list, $rights_list_old, $db;
    global $cfg, $groupid, $rights_client, $rights_lang;
    global $perm, $sess, $notification;

    // If no checkbox is checked
    if (!is_array($rights_list)) {
        $rights_list = array();
    }

    // Search all checks which are not in the new rights_list for deleting
    $arraydel = array_diff(array_keys($rights_list_old), array_keys($rights_list));

    // Search all checks which are not in the rights_list_old for saving
    $arraysave = array_diff(array_keys($rights_list), array_keys($rights_list_old));

    if (is_array($arraydel)) {
        foreach ($arraydel as $value) {
            $data = explode('|', $value);
            $data[0] = $perm->getIDForArea($data[0]);
            $data[1] = $perm->getIDForAction($data[1]);

            $where = "user_id = '" . $db->escape($groupid) . "' AND idclient = " . (int) $rights_client
                   . " AND idlang = " . (int) $rights_lang . " AND idarea = " . (int) $data[0]
                   . " AND idcat = " . (int) $data[2] . " AND idaction = " . (int) $data[1] . " AND type = 1";
            $oRightColl = new cApiRightCollection();
            $oRightColl->deleteByWhereClause($where);
        }
    }

    unset($data);

    // Search for all mentioned checkboxes
    if (is_array($arraysave)) {
        foreach ($arraysave as $value) {
            // Explodes the key it consits areaid+actionid+itemid
            $data = explode('|', $value);

            // Since areas are stored in a numeric form in the rights table, we have
            // to convert them from strings into numbers
            $data[0] = $perm->getIDForArea($data[0]);
            $data[1] = $perm->getIDForAction($data[1]);

            if (!isset($data[1])) {
                $data[1] = 0;
            }

            // Insert new right
            $oRightColl = new cApiRightCollection();
            $oRightColl->create($groupid, $data[0], $data[1], $data[2], $rights_client, $rights_lang, 1);
        }
    }

    $rights_list_old = $rights_list;
    $notification->displayNotification('info', i18n('Changes saved'));
}

?>