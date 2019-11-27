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
class cHtmlCanvasTest extends TestCase {

    public function testConstruct() {
        $canvas = new cHTMLCanvas('', '', 'm20');
        $this->assertSame('<canvas id="m20"></canvas>', $canvas->toHtml());
        $canvas = new cHTMLCanvas('testContent', 'testClass', 'testId');
        $this->assertSame('<canvas id="testId" class="testClass">testContent</canvas>', $canvas->toHtml());
    }

    public function testSetHeight() {
        $canvas = new cHTMLCanvas('', '', 'm21');
        $canvas->setHeight(200);
        $this->assertSame(200, $canvas->getAttribute('height'));
        $this->assertSame('<canvas id="m21" height="200"></canvas>', $canvas->toHtml());
        $canvas->setHeight('');
        $this->assertSame('', $canvas->getAttribute('height'));
        $this->assertSame('<canvas id="m21" height=""></canvas>', $canvas->toHtml());
    }

    public function testSetWidth() {
        $canvas = new cHTMLCanvas('', '', 'm22');
        $canvas->setWidth(200);
        $this->assertSame(200, $canvas->getAttribute('width'));
        $this->assertSame('<canvas id="m22" width="200"></canvas>', $canvas->toHtml());
        $canvas->setWidth('');
        $this->assertSame('', $canvas->getAttribute('width'));
        $this->assertSame('<canvas id="m22" width=""></canvas>', $canvas->toHtml());
    }

}
?>