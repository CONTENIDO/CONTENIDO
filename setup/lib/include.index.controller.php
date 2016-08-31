<?php
/**
 * Setup and upgrade script main controller.
 *
 * @package    Setup
 * @subpackage Controller
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$currentStep = (isset($_REQUEST['step'])) ? $_REQUEST['step'] : '';

switch ($currentStep) {
    case 'setuptype':
        checkAndInclude(CON_SETUP_PATH . '/steps/setuptype.php');
        break;
    case 'setup1':
        checkAndInclude(CON_SETUP_PATH . '/steps/setup/step1.php');
        break;
    case 'setup2':
        checkAndInclude(CON_SETUP_PATH . '/steps/setup/step2.php');
        break;
    case 'setup3':
        checkAndInclude(CON_SETUP_PATH . '/steps/setup/step3.php');
        break;
    case 'setup4':
        checkAndInclude(CON_SETUP_PATH . '/steps/setup/step4.php');
        break;
    case 'setup5':
        checkAndInclude(CON_SETUP_PATH . '/steps/setup/step5.php');
        break;
    case 'setup6':
        checkAndInclude(CON_SETUP_PATH . '/steps/setup/step6.php');
        break;
    case 'setup7':
        checkAndInclude(CON_SETUP_PATH . '/steps/setup/step7.php');
        break;
    case 'setup8':
        checkAndInclude(CON_SETUP_PATH . '/steps/setup/step8.php');
        break;
    case 'upgrade1':
        checkAndInclude(CON_SETUP_PATH . '/steps/upgrade/step1.php');
        break;
    case 'upgrade2':
        checkAndInclude(CON_SETUP_PATH . '/steps/upgrade/step2.php');
        break;
    case 'upgrade3':
        checkAndInclude(CON_SETUP_PATH . '/steps/upgrade/step3.php');
        break;
    case 'upgrade4':
        checkAndInclude(CON_SETUP_PATH . '/steps/upgrade/step4.php');
        break;
    case 'upgrade5':
        checkAndInclude(CON_SETUP_PATH . '/steps/upgrade/step5.php');
        break;
    case 'upgrade6':
        checkAndInclude(CON_SETUP_PATH . '/steps/upgrade/step6.php');
        break;
    case 'doupgrade':
        checkAndInclude(CON_SETUP_PATH . '/steps/upgrade/doupgrade.php');
        break;
    case 'doinstall':
        checkAndInclude(CON_SETUP_PATH . '/steps/setup/doinstall.php');
        break;
    case 'languagechooser':
    default:
        checkAndInclude(CON_SETUP_PATH . '/steps/languagechooser.php');
        break;
}

?>