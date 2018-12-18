<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlVideoTest extends PHPUnit_Framework_TestCase {

    protected $_cVideo = null;

    protected function setUp() {
        $this->_cVideo = new cHTMLVideo('testcontent', 'testClass', 'testId', 'testSrc');
    }

    public function testConstruct() {
        $cVideo = new cHTMLVideo();
        $this->assertSame('<video id="" src=""></video>', $cVideo->toHtml());
        $this->assertSame('<video id="testId" class="testClass" src="testSrc">testcontent</video>', $this->_cVideo->toHtml());
    }

    public function testSetSrc() {
        $this->_cVideo->setSrc('testSrc1');
        $this->assertSame('testSrc1', $this->_cVideo->getAttribute('src'));
    }

    public function testSetControls() {
        $this->assertSame(NULL, $this->_cVideo->getAttribute('controls'));
        $this->_cVideo->setControls(true);
        $this->assertSame('controls', $this->_cVideo->getAttribute('controls'));
        $this->_cVideo->setControls(false);
        $this->assertSame(NULL, $this->_cVideo->getAttribute('controls'));
    }

    public function testSetAutoplay() {
        $this->assertSame(NULL, $this->_cVideo->getAttribute('autoplay'));
        $this->_cVideo->setAutoplay(true);
        $this->assertSame('autoplay', $this->_cVideo->getAttribute('autoplay'));
        $this->_cVideo->setAutoplay(false);
        $this->assertSame(NULL, $this->_cVideo->getAttribute('autoplay'));
    }

    public function testSetPoster() {
        $this->assertSame(NULL, $this->_cVideo->getAttribute('poster'));
        $this->_cVideo->setPoster('poster');
        $this->assertSame('poster', $this->_cVideo->getAttribute('poster'));
        $this->_cVideo->setPoster('');
        $this->assertSame('', $this->_cVideo->getAttribute('poster'));
    }

}
?>