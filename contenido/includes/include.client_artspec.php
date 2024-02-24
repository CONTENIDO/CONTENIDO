<?php

/**
 * This file contains the backend page for client article specification.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cPermission $perm
 * @var cGuiNotification $notification
 * @var array $cfg
 * @var string $area
 * @var int $frame
 * @var int $idartspec
 */

$page = new cGuiPage("client_artspec");

$online = cSecurity::toInteger($_GET['online'] ?? '0');
$online = $online === 1 ? 1 : 0;

$action = $action ?? '';

if ($action == "client_artspec_save") {
    if (!$perm->have_perm_area_action($area, $action)) {
        $notification->displayNotification("error", i18n("Permission denied"));
    } else {
        // It is an update if idartspec exists, otherwise it is a new entry.
        $_idArtSpec = isset($_POST['idartspec']) ? cSecurity::toInteger($_POST['idartspec']) : null;
        cCreateOrUpdateArtSpec($_POST['artspectext'], $online, $_idArtSpec);
    }
}

if ($action == "client_artspec_delete") {
    if (!$perm->have_perm_area_action($area, $action)) {
        $notification->displayNotification("error", i18n("Permission denied"));
    } else {
        cDeleteArtSpec(cSecurity::toInteger($_GET['idartspec']));
    }
}

if ($action == "client_artspec_online") {
    if (!$perm->have_perm_area_action($area, "client_artspec_save")) {
        $notification->displayNotification("error", i18n("Permission denied"));
    } else {
        cSetArtSpecOnline(cSecurity::toInteger($_GET['idartspec']), $online);
    }
}

if ($action == "client_artspec_default") {
    if (!$perm->have_perm_area_action($area, "client_artspec_save")) {
        $notification->displayNotification("error", i18n("Permission denied"));
    } else {
        cSetArtSpecDefault(cSecurity::toInteger($_GET['idartspec']));
    }
}

$artSpecs = cGetArtSpecs(
    cSecurity::toInteger(cRegistry::getClientId()),
    cSecurity::toInteger(cRegistry::getLanguageId())
);

$list = new cGuiList();

$list->setCell(1, 1, i18n("Article specification"));
$list->setCell(1, 2, i18n("Options"));

$count = 2;

if (!empty($artSpecs)) {
    $backendUrl = cRegistry::getBackendUrl();

    $imagesPath = $backendUrl . $cfg['path']['images'];

    // Wrapper for the buttons
    $controls = new cHTMLDiv('', 'con_form_action_control');

    $link = new cHTMLLink();
    $link->setClass('con_img_button')
        ->setCLink($area, $frame, "client_artspec_edit")
        ->setContent(cHTMLImage::img($imagesPath . 'editieren.gif', i18n('Edit')));

    $olink = new cHTMLLink();
    $olink->setClass('con_img_button')
        ->setCLink($area, $frame, "client_artspec_online");

    $defLink = new cHTMLLink();
    $defLink->setClass('con_img_button')
        ->setCLink($area, $frame, "client_artspec_default");

    $dlink = new cHTMLLink();
    $dlink->setClass('con_img_button')
        ->setCLink($area, $frame, "client_artspec_delete")
        ->setContent(cHTMLImage::img($imagesPath . 'delete.gif', i18n('Delete')));

    foreach ($artSpecs as $id => $artSpecItem) {
        $link->setCustom("idartspec", $id);
        $olink->setCustom("idartspec", $id);
        $defLink->setCustom("idartspec", $id);
        $dlink->setCustom("idartspec", $id);

        if (($action == "client_artspec_edit") && ($idartspec == $id)) {
            $form = new cHTMLForm("artspec");
            $form->setVar("area", $area);
            $form->setVar("frame", $frame);
            $form->setVar("idartspec", $id);
            $form->setVar("action", "client_artspec_save");
            $form->setVar("online", $artSpecItem['online']);
            $inputBox = new cHTMLTextbox("artspectext", conHtmlentities(stripslashes($artSpecItem['artspec'])));
            $form->appendContent($inputBox->render());
            $form->appendContent(cHTMLButton::image($imagesPath . 'submit.gif', i18n('Save'), ['class' => 'con_img_button']));

            $list->setCell($count, 1, $form->render());
        } else {
            $list->setCell($count, 1, stripslashes($artSpecItem['artspec']));
        }

        if ($artSpecItem['online'] == 0) {
            // it is offline (std!)
            $olink->setContent(cHTMLImage::img($imagesPath . 'offline.gif', i18n('Make online')));
            $olink->setCustom("online", 1);
        } else {
            $olink->setContent(cHTMLImage::img($imagesPath . 'online.gif', i18n('Make offline')));
            $olink->setCustom("online", 0);
        }

        if ($artSpecItem['artspecdefault'] == 0) {
            $defLink->setContent(cHTMLImage::img($imagesPath . 'artikel_spez_inakt.gif', i18n("Make this article specification default")));
        } else {
            $defLink->setContent(cHTMLImage::img($imagesPath . 'artikel_spez_akt.gif', i18n("This is the default article specification")));
        }

        $controls->setContent([
            $link->render(), $olink->render(), $defLink->render(), $dlink->render()
        ]);
        $list->setCell($count, 2, $controls->render());

        $count++;
    }
} else {
    $list->setCell($count, 1, i18n("No article specifications found!"));
    $list->setCell($count, 2, '&nbsp;');
}
unset($form);

$form = new cGuiTableForm("artspec");
$form->setTableClass('generic con_block col_sm');
$form->setVar("area", $area);
$form->setVar("frame", $frame);
$form->setVar("action", "client_artspec_save");
$form->setHeader(i18n("Create new article specification"));
$inputBox = new cHTMLTextbox("artspectext");
$form->add(i18n("Specification name"), $inputBox->render());

$content = [];
if (!empty($list)) {
    // Wrap the list with a block
    $block = new cHTMLDiv($list->render(), 'con_block');
    $content[] = $block->render();
}
$content[] = $form->render();
$page->setContent($content);

$page->render();
