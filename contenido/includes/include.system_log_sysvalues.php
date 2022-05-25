<?php

/**
 * This file contains the system log display backend page.
 *
 * @package Core
 * @subpackage Backend
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$cfg = cRegistry::getConfig();
$action = cRegistry::getAction();

cInclude('includes', 'functions.general.php');

$page = new cGuiPage('system_log_sysvalues');

$path = $cfg['path']['frontend'] . '/' . $cfg['path']['logs'];
$numberOfLines = $cfg['system_log']['number_of_lines'];

$logfile = isset($_REQUEST['logfile']) ? basename($_REQUEST['logfile']) : '';

// memory limit
$memoryLimit = machineReadableSize(ini_get('memory_limit'));
if ($memoryLimit <= 0) {
    $memoryLimit = $cfg['system_log']['default_memory_limit'];
}

// process the actions delete / clear log
if ($action == 'deletelog' && !empty($logfile)) {
    if (cFileHandler::remove($path . cSecurity::escapeString($logfile))) {
        $page->displayOk(sprintf(i18n('Logfile "%s" deleted successfully'), $logfile));
    }
    $logfile = "";
} else if ($action == 'clearlog' && !empty($logfile)) {
    $lines = file($path . $logfile);
    $lines = array_slice($lines, cSecurity::toInteger($_REQUEST['keepLines']) * -1);
    cFileHandler::write($path . $logfile, implode('', $lines));
} else if ($action == 'showLog' && !empty($logfile)) {
    if (!empty($_REQUEST['numberOfLines'])) {
        $numberOfLines = cSecurity::toInteger($_REQUEST['numberOfLines']);
    }
}

$files = array();
foreach (new DirectoryIterator($path) as $fileInfo) {
    if (in_array($fileInfo->getFilename(), $cfg['system_log']['allowed_filenames'])) {
        $files[] = $path . $fileInfo->getFilename();
    }
}

if (!empty($files)) {
    $logHeader = new cHTMLDiv('', 'log-header');

    $logDirectory = new cHTMLSpan($path);
    $logHeader->appendContent($logDirectory);

    // create the config file select
    $select = new cHTMLSelectElement('logfile');
    foreach ($files as $file) {
        $fileInfo = cFileHandler::info($file);
        $title = basename($file) . ' (';
        $title .= humanReadableSize($fileInfo['size']);
        $title .= ')';
        $disabled = (!cFileHandler::exists($file) || $fileInfo['size'] > $memoryLimit) ? true : false;
        $element = new cHTMLOptionElement($title, basename($file));
        if ($disabled) {
            $element->setClass('color6');
            $element->updateAttribute('data-notreadable', '1');
        }
        if ($logfile == basename($file)) {
            $element->setSelected(true);
        }
        $select->appendOptionElement($element);
    }
    $logHeader->appendContent($select);

    $logHeader->appendContent(
        new cHTMLSpan('', 'text_error pdr5 pdl5 js-message')
    );

    // create the line number selection
    $link = new cHTMLLink('#');
    $link->setClass('js-action-show-log');
    $image = new cHTMLImage('images/submit.gif');
    $link->appendContent($image);
    $div = new cHTMLDiv(array(
        new cHTMLSpan(i18n('Show ')),
        new cHTMLTextbox('number-of-lines', $numberOfLines, 3),
        new cHTMLSpan(i18n(' lines')),
        $link
    ), 'right');
    $logHeader->appendContent($div);

    $page->appendContent($logHeader);

    if (empty($logfile)) {
        $fileName = $files[0];
    } else {
        $fileName = $path . cSecurity::escapeString($logfile);
    }

    $fileInfo = cFileHandler::info($fileName);

    if (cFileHandler::exists($fileName) && $fileInfo['size'] < $memoryLimit) {
        $lines = file($fileName);
        $lines = array_splice($lines, $numberOfLines * -1);
    } else {
        $lines = [];
    }

    // create the textarea
    $textarea = new cHTMLTextarea('logfile-content', implode('', $lines));
    $textarea->setAttribute('readonly', 'readonly');
    $textarea->appendStyleDefinition('width', '99%');
    $textarea->appendStyleDefinition('height', '200px');
    $textarea->setAttribute('cols', '100');

    $page->appendContent($textarea);

    // create the action buttons
    $logFooter = new cHTMLDiv('', 'log-footer');
    $logFooter->appendContent(new cHTMLSpan(i18n('Clear log file, keep last ')));
    $input = new cHTMLTextbox('keep-last-lines', '20');
    $input->setWidth(2);
    $logFooter->appendContent($input);
    $logFooter->appendContent(new cHTMLSpan(i18n(' lines')));
    $link = new cHTMLLink('#');
    $link->setClass('js-action-clear-log');
    $image = new cHTMLImage('images/but_ok.gif');
    $link->appendContent($image);
    $logFooter->appendContent($link);

    $link = new cHTMLLink('#');
    $link->setClass('right js-action-delete-log');
    $link->appendContent(i18n('Delete log file'));
    $image = new cHTMLImage('images/delete.gif');
    $link->appendContent($image);
    $logFooter->appendContent($link);

    $page->appendContent($logFooter);

    $tooLargeMsg = i18n("The content of the selected log file can't be displayed, since it is not readable or too large.");
    $sExecScript = <<<JS
        <script type="text/javascript">
        (function(Con, $) {
            $(function() {
                new Con.SystemLogSysValues({
                    root: $("#system_log_sysvalues"),
                    fileIsTooLargeMsg: "{$tooLargeMsg}"
                });
            });
        })(Con, Con.$);
        </script>
JS;

    $page->addScript($sExecScript);

} else {
    $page->displayError(i18n('No log files found!'));
}

$page->render();
