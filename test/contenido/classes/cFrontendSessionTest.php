<?php

/**
 * This file contains tests for the cFrontendSession class.
 *
 * @package          Testing
 * @subpackage       Test_Session
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          https://www.contenido.org/license/LIZENZ.txt
 * @link             https://www.4fb.de
 * @link             https://www.contenido.org
 */

/**
 * Class to check cFrontendSession class
 *
 * @todo             Implement more tests
 *
 * @package          Testing
 * @subpackage       Test_Session
 */
class cFrontendSessionTest extends cTestingTestCase
{
    const URL_BASE = 'http:://contenido.localhost';

    /**
     * @var cFrontendSession
     */
    protected $_session;

    protected function setUp(): void
    {
        $this->_session     = new cFrontendSession();
        $this->_session->id = 'TestFeXupedOhesi';
    }

    public function testUrl()
    {
        $url = self::URL_BASE;

        $expectedUrl = self::URL_BASE;
        $sessUrl     = $this->_session->url($url);
        $this->assertEquals($expectedUrl, $sessUrl);
    }

    public function testUrlWidthParameter()
    {
        $url = self::URL_BASE . '?foo=bar';

        $expectedUrl = self::URL_BASE . '?foo=bar';
        $sessUrl     = $this->_session->url($url);
        $this->assertEquals($expectedUrl, $sessUrl);
    }

    public function testUrlWidthQuestionMark()
    {
        $url = self::URL_BASE . '?';

        $expectedUrl = self::URL_BASE;
        $sessUrl     = $this->_session->url($url);
        $this->assertEquals($expectedUrl, $sessUrl);
    }

    public function testUrlWithSessionName()
    {
        $url = self::URL_BASE . '?' . http_build_query([$this->_session->name => 'foobar']);

        $expectedUrl = self::URL_BASE;
        $sessUrl     = $this->_session->url($url);
        $this->assertEquals($expectedUrl, $sessUrl);
    }

    public function testUrlWithFragment()
    {
        $url = self::URL_BASE . '?' . http_build_query([$this->_session->name => $this->_session->id, 'foo' => 'bar'])
            . '#baz';

        $expectedUrl = self::URL_BASE . '?foo=bar#baz';
        $sessUrl     = $this->_session->url($url);
        $this->assertEquals($expectedUrl, $sessUrl);
    }

    public function testUrlWithEntityEncodedAmpersand()
    {
        $url = self::URL_BASE . '?foo=bar&amp;baz=1';

        $expectedUrl = self::URL_BASE . '?foo=bar&baz=1';
        $sessUrl     = $this->_session->url($url);
        $this->assertEquals($expectedUrl, $sessUrl);
    }
}
