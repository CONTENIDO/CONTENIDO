<?PHP

/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlLinkTest extends cTestingTestCase {

    protected $_link = null;

    protected function setUp(): void {
        $this->_link = new cHTMLLink();
    }

    public function testConstruct() {
        $this->assertSame('', $this->_readAttribute($this->_link, '_link'));
        $this->assertSame('', $this->_readAttribute($this->_link, '_content'));
        $this->assertSame(NULL, $this->_readAttribute($this->_link, '_anchor'));
        $this->assertSame(NULL, $this->_readAttribute($this->_link, '_custom'));
        $this->assertSame('', $this->_readAttribute($this->_link, '_image'));

        $this->_link = new cHTMLLink('contenido.org');
        $this->assertSame('contenido.org', $this->_readAttribute($this->_link, '_link'));
        $this->assertSame('', $this->_readAttribute($this->_link, '_content'));
        $this->assertSame(NULL, $this->_readAttribute($this->_link, '_anchor'));
        $this->assertSame(NULL, $this->_readAttribute($this->_link, '_custom'));
        $this->assertSame('', $this->_readAttribute($this->_link, '_image'));
        $this->assertSame('a', $this->_readAttribute($this->_link, '_tag'));

        $this->_link = new cHTMLLink('contenido.org', '<img src="path/to/image.jpg" />');
        $this->assertSame('<a href="contenido.org"><img src="path/to/image.jpg" /></a>', $this->_link->render());

        $this->_link = new cHTMLLink('contenido.org', '<img src="path/to/image.jpg" />', 'text_link');
        $this->assertSame('<a class="text_link" href="contenido.org"><img src="path/to/image.jpg" /></a>', $this->_link->render());

        $this->_link = new cHTMLLink('contenido.org', '<img src="path/to/image.jpg" />', 'text_link', 'testId');
        $this->assertSame('<a id="testId" class="text_link" href="contenido.org"><img src="path/to/image.jpg" /></a>', $this->_link->render());

    }

    public function testSetLink() {
        $this->assertSame('', $this->_readAttribute($this->_link, '_link'));
        $this->_link->setLink('www.contenido.org');
        $this->assertSame('www.contenido.org', $this->_readAttribute($this->_link, '_link'));
        $this->_link->setLink('contenido.org');

        $this->_link->enableAutomaticParameterAppend();
        $this->assertSame('contenido.org', $this->_readAttribute($this->_link, '_link'));
        $this->_link->setLink('javascript:void(0)');
        $this->assertSame(NULL, $this->_link->getAttribute('onclick'));
    }

    public function testSetTargetFrame() {
        $this->assertSame(NULL, $this->_link->getAttribute('target'));
        $this->_link->setTargetFrame('frame1');
        $this->assertSame('frame1', $this->_link->getAttribute('target'));
    }

    public function testSetImage() {
        $this->assertSame('', $this->_readAttribute($this->_link, '_image'));
        $this->_link->setImage('http://contenido.org/images/contenido.png');
        $this->assertSame('http://contenido.org/images/contenido.png', $this->_readAttribute($this->_link, '_image'));
    }

    public function testSetAnchor() {
        $this->assertSame(NULL, $this->_readAttribute($this->_link, '_anchor'));
        $this->_link->setAnchor('anchorTest');
        $this->assertSame('anchorTest', $this->_readAttribute($this->_link, '_anchor'));
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
        $this->assertSame('clink', $this->_readAttribute($this->_link, '_type'));
        $this->assertSame('top', $this->_readAttribute($this->_link, '_targetarea'));
        $this->assertSame('frame4', $this->_readAttribute($this->_link, '_targetframe'));
        $this->assertSame('', $this->_readAttribute($this->_link, '_targetaction'));
    }

    public function testSetMultiLink() {
        $this->_link->setMultiLink('right_top', 'right_top', 'right_bottom', 'right_bottom');
        $this->assertSame('multilink', $this->_readAttribute($this->_link, '_type'));
        $this->assertSame('right_top', $this->_readAttribute($this->_link, '_targetarea'));
        $this->assertSame(3, $this->_readAttribute($this->_link, '_targetframe'));
        $this->assertSame('right_top', $this->_readAttribute($this->_link, '_targetaction'));

        $this->assertSame('right_bottom', $this->_readAttribute($this->_link, '_targetarea2'));
        $this->assertSame(4, $this->_readAttribute($this->_link, '_targetframe2'));
        $this->assertSame('right_bottom', $this->_readAttribute($this->_link, '_targetaction2'));
    }

    public function testEnableAutomaticParameterAppend() {
        $this->_link->enableAutomaticParameterAppend();
        $this->assertSame('var doit = true; try { var i = get_registered_parameters() } catch (e) { doit = false; }; if (doit == true) { this.href += i; }', $this->_link->getAttribute('onclick'));
    }

    public function testDisableAutomaticParameterAppend() {
        $this->_link->enableAutomaticParameterAppend();
        $this->assertSame('var doit = true; try { var i = get_registered_parameters() } catch (e) { doit = false; }; if (doit == true) { this.href += i; }', $this->_link->getAttribute('onclick'));
        $this->_link->disableAutomaticParameterAppend();
        $this->assertSame(NULL, $this->_link->getAttribute('onclick'));
    }

    public function testSetCustom(){
        $this->_link->setCustom('testKey', 'testValue');
        $ret = $this->_readAttribute($this->_link, '_custom');
        $this->assertSame('testValue', $ret['testKey']);
    }

    public function testUnSetCustom(){
        $this->_link->setCustom('testKey', 'testValue');
        $ret = $this->_readAttribute($this->_link, '_custom');
        $this->assertSame('testValue', $ret['testKey']);
        $this->_link->unsetCustom('testKey');
        $this->assertSame(array(),$this->_readAttribute($this->_link, '_custom'));


    }

}
?>