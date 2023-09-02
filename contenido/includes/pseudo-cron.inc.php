<?php

/**
 * *************************************************************************

  pseudo-cron v1.2.1.con // modified version for CONTENIDO
  (c) 2003 Kai Blankenhorn
  www.bitfolge.de/en
  kaib@bitfolge.de

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

 * ***************************************************************************

  Usually regular tasks like backup up the site's database are run using cron
  jobs. With cron jobs, you can exactly plan when a certain command is to be
  executed. But most homepage owners can't create cron jobs on their web
  server - providers demand some extra money for that.
  The only thing that's certain to happen quite regularly on a web page are
  page requests. This is where pseudo-cron comes into play: With every page
  request it checks if any cron jobs should have been run since the previous
  request. If there are, they are run and logged.

  Pseudo-cron uses a syntax very much like the Unix cron's one. For an
  overview of the syntax used, see a page of the UNIXGEEKS. The syntax
  pseudo-cron uses is different from the one described on that page in
  the following points:

  -  there is no user column
  -  the executed command has to be an include()able file (which may contain further PHP code)


  All job definitions are made in a text file on the server with a
  user-definable name. A valid command line in this file is, for example:

 *   2   1,15   *   *   samplejob.inc.php

  This runs samplejob.inc.php at 2am on the 1st and 15th of each month.


  Features:
  -  runs any PHP script
  -  periodical or time-controlled script execution
  -  logs all executed jobs
  -  can be run from an IMG tag in an HTML page
  -  follow Unix cron syntax for crontabs


  Usage:
  -  Modify the variables in the config section below to match your server.
  -  Write a PHP script that does the job you want to be run regularly. Be
  sure that any paths in it are relative to the script that will run
  pseudo-cron in the end.
  -  Set up your crontab file with your script
  -  Wait for the next scheduled run :)


  Note:
  You can log messages to pseudo-cron's log file by calling
  logMessage("log a message", $PC_writeDir, $PC_useLog, $PC_debug);


  Changelog:

  v1.2.1.con   11-28-03
  changed: removed all global variables
  changed: renamed intern variables
  changed: intern function calls
  changed: extended debug information
  modified by horwath@opensa.org

  v1.2.1   02-03-03
  fixed:    jobs may be run too often under certain conditions
  added:    global debug switch
  changed: typo in imagecron.php which prevented it from working


  v1.2   01-31-03
  added:   more documentation
  changed: log file should now be easier to use
  changed: log file name


  v1.1   01-29-03
  changed: renamed pseudo-cron.php to pseudo-cron.inc.php
  fixed:   comments at the end of a line don't work
  fixed:   empty lines in crontab file create nonsense jobs
  changed: log file grows big very quickly
  changed: included config file in main file to avoid directory confusion
  added:   day of week abbreviations may now be used (three letters, english)


  v1.0   01-17-03
  inital release

 * ************************************************************************* */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @global array $cfg
 */

/* * ************************************* */
/*      config section               */
/* * ************************************* */

// || PLEASE NOTE:
// || all paths used here and in cron scripts
// || must be absolute or relative to the script which includes pseudo-cron.inc.php!
// the file that contains the job descriptions
// for a description of the format, see http://www.unixgeeks.org/security/newbie/unix/cron-1.html
// and http://www.bitfolge.de/pseudocron
$PC_cronTab = $cfg['path']['contenido_cronlog'] . 'crontab.txt';
$PC_localCronTab = $cfg['path']['contenido_cronlog'] . 'crontab.local.txt';

// the directory where the script can store information on completed jobs and its log file
// include trailing slash
$PC_writeDir = $cfg['path']['contenido_cronlog'];

// the directory where the script can store information on completed jobs and its log file
// include trailing slash
$PC_jobDir = cRegistry::getBackendPath() . $cfg['path']['cronjobs'];

// store directory information
$PC_reqDir = getcwd();

// control logging, 1=use log file, 0=don't use log file
$PC_useLog = 1;

// turn on / off debugging output
// DO NOT use this on live servers!
$PC_debug = false;

/* * ************************************* */
/*      don't change anything here      */
/* * ************************************* */

define("PC_MINUTE", 1);
define("PC_HOUR", 2);
define("PC_DOM", 3);
define("PC_MONTH", 4);
define("PC_DOW", 5);
define("PC_CMD", 7);
define("PC_CRONLINE", 8);

