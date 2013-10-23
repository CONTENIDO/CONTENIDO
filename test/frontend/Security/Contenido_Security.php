<?php
/**
 * Unittest for class Contenido_Security
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        20.05.2010
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Security
 */


/**
 * Class to check Contenido_Security class
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        20.05.2010
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Security
 */
class Contenido_SecurityTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $_REQUEST = array();
    }


    protected function tearDown()
    {
        $_REQUEST = array();
    }


    /**
     * Test valid backend language parameter in request
     */
    public function testValidRequestBackendLanguages()
    {
        $belangs = Contenido_Security::getAcceptedBelangValues();
        foreach ($belangs as $belang) {
            $_REQUEST['belang'] = $belang;
            try {
                Contenido_Security::checkRequestBelang();
            } catch (Contenido_Security_Exception $e) {
                $this->fail('An unexpected exception Contenido_Security_Exception has been raised.');
            }
        }
    }


    /**
     * Test valid backend language parameter in request
     */
    public function testInvalidRequestBackendLanguage()
    {
        // use a invalid and propably never available locale ID
        $_REQUEST['belang'] = 'fo_FO';
        try {
            Contenido_Security::checkRequestBelang();
        } catch (Contenido_Security_Exception $e) {
            // donut
        }
    }


    /**
     * Test for forbitten request parameter
     */
    public function testRequestForbittenParameters()
    {
        $params = Contenido_Security::getForbittenParameters();
        foreach ($params as $param) {
            $_REQUEST = array();
            $_REQUEST[$param] = 'foobar';
            try {
                Contenido_Security::checkRequestForbittenParameter();
            } catch (Contenido_Security_Exception $e) {
                continue;
            }
            $this->fail('An expected exception Contenido_Security_Exception has not been raised.');
        }
    }


    /**
     * Test valid must be numeric request parameters
     */
    public function testValidRequestMustbeNumericParameters()
    {
        $params = Contenido_Security::getMustbeNumericParameters();
        foreach ($params as $param) {
            $_REQUEST[$param] = substr(str_shuffle(str_repeat("abcdefghijklmnopqrstuvwxyz", 6)), 0, 6);
        }
        Contenido_Security::checkRequestMustbeNumericParameter();

        foreach ($_REQUEST as $k => $v) {
            if (!is_numeric($v)) {
                $this->fail('An expected "must be numeric" parameter "' . $k . '" was not numeric "' . $v . '".');
            }
        }
    }


    /**
     * Test invalid must be numeric request parameters
     */
    public function testInvalidRequestMustbeNumericParameters()
    {
        $params = Contenido_Security::getMustbeNumericParameters();
        foreach ($params as $param) {
            $_REQUEST[$param] = rand(1, 5);
        }
        Contenido_Security::checkRequestMustbeNumericParameter();

        foreach ($_REQUEST as $k => $v) {
            if (!is_numeric($v)) {
                $this->fail('An invalid "must be numeric" parameter "' . $k . '" was not type casted to numeric value "' . $v . '".');
            }
        }
    }


    /**
     * Test for valid Contenido session id
     */
    public function testValidRequestSession()
    {
        $_REQUEST['contenido'] = 'b8bad39788778674805eada191f296af';
        try {
            Contenido_Security::checkRequestSession();
        } catch (Contenido_Security_Exception $e) {
            $this->fail('An unexpected exception Contenido_Security_Exception has been raised.');
        }
    }


    /**
     * Test for invalid Contenido session id
     */
    public function testInvalidRequestSession()
    {
        $_REQUEST['contenido'] = 'foobar';
        try {
            Contenido_Security::checkRequestSession();
        } catch (Contenido_Security_Exception $e) {
            return;
        }
        $this->fail('An expected exception Contenido_Security_Exception has not been raised.');
    }


/**
    // @TODO  hmm, no time to do this at the moment...
    public function testFrontendGlobals()
    {
    }
*/

}
