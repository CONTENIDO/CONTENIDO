<?php
/**
 * Testsuite loader for Contenido tests.
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        03.04.2008
 * @category    Testing
 * @package     Contenido
 * @subpackage  Helper
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
        self::_loadSuite(CONTENIDO_TEST_PATH . '/frontend/' . $name . '/');
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
