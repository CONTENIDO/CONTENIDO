<?php
 /**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO setup script
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO setup
 * @version    0.2.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
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
define('C_FRONTEND_PATH', str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/');

include_once('lib/startup.php');


$currentStep = (isset($_REQUEST['step'])) ? $_REQUEST['step'] : '';

switch ($currentStep) {
    case 'setuptype':
        checkAndInclude($cfg['path']['setup'] . 'steps/setuptype.php');
        break;
    case 'setup1':
        checkAndInclude($cfg['path']['setup'] . 'steps/setup/step1.php');
        break;
    case 'setup2':
        checkAndInclude($cfg['path']['setup'] . 'steps/setup/step2.php');
        break;
    case 'setup3':
        checkAndInclude($cfg['path']['setup'] . 'steps/setup/step3.php');
        break;
    case 'setup4':
        checkAndInclude($cfg['path']['setup'] . 'steps/setup/step4.php');
        break;
    case 'setup5':
        checkAndInclude($cfg['path']['setup'] . 'steps/setup/step5.php');
        break;
    case 'setup6':
        checkAndInclude($cfg['path']['setup'] . 'steps/setup/step6.php');
        break;
    case 'setup7':
        checkAndInclude($cfg['path']['setup'] . 'steps/setup/step7.php');
        break;
    case 'setup8':
        checkAndInclude($cfg['path']['setup'] . 'steps/setup/step8.php');
        break;
    case 'migration1':
        checkAndInclude($cfg['path']['setup'] . 'steps/migration/step1.php');
        break;
    case 'migration2':
        checkAndInclude($cfg['path']['setup'] . 'steps/migration/step2.php');
        break;
    case 'migration3':
        checkAndInclude($cfg['path']['setup'] . 'steps/migration/step3.php');
        break;
    case 'migration4':
        checkAndInclude($cfg['path']['setup'] . 'steps/migration/step4.php');
        break;
    case 'migration5':
        checkAndInclude($cfg['path']['setup'] . 'steps/migration/step5.php');
        break;
    case 'migration6':
        checkAndInclude($cfg['path']['setup'] . 'steps/migration/step6.php');
        break;
    case 'migration7':
        checkAndInclude($cfg['path']['setup'] . 'steps/migration/step7.php');
        break;
    case 'migration8':
        checkAndInclude($cfg['path']['setup'] . 'steps/migration/step8.php');
        break;
    case 'upgrade1':
        checkAndInclude($cfg['path']['setup'] . 'steps/upgrade/step1.php');
        break;
    case 'upgrade2':
        checkAndInclude($cfg['path']['setup'] . 'steps/upgrade/step2.php');
        break;
    case 'upgrade3':
        checkAndInclude($cfg['path']['setup'] . 'steps/upgrade/step3.php');
        break;
    case 'upgrade4':
        checkAndInclude($cfg['path']['setup'] . 'steps/upgrade/step4.php');
        break;
    case 'upgrade5':
        checkAndInclude($cfg['path']['setup'] . 'steps/upgrade/step5.php');
        break;
    case 'upgrade6':
        checkAndInclude($cfg['path']['setup'] . 'steps/upgrade/step6.php');
        break;
    case 'upgrade7':
        checkAndInclude($cfg['path']['setup'] . 'steps/upgrade/step7.php');
        break;
    case 'domigration':
        checkAndInclude($cfg['path']['setup'] . 'steps/migration/domigration.php');
        break;
    case 'doupgrade':
        checkAndInclude($cfg['path']['setup'] . 'steps/upgrade/doupgrade.php');
        break;
    case 'doinstall':
        checkAndInclude($cfg['path']['setup'] . 'steps/setup/doinstall.php');
        break;
    case 'languagechooser':
    default:
        checkAndInclude($cfg['path']['setup'] . 'steps/languagechooser.php');
        break;
}

?>