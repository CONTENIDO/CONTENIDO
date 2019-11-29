<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */
class cHtmlFieldSetTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $fieldset = new cHTMLFieldset('testContent', 'testClass', 'testId');
        $this->assertSame('<fieldset id="testId" class="testClass">testContent</fieldset>', $fieldset->toHtml());
        $fieldset = new cHTMLFieldset('', '', 'testId2');
        $this->assertSame('<fieldset id="testId2"></fieldset>', $fieldset->toHtml());
    }
}
