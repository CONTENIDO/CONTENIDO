<?php
 /**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Contenido setup script
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    Contenido setup
 * @version    0.2.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 *
 * {@internal
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *   modified 2011-01-21, Ortwin Pinke, added php-errorhandling function calls, uncomment if needed
 *   modified 2011-02-24, Murat Purc, extended mysql extension detection and some other changes
 *   modified 2011-02-28, Murat Purc, normalized setup startup process and some cleanup/formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}
define('C_CONTENIDO_PATH', '../contenido/');

include_once('lib/startup.php');


if (is_array($_REQUEST)) {
    foreach ($_REQUEST as $key => $value) {
        if (($value != '' && $key != 'dbpass') || ($key == 'dbpass' && $_REQUEST['dbpass_changed'] == 'true')) {
            $_SESSION[$key] = $value;
        }
    }
/*
################################################################################
// FIXME  Following lines of code would enshure that previous selected optional 
//        settings will be removed from session, if they are unselected afterwards.
//        But, how should we handle not selected plugins, whose files will be included
//        even if the are not installed?

    // check for not selected options (radio button or checkbox)
    $aSetupOptionalSettingsList = array(
        'setup7' => array(
            'plugin_newsletter',
            'plugin_content_allocation',
            'plugin_mod_rewrite',
        )
    );

    if (isset($_REQUEST['step']) && isset($aSetupOptionalSettingsList[$_REQUEST['step']])) {
        $aList = $aSetupOptionalSettingsList[$_REQUEST['step']];
        foreach ($aList as $key) {
            if (isset($_SESSION[$key]) && !isset($_REQUEST[$key])) {
                unset($_SESSION[$key]);
            }
        }
    }
################################################################################
*/
}


if (getPHPIniSetting('session.use_cookies') == 0) {
    $sNotInstallableReason = 'session_use_cookies';
    checkAndInclude('steps/notinstallable.php');
}

if (hasMySQLiExtension() && !hasMySQLExtension()) {
    // use MySQLi extension by default if available
    $cfg['database_extension'] = 'mysqli';
} elseif (hasMySQLExtension()) {
    // use MySQL extension if available
    $cfg['database_extension'] = 'mysql';
} else {
    $sNotInstallableReason = 'database_extension';
    checkAndInclude('steps/notinstallable.php');
}

checkAndInclude('../conlib/prepend.php');

if (array_key_exists('language', $_SESSION)) {
    i18nInit('locale/', $_SESSION['language']);
}

if (phpversion() < C_SETUP_MIN_PHP_VERSION) {
    $sNotInstallableReason = 'php_version';
    checkAndInclude('steps/notinstallable.php');
}

if (array_key_exists('step', $_REQUEST)) {
    $iStep = $_REQUEST['step'];
} else {
    $iStep = '';
}

switch ($iStep) {
    case 'setuptype':
        checkAndInclude('steps/setuptype.php');
        break;
    case 'setup1':
        checkAndInclude('steps/setup/step1.php');
        break;
    case 'setup2':
        checkAndInclude('steps/setup/step2.php');
        break;
    case 'setup3':
        checkAndInclude('steps/setup/step3.php');
        break;
    case 'setup4':
        checkAndInclude('steps/setup/step4.php');
        break;
    case 'setup5':
        checkAndInclude('steps/setup/step5.php');
        break;
    case 'setup6':
        checkAndInclude('steps/setup/step6.php');
        break;
    case 'setup7':
        checkAndInclude('steps/setup/step7.php');
        break;
    case 'setup8':
        checkAndInclude('steps/setup/step8.php');
        break;
    case 'migration1':
        checkAndInclude('steps/migration/step1.php');
        break;
    case 'migration2':
        checkAndInclude('steps/migration/step2.php');
        break;
    case 'migration3':
        checkAndInclude('steps/migration/step3.php');
        break;
    case 'migration4':
        checkAndInclude('steps/migration/step4.php');
        break;
    case 'migration5':
        checkAndInclude('steps/migration/step5.php');
        break;
    case 'migration6':
        checkAndInclude('steps/migration/step6.php');
        break;
    case 'migration7':
        checkAndInclude('steps/migration/step7.php');
        break;
    case 'migration8':
        checkAndInclude('steps/migration/step8.php');
        break;
    case 'upgrade1':
        checkAndInclude('steps/upgrade/step1.php');
        break;
    case 'upgrade2':
        checkAndInclude('steps/upgrade/step2.php');
        break;
    case 'upgrade3':
        checkAndInclude('steps/upgrade/step3.php');
        break;
    case 'upgrade4':
        checkAndInclude('steps/upgrade/step4.php');
        break;
    case 'upgrade5':
        checkAndInclude('steps/upgrade/step5.php');
        break;
    case 'upgrade6':
        checkAndInclude('steps/upgrade/step6.php');
        break;
    case 'upgrade7':
        checkAndInclude('steps/upgrade/step7.php');
        break;
    case 'domigration':
        checkAndInclude('steps/migration/domigration.php');
        break;
    case 'doupgrade':
        checkAndInclude('steps/upgrade/doupgrade.php');
        break;
    case 'doinstall':
        checkAndInclude('steps/setup/doinstall.php');
        break;
    case 'languagechooser':
    default:
        checkAndInclude('steps/languagechooser.php');
        break;
}

?>