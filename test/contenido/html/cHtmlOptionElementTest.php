<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */
class cHtmlOptionElementTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $option = new cHTMLOptionElement('testTitle', 'testValue');
        $this->assertSame('testValue', $option->getAttribute('value'));
        $this->assertSame(1, count($option->getAttributes()));

        $option = new cHTMLOptionElement('testTitle', 'testValue', true);
        $option->toHtml();
        $this->assertSame('testValue', $option->getAttribute('value'));
        $this->assertSame('testTitle', $this->_readAttribute($option, '_content'));
        $this->assertSame(2, count($option->getAttributes()));

        $option = new cHTMLOptionElement('testTitle', 'testValue', false, false);
        $option->toHtml();
        $this->assertSame('testTitle', $this->_readAttribute($option, '_content'));
        $this->assertSame('testValue', $option->getAttribute('value'));
        $this->assertSame(1, count($option->getAttributes()));

        $option = new cHTMLOptionElement('testTitle', 'testValue', true, true, 'testClass');
        $option->toHtml();
        $this->assertSame(4, count($option->getAttributes()));
        $this->assertSame('testTitle', $this->_readAttribute($option, '_content'));
        $this->assertSame('testValue', $option->getAttribute('value'));
        $this->assertSame('selected', $option->getAttribute('selected'));
        $this->assertSame('disabled', $option->getAttribute('disabled'));
        $this->assertSame('testClass', $option->getAttribute('class'));
    }

    public function testSetSelected()
    {
        $option = new cHTMLOptionElement('testTitle', 'testValue', false, false);
        $this->assertSame(1, count($option->getAttributes()));
        $this->assertSame(null, $option->getAttribute('selected'));

        $option->setSelected(true);
        $this->assertSame(2, count($option->getAttributes()));
        $this->assertSame('selected', $option->getAttribute('selected'));

        $option->setSelected(false);
        $this->assertSame(1, count($option->getAttributes()));
        $this->assertSame(null, $option->getAttribute('selected'));
    }

    public function testIsSelected()
    {
        $option = new cHTMLOptionElement('testTitle', 'testValue', false, false);
        $this->assertSame(1, count($option->getAttributes()));
        $this->assertSame(false, ($option->isSelected()));

        $option->setSelected(true);
        $this->assertSame(2, count($option->getAttributes()));
        $this->assertSame(true, ($option->isSelected()));

        $option->setSelected(false);
        $this->assertSame(1, count($option->getAttributes()));
        $this->assertSame(false, ($option->isSelected()));
    }

    public function testSetDisabled()
    {
        $option = new cHTMLOptionElement('testTitle', 'testValue', false, false);
        $this->assertSame(1, count($option->getAttributes()));
        $this->assertSame(null, $option->getAttribute('disabled'));

        $option->setDisabled(true);
        $this->assertSame(2, count($option->getAttributes()));
        $this->assertSame('disabled', $option->getAttribute('disabled'));

        $option->setDisabled(false);
        $this->assertSame(1, count($option->getAttributes()));
        $this->assertSame(null, $option->getAttribute('disabled'));
    }

    public function testToHtml()
    {
        $option = new cHTMLOptionElement('testTitle', 'testValue', false, false);
        $this->assertSame($option->toHtml(), $option->toHtml());
    }
}
