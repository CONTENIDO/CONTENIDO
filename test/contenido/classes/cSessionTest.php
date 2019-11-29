<?php

/**
 * This file contains tests for the cSession class.
 *
 * @package          Testing
 * @subpackage       Test_Session
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

/**
 * Class to check cSession class
 *
 * @todo             Implement more tests
 *
 * @package          Testing
 * @subpackage       Test_Session
 */
class cSessionTest extends cTestingTestCase
{
    const URL_BASE = 'http:://contenido.localhost';

    /**
     * @var cSession
     */
    protected $_session;

    protected function setUp(): void
    {
        $this->_session     = new cSession();
        $this->_session->id = 'TestBeXupedOhesi';
    }

    public function testUrl()
    {
        $url = self::URL_BASE;

        $expectedUrl = self::URL_BASE . '?' . http_build_query([$this->_session->name => $this->_session->id]);
        $sessUrl     = $this->_session->url($url);
        $this->assertEquals($expectedUrl, $sessUrl);
    }

    public function testUrlWidthParameter()
    {
        $url = self::URL_BASE . '?foo=bar';

        $expectedUrl =
            self::URL_BASE . '?' . http_build_query([$this->_session->name => $this->_session->id, 'foo' => 'bar']);
        $sessUrl     = $this->_session->url($url);
        $this->assertEquals($expectedUrl, $sessUrl);
    }

    public function testUrlWidthQuestionMark()
    {
        $url = self::URL_BASE . '?';

        $expectedUrl = self::URL_BASE . '?' . http_build_query([$this->_session->name => $this->_session->id]);
        $sessUrl     = $this->_session->url($url);
        $this->assertEquals($expectedUrl, $sessUrl);
    }

    public function testUrlWithSessionName()
    {
        $url = self::URL_BASE . '?' . http_build_query([$this->_session->name => 'foobar']);

        $expectedUrl = self::URL_BASE . '?' . http_build_query([$this->_session->name => $this->_session->id]);
        $sessUrl     = $this->_session->url($url);
        $this->assertEquals($expectedUrl, $sessUrl);
    }

    public function testUrlWithFragment()
    {
        $url = self::URL_BASE . '?' . http_build_query([$this->_session->name => $this->_session->id, 'foo' => 'bar'])
            . '#baz';

        $expectedUrl =
            self::URL_BASE . '?' . http_build_query([$this->_session->name => $this->_session->id, 'foo' => 'bar'])
            . '#baz';
        $sessUrl     = $this->_session->url($url);
        $this->assertEquals($expectedUrl, $sessUrl);
    }
}