<?PHP

use PHPUnit\Framework\TestCase;

/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlFormElementTest extends TestCase {

    public function testConstruct() {
        $formElem = new cHTMLFormElement();
        $this->assertSame('', $formElem->getAttribute('name'));

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
        $this->assertSame(NULL, $formElem->getAttribute('accesskey'));
    }

    public function testSetDisabled() {
        $formElem = new cHTMLFormElement('testClass', 'testId', false);
        $this->assertSame('testClass', $formElem->getAttribute('name'));
        $this->assertSame('testId', $formElem->getAttribute('id'));

        $formElem->setDisabled(true);
        $this->assertSame('disabled', $formElem->getAttribute('disabled'));

        $formElem->setDisabled(false);
        $this->assertSame(NULL, $formElem->getAttribute('disabled'));
    }

    public function testSetTabIndex() {
        $formElem = new cHTMLFormElement('testClass', 'testId', false);
        $this->assertSame(NULL, $formElem->getAttribute('tabindex'));
        $formElem->setTabindex(100);
        $this->assertSame(100, $formElem->getAttribute('tabindex'));

        $formElem->setTabindex(-1);
        $this->assertSame(100, $formElem->getAttribute('tabindex'));
        $formElem->setTabindex(32768);
        $this->assertSame(100, $formElem->getAttribute('tabindex'));
        $formElem->setTabindex(32765);
        $this->assertSame(32765, $formElem->getAttribute('tabindex'));
    }

    public function SetAccessKey() {
        $formElem = new cHTMLFormElement('testClass', 'testId', false);
        $this->assertSame(NULL, $formElem->getAttribute('accesskey'));
        $formElem->setAccessKey(100);
        $this->assertSame(NULL, $formElem->getAttribute('accesskey'));
        $formElem->setAccessKey(-1);
        $this->assertSame(NULL, $formElem->getAttribute('accesskey'));
        $formElem->setAccessKey(1);
        $this->assertSame(1, $formElem->getAttribute('accesskey'));
    }

}
?>