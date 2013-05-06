<?php
/**
 * This file contains the Testsuite loader for Contenido tests.
 *
 * @package          Testing
 * @subpackage       Helper
 * @version          SVN Revision $Rev:$
 *
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

/**
 * Class TestSuiteHelper
 * @package          Testing
 * @subpackage       Helper
 */
class TestSuiteHelper
{

    /**
     * Loads all available tests inside a frontend testsuite folder.
     *
     * @param   string  $name  Name of folder (inside /test/frontend/) from where the tests are to load.
     * @return  void
     */
    public static function loadFeSuite($name)
    {
        self::_loadSuite(CON_TEST_PATH . '/frontend/' . $name . '/');
    }


    /**
     * Loads a desired testsuite by including all found files inside a directory
     *
     * @param   string  $path
     * @return  void
     */
    private static function _loadSuite($path)
    {
        $dir = new DirectoryIterator($path);
        foreach ($dir as $file) {
            if ($file->isFile()) {
                require_once($path . $file->getFilename());
            }
        }
    }

}
