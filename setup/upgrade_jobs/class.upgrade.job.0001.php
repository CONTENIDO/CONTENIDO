<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 *
 * @package    CONTENIDO Setup upgrade
 * @version    0.1
 * @author     Murat Purc <murat@purc>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9
 */


if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}


class cUpgradeJob_0001 extends cUpgradeJobAbstract {

    public function execute() {
        global $cfg, $cfgClient;
        if ($this->_setupType == 'setup') {
            switch ($_SESSION['clientmode']) {
                case 'CLIENTMODULES':
                case 'CLIENTEXAMPLES':
                    updateClientPath($this->_oDb, $cfg['tab']['clients'], 1, self::$_rootPath . '/cms/', self::$_rootHttpPath . '/cms/');
                    break;
                default:
                    break;
            }
        }
    }

}
