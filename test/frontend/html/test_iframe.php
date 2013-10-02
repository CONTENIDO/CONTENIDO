<?PHP
/**
 *
 * @version SVN Revision $Rev:$
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlIframeTest extends PHPUnit_Framework_TestCase {

    protected $_cIframe = null;

    protected function setUp() {
        $this->_cIframe = new cHTMLIFrame();
    }

    public function testSetSrc() {
        $this->_cIframe->setSrc(200);
        $this->assertSame(200, $this->_cIframe->getAttribute('src'));
        $this->_cIframe->setSrc('');
        $this->assertSame('', $this->_cIframe->getAttribute('src'));
    }

    public function testSetWidth() {
        $this->_cIframe->setWidth(200);
        $this->assertSame(200, $this->_cIframe->getAttribute('width'));
        $this->_cIframe->setWidth('');
        $this->assertSame('', $this->_cIframe->getAttribute('width'));
    }

    public function testSetHeight() {
        $this->_cIframe->setHeight(200);
        $this->assertSame(200, $this->_cIframe->getAttribute('height'));
        $this->_cIframe->setHeight('');
        $this->assertSame('', $this->_cIframe->getAttribute('height'));
    }

    public function testSetBorder() {
        $this->_cIframe->setBorder(200);
        $this->assertSame(200, $this->_cIframe->getAttribute('frameborder'));
        $this->_cIframe->setBorder(0);
        $this->assertSame(0, $this->_cIframe->getAttribute('frameborder'));
        $this->_cIframe->setBorder('');
        $this->assertSame(0, $this->_cIframe->getAttribute('frameborder'));
    }

}
?>