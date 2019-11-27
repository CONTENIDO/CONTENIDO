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
class cHtmlHeaderHgroupTest extends TestCase {

    public function testConstruct() {
        $hgroup = new cHTMLHgroup('testContent', 'testClass', 'testId');
        $this->assertSame('<hgroup id="testId" class="testClass">testContent</hgroup>', $hgroup->toHtml());
        $hgroup = new cHTMLHgroup('', '', 'testId2');
        $this->assertSame('<hgroup id="testId2"></hgroup>', $hgroup->toHtml());
    }

}
?>