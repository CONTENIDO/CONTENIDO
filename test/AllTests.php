<?php
/**
 * This is the testing bootstrap.
 *
 * @package          Testing
 * @subpackage       Bootstrap
 * @version          SVN Revision $Rev:$
 *
 * @author           Dominik Ziegler
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

error_reporting((E_ALL ^ E_NOTICE) | E_STRICT);
ini_set('display_errors', true);

class AllTests extends PHPUnit_Framework_TestCase {
    static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('CONTENIDO Total Unit Tests');

        $directories = array('frontend/Chains', 'frontend/Security', 'frontend/Url', 'frontend/Validator');
        foreach ($directories as $directory) {
            $directory = rtrim(__DIR__ . "/" . $directory);
            foreach (glob($directory . "/*.php") as $filename) {
                include_once $filename;
                $className = str_replace(".php", "", basename($filename)) . "Test";
                if (class_exists($className)) {
                    $suite->addTestSuite($className);
                }
            }
        }

        return $suite;
    }
}