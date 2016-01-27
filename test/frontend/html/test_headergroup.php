<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlHeaderHgroupTest extends PHPUnit_Framework_TestCase {

    protected $_cHeaderHgroup = null;

    protected function setUp() {
        $this->_cHeaderHgroup = new cHTMLHgroup();
    }

    public function testConstruct() {
        $hgroup = new cHTMLHgroup('testContent', 'testClass', 'testId');
        $this->assertSame('<hgroup id="testId" class="testClass">testContent</hgroup>', $hgroup->toHTML());
        $this->assertSame('<hgroup id=""></hgroup>', $this->_cHeaderHgroup->toHTML());
    }

}
?>