<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlRadioButtonTest extends PHPUnit_Framework_TestCase {

    public function testConstruct() {
        $pwBox = new cHTMLRadiobutton('testName', 'testValue', 'testId');
        $this->assertSame(4, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testValue', $pwBox->getAttribute('value'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));
        $this->assertSame('radio', $pwBox->getAttribute('type'));

        $this->assertSame(NULL, $pwBox->getAttribute('disabled'));
        $this->assertSame(NULL, $pwBox->getAttribute('checked'));

        $pwBox = new cHTMLRadiobutton('testName', 'testValue', 'testId', true);
        $this->assertSame(5, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testValue', $pwBox->getAttribute('value'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));
        $this->assertSame('radio', $pwBox->getAttribute('type'));

        $this->assertSame(NULL, $pwBox->getAttribute('disabled'));
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

    public function testSetChecked() {
        $pwBox = new cHTMLRadiobutton('testName', 'testValue', 'testId');
        $this->assertSame(4, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testValue', $pwBox->getAttribute('value'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));
        $this->assertSame('radio', $pwBox->getAttribute('type'));
        $this->assertSame(NULL, $pwBox->getAttribute('checked'));

        $pwBox->setChecked(true);
        $this->assertSame('checked', $pwBox->getAttribute('checked'));

        $pwBox->setChecked(false);
        $this->assertSame(NULL, $pwBox->getAttribute('checked'));
    }

    public function testSetLabelText() {
        $pwBox = new cHTMLRadiobutton('testName', 'testValue', 'testId');
        $this->assertSame(4, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame('testValue', $pwBox->getAttribute('value'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));
        $this->assertSame('radio', $pwBox->getAttribute('type'));

        $this->assertSame(NULL, PHPUnit_Framework_Assert::readAttribute($pwBox, '_labelText'));
        $pwBox->setLabelText('testLabel');
        $this->assertSame('testLabel', PHPUnit_Framework_Assert::readAttribute($pwBox, '_labelText'));
    }

    public function testToHTMLText() {
        $pwBox = new cHTMLRadiobutton('testName', 'testValue', 'testId');
        $this->assertSame($pwBox->toHtml(), $pwBox->toHtml());
        $this->assertSame($pwBox->toHtml(false), $pwBox->toHtml(false));

    }

}
?>