<?php

/**
 * This file contains the abstract authentication handler class.
 *
 * @package    Core
 * @subpackage Authentication
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class is the abstract authentication handler for CONTENIDO
 * which may be extended differently for frontend and backend authentication.
 *
 * NOTE:
 * Moved all abstract method declarations to `cAuth` class since `cAuth` calls
 * some of them. There is no need to split abstract method definitions between
 * `cAuthHandlerAbstract` and `cAuth`.
 * Please update your class definition, in case you've implemented your own
 * authentication handler as follows:
 * <pre>
 * class MyCustomAuthHandler extends cAuth { ... }
 * </pre>
 *
 * @package    Core
 * @subpackage Authentication
 * @deprecated [2023-02-05] Since 4.10.2, use {@see cAuth} instead
 */
abstract class cAuthHandlerAbstract extends cAuth {

}
