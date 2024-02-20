<?PHP

/**
 * @package    Testing
 * @subpackage GUI_HTML
 * @author     claus.schunk@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */
class cHtmlButtonTest extends cTestingTestCase
{
    /**
     * @var cHTMLButton
     */
    protected $_cButton;

    protected function setUp(): void
    {
        $this->_cButton = new cHTMLButton('testButton');
    }

    public function testConstruct()
    {
        $cButton = new cHTMLButton('testButton', 'testTitle', 'testId', false, null, 'accesKey', 'submit', 'testClass');
        $this->assertSame(
            '<input name="testButton" id="testId" class="testClass" value="testTitle" type="submit" />',
            $cButton->toHtml()
        );

        $cButton = new cHTMLButton('testButton', 'testTitle', 'testId', false, null, 'accesKey', 'submit');
        $this->assertSame(
            '<input name="testButton" id="testId" value="testTitle" type="submit" />',
            $cButton->toHtml()
        );

        $cButton = new cHTMLButton('testButton', 'testTitle', 'testId', false, null, 'accesKey');
        $this->assertSame(
            '<input name="testButton" id="testId" value="testTitle" type="submit" />',
            $cButton->toHtml()
        );

        $cButton = new cHTMLButton('testButton', 'testValue', 'testId');
        $this->assertSame(
            '<input name="testButton" id="testId" value="testValue" type="submit" />',
            $cButton->toHtml()
        );
    }

    public function testSetTitle()
    {
        $this->_cButton->setTitle('huhu');
        $this->assertSame('huhu', $this->_cButton->getAttribute('value'));
        $this->_cButton->setTitle('');
        $this->assertSame('', $this->_cButton->getAttribute('value'));
    }

    public function testSetMode()
    {
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

    public function testSetImageSource()
    {
        $this->_cButton->setImageSource('http://www.google.jpg');
        $this->assertSame('http://www.google.jpg', $this->_cButton->getAttribute('src'));
        $this->_cButton->setImageSource('');
        $this->assertSame('', $this->_cButton->getAttribute('src'));
    }
}
