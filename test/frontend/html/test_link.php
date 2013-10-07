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
class cHtmlLinkTest extends PHPUnit_Framework_TestCase {

    protected $_link = null;

    public function setUp() {
        $this->_link = new cHTMLLink();
    }

    public function testConstruct() {
        $this->assertSame('', PHPUnit_Framework_Assert::readAttribute($this->_link, '_link'));
        $this->assertSame('', PHPUnit_Framework_Assert::readAttribute($this->_link, '_content'));
        $this->assertSame(NULL, PHPUnit_Framework_Assert::readAttribute($this->_link, '_anchor'));
        $this->assertSame(NULL, PHPUnit_Framework_Assert::readAttribute($this->_link, '_custom'));
        $this->assertSame('', PHPUnit_Framework_Assert::readAttribute($this->_link, '_image'));

        $this->_link = new cHTMLLink('contenido.org');
        $this->assertSame('contenido.org', PHPUnit_Framework_Assert::readAttribute($this->_link, '_link'));
        $this->assertSame('', PHPUnit_Framework_Assert::readAttribute($this->_link, '_content'));
        $this->assertSame(NULL, PHPUnit_Framework_Assert::readAttribute($this->_link, '_anchor'));
        $this->assertSame(NULL, PHPUnit_Framework_Assert::readAttribute($this->_link, '_custom'));
        $this->assertSame('', PHPUnit_Framework_Assert::readAttribute($this->_link, '_image'));
        $this->assertSame('a', PHPUnit_Framework_Assert::readAttribute($this->_link, '_tag'));
    }

    public function testSetLink() {
        $this->assertSame('', PHPUnit_Framework_Assert::readAttribute($this->_link, '_link'));
        $this->_link->setLink('www.contenido.org');
        $this->assertSame('www.contenido.org', PHPUnit_Framework_Assert::readAttribute($this->_link, '_link'));
        $this->_link->setLink('contenido.org');

        $this->_link->enableAutomaticParameterAppend();
        $this->assertSame('contenido.org', PHPUnit_Framework_Assert::readAttribute($this->_link, '_link'));
        $this->_link->setLink('javascript:void(0)');
        $this->assertSame(NULL, $this->_link->getAttribute('onclick'));
    }

    public function testSetTargetFrame() {
        $this->assertSame(NULL, $this->_link->getAttribute('target'));
        $this->_link->setTargetFrame('frame1');
        $this->assertSame('frame1', $this->_link->getAttribute('target'));
    }

    public function testSetImage() {
        $this->assertSame('', PHPUnit_Framework_Assert::readAttribute($this->_link, '_image'));
        $this->_link->setImage('http://contenido.org/images/contenido.png');
        $this->assertSame('http://contenido.org/images/contenido.png', PHPUnit_Framework_Assert::readAttribute($this->_link, '_image'));
    }

    public function testSetAnchor() {
        $this->assertSame(NULL, PHPUnit_Framework_Assert::readAttribute($this->_link, '_anchor'));
        $this->_link->setAnchor('anchorTest');
        $this->assertSame('anchorTest', PHPUnit_Framework_Assert::readAttribute($this->_link, '_anchor'));
    }

    public function testToHtml() {
        $this->assertSame($this->_link->toHTML(), $this->_link->toHTML());
    }

    public function testGetHref() {
        $this->assertSame('', $this->_link->getHref());
        $this->_link = new cHTMLLink('contenido.org');
       // $this->_link->setAnchor('contenido.org');
       // $this->assertSame('contenido.org', $this->_link->getHref());
    }

}
?>