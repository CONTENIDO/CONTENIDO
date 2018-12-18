<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlTableBodyTest extends PHPUnit_Framework_TestCase {

    protected $_tableBody;

    public function setUp() {
        $this->_tableBody = new cHTMLTableBody();
    }

    public function testConstructor() {
        $this->assertSame('tbody', PHPUnit_Framework_Assert::readAttribute($this->_tableBody, '_tag'));
    }

}
?>