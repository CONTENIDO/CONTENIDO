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
class cHtmlImageTest extends PHPUnit_Framework_TestCase {

    protected $_cImage = null;

    protected function setUp() {
        $this->_cImage = new cHTMLImage();
    }

    public function testSrc() {
        $this->_cImage->setSrc('http://google.jpg');
        $this->assertSame('http://google.jpg', $this->_cImage->getAttribute('src'));
    }

    public function testSetWidth() {
        $this->_cImage->setWidth(200);
        $this->assertSame(200, $this->_cImage->getAttribute('width'));

        $this->_cImage->setWidth(0);
        $this->assertSame(0, $this->_cImage->getAttribute('width'));

        $this->_cImage->setWidth('');
        $this->assertSame('', $this->_cImage->getAttribute('width'));
    }

    public function testSetHeight() {
        $this->_cImage->setHeight(200);
        $this->assertSame(200, $this->_cImage->getAttribute('height'));

        $this->_cImage->setHeight(0);
        $this->assertSame(0, $this->_cImage->getAttribute('height'));

        $this->_cImage->setHeight('');
        $this->assertSame('', $this->_cImage->getAttribute('height'));
    }

    public function testSetBorder() {
        $this->_cImage->setBorder(200);
        $this->assertSame(200, $this->_cImage->getAttribute('border'));

        $this->_cImage->setBorder(0);
        $this->assertSame(0, $this->_cImage->getAttribute('border'));

        $this->_cImage->setBorder('');
        $this->assertSame('', $this->_cImage->getAttribute('border'));
    }

    /**
     * @todo This test has not been implemented yet.
     */
    public function testApplyDimensions() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

}
?>