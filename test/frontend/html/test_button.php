<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlButtonTest extends PHPUnit_Framework_TestCase {

    protected $_cButton = null;

    protected function setUp() {
        $this->_cButton = new cHTMLButton('testButton');
    }

    public function testConstruct() {
        $cButton = new cHTMLButton('testButton', 'testTitle', 'testId', false, '', 'accesKey', 'submit', 'testClass');
        $this->assertSame('<input id="testId" name="testButton" value="testTitle" type="submit" class="testClass" />', $cButton->toHtml());

        $cButton = new cHTMLButton('testButton', 'testTitle', 'testId', false, '', 'accesKey', 'submit');
        $this->assertSame('<input id="testId" name="testButton" value="testTitle" type="submit" />', $cButton->toHtml());

        $cButton = new cHTMLButton('testButton', 'testTitle', 'testId', false, '', 'accesKey');
        $this->assertSame('<input id="testId" name="testButton" value="testTitle" type="submit" />', $cButton->toHtml());

        $cButton = new cHTMLButton('testButton', 'testValue', 'testId');
        $this->assertSame('<input id="testId" name="testButton" value="testValue" type="submit" />', $cButton->toHtml());
    }

    public function testSetTitle() {
        $this->_cButton->setTitle('huhu');
        $this->assertSame('huhu', $this->_cButton->getAttribute('value'));
        $this->_cButton->setTitle('');
        $this->assertSame('', $this->_cButton->getAttribute('value'));
    }

    public function testSetMode() {
        $this->assertSame('submit', $this->_cButton->getAttribute('type'));
        $this->_cButton->setMode('submit');
        $this->assertSame('submit', $this->_cButton->getAttribute('type'));
        $this->_cButton->setMode('reset');
        $this->assertSame('reset', $this->_cButton->getAttribute('type'));
        $this->_cButton->setMode('image');
        $this->assertSame('image', $this->_cButton->getAttribute('type'));
        $this->_cButton->setMode('button');
        $this->assertSame('button', $this->_cButton->getAttribute('type'));
        $this->_cButton->setMode('hansWurst');
        $this->assertSame('button', $this->_cButton->getAttribute('type'));
        $this->_cButton->setMode('');
        $this->assertSame('button', $this->_cButton->getAttribute('type'));
    }

    public function testSetImageSource() {
        $this->_cButton->setImageSource('http://www.google.jpg');
        $this->assertSame('http://www.google.jpg', $this->_cButton->getAttribute('src'));
        $this->_cButton->setImageSource('');
        $this->assertSame('', $this->_cButton->getAttribute('src'));
    }

}
?>