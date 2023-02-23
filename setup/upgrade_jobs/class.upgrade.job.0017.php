<?php
/**
 * This file contains the upgrade job 17.
 *
 * @package Setup
 * @subpackage UpgradeJob
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 17
 * Fill new name column at nav_main table
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0017 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.8";

    public function _execute() {

        if ($_SESSION['setuptype'] == 'upgrade') {

        	// Initializing cApiNavMain
        	$navm = new cApiNavMain();

        	// navigation/content/main
        	$navm->loadBy('location', 'navigation/content/main');

        	// If entry exist, set name to "content"
        	if ($navm !== null) {
        		$navm->set('name', 'content');
        		$navm->store();
        	}

        	// navigation/style/main
        	$navm->loadBy('location', 'navigation/style/main');

        	// If entry exist, set name to "style"
        	if ($navm !== null) {
        		$navm->set('name', 'style');
        		$navm->store();
        	}

        	// navigation/statistic/main
        	$navm->loadBy('location', 'navigation/statistic/main');

        	// If entry exist, set name to "statistic"
        	if ($navm !== null) {
        		$navm->set('name', 'statistic');
        		$navm->store();
        	}

        	// navigation/administration/main
        	$navm->loadBy('location', 'navigation/administration/main');

        	// If entry exist, set name to "administration"
        	if ($navm !== null) {
        		$navm->set('name', 'administration');
        		$navm->store();
        	}

        	// navigation/extra/main
        	$navm->loadBy('location', 'navigation/extra/main');

        	// If entry exist, set name to "extra"
        	if ($navm !== null) {
        		$navm->set('name', 'extra');
        		$navm->store();
        	}
        }
    }

}
