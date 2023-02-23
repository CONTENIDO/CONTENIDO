<?php

/**
 * This file contains tests for the cUri class.
 *
 * @package    Testing
 * @subpackage Test_Url
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * Class to test cUri.
 *
 * @package    Testing
 * @subpackage Test_Url
 */
class cUriTest extends cTestingTestCase
{
    /**
     * @var int
     */
    protected $languageId;

    /**
     * @var array
     */
    protected $clientCfg;

    /**
     * @var int
     */
    protected $clientId;

    protected function setUp(): void
    {
        $this->clientId   = cRegistry::getClientId();
        $this->clientCfg  = cRegistry::getClientConfig($this->clientId);
        $this->languageId = cRegistry::getLanguageId();
    }

    /**
     * Test url creation to error page
     *
     * @throws cInvalidArgumentException
     */
    public function testErrorPageUrlCreation()
    {
        $errSite = cRegistry::getErrSite();

        // error page
        $aParams = [
            'client' => $this->clientId,
            'idcat'  => $errSite['idcat'],
            'idart'  => $errSite['idart'],
            'lang'   => $this->languageId,
            'error'  => '1',
        ];

        $url = cUri::getInstance()->buildRedirect($aParams);

        $isToBeUrl =
            $this->clientCfg['path']['htmlpath'] . 'front_content.php?idcat=2&idart=15&client=1&lang=1&error=1';

        $this->assertEquals($isToBeUrl, $url);
    }

    /**
     * Test url creation to internal pages (article redirects)
     *
     * @throws cInvalidArgumentException
     */
    public function testInternalRedirectUrlCreation()
    {
        // internal redirect
        $redirectUrl = 'front_content.php?idart=12&param=value';
        $redirectUrl = $this->_createArticleRedirectUrl($redirectUrl);

        $isToBeUrl =
            $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=value&lang=' . $this->languageId;

        $this->assertEquals($isToBeUrl, $redirectUrl);
    }

    /**
     * Test url creation to internal homepage without existing parameter idart/idcat.
     *
     * Tests following article redirect settings:
     * - front_content.php
     * - /
     * - /cms/
     * - /cms/front_content.php
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function testInternalRedirectUrlToHomepageCreation()
    {
        // get idcat to homepage
        $idcatHome = getEffectiveSetting('navigation', 'idcat-home', 1);

        // result should be following url
        $isToBeUrl = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idcat=' . $idcatHome . '&lang='
            . $this->languageId;

        // internal redirect with 'front_content.php'
        $redirectUrl = $this->_createArticleRedirectUrl('front_content.php');
        $this->assertEquals($isToBeUrl, $redirectUrl);

        // internal redirect with '/'
        $redirectUrl = $this->_createArticleRedirectUrl('/');
        $this->assertEquals($isToBeUrl, $redirectUrl);

        // internal redirect with '/cms/' (note: 'cms' is hard coded but the default client folder)
        $redirectUrl = $this->_createArticleRedirectUrl('/cms/');
        $this->assertEquals($isToBeUrl, $redirectUrl);

        // internal redirect with '/cms/front_content.php'
        $redirectUrl = $this->_createArticleRedirectUrl('/cms/front_content.php');
        $this->assertEquals($isToBeUrl, $redirectUrl);
    }

    /**
     * Test url creation to internal pages (article redirects)
     *
     * @throws cInvalidArgumentException
     */
    public function testInternalRedirectFullUrlCreation()
    {
        // internal redirect with full url
        $redirectUrl = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=value';
        $redirectUrl = $this->_createArticleRedirectUrl($redirectUrl);

        $isToBeUrl =
            $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=value&lang=' . $this->languageId;

        $this->assertEquals($isToBeUrl, $redirectUrl);
    }

    /**
     * Test url creation to external pages (article redirects)
     *
     * @throws cInvalidArgumentException
     */
    public function testExternalRedirectUrlCreation()
    {
        // external redirect
        $redirectUrl = 'http://www.contenido.de/?id=12321';
        $redirectUrl = $this->_createArticleRedirectUrl($redirectUrl);

        $isToBeUrl = 'http://www.contenido.de/?id=12321';

        $this->assertEquals($isToBeUrl, $redirectUrl);
    }

