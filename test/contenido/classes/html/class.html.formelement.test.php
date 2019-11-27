<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */
class cHtmlFormelementTest extends cTestingTestCase
{
    public function testConstruct()
    {
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
}
