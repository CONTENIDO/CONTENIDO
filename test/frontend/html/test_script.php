<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlScriptTest extends PHPUnit_Framework_TestCase {

    public function testConstruct() {
        $script = new cHTMLScript();
        $this->assertSame('script', PHPUnit_Framework_Assert::readAttribute($script, '_tag'));
    }

}
?>