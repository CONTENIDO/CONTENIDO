<?php
/**
 * This file contains the backend page for editing language.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel, Jan Lengowski
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

includePlugins('languages');

if ($action == "lang_newlanguage" && (int) $newidlang > 0) {
    $idlang = $newidlang;
}

function generateInfoIcon($title, $id = '', $class = 'vAlignMiddle', $img = 'images/info.gif') {
    return  '<img class="' . $class . '" id="' . $id . '" title="'. $title . '" src="' . $img . '">';
}

$oLanguage = new cApiLanguage($idlang);

$page = new cGuiPage("lang_edit");

// Script for refreshing Language Box in Header
$newOption = '';

$db2 = cRegistry::getDb();

$sReload = '<script type="text/javascript">
            (function() {
                var left_bottom = top.content.left.left_bottom;
                if (left_bottom) {
                    var href = left_bottom.location.href;
                    href = href.replace(/&idlang[^&]*/, \'\');
                    left_bottom.location.href = href+"&idlang="+"' . $idlang . '";
                }
            })();
            </script>';

if ($action == "lang_newlanguage") {

    $page->displayInfo(i18n("Created new language successfully!"));

    // update language dropdown in header, but only for current client
    if ($targetclient == $client) {
        $jsCode = '<script type="text/javascript">
                        (function() {
                            top.header.languageSelectAdd("' . i18n("New language") . '", "' . $idlang . '");
                        })();
                      </script>';
        $page->addScript($jsCode);
    }

    if (!empty($sReload)) {
        $page->addScript($sReload);
    }
    $page->render();
} elseif ($action == "lang_deletelanguage") {

    $page->displayInfo(i18n("Deleted language successfully!"));

    // finally delete from dropdown in header, but only for current client
    if ($targetclient == $client) {
        $jsCode = '<script type="text/javascript">
                        (function() {
                            top.header.languageSelectRemove(' . $idlang . ');
                        })();
                      </script>';
        $page->addScript($jsCode);
    }

    if (!empty($sReload)) {
        $page->addScript($sReload);
    }
    $page->render();
} else {

    if ($action == "lang_edit") {
        callPluginStore('languages');

        $oLanguage->setProperty("dateformat", "full", stripslashes($datetimeformat), $targetclient);
        $oLanguage->setProperty("dateformat", "date", stripslashes($dateformat), $targetclient);
        $oLanguage->setProperty("dateformat", "time", stripslashes($timeformat), $targetclient);
        $oLanguage->setProperty("dateformat", "locale", stripslashes($datetimelocale), $targetclient);

        $oLanguage->setProperty("language", "code", stripslashes($languagecode), $targetclient);
        $oLanguage->setProperty("country", "code", stripslashes($countrycode), $targetclient);
    }

    if (!$perm->have_perm_area_action($area, $action)) {
        $page->displayCriticalError(i18n("Permission denied"));
    } else {
        if (!isset($idlang) && $action != "lang_new") {
            $page->displayCriticalError("no language id given. Usually, this shouldn't happen, except if you played around with your system. if you didn't play around, please report a bug.");
        } else {
            if (($action == "lang_edit") && ($perm->have_perm_area_action($area, $action))) {
                langEditLanguage($idlang, $langname, $sencoding, $active, $direction);
                $page->displayInfo(i18n("Changes saved"));
            }

            $tpl->reset();

            $sql = "SELECT
                        A.idlang AS idlang, A.name AS name, A.active as active, A.encoding as encoding, A.direction as direction,
                        B.idclient AS idclient
                    FROM
                        " . $cfg["tab"]["lang"] . " AS A,
                        " . $cfg["tab"]["clients_lang"] . " AS B
                    WHERE
                        A.idlang = " . cSecurity::toInteger($idlang) . " AND
                        B.idlang = " . cSecurity::toInteger($idlang);

            $db->query($sql);
            $db->nextRecord();

            $form = new cGuiTableForm("lang_properties");
            $form->setVar("idlang", $idlang);
            $form->setVar("targetclient", $db->f("idclient"));
            $form->setVar("action", "lang_edit");
            $form->setVar("area", $area);
            $form->setVar("frame", $frame);

            $charsets = array();
            foreach ($cfg['AvailableCharsets'] as $charset) {
                $charsets[$charset] = $charset;
            }

            if ($error) {
                echo $error;
            }
            $isoCollection = new cApiIso6392Collection();
            $isoCollection->query();
            $iso_639_2_result = $isoCollection->fetchArray('iso', 'en');
            array_multisort($iso_639_2_result);

            $iso3166Collection = new cApiIso3166Collection();
            $iso3166Collection->query();
            $iso_3166_result = $iso3166Collection->fetchArray('iso', 'en');
            array_multisort($iso_3166_result);

            $eselect = new cHTMLSelectElement("sencoding");
            $eselect->setStyle('width:255px');
            $eselect->autoFill($charsets);
            $eselect->setDefault($db->f("encoding"));



            $languagecode = new cHTMLSelectElement("languagecode");
            $languagecode->setStyle('width:255px');
            $languagecode->autoFill($iso_639_2_result);
            $languagecode->setDefault($oLanguage->getProperty("language", "code", $targetclient));

            $countrycode = new cHTMLSelectElement("countrycode");
            $countrycode->setStyle('width:255px');
            $countrycode->autoFill($iso_3166_result);
            $countrycode->setDefault($oLanguage->getProperty("country", "code", $targetclient));

            $directionSelect = new cHTMLSelectElement("direction");
            $directionSelect->setStyle('width:255px');
            $directionSelect->autoFill(array(
                "ltr" => i18n("Left to right"),
                "rtl" => i18n("Right to left")
            ));
            $directionSelect->setDefault($db->f("direction"));

            $fulldateformat = new cHTMLTextbox("datetimeformat", $oLanguage->getProperty("dateformat", "full", $targetclient), 40);

            $dateformat = new cHTMLTextbox("dateformat", $oLanguage->getProperty("dateformat", "date", $targetclient), 40);

            $timeformat = new cHTMLTextbox("timeformat", $oLanguage->getProperty("dateformat", "time", $targetclient), 40);

            $dateLocale = new cHTMLTextbox("datetimelocale", $oLanguage->getProperty("dateformat", "locale", $targetclient), 40);

            $form->addHeader(i18n("Edit language"));
            $oTxtLang = new cHTMLTextBox("langname", conHtmlSpecialChars($db->f("name")), 40, 255);
            $form->add(i18n("Language name"), $oTxtLang->render());
            $oCheckbox = new cHTMLCheckbox("active", "1", "active1", $db->f("active"));
            $form->add(i18n("Active"), $oCheckbox->toHTML(false));

            $form->addSubHeader(i18n("Language"));
            $form->add(i18n("Encoding"), $eselect);
            $form->add(i18n("Language"), $languagecode->render());
            $form->add(i18n("Country"), $countrycode->render());
            $form->add(i18n("Text direction"), $directionSelect);

            $form->addSubHeader(i18n("Time format"));

            $infoButton = new cGuiBackendHelpbox(i18n("FORMAT_DATE_TIME"));
            $form->add(i18n("Date/Time format"), $fulldateformat->render().' '.$infoButton->render());
            $infoButton->setHelpText(i18n("FORMAT_DATE"));
            $form->add(i18n("Date format"), $dateformat->render().' '.$infoButton->render());
            $infoButton->setHelpText(i18n("FORMATE_TIME"));
            $form->add(i18n("Time format"), $timeformat->render().' '.$infoButton->render());
            $infoButton->setHelpText(i18n("LANUAGE_DATE_TIME"));
            $form->add(i18n("Date/Time locale"), $dateLocale->render().' '.$infoButton->render());

            $page->setContent($form);

            // update language dropdown in header, but only for current client
            if ($targetclient == $client) {
                // update dropdown in header
                $jsCode = '<script type="text/javascript">
                            (function() {
                                top.header.languageSelectUpdate("' . $db->f("name") . '", "' . $idlang . '");
                            })();
                          </script>';

                $page->addScript($jsCode);
            }

            if ($_REQUEST['action'] != '') {
                if (!empty($sReload)) {
                    $page->addScript($sReload);
                }
            }

            $page->render();
        }
    }
}
