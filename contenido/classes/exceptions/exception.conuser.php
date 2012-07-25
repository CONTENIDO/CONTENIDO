<?php
/**
 * Simple exception extension for new ConUser class.
 *
 * @package CONTENIDO Backend Classes
 * @subpackage Backend User
 *
 * @author Holger Librenz
 * @version $Revision$
 * @copyright four for business AG <www.4fb.de>
 *
 * {@internal
 *  created 2008-11-16, H. Librenz
 * }}
 */

/**
 * Simple excpetion extension for better error handling.
 *
 * @package CONTENIDO Backend Classes
 * @subpackage Backend User
 *
 * @version 1.0.0
 * @author Holger Librenz
 * @copyright four for business AG
 *
 * @deprecated Please use cApiUser instead [2012-02-23]
 */
class ConUserException extends Exception
{
    public function __construct()
    {
        parent::__construct();

        cDeprecated("Deprecated class. Please use cApiUser instead");
    }
}

?>