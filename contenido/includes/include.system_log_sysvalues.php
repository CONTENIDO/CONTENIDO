<?php
/**
 * Project: CONTENIDO Content Management System Description: Output of important
 * system variables
 *
 * @package CONTENIDO Backend Includes
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release <= 4.6
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.general.php');

$page = new cGuiPage('system_log_sysvalues');

$path = $cfg['path']['frontend'] . DS . $cfg['path']['logs'];
$numberOfLines = 100;

$logfile = basename($_REQUEST['logfile']);

// process the actions delete / clear log
if ($action == 'deletelog' && !empty($logfile)) {
    if (cFileHandler::remove($path . $logfile)) {
        $page->displayInfo(sprintf(i18n('Logfile "%s" deleted successfully'), $logfile));
    }
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
foreach (glob($path . '{*.txt,*.log}', GLOB_BRACE) as $filename) {
	$files[] = $filename;
}

if (!empty($files)) {
    $logHeader = new cHTMLDiv('', 'log-header');

    $logDirectory = new cHTMLSpan($path);
    $logHeader->appendContent($logDirectory);

    // create the config file select
    $select = new cHTMLSelectElement('logfile');
    foreach ($files as $file) {
        $title = basename($file) . ' (';
        $title .= humanReadableSize(filesize($file));
        $title .= ')';
        $element = new cHTMLOptionElement($title, basename($file));
        if ($logfile == basename($file)) {
            $element->setSelected(true);
        }
        $select->appendOptionElement($element);
    }
    $logHeader->appendContent($select);

    // create the line number selection
    $link = new cHTMLLink('javascript:void(0)');
    $link->setEvent('click', 'showLog()');
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

    // create the textarea
    if (empty($logfile)) {
        $filename = $files[0];
    } else {
        $filename = $path . cSecurity::escapeString($logfile);
    }

    if (cFileHandler::exists($filename)) {
        $lines = file($filename);
        $lines = array_splice($lines, $numberOfLines * -1);
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
        $link = new cHTMLLink('javascript:void(0)');
        $link->setEvent('click', 'clearLogFile()');
        $image = new cHTMLImage('images/but_ok.gif');
        $link->appendContent($image);
        $logFooter->appendContent($link);

        $link = new cHTMLLink('javascript:void(0)');
        $link->setClass('right');
        $link->setEvent('click', 'deleteLogFile()');
        $link->appendContent(i18n('Delete log file'));
        $image = new cHTMLImage('images/delete.gif');
        $link->appendContent($image);
        $logFooter->appendContent($link);

        $page->appendContent($logFooter);
    }
} else {
    $page->displayError(i18n('No log files found!'));
}

$page->render();