if ($PC_debug) {
    echo "<pre>Configuration:";
    echo "\nPC_cronTab = " . $PC_cronTab;
    echo "\nPC_writeDir = " . $PC_writeDir;
    echo "\nPC_jobDir = " . $PC_jobDir;
    echo "\nPC_reqDir = " . $PC_reqDir;
    echo "\nPC_useLog = " . $PC_useLog;
    echo "\n";
}

global $bJobRunned;

$bJobRunned = false;

chdir($PC_jobDir);
$PC_jobs = parseCronFile($PC_cronTab, $PC_debug);

for ($i = 0; $i < count($PC_jobs); $i++) {
    $bJobRunned = true;
    runJob($PC_jobs[$i], $PC_jobDir, $PC_writeDir, $PC_useLog, $PC_debug);
}

$PC_jobs = parseCronFile($PC_localCronTab, $PC_debug);
for ($i = 0; $i < count($PC_jobs); $i++) {
    $bJobRunned = true;
    runJob($PC_jobs[$i], $PC_jobDir, $PC_writeDir, $PC_useLog, $PC_debug);
}
chdir($PC_reqDir);

if ($PC_debug) {
    echo "\n</pre>";
}

/**
 * Logs an entry into the cron log file.
 *
 * @param string $msg
 * @param string $PC_writeDir
 * @param string $PC_useLog
 * @param bool $PC_debug
 */
function logMessage(string $msg, string $PC_writeDir, string $PC_useLog, bool $PC_debug)
{
    if ($PC_useLog == 1) {
        $logfile = $PC_writeDir . "pseudo-cron.log";

        if (is_writable($logfile)) {
            $file = fopen($logfile, "ab");
            if ($msg[cString::getStringLength($msg) - 1] != "\n") {
                $msg.="\r\n";
            }
            if ($PC_debug) {
                echo $msg;
            }
            fputs($file, date("r", time()) . "  " . $msg);
            fclose($file);
        }
    }
}

/**
 * Trims preceding zeros from given value.
 *
 * @param int|string $number
 * @return int
 */
function lTrimZeros($number): int
{
    return (int)ltrim($number, '0');
}

/**
 * Parses a crontab schedule expression item.
 *
 * @param string $element
 * @param array $targetArray
 * @param int $numberOfElements
 */
function parseElement(string $element, array &$targetArray, int $numberOfElements)
{
    $subElements = explode(",", $element);
    for ($i = 0; $i < $numberOfElements; $i++) {
        $targetArray[$i] = $subElements[0] == "*";
    }

    $pattern = "~^(\\*|([0-9]{1,2})(-([0-9]{1,2}))?)(/([0-9]{1,2}))?$~";
    for ($i = 0; $i < count($subElements); $i++) {
        if (preg_match($pattern, $subElements[$i], $matches)) {
            if ($matches[1] == "*") {
                $matches[2] = 0;      // from
                $matches[4] = $numberOfElements;      //to
            } elseif (!array_key_exists(4, $matches) || $matches[4] == "") {
                $matches[4] = $matches[2];
            }
            if (array_key_exists(5, $matches)) {
                if ($matches[5][0] != "/") {
                    $matches[6] = 1;      // step
                }
            } else {
                $matches[6] = 1;      // step
            }
            $begin = lTrimZeros($matches[2]);
            $end = lTrimZeros($matches[4]);
            $step = lTrimZeros($matches[6]);
            for ($j = $begin; $j <= $end; $j += $step) {
                $targetArray[$j] = TRUE;
            }
        }
    }
}

/**
 * Decreases the passed date array by amount and date unit.
 *
 * @param array $dateArr
 * @param int $amount
 * @param string $unit
 * @param bool $PC_debug
 */
