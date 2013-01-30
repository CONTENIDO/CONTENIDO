<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Runs the upgrade job for convert date time and urldecode
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


class cUpgradeJob_0003 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.0-beta1";

    public function _execute() {
        global $cfg;

        convertToDatetime($this->_oDb, $cfg);
        urlDecodeTables($this->_oDb);
    }

}
