<?php
/**
 * This file contains the cronjob for newsletter plugin.
 *
 * @package    Plugin
 * @subpackage Newsletter
 *
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

global $cfg;

// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');

if (class_exists('NewsletterJobCollection') && (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs')) {
    $oJobs = new NewsletterJobCollection();
    $oJobs->setWhere('status', 1);
    $oJobs->setWhere('use_cronjob', 1);
    $oJobs->setLimit('0', '1'); // Load only one job at a time
    $oJobs->setOrder('created DESC'); // Newest job will be run first
    $oJobs->query();

    if ($oJob = $oJobs->next()) {
        // Active jobs found, run job
        $oJob->runJob();
    } else {
        // Nothing to do, check dead jobs
        $oJobs->resetQuery();
        $oJobs->setWhere('status', 2);
        $oJobs->setWhere('use_cronjob', 1);
        $oJobs->setLimit('0', '1'); // Load only one job at a time
        $oJobs->setOrder('created DESC'); // Newest job will be run first
        $oJobs->query();

        if ($oJob = $oJobs->next()) {
            // Maybe hanging jobs found, run job
            $oJob->runJob();
        }
    }
}

?>