function decDate(array &$dateArr, int $amount, string $unit, bool $PC_debug)
{
    if ($PC_debug) {
        echo sprintf(
            "Decreasing from %02d.%02d. %02d:%02d by %d %6s ",
            $dateArr['mday'], $dateArr['mon'], $dateArr['hours'], $dateArr['minutes'], $amount, $unit
        );
    }
    if ($unit == "mday") {
        $dateArr["hours"] = 23;
        $dateArr["minutes"] = 59;
        $dateArr["seconds"] = 59;
        $dateArr["mday"] -= $amount;
        $dateArr["wday"] -= $amount % 7;
        if ($dateArr["wday"] < 0) {
            $dateArr["wday"]+=7;
        }
        if ($dateArr["mday"] < 1) {
            $dateArr["mon"]--;
            switch ($dateArr["mon"]) {
                case 0:
                    $dateArr["mon"] = 12;
                    $dateArr["year"]--;
                // fall through
                case 1:
                case 3:
                case 5:
                case 7:
                case 8:
                case 10:
                case 12:
                    $dateArr["mday"] = 31;
                    break;
                case 4:
                case 6:
                case 9:
                case 11:
                    $dateArr["mday"] = 30;
                    break;
                case 2:
                    $dateArr["mday"] = 28;
                    break;
            }
        }
    } elseif ($unit == "hour") {
        if ($dateArr["hours"] == 0) {
            decDate($dateArr, 1, "mday", $PC_debug);
        } else {
            $dateArr["minutes"] = 59;
            $dateArr["seconds"] = 59;
            $dateArr["hours"]--;
        }
    } elseif ($unit == "minute") {
        if ($dateArr["minutes"] == 0) {
            decDate($dateArr, 1, "hour", $PC_debug);
        } else {
            $dateArr["seconds"] = 59;
            $dateArr["minutes"]--;
        }
    }
    if ($PC_debug) {
        echo sprintf(
            "to %02d.%02d. %02d:%02d\n",
            $dateArr['mday'], $dateArr['mon'], $dateArr['hours'], $dateArr['minutes']
        );
    }
}

/**
 * Returns the last scheduled run time of a job.
 *
 * @param array $job
 * @param bool $PC_debug
 * @return int
 */
function getLastScheduledRunTime(array $job, bool $PC_debug): int
{
    $dateArr = getdate();
    $minutesBack = 0;

    while (
        $minutesBack < 525600 && (
            empty($job[PC_MINUTE][$dateArr["minutes"]]) ||
            empty($job[PC_HOUR][$dateArr["hours"]]) ||
            (empty($job[PC_DOM][$dateArr["mday"]]) || empty($job[PC_DOW][$dateArr["wday"]])) OR
            empty($job[PC_MONTH][$dateArr["mon"]])
        )
    ) {
        if (empty($job[PC_DOM][$dateArr["mday"]]) || empty($job[PC_DOW][$dateArr["wday"]])) {
            decDate($dateArr, 1, "mday", $PC_debug);
            $minutesBack += 1440;
            continue;
        }
        if (empty($job[PC_HOUR][$dateArr["hours"]])) {
            decDate($dateArr, 1, "hour", $PC_debug);
            $minutesBack += 60;
            continue;
        }
        if (empty($job[PC_MINUTE][$dateArr["minutes"]])) {
            decDate($dateArr, 1, "minute", $PC_debug);
            $minutesBack++;
        }
    }

    if ($PC_debug) {
        print_r($dateArr);
    }

    return mktime(
        $dateArr["hours"], $dateArr["minutes"], 0, $dateArr["mon"], $dateArr["mday"], $dateArr["year"]
    );
}

/**
 * Returns the file name (full path + file name) to a job.
 *
 * @param string $jobName
 * @param string $PC_writeDir
 * @return string
 */
function getJobFileName(string $jobName, string $PC_writeDir): string
{
    return $PC_writeDir . urlencode($jobName) . ".job";
}

/**
 * Return last run time of a job.
 *
 * @param string $jobName
 * @param string $PC_writeDir
 * @return int
 */
function getLastActialRunTime(string $jobName, string $PC_writeDir): int
{
    $jobFile = getJobFileName($jobName, $PC_writeDir);
    if (cFileHandler::exists($jobFile)) {
        $file = fopen($jobFile, "rb");
        $lastRun = fgets($file, 100);
        fclose($file);
        if (is_numeric($lastRun)) {
            return (int) $lastRun;
        }
    }
    return 0;
}

/**
 * Marks last run time of a job.
 *
 * @param string $jobName
 * @param int $lastRun
 * @param string $PC_writeDir
 */
function markLastRun(string $jobName, int $lastRun, string $PC_writeDir)
{
    $jobFile = getJobFileName($jobName, $PC_writeDir);

    if ($file = @fopen($jobFile, "w")) {
        fputs($file, $lastRun);
        fclose($file);
    } else {
        try {
            cWarning("Could not write into file $jobFile - permission denied.");
        } catch (cInvalidArgumentException $e) {
        }
    }
}

