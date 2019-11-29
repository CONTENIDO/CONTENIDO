<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */
class cHtmlVideoTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $cVideo = new cHTMLVideo('', '', 'testId2');
        $this->assertSame('<video id="testId2" src=""></video>', $cVideo->toHtml());
        $cVideo = new cHTMLVideo('testcontent', 'testClass', 'testId', 'testSrc');
        $this->assertSame('<video id="testId" class="testClass" src="testSrc">testcontent</video>', $cVideo->toHtml());
    }

    public function testSetSrc()
    {
        $cVideo = new cHTMLVideo();
        $cVideo->setSrc('testSrc1');
        $this->assertSame('testSrc1', $cVideo->getAttribute('src'));
    }

    public function testSetControls()
    {
        $cVideo = new cHTMLVideo();
        $this->assertSame(null, $cVideo->getAttribute('controls'));
        $cVideo->setControls(true);
        $this->assertSame('controls', $cVideo->getAttribute('controls'));
        $cVideo->setControls(false);
        $this->assertSame(null, $cVideo->getAttribute('controls'));
    }

    public function testSetAutoplay()
    {
        $cVideo = new cHTMLVideo();
        $this->assertSame(null, $cVideo->getAttribute('autoplay'));
        $cVideo->setAutoplay(true);
        $this->assertSame('autoplay', $cVideo->getAttribute('autoplay'));
        $cVideo->setAutoplay(false);
        $this->assertSame(null, $cVideo->getAttribute('autoplay'));
    }

    public function testSetPoster()
    {
        $cVideo = new cHTMLVideo();
        $this->assertSame(null, $cVideo->getAttribute('poster'));
        $cVideo->setPoster('poster');
        $this->assertSame('poster', $cVideo->getAttribute('poster'));
        $cVideo->setPoster('');
        $this->assertSame('', $cVideo->getAttribute('poster'));
    }
}
