<?PHP

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Assert;

/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlTableHeadTest extends TestCase {

    protected $_tableHead;

    protected function setUp(): void {
        $this->_tableHead = new cHTMLTableHead();
    }

    public function testConstructor() {
        $this->assertSame('th', Assert::readAttribute($this->_tableHead, '_tag'));
    }

}
?>

