<?PHP
/**
 *
 * @version SVN Revision $Rev:$
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlDivTest extends PHPUnit_Framework_TestCase {

    protected $_cDiv = null;

    protected function setUp() {
        $this->_cDiv = new cHTMLDiv();
    }

    public function testConstruct() {
        $div = new cHTMLDiv('testContent', 'testClass', 'testId');
        $this->assertSame('<div id="testId" class="testClass">testContent</div>', $div->toHTML());
        $this->assertSame('<div id=""></div>', $this->_cDiv->toHTML());
    }

}
?>