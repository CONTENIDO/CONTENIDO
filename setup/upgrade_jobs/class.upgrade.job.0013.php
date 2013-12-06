<?php

/**
 * This file contains the upgrade job 13.
 *
 * @package Setup
 * @subpackage UpgradeJob
 * @version SVN Revision $Rev:$
 *
 * @author frederic.schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 13.
 *
 * Change of area for action pifa_export_form
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0013 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.3";

    public function _execute() {
        global $cfg;

        if ($_SESSION['setuptype'] == 'upgrade') {

            // Initializing cApiArea
            $area = new cApiArea();

            // Get informations for area form_ajax
            $area->loadBy('name', 'form_ajax');

            // If area form_ajax not exist, return false
            if ($area === null) {
                return false;
            }

            // Initializing cApiAction
            $action = new cApiAction();

            // Get informations for action pifa_export_form
            $action->loadBy('name', 'pifa_export_form');

            // If action pifa_export_form not exist, return false
            if ($action === null) {
                return false;
            }

            // Change area for action pifa_export_form to form_ajax
            $action->set('idarea', $area->get('idarea'));
            $action->store();

        }
    }

}
