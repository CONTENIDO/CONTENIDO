<?php

/**
 * This file contains the backend page for editing language.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $contenido, $db, $tpl, $newidlang, $targetclient, $datetimeformat, $dateformat, $timeformat, $datetimelocale;
global $languagecode, $countrycode, $langname, $active, $direction, $error;

$perm = cRegistry::getPerm();
$cfg = cRegistry::getConfig();
$area = cRegistry::getArea();
$client = cRegistry::getClientId();
$action = cRegistry::getAction();
$frame = cRegistry::getFrame();

cIncludePlugins('languages');

if ($action == "lang_newlanguage" && (int) $newidlang > 0) {
    $idlang = $newidlang;
}

$oLanguage = new cApiLanguage($idlang);

$page = new cGuiPage("lang_edit");

// Script for refreshing Language Box in Header
$newOption = '';

$db2 = cRegistry::getDb();

if ($action == "lang_newlanguage") {

    $page->displayOk(i18n("Created new language successfully!"));

    // update language dropdown in header, but only for current client
    if ($targetclient == $client) {
        $msgNewLanguage = i18n("New language");

        $page->set("s", "NEW_LANG", $msgNewLanguage);
        $page->set("s", "ADD_LANG", "true");
    } else {
        $page->set("s", "ADD_LANG", "false");
    }

    $page->set("s", "RELOAD_LEFT_BOTTOM", "true");
    $page->set("s", "IDLANG", $idlang);
    $page->set("s", "REMOVE_LANG", "false");
    $page->set("s", "UPDATE_LANG", "false");
    $page->set("s", "FORM", "");
    $page->set("s", "NEW_LANG", "");
    $page->set("s", "NEW_LANG_NAME", "");
    $page->render();
} elseif ($action == "lang_deletelanguage") {

    $page->displayOk(i18n("Deleted language successfully!"));

    // finally delete from dropdown in header, but only for current client
    if ($targetclient == $client) {
        $page->set("s", "REMOVE_LANG", "true");
    } else {
        $page->set("s", "REMOVE_LANG", "false");
    }

    $page->set("s", "RELOAD_LEFT_BOTTOM", "true");
    $page->set("s", "IDLANG", $idlang);
    $page->set("s", "ADD_LANG", "false");
    $page->set("s", "UPDATE_LANG", "false");
    $page->set("s", "FORM", "");
    $page->set("s", "NEW_LANG", "");
    $page->set("s", "NEW_LANG_NAME", "");
    $page->render();
} else {
    // whether all data is ok
    $invalidData = false;
    if ($action == "lang_edit") {
        cCallPluginStore('languages');

        if (true === cString::validateDateFormat(stripslashes($datetimeformat))) {
            $oLanguage->setProperty("dateformat", "full", stripslashes($datetimeformat), $targetclient);
        } else {
            $invalidData = true;
            $page->displayError(i18n("Incorrect date/time format"));
        }
        if (true === cString::validateDateFormat(stripslashes($dateformat))) {
            $oLanguage->setProperty("dateformat", "date", stripslashes($dateformat), $targetclient);
        } elseif (false === $invalidData) {
            $invalidData = true;
            $page->displayError(i18n("Incorrect date format"));
        }
        if (true === cString::validateDateFormat(stripslashes($timeformat))) {
            $oLanguage->setProperty("dateformat", "time", stripslashes($timeformat), $targetclient);
        } elseif (false === $invalidData) {
            $invalidData = true;
            $page->displayError(i18n("Incorrect time format"));
        }
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

                // Set utf-8 as encoding if CON_UTF8 constant is defined
                if (defined('CON_UTF8') && CON_UTF8 !== false) {
                        $sencoding = 'utf-8';
                }

                if (false === $invalidData) {
                    if (false === langEditLanguage($idlang, $langname, $sencoding, $active, $direction)) {
                        $page->displayError(i18n("An error occurred during saving the changes"));
                    } else {
                        $page->displayOk(i18n("Changes saved"));
                    }
                }
            }

            $tpl->reset();

            $sql = "SELECT
                        A.idlang AS idlang, A.name AS name, A.active as active, A.encoding as encoding,
                        A.direction as direction, B.idclient AS idclient
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

            $charsets = [];
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

            // Display encoding options only if CON_UTF8 constant is not set
            if (!defined('CON_UTF8') || (defined('CON_UTF8') && CON_UTF8 === false)) {
                $eselect = new cHTMLSelectElement("sencoding");
                $eselect->setStyle('width:255px');
                $eselect->autoFill($charsets);
                $eselect->setDefault((($db->f("encoding") != "") ? $db->f("encoding") : 'utf-8'));
            } else {
                $eselect = 'utf-8';
            }

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
            $directionSelect->autoFill([
                "ltr" => i18n("Left to right"),
                "rtl" => i18n("Right to left")
            ]);
            $directionSelect->setDefault($db->f("direction"));

            $fulldateformat = new cHTMLTextbox("datetimeformat", $oLanguage->getProperty("dateformat", "full", $targetclient), 40);

            $dateformat = new cHTMLTextbox("dateformat", $oLanguage->getProperty("dateformat", "date", $targetclient), 40);

            $timeformat = new cHTMLTextbox("timeformat", $oLanguage->getProperty("dateformat", "time", $targetclient), 40);

            $dateLocale = new cHTMLTextbox("datetimelocale", $oLanguage->getProperty("dateformat", "locale", $targetclient), 40);

            $form->setHeader(i18n("Edit language"));
            $oTxtLang = new cHTMLTextBox("langname", conHtmlSpecialChars($db->f("name")), 40, 255);
            $form->add(i18n("Language name"), $oTxtLang->render());
            $oCheckbox = new cHTMLCheckbox("active", "1", "active1", $db->f("active"));
            $form->add(i18n("Active"), $oCheckbox->toHtml(false));

            $form->addSubHeader(i18n("Language"));

            $form->add(i18n("Encoding"), $eselect);

            $form->add(i18n("Language"), $languagecode->render());
            $form->add(i18n("Country"), $countrycode->render());
            $form->add(i18n("Text direction"), $directionSelect);

            $form->addSubHeader(i18n("Time format"));

            $infoButton = new cGuiBackendHelpbox(i18n("FORMAT_DATE_TIME"));
            $form->add(i18n("Date/Time format"), $fulldateformat->render() . ' ' . $infoButton->render());
            $infoButton->setHelpText(i18n("FORMAT_DATE"));
            $form->add(i18n("Date format"), $dateformat->render() . ' ' . $infoButton->render());
            $infoButton->setHelpText(i18n("FORMATE_TIME"));
            $form->add(i18n("Time format"), $timeformat->render() . ' ' . $infoButton->render());
            $infoButton->setHelpText(i18n("LANGUAGE_DATE_TIME"));
            $form->add(i18n("Date/Time locale"), $dateLocale->render() . ' ' . $infoButton->render());

            // update language dropdown in header, but only for current client
            if ($targetclient == $client) {
                // update dropdown in header
                $languageName = $db->f("name");

                $page->set("s", "UPDATE_LANG", "true");
                $page->set("s", "NEW_LANG_NAME", $languageName);

            } else {
                $page->set("s", "UPDATE_LANG", "false");
                $page->set("s", "NEW_LANG_NAME", "");
            }

            if ($action != '') {
                $page->set('s', 'CONTENIDO', $contenido);
                $page->set("s", "RELOAD_LEFT_BOTTOM", "true");
            } else {
                $page->set("s", "RELOAD_LEFT_BOTTOM", "false");
            }

            $page->set("s", "IDLANG", $idlang);
            $page->set("s", "ADD_LANG", "false");
            $page->set("s", "REMOVE_LANG", "false");
            $page->set("s", "NEW_LANG", "");

            $page->set("s", "FORM", $form->render());
            $page->render();
        }
    }
}
