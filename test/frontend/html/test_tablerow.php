<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlTableRowTest extends PHPUnit_Framework_TestCase {

    protected $_tableRow;

    public function setUp() {
        $this->_tableRow = new cHTMLTableRow();
    }

    public function testConstructor() {
        $this->assertSame('tr', PHPUnit_Framework_Assert::readAttribute($this->_tableRow, '_tag'));
    }

}
?>

