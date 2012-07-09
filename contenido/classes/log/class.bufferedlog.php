<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Buffered Log facility
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created 2004-09-28
 *
 *   $Id$
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/** @deprecated 2012-07-08 This class is not needed any longer. */
class cBufferedLog extends cLog {
    public function __construct($oLogger = false) {
        cDeprecated("This class is not needed any longer because cLog implements the handling for buffered logging");
        parent::__construct();
    }

    /** @deprecated  [2012-05-25] Old constructor function for downwards compatibility */
    public function cBufferedLog($oLogger = false) {
        cDeprecated("Use __construct() instead");
        $this->__construct($oLogger);
    }
}
?>