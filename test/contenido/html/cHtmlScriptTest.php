<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   https://www.contenido.org/license/LIZENZ.txt
 * @link      https://www.4fb.de
 * @link      https://www.contenido.org
 */
class cHtmlScriptTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $script = new cHTMLScript();
        $this->assertSame('script', $this->_readAttribute($script, '_tag'));
    }

    /**
     * Tests {@see cHtmlScript::external()}
     */
    public function testExternal()
    {
        // Empty path
        $result = cHtmlScript::external('');
        $this->assertSame('<script type="text/javascript" src=""></script>', $result);

        // Script path
        $result = cHtmlScript::external('scripts/contenido.js');
        $this->assertSame('<script type="text/javascript" src="scripts/contenido.js"></script>', $result);

        // Script path
        $result = cHtmlScript::external('/script.js', [
            'referrerpolicy' => 'origin'
        ]);
        $this->assertSame('<script referrerpolicy="origin" type="text/javascript" src="/script.js"></script>', $result);
    }

}
