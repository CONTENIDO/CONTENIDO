<?php

/**
 * This file contains the system integrity backend page.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Mischa Holz
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$page = new cGuiPage("system_integrity");

$test = new cSystemtest($cfg);
$test->runTests();
$results = $test->getResults();

foreach ($results as $result) {
    if ($result["severity"] == cSystemtest::C_SEVERITY_NONE) {
        continue;
    }

    if ($result["result"] == true) {
        $page->set("d", "IMAGESOURCE", $cfg['path']['contenido_fullhtml']."images/but_ok.gif");
    } elseif ($result["severity"] == cSystemtest::C_SEVERITY_WARNING) {
        $page->set("d", "IMAGESOURCE", $cfg['path']['contenido_fullhtml']."images/icon_warning.gif");
    } elseif ($result["severity"] == cSystemtest::C_SEVERITY_ERROR) {
        $page->set("d", "IMAGESOURCE", $cfg['path']['contenido_fullhtml']."images/icon_fatalerror.gif");
    } elseif ($result["severity"] == cSystemtest::C_SEVERITY_INFO) {
        $page->set("d", "IMAGESOURCE", $cfg['path']['contenido_fullhtml']."images/info.gif");
    }
    $page->set("d", "HEADLINE", $result["headline"]);
    $page->set("d", "MESSAGE", $result["message"]);
    $page->next();
}

$page->set("s", "RESULTS", i18n("Results"));
$page->render();
