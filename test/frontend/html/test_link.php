<?PHP

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Assert;

/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlLinkTest extends TestCase {

    protected $_link = null;

    protected function setUp(): void {
        $this->_link = new cHTMLLink();
    }

    public function testConstruct() {
        $this->assertSame('', Assert::readAttribute($this->_link, '_link'));
        $this->assertSame('', Assert::readAttribute($this->_link, '_content'));
        $this->assertSame(NULL, Assert::readAttribute($this->_link, '_anchor'));
        $this->assertSame(NULL, Assert::readAttribute($this->_link, '_custom'));
        $this->assertSame('', Assert::readAttribute($this->_link, '_image'));

        $this->_link = new cHTMLLink('contenido.org');
        $this->assertSame('contenido.org', Assert::readAttribute($this->_link, '_link'));
        $this->assertSame('', Assert::readAttribute($this->_link, '_content'));
        $this->assertSame(NULL, Assert::readAttribute($this->_link, '_anchor'));
        $this->assertSame(NULL, Assert::readAttribute($this->_link, '_custom'));
        $this->assertSame('', Assert::readAttribute($this->_link, '_image'));
        $this->assertSame('a', Assert::readAttribute($this->_link, '_tag'));
    }

    public function testSetLink() {
        $this->assertSame('', Assert::readAttribute($this->_link, '_link'));
        $this->_link->setLink('www.contenido.org');
        $this->assertSame('www.contenido.org', Assert::readAttribute($this->_link, '_link'));
        $this->_link->setLink('contenido.org');

        $this->_link->enableAutomaticParameterAppend();
        $this->assertSame('contenido.org', Assert::readAttribute($this->_link, '_link'));
        $this->_link->setLink('javascript:void(0)');
        $this->assertSame(NULL, $this->_link->getAttribute('onclick'));
    }

    public function testSetTargetFrame() {
        $this->assertSame(NULL, $this->_link->getAttribute('target'));
        $this->_link->setTargetFrame('frame1');
        $this->assertSame('frame1', $this->_link->getAttribute('target'));
    }

    public function testSetImage() {
        $this->assertSame('', Assert::readAttribute($this->_link, '_image'));
        $this->_link->setImage('http://contenido.org/images/contenido.png');
        $this->assertSame('http://contenido.org/images/contenido.png', Assert::readAttribute($this->_link, '_image'));
    }

    public function testSetAnchor() {
        $this->assertSame(NULL, Assert::readAttribute($this->_link, '_anchor'));
        $this->_link->setAnchor('anchorTest');
        $this->assertSame('anchorTest', Assert::readAttribute($this->_link, '_anchor'));
    }

    public function testToHtml() {
        $this->assertSame($this->_link->toHtml(), $this->_link->toHtml());
    }

    public function testGetHref() {
        $this->assertSame('', $this->_link->getHref());
        $this->_link = new cHTMLLink('contenido.org');
        // $this->_link->setAnchor('contenido.org');
        // $this->assertSame('contenido.org', $this->_link->getHref());
    }

    public function testSetCLink() {
        $this->_link->setCLink('top', 'frame4');
        $this->assertSame('clink', Assert::readAttribute($this->_link, '_type'));
        $this->assertSame('top', Assert::readAttribute($this->_link, '_targetarea'));
        $this->assertSame('frame4', Assert::readAttribute($this->_link, '_targetframe'));
        $this->assertSame('', Assert::readAttribute($this->_link, '_targetaction'));
    }

    public function testSetMultiLink() {
        $this->_link->setMultiLink('right_top', 'right_top', 'right_bottom', 'right_bottom');
        $this->assertSame('multilink', Assert::readAttribute($this->_link, '_type'));
        $this->assertSame('right_top', Assert::readAttribute($this->_link, '_targetarea'));
        $this->assertSame(3, Assert::readAttribute($this->_link, '_targetframe'));
        $this->assertSame('right_top', Assert::readAttribute($this->_link, '_targetaction'));

        $this->assertSame('right_bottom', Assert::readAttribute($this->_link, '_targetarea2'));
        $this->assertSame(4, Assert::readAttribute($this->_link, '_targetframe2'));
        $this->assertSame('right_bottom', Assert::readAttribute($this->_link, '_targetaction2'));
    }

    public function testEnableautomaticParameterAppend() {
        $this->_link->enableAutomaticParameterAppend();
        $this->assertSame('var doit = true; try { var i = get_registered_parameters() } catch (e) { doit = false; }; if (doit == true) { this.href += i; }', $this->_link->getAttribute('onclick'));
    }

    public function testDisableeautomaticParameterAppend() {
        $this->_link->enableAutomaticParameterAppend();
        $this->assertSame('var doit = true; try { var i = get_registered_parameters() } catch (e) { doit = false; }; if (doit == true) { this.href += i; }', $this->_link->getAttribute('onclick'));
        $this->_link->disableAutomaticParameterAppend();
        $this->assertSame(NULL, $this->_link->getAttribute('onclick'));
    }

    public function testSetCustom(){
        $this->_link->setCustom('testKey', 'testValue');
        $ret = Assert::readAttribute($this->_link, '_custom');
        $this->assertSame('testValue', $ret['testKey']);
    }

    public function testUnSetCustom(){
        $this->_link->setCustom('testKey', 'testValue');
        $ret = Assert::readAttribute($this->_link, '_custom');
        $this->assertSame('testValue', $ret['testKey']);
        $this->_link->unsetCustom('testKey');
        $this->assertSame(array(),Assert::readAttribute($this->_link, '_custom'));


    }

}
?>