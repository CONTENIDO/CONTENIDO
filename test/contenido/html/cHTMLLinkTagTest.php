<?php

/**
 * This file contains tests for the class cHTMLLinkTag.
 *
 * @package    Testing
 * @subpackage GUI_HTML
 * @author     Murat Purç <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */
class cHTMLLinkTagTest extends cTestingTestCase
{

    public function setUp(): void
    {
        cHTML::setGenerateXHTML(false);
    }

    /**
     * Tests {@see cHTMLLinkTag::__construct()}
     */
    public function testConstruct()
    {
        $link = new cHTMLLinkTag();
        $this->assertSame('link', $this->_readAttribute($link, '_tag'));
    }

    /**
     * Tests {@see cHTMLLinkTag::stylesheet()}
     */
    public function testStylesheet()
    {
        // Empty href
        $result = cHTMLLinkTag::stylesheet('');
        $this->assertSame('<link rel="stylesheet" type="text/css" href="">', $result);

        // Stylesheet href
        $result = cHTMLLinkTag::stylesheet('styles/contenido.css');
        $this->assertSame('<link rel="stylesheet" type="text/css" href="styles/contenido.css">', $result);

        // Stylesheet href with media attribute
        $result = cHTMLLinkTag::stylesheet('/styles/x-small.css', [
            'media' => 'screen and (max-width: 576px)'
        ]);
        $this->assertSame('<link media="screen and (max-width: 576px)" rel="stylesheet" type="text/css" href="/styles/x-small.css">', $result);
    }

}
