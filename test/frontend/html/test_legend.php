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
class cHtmlLegendTest extends TestCase {

    public function testConstruct() {
        $cLegend = new cHTMLLegend('testContent', 'testClass', 'testId');
        $this->assertSame('<legend id="testId" class="testClass">testContent</legend>', $cLegend->toHtml());
        $cLegend = new cHTMLLegend('', '', 'testId2');
        $this->assertSame('<legend id="testId2"></legend>', $cLegend->toHtml());
    }

}
?>