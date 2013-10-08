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
class cHTMLAlignmentTableTest extends PHPUnit_Framework_TestCase {

    public function testConstruct() {

        $table = new cHTMLAlignmentTable();
        $this->assertSame('table', PHPUnit_Framework_Assert::readAttribute($table, '_tag'));
        $this->assertSame(false, PHPUnit_Framework_Assert::readAttribute($table, '_contentlessTag'));
    }

    public function testRender(){
        $table = new cHTMLAlignmentTable();
        $this->assertSame($table->render(),$table->toHTML());
    }

}
?>
