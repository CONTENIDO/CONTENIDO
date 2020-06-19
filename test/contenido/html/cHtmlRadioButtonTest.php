<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */
class cHtmlRadioButtonTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $pwBox = new cHTMLRadiobutton('testName', 'testValue', 'testId');
        $this->assertSame(4, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testValue', $pwBox->getAttribute('value'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));
        $this->assertSame('radio', $pwBox->getAttribute('type'));

        $this->assertSame(null, $pwBox->getAttribute('disabled'));
        $this->assertSame(null, $pwBox->getAttribute('checked'));

        $pwBox = new cHTMLRadiobutton('testName', 'testValue', 'testId', true);
        $this->assertSame(5, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testValue', $pwBox->getAttribute('value'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));
        $this->assertSame('radio', $pwBox->getAttribute('type'));

        $this->assertSame(null, $pwBox->getAttribute('disabled'));
        $this->assertSame('checked', $pwBox->getAttribute('checked'));

        $pwBox = new cHTMLRadiobutton('testName', 'testValue', 'testId', true, true);
        $this->assertSame(6, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testValue', $pwBox->getAttribute('value'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));
        $this->assertSame('radio', $pwBox->getAttribute('type'));

        $this->assertSame('disabled', $pwBox->getAttribute('disabled'));
        $this->assertSame('checked', $pwBox->getAttribute('checked'));
    }

    public function testSetChecked()
    {
        $pwBox = new cHTMLRadiobutton('testName', 'testValue', 'testId');
        $this->assertSame(4, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testValue', $pwBox->getAttribute('value'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));
        $this->assertSame('radio', $pwBox->getAttribute('type'));
        $this->assertSame(null, $pwBox->getAttribute('checked'));

        $pwBox->setChecked(true);
        $this->assertSame('checked', $pwBox->getAttribute('checked'));

        $pwBox->setChecked(false);
        $this->assertSame(null, $pwBox->getAttribute('checked'));
    }

    public function testSetLabelText()
    {
        $pwBox = new cHTMLRadiobutton('testName', 'testValue', 'testId');
        $this->assertSame(4, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testValue', $pwBox->getAttribute('value'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));
        $this->assertSame('radio', $pwBox->getAttribute('type'));

        $this->assertSame(null, $this->_readAttribute($pwBox, '_labelText'));
        $pwBox->setLabelText('testLabel');
        $this->assertSame('testLabel', $this->_readAttribute($pwBox, '_labelText'));
    }

    public function testToHTMLText()
    {
        $pwBox = new cHTMLRadiobutton('testName', 'testValue', 'testId');
        $this->assertSame($pwBox->toHtml(), $pwBox->toHtml());
        $this->assertSame($pwBox->toHtml(false), $pwBox->toHtml(false));
    }

    public function testIdRenderWithLabelWithoutSettingId()
    {
        // Render with label
        $radioButton = new cHTMLRadiobutton('testName', 'testValue');
        $this->assertNull($radioButton->getAttribute('id'));
        // Note: Calling render() renders with label, see cHTMLRadiobutton->totoHtml()
        $radioButton->render();
        $this->assertNotNull($radioButton->getAttribute('id'));
    }

    public function testIdRenderWithoutLabelWithoutSettingId()
    {
        // Render without label
        $radioButton = new cHTMLRadiobutton('testName', 'testValue');
        $this->assertNull($radioButton->getAttribute('id'));
        // Note: Calling toHtml(false) renders without label, see cHTMLRadiobutton->toHtml()
        $radioButton->toHtml(false);
        $this->assertNull($radioButton->getAttribute('id'));
    }
}
