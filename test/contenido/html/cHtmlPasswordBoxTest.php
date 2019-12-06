<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */
class cHtmlPasswordBoxTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $pwBox = new cHTMLPasswordbox('testName');
        $this->assertSame(4, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('', $pwBox->getAttribute('value'));
        $this->assertSame('password', $pwBox->getAttribute('type'));

        $pwBox = new cHTMLPasswordbox('testName', 'testInitValue');
        $this->assertSame(4, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testInitValue', $pwBox->getAttribute('value'));
        $this->assertSame('password', $pwBox->getAttribute('type'));

        $pwBox = new cHTMLPasswordbox('testName', 'testInitValue', 200);
        $this->assertSame(4, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testInitValue', $pwBox->getAttribute('value'));
        $this->assertSame('password', $pwBox->getAttribute('type'));
        $this->assertSame(200, $pwBox->getAttribute('size'));

        $pwBox = new cHTMLPasswordbox('testName', 'testInitValue', 200, 100);
        $this->assertSame(5, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testInitValue', $pwBox->getAttribute('value'));
        $this->assertSame('password', $pwBox->getAttribute('type'));
        $this->assertSame(200, $pwBox->getAttribute('size'));
        $this->assertSame(100, $pwBox->getAttribute('maxlength'));

        $pwBox = new cHTMLPasswordbox('testName', 'testInitValue', 200, 100, 'testId');
        $this->assertSame(6, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testInitValue', $pwBox->getAttribute('value'));
        $this->assertSame('password', $pwBox->getAttribute('type'));
        $this->assertSame(200, $pwBox->getAttribute('size'));
        $this->assertSame(100, $pwBox->getAttribute('maxlength'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));

        $pwBox = new cHTMLPasswordbox('testName', 'testInitValue', 200, 100, 'testId', true);
        $this->assertSame(7, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testInitValue', $pwBox->getAttribute('value'));
        $this->assertSame('password', $pwBox->getAttribute('type'));
        $this->assertSame(200, $pwBox->getAttribute('size'));
        $this->assertSame(100, $pwBox->getAttribute('maxlength'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));
        $this->assertSame('disabled', $pwBox->getAttribute('disabled'));

        $pwBox = new cHTMLPasswordbox('testName', 'testInitValue', 200, 100, 'testId', true, null, '', 'testClass');
        $this->assertSame(8, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testInitValue', $pwBox->getAttribute('value'));
        $this->assertSame('password', $pwBox->getAttribute('type'));
        $this->assertSame(200, $pwBox->getAttribute('size'));
        $this->assertSame(100, $pwBox->getAttribute('maxlength'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));
        $this->assertSame('disabled', $pwBox->getAttribute('disabled'));
        $this->assertSame('testClass', $pwBox->getAttribute('class'));
    }

    public function testSetWidth()
    {
        $pwBox = new cHTMLPasswordbox('testName', 'testInitValue', 200);
        $this->assertSame(4, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testInitValue', $pwBox->getAttribute('value'));
        $this->assertSame('password', $pwBox->getAttribute('type'));
        $this->assertSame(200, $pwBox->getAttribute('size'));

        $pwBox->setWidth(-1);
        $this->assertSame(20, $pwBox->getAttribute('size'));
        $pwBox->setWidth(0);
        $this->assertSame(20, $pwBox->getAttribute('size'));
        $pwBox->setWidth(1);
        $this->assertSame(1, $pwBox->getAttribute('size'));
    }

    public function testSetMaxLength()
    {
        $pwBox = new cHTMLPasswordbox('testName', 'testInitValue', 200, 100, 'testId');
        $this->assertSame(6, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testInitValue', $pwBox->getAttribute('value'));
        $this->assertSame('password', $pwBox->getAttribute('type'));
        $this->assertSame(200, $pwBox->getAttribute('size'));
        $this->assertSame(100, $pwBox->getAttribute('maxlength'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));

        $pwBox->setMaxLength(-1);
        $this->assertSame(null, $pwBox->getAttribute('maxlength'));

        $pwBox->setMaxLength(0);
        $this->assertSame(null, $pwBox->getAttribute('maxlength'));
        $pwBox->setMaxLength(1);
        $this->assertSame(1, $pwBox->getAttribute('maxlength'));
    }

    public function testSetValue()
    {
        $pwBox = new cHTMLPasswordbox('testName', 'testInitValue');
        $this->assertSame(4, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testInitValue', $pwBox->getAttribute('value'));
        $this->assertSame('password', $pwBox->getAttribute('type'));

        $pwBox->setValue('testTestInitValue');
        $this->assertSame('testTestInitValue', $pwBox->getAttribute('value'));
        $pwBox->setValue('testInitValue');
        $this->assertSame('testInitValue', $pwBox->getAttribute('value'));
    }

    public function testAutocomplete() {
        // Test autocomplete="off"
        $pwBox = new cHTMLPasswordbox('testName', 'testInitValue');
        // Setting autocomplete="off" sets also readonly="readonly" & renders script together with the element
        $pwBox->setAutocomplete('off');
        $this->assertSame('off', $pwBox->getAttribute('autocomplete'));

        $html = $pwBox->toHtml();
        $this->assertSame('readonly', $pwBox->getAttribute('readonly'));
        $this->assertStringContainsString('<script', $html);
        $this->assertStringContainsString('$("#' . $pwBox->getID() . '").on("focus", function() {', $html);
        $this->assertStringContainsString('$(this).prop("readonly", false);', $html);

        // Test autocomplete="on"
        $pwBox = new cHTMLPasswordbox('testName', 'testInitValue');
        // Setting autocomplete="on" sets also readonly="readonly" & renders script together with the element
        $pwBox->setAutocomplete('on');
        $this->assertSame('on', $pwBox->getAttribute('autocomplete'));

        $html = $pwBox->toHtml();
        $this->assertNull($pwBox->getAttribute('readonly'));
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('$("#' . $pwBox->getID() . '").on("focus", function() {', $html);
        $this->assertStringNotContainsString('$(this).prop("readonly", false);', $html);
    }
}