    /**
     * Test url creation to unidentifiable internal pages (article redirects)
     *
     * @throws cInvalidArgumentException
     */
    public function testUnidentifiableInternalRedirectUrlCreation()
    {
        // unidentifiable internal
        $redirectUrl = '/unknown/path/to/some/page.html';
        $redirectUrl = $this->_createArticleRedirectUrl($redirectUrl);

        $isToBeUrl = '/unknown/path/to/some/page.html';

        $this->assertEquals($isToBeUrl, $redirectUrl);
    }

    /**
     * Test append parameters
     */
    public function testAppendParameters()
    {
        // Append parameters
        $url = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=value';
        $url = cUri::getInstance()->appendParameters($url, ['a' => '1', 'b' => 2]);
        $isToBeUrl = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=value&a=1&b=2';
        $this->assertEquals($isToBeUrl, $url);
    }

    /**
     * Test append parameters with reserved parameters
     */
    public function testAppendParameterReservedParameters()
    {
        $url = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=value';
        $url = cUri::getInstance()->appendParameters($url, ['a' => '1', 'b' => 2, 'client' => 1, 'idart' => 123]);
        $isToBeUrl = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=value&a=1&b=2';
        $this->assertEquals($isToBeUrl, $url);
    }

    /**
     * Test append parameters with user defined reserved parameters
     */
    public function testAppendParametersWithUserDefinedReservedParameters()
    {
        $url = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=value';
        $url = cUri::getInstance()->appendParameters($url, ['a' => '1', 'b' => 2, 'client' => 1, 'lang' => 3], ['client', 'lang']);
        $isToBeUrl = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=value&a=1&b=2';
        $this->assertEquals($isToBeUrl, $url);
    }

    /**
     * Test parameters and overwrite reserved parameters
     */
    public function testAppendParametersOverwriteReservedParameters()
    {
        $url = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=value';
        $url = cUri::getInstance()->appendParameters($url, ['a' => '1', 'b' => 2, 'client' => 1, 'lang' => 3], []);
        $isToBeUrl = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=value&a=1&b=2&client=1&lang=3';
        $this->assertEquals($isToBeUrl, $url);
    }

    /**
     * Test parameters and don't overwrite or overwrite existing parameters
     */
    public function testAppendParametersOverride()
    {
        // Don't overwrite existing parameters
        $url = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=value';
        $url = cUri::getInstance()->appendParameters($url, ['a' => '1', 'b' => 2, 'param' => 'newValue'], []);
        $isToBeUrl = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=newValue&a=1&b=2';
        $this->assertNotEquals($isToBeUrl, $url);

        // Overwrite existing parameters
        $url = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=value';
        $url = cUri::getInstance()->appendParameters($url, ['a' => '1', 'b' => 2, 'param' => 'newValue'], [], true);
        $isToBeUrl = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&param=newValue&a=1&b=2';
        $this->assertEquals($isToBeUrl, $url);
    }

    /**
     * Test redirect url with complex parameters
     * @link https://github.com/CONTENIDO/CONTENIDO/issues/132
     */
    public function testRedirectUrlWithComplexParameters() {
        $redirectUrl = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12';
        // Query parameter a=1&b[]=2&b[1][]=3
        $parameters = ['a' => 1, 'b' => [2, [3]]];
        $redirectUrl = cUri::getInstance()->appendParameters($redirectUrl, $parameters);
        $isToBeUrl = $this->clientCfg['path']['htmlpath'] . 'front_content.php?idart=12&a=1&b[]=2&b[1][]=3';
        $this->assertNotEquals($isToBeUrl, $redirectUrl);
    }

    /**
     * @param $redirectUrl
     *
     * @return string
     * @throws cInvalidArgumentException
     */
    private function _createArticleRedirectUrl($redirectUrl)
    {
        $oUrl = cUri::getInstance();

        if ($oUrl->isIdentifiableFrontContentUrl($redirectUrl)) {
            // perform urlbuilding only for identified internal urls
            $aUrl = $oUrl->parse($redirectUrl);
            if (!isset($aUrl['params']['lang'])) {
                $aUrl['params']['lang'] = $this->languageId;
            }
            $redirectUrl = $oUrl->buildRedirect($aUrl['params']);
        }

        return $redirectUrl;
    }
}