/**
 * Runs a job.
 *
 * @param array $job
 * @param string $PC_jobDir
 * @param string $PC_writeDir
 * @param int $PC_useLog
 * @param bool $PC_debug
 *
 * @return bool
 */
function runJob(
    array $job, string $PC_jobDir, string $PC_writeDir, int $PC_useLog, bool $PC_debug = false
): bool
{
    $sess = cRegistry::getSession();
    $extJob = [
        PC_MINUTE => [],
        PC_HOUR => [],
        PC_DOM => [],
        PC_MONTH => [],
        PC_DOW => [],
    ];

    parseElement($job[PC_MINUTE], $extJob[PC_MINUTE], 60);
    parseElement($job[PC_HOUR], $extJob[PC_HOUR], 24);
    parseElement($job[PC_DOM], $extJob[PC_DOM], 31);
    parseElement($job[PC_MONTH], $extJob[PC_MONTH], 12);
    parseElement($job[PC_DOW], $extJob[PC_DOW], 7);

    $lastActual = getLastActialRunTime($job[PC_CMD], $PC_writeDir);
    $lastScheduled = getLastScheduledRunTime($extJob, $PC_debug);

    if ($lastScheduled > $lastActual) {
        logMessage("Running    " . $job[PC_CRONLINE], $PC_writeDir, $PC_useLog, $PC_debug);
        logMessage("  Last run:       " . date("r", $lastActual), $PC_writeDir, $PC_useLog, $PC_debug);
        logMessage("  Last scheduled: " . date("r", $lastScheduled), $PC_writeDir, $PC_useLog, $PC_debug);
        markLastRun($job[PC_CMD], $lastScheduled, $PC_writeDir);

        if ($PC_debug) {
            echo getcwd();
            include($PC_jobDir . $job[PC_CMD]);      // display errors only when debugging
        } else {
            if (is_object($sess)) {
                $sess->freeze();
            }
            @include($PC_jobDir . $job[PC_CMD]);      // any error messages are suppressed
            if (is_object($sess)) {
                $sess->thaw();
            }
        }
        logMessage("Completed   " . $job[PC_CRONLINE], $PC_writeDir, $PC_useLog, $PC_debug);
        return true;
    } else {
        if ($PC_debug) {
            logMessage("Skipping    " . $job[PC_CRONLINE], $PC_writeDir, $PC_useLog, $PC_debug);
            logMessage("  Last run:       " . date("r", $lastActual), $PC_writeDir, $PC_useLog, $PC_debug);
            logMessage("  Last scheduled: " . date("r", $lastScheduled), $PC_writeDir, $PC_useLog, $PC_debug);
            logMessage("Completed   " . $job[PC_CRONLINE], $PC_writeDir, $PC_useLog, $PC_debug);
        }
        return false;
    }
}

/**
 * Parses the content of the cron file and returns the list of found jobs.
 *
 * @param string $PC_cronTabFile
 * @param bool $PC_debug
 *
 * @return array List of jobs
 */
function parseCronFile(string $PC_cronTabFile, bool $PC_debug): array
{
    $file = @file($PC_cronTabFile);
    $job  = [];
    $jobs = [];

    if (!is_array($file)) {
        return $jobs;
    }

    $pattern = "~^([-0-9,/*]+)\\s+([-0-9,/*]+)\\s+([-0-9,/*]+)\\s+([-0-9,/*]+)\\s+([-0-7,/*]+|(-|/|Sun|Mon|Tue|Wed|Thu|Fri|Sat)+)\\s+([^#]*)(#.*)?$~i";
    for ($i = 0; $i < count($file); $i++) {
        if ($file[$i][0] != '#') {
            if (preg_match($pattern, $file[$i], $job)) {
                $jobNumber = count($jobs);
                $jobs[$jobNumber] = $job;
                if ($jobs[$jobNumber][PC_DOW][0] != '*' AND !is_numeric($jobs[$jobNumber][PC_DOW])) {
                    $jobs[$jobNumber][PC_DOW] = str_replace(
                        ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
                        [0, 1, 2, 3, 4, 5, 6],
                        $jobs[$jobNumber][PC_DOW]
                    );
                }
                $jobs[$jobNumber][PC_CMD] = trim($job[PC_CMD]);
                $jobs[$jobNumber][PC_CRONLINE] = $file[$i];
            }
        }
    }

    if ($PC_debug) {
        var_dump($jobs);
    }
    return $jobs;
}
