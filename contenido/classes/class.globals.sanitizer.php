<?php
/**
 * This file contains the globals sanitizer class.
 *
 * @since      CONTENIDO 4.10.2
 * @package    Core
 * @subpackage Security
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class to sanitize global variables.
 *
 * @package    Core
 * @subpackage Security
 */
class cGlobalsSanitizer
{

    /**
     * Instance of this class.
     *
     * @var cGlobalsSanitizer
     */
    private static $instance = null;

    private function __construct()
    {
    }

    /**
     * Returns the instance of this class.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function sanitize()
    {
        // TODO: Make this configurable, extend it to sanitize more globals, add logging
        //       & provide functions to retrieve about sanitized globals.
        $integerTypes = [
            'idart', 'idcat', 'idartlang', 'idcatart', 'lang', 'changelang', 'idcatlang',
            'client', 'frame', 'tmpchangelang', 'changeclient', 'page',
        ];

        foreach ($integerTypes as $type) {
            if (isset($GLOBALS[$type]) && !is_numeric($GLOBALS[$type])) {
                unset($GLOBALS[$type]);
            }
        }
    }

}