<?php
/**
 * This file contains the system integrity backend page.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Mischa Holz
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
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
    } else if ($result["severity"] == cSystemtest::C_SEVERITY_WARNING) {
        $page->set("d", "IMAGESOURCE", $cfg['path']['contenido_fullhtml']."images/icon_warning.gif");
    } else if ($result["severity"] == cSystemtest::C_SEVERITY_ERROR) {
        $page->set("d", "IMAGESOURCE", $cfg['path']['contenido_fullhtml']."images/icon_fatalerror.gif");
    } else if ($result["severity"] == cSystemtest::C_SEVERITY_INFO) {
        $page->set("d", "IMAGESOURCE", $cfg['path']['contenido_fullhtml']."images/info.gif");
    }
    $page->set("d", "HEADLINE", $result["headline"]);
    $page->set("d", "MESSAGE", $result["message"]);
    $page->next();
}

$page->set("s", "RESULTS", i18n("Results"));
$page->render();
?>