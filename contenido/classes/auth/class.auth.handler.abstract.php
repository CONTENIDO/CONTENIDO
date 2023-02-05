<?php

/**
 * This file contains the abstract authentication handler class.
 *
 * @package Core
 * @subpackage Authentication
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class is the abstract authentication handler for CONTENIDO
 * which may be extended differently for frontend and backend authentication.
 *
 * NOTE:
 * Moved all abtract method declarations to `cAuth` class since `cAuth` calls
 * some of them. There is no need to split abtract method definitions between
 * `cAuthHandlerAbstract` and `cAuth`.
 * Please update your class definition, in case you've implemented your own
 * authentication handler as follows:
 * <pre>
 * class MyCustomAuthHandler extends cAuth { ... }
 * </pre>
 *
 * @package    Core
 * @subpackage Authentication
 * @deprecated Since 4.10.2, use {@see cAuth} instead
 */
abstract class cAuthHandlerAbstract extends cAuth {

}
