<?php

/**
 * This file contains the backend page for client article specification.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$page = new cGuiPage("client_artspec");

if ($action == "client_artspec_save") {
    if (!$perm->have_perm_area_action($area, $action)) {
        $notification->displayNotification("error", i18n("Permission denied"));
    } else {
        addArtspec($_POST['artspectext'], $online);
    }
}

if ($action == "client_artspec_delete") {
    if (!$perm->have_perm_area_action($area, $action)) {
        $notification->displayNotification("error", i18n("Permission denied"));
    } else {
        deleteArtspec($_GET['idartspec']);
    }
}

if ($action == "client_artspec_online") {
    if (!$perm->have_perm_area_action($area, "client_artspec_save")) {
        $notification->displayNotification("error", i18n("Permission denied"));
    } else {
        setArtspecOnline($_GET['idartspec'], $online);
    }
}

if ($action == "client_artspec_default") {
    if (!$perm->have_perm_area_action($area, "client_artspec_save")) {
        $notification->displayNotification("error", i18n("Permission denied"));
    } else {
        setArtspecDefault($_GET['idartspec']);
    }
}

$artspec = getArtspec();

if (!empty($artspec)) {

    $backendUrl = cRegistry::getBackendUrl();

    $list = new cGuiList();

    $list->setCell(1, 1, i18n("Article specification"));
    $list->setCell(1, 2, i18n("Options"));

    $count = 2;

    $link = new cHTMLLink();
    $link->setCLink($area, $frame, "client_artspec_edit");
    $link->setContent('<img src="' . $backendUrl . $cfg['path']['images'] . 'editieren.gif" alt="' . i18n('Edit') . '" title="' . i18n('Edit') . '">');

    $dlink = new cHTMLLink();
    $dlink->setCLink($area, $frame, "client_artspec_delete");
    $dlink->setContent('<img src="' . $backendUrl . $cfg['path']['images'] . 'delete.gif" alt="' . i18n('Delete') . '" title="' . i18n('Delete') . '">');

    $olink = new cHTMLLink();
    $olink->setCLink($area, $frame, "client_artspec_online");

    $defLink = new cHTMLLink();
    $defLink->setCLink($area, $frame, "client_artspec_default");

    $artspec = getArtspec();

    if (is_array($artspec)) {
        foreach ($artspec as $id => $tmp_artspec) {
            $link->setCustom("idartspec", $id);
            $link->updateAttributes(array(
                'style' => 'padding:3'
            ));

            $dlink->setCustom("idartspec", $id);
            $dlink->updateAttributes(array(
                'style' => 'padding:3'
            ));

            $olink->setCustom("idartspec", $id);
            $olink->updateAttributes(array(
                'style' => 'padding:3'
            ));

            $defLink->setCustom("idartspec", $id);
            $defLink->updateAttributes(array(
                'style' => 'padding:3'
            ));

            if (($action == "client_artspec_edit") && ($idartspec == $id)) {
                $form = new cHTMLForm("artspec");
                $form->setVar("area", $area);
                $form->setVar("frame", $frame);
                $form->setVar("idartspec", $id);
                $form->setVar("action", "client_artspec_save");
                $form->setVar("online", $artspec[$id]['online']);
                $inputbox = new cHTMLTextbox("artspectext", $artspec[$id]['artspec']);
                $form->appendContent($inputbox->render());
                $form->appendContent('<input type="image" value="submit" src="' . $backendUrl . $cfg['path']['images'] . 'submit.gif" alt="' . i18n('Save') . '" title="' . i18n('Save') . '">');

                $list->setCell($count, 1, $form->render());
            } else {
                $list->setCell($count, 1, $artspec[$id]['artspec']);
            }

            if ($artspec[$id]['online'] == 0) {
                // it is offline (std!)
                $olink->setContent('<img src="' . $backendUrl . $cfg['path']['images'] . 'offline.gif" alt="' . i18n('Make online') . '" title="' . i18n('Make online') . '">');
                $olink->setCustom("online", 1);
            } else {
                $olink->setContent('<img src="' . $backendUrl . $cfg['path']['images'] . 'online.gif" alt="' . i18n('Make offline') . '" title="' . i18n('Make offline') . '">');
                $olink->setCustom("online", 0);
            }

            if ($artspec[$id]['default'] == 0) {
                $defLink->setContent('<img alt="" src="' . $backendUrl . $cfg['path']['images'] . 'artikel_spez_inakt.gif" title="' . i18n("Make this article specification default") . '">');
                $list->setCell($count, 2, $link->render() . $dlink->render() . $olink->render() . $defLink->render());
            } else {
                $standardImage = new cHTMLImage($backendUrl . $cfg['path']['images'] . 'artikel_spez_akt.gif');
                $standardImage->setAttribute("title", i18n("This is the default article specification"));
                $standardImage->appendStyleDefinition("padding-left", "3px");
                $list->setCell($count, 2, $link->render() . $dlink->render() . $olink->render() . $standardImage->toHtml());
            }

            $count++;
        }
    } else {
        $list->setCell($count, 1, i18n("No article specifications found!"));
        $list->setCell($count, 2, '');
    }
}
unset($form);

$form = new cGuiTableForm("artspec");
$form->setVar("area", $area);
$form->setVar("frame", $frame);
$form->setVar("action", "client_artspec_save");
$form->addHeader(i18n("Create new article specification"));
$inputbox = new cHTMLTextbox("artspectext");
$form->add(i18n("Specification name"), $inputbox->render());

$spacer = new cHTMLDiv();
$spacer->setStyle("width: 1%");
$spacer->setContent("<br>" . $form->render());

$content = array();
if (!empty($list)) {
    $content[] = $list;
}
$content[] = $spacer;
$page->setContent($content);

$page->render();

?>