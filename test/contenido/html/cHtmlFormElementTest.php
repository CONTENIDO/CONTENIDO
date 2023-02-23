<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   https://www.contenido.org/license/LIZENZ.txt
 * @link      https://www.4fb.de
 * @link      https://www.contenido.org
 */
class cHtmlFormElementTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $formElem = new cHTMLFormElement();
        $this->assertSame(null, $formElem->getAttribute('name'));

        $formElem = new cHTMLFormElement('testClass');
        $this->assertSame('testClass', $formElem->getAttribute('name'));

        $formElem = new cHTMLFormElement('testClass', 'testId');
        $this->assertSame('testClass', $formElem->getAttribute('name'));
        $this->assertSame('testId', $formElem->getAttribute('id'));

        $formElem = new cHTMLFormElement('testClass', 'testId', true);
        $this->assertSame('testClass', $formElem->getAttribute('name'));
        $this->assertSame('testId', $formElem->getAttribute('id'));
        $this->assertSame('disabled', $formElem->getAttribute('disabled'));

        $formElem = new cHTMLFormElement('testClass', 'testId', true, 100);
        $this->assertSame('testClass', $formElem->getAttribute('name'));
        $this->assertSame('testId', $formElem->getAttribute('id'));
        $this->assertSame('disabled', $formElem->getAttribute('disabled'));
        $this->assertSame(100, $formElem->getAttribute('tabindex'));

        $formElem = new cHTMLFormElement('testClass', 'testId', true, 100, 5);
        $this->assertSame('testClass', $formElem->getAttribute('name'));
        $this->assertSame('testId', $formElem->getAttribute('id'));
        $this->assertSame('disabled', $formElem->getAttribute('disabled'));
        $this->assertSame(5, $formElem->getAttribute('accesskey'));

        $formElem = new cHTMLFormElement('testClass', 'testId', true, 100, 50);
        $this->assertSame('testClass', $formElem->getAttribute('name'));
        $this->assertSame('testId', $formElem->getAttribute('id'));
        $this->assertSame('disabled', $formElem->getAttribute('disabled'));
        $this->assertSame(null, $formElem->getAttribute('accesskey'));

        $formelement = new cHTMLFormElement();
        $this->assertNull($formelement->getAttribute('name'));
        $this->assertNull($formelement->getAttribute('id'));
        $this->assertSame('text_medium', $formelement->getAttribute('class'));

        $formelement = new cHTMLFormElement('');
        $this->assertNull($formelement->getAttribute('name'));

        $formelement = new cHTMLFormElement('my-name');
        $this->assertSame('my-name', $formelement->getAttribute('name'));

        $formelement = new cHTMLFormElement('', 'my-id');
        $this->assertSame('my-id', $formelement->getAttribute('id'));

        $formelement = new cHTMLFormElement('my-name', 'my-id', false, null, '', 'my-class');
        $this->assertSame('my-name', $formelement->getAttribute('name'));
        $this->assertSame('my-id', $formelement->getAttribute('id'));
        $this->assertSame('my-class', $formelement->getAttribute('class'));
    }

    public function testSetDisabled()
    {
        $formElem = new cHTMLFormElement('testClass', 'testId', false);
        $this->assertSame('testClass', $formElem->getAttribute('name'));
        $this->assertSame('testId', $formElem->getAttribute('id'));

        $formElem->setDisabled(true);
        $this->assertSame('disabled', $formElem->getAttribute('disabled'));

        $formElem->setDisabled(false);
        $this->assertSame(null, $formElem->getAttribute('disabled'));
    }

    public function testSetTabIndex()
    {
        $formElem = new cHTMLFormElement();

        $formElem->setTabindex(null);
        $this->assertSame(null, $formElem->getAttribute('tabindex'));

        $formElem->setTabindex(-2);
        $this->assertSame(null, $formElem->getAttribute('tabindex'));

        $formElem->setTabindex(-1);
        $this->assertSame(-1, $formElem->getAttribute('tabindex'));

        $formElem->setTabindex(100);
        $this->assertSame(100, $formElem->getAttribute('tabindex'));

        $formElem->setTabindex(32768);
        $this->assertSame(100, $formElem->getAttribute('tabindex'));

        $formElem->setTabindex(32767);
        $this->assertSame(32767, $formElem->getAttribute('tabindex'));
    }

    public function testSetAccessKey()
    {
        $formElem = new cHTMLFormElement('testClass', 'testId', false);
        $this->assertSame(null, $formElem->getAttribute('accesskey'));
        $formElem->setAccessKey(100);
        $this->assertSame(null, $formElem->getAttribute('accesskey'));
        $formElem->setAccessKey(-1);
        $this->assertSame(null, $formElem->getAttribute('accesskey'));
        $formElem->setAccessKey(1);
        $this->assertSame(1, $formElem->getAttribute('accesskey'));
    }
}
