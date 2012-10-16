<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Cron Job to move old statistics into the stat_archive table
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    0.3.1
 * @author     Björn Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   $Id: run_newsletter_job.php 1157 2010-05-20 14:10:43Z xmurrix $:
 * }}
 */

if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}

global $cfg, $area;

// Contenido startup process
include_once('../includes/startup.php');

if (!isRunningFromWeb || function_exists('runJob') || $area == 'cronjobs') {
    $oJobs = new cNewsletterJobCollection();
    $oJobs->setWhere('status', 1);
    $oJobs->setWhere('use_cronjob', 1);
    $oJobs->setLimit('0', '1');         // Load only one job at a time
    $oJobs->setOrder('created DESC');    // Newest job will be run first
    $oJobs->query();

    if ($oJob = $oJobs->next()) {
        // Active jobs found, run job
        $oJob->runJob();
    } else {
        // Nothing to do, check dead jobs
        $oJobs->resetQuery();
        $oJobs->setWhere('status', 2);
        $oJobs->setWhere('use_cronjob', 1);
        $oJobs->setLimit('0', '1');         // Load only one job at a time
        $oJobs->setOrder('created DESC');    // Newest job will be run first
        $oJobs->query();

        if ($oJob = $oJobs->next()) {
            // Maybe hanging jobs found, run job
            $oJob->runJob();
        }
    }
}

?>