<?php

/**
 * This file contains the upgrade job 13.
 *
 * @package Setup
 * @subpackage UpgradeJob
 * @author frederic.schneider
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 13.
 *
 * Change of areas for actions pifa_export_form, pifa_show_fields and
 * pifa_show_data
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

            // Initializing cApiAction
            $action = new cApiAction();

            // PIFA_EXPORT_FORM
            // Get informations for area form_ajax
            $area->loadBy('name', 'form_ajax');

            // If area form_ajax not exist, return false
            if ($area === null) {
                return;
            }

            // Get informations for action pifa_export_form
            $action->loadBy('name', 'pifa_export_form');

            // If action pifa_export_form not exist, return false
            if ($action === null) {
                return;
            }

            // Change area for action pifa_export_form to form_ajax
            $action->set('idarea', $area->get('idarea'));
            $action->store();

            // PIFA_SHOW_FIELDS
            $area->loadBy('name', 'form_fields');

            // If area form_fields not exist, return false
            if ($area === null) {
                return;
            }

            // Get informations for action pifa_show_fields
            $action->loadBy('name', 'pifa_show_fields');

            // If action pifa_show_fields not exist, return false
            if ($action === null) {
                return;
            }

            // Change area for action pifa_show_fields to form_fields
            $action->set('idarea', $area->get('idarea'));
            $action->store();

            // PIFA_SHOW_DATA
            $area->loadBy('name', 'form_data');

            // If area form_data not exist, return false
            if ($area === null) {
                return;
            }

            // Get informations for action pifa_show_data
            $action->loadBy('name', 'pifa_show_data');

            // If action pifa_show_data not exist, return false
            if ($action === null) {
                return;
            }

            // Change area for action pifa_show_data to form_data
            $action->set('idarea', $area->get('idarea'));
            $action->store();
        }
    }
}